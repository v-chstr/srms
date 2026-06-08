import Alpine from 'alpinejs';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.mjs?url';

// pdfjs-dist v6 uses Map.prototype.getOrInsertComputed (TC39 Stage 3, not yet in all browsers).
// Polyfill it so the PDF renderer works on Chromium < 136 and other environments.
if (!Map.prototype.getOrInsertComputed) {
    Map.prototype.getOrInsertComputed = function (key, callbackfn) {
        if (!this.has(key)) {
            this.set(key, callbackfn(key));
        }
        return this.get(key);
    };
}

Alpine.data('annotationViewer', (config) => {
    // pdfjs-dist objects use private class fields (#n, etc.).
    // Alpine v3 deeply proxies all properties of the data object.
    // Accessing a private field on a Proxy throws "Cannot read private member".
    // Store these references in closure variables so Alpine never wraps them.
    let _pdf = null;
    let _renderTask = null;

    return {
        pdfUrl: config.pdfUrl,
        loadUrl: config.loadUrl,
        storeUrl: config.storeUrl || null,
        canEdit: Boolean(config.canEdit),
        activeTool: null,
        currentPage: 1,
        totalPages: 0,
        annotations: [],
        initialized: false,
        renderSequence: 0,
        rendering: false,
        loading: true,
        loadError: false,
        renderError: false,
        annotationLoadError: false,
        saving: false,
        lastSaved: false,
        isDrawing: false,
        startX: 0,
        startY: 0,
        pendingNote: null,
        noteText: '',
        pendingText: null,
        textContent: '',
        draggingNote: null,

        get notes() {
            return [...this.annotations]
                .filter((annotation) => annotation.type === 'note')
                .sort((a, b) => (a.page - b.page) || String(a.created_at).localeCompare(String(b.created_at)));
        },

        get currentPageAnnotations() {
            return this.annotations.filter(a => Number(a.page) === this.currentPage);
        },

        async init() {
            if (this.initialized) return;
            this.initialized = true;

            try {
                const pdfjsLib = await import('pdfjs-dist');
                pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

                _pdf = await pdfjsLib.getDocument({
                    url: this.pdfUrl,
                    withCredentials: true,
                }).promise;
                this.totalPages = _pdf.numPages;
            } catch (error) {
                console.error('PDF load failed', error);
                this.loadError = true;
            } finally {
                this.loading = false;
            }

            if (this.loadError) return;

            await this.renderPage();
            await this.loadAnnotations();
        },

        setTool(tool) {
            if (!this.canEdit) return;
            this.activeTool = this.activeTool === tool ? null : tool;
            this.pendingNote = null;
            this.pendingText = null;
            this.noteText = '';
            this.textContent = '';
        },

        async prevPage() {
            if (this.currentPage <= 1 || this.rendering) return;
            this.currentPage--;
            await this.renderPage();
        },

        async nextPage() {
            if (this.currentPage >= this.totalPages || this.rendering) return;
            this.currentPage++;
            await this.renderPage();
        },

        async renderPage() {
            if (!_pdf) return;

            const sequence = ++this.renderSequence;
            this.rendering = true;
            this.renderError = false;

            try {
                if (_renderTask) {
                    _renderTask.cancel();

                    try {
                        await _renderTask.promise;
                    } catch {
                        // Expected when replacing an in-progress PDF.js render.
                    }

                    _renderTask = null;
                }

                const page = await _pdf.getPage(this.currentPage);
                const viewport = page.getViewport({ scale: 1.35 });
                const outputScale = window.devicePixelRatio || 1;
                const pdfCanvas = this.$refs.pdfCanvas;
                const annotationCanvas = this.$refs.annotationCanvas;

                pdfCanvas.width = Math.floor(viewport.width * outputScale);
                pdfCanvas.height = Math.floor(viewport.height * outputScale);
                annotationCanvas.width = Math.floor(viewport.width * outputScale);
                annotationCanvas.height = Math.floor(viewport.height * outputScale);
                pdfCanvas.style.width = `${Math.floor(viewport.width)}px`;
                pdfCanvas.style.height = `${Math.floor(viewport.height)}px`;
                annotationCanvas.style.width = `${Math.floor(viewport.width)}px`;
                annotationCanvas.style.height = `${Math.floor(viewport.height)}px`;

                _renderTask = page.render({
                    canvas: pdfCanvas,
                    transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null,
                    viewport,
                });

                await _renderTask.promise;

                if (sequence !== this.renderSequence) return;

                this.renderAnnotations();
            } catch (error) {
                if (error?.name === 'RenderingCancelledException') return;

                console.error('PDF page render failed', error);
                this.renderError = true;
            } finally {
                if (sequence === this.renderSequence) {
                    _renderTask = null;
                    this.rendering = false;
                }
            }
        },

        async loadAnnotations() {
            this.annotationLoadError = false;

            try {
                const response = await fetch(this.loadUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Annotation load failed');
                }

                this.annotations = await response.json();
                this.renderAnnotations();
            } catch (error) {
                console.error('Annotation load failed', error);
                this.annotationLoadError = true;
                this.annotations = [];
            }
        },

        canvasPoint(event) {
            const rect = this.$refs.annotationCanvas.getBoundingClientRect();

            return {
                x: Math.min(100, Math.max(0, ((event.clientX - rect.left) / rect.width) * 100)),
                y: Math.min(100, Math.max(0, ((event.clientY - rect.top) / rect.height) * 100)),
            };
        },

        handleMouseDown(event) {
            if (!this.canEdit || !this.activeTool || this.saving) return;
            if (this.pendingNote || this.pendingText) return;

            const point = this.canvasPoint(event);

            if (this.activeTool === 'text') {
                this.pendingText = { page: this.currentPage, x: point.x, y: point.y };
                this.textContent = '';
                return;
            }

            if (this.activeTool === 'note') {
                const hit = this.hitTestNote(point);
                if (hit) {
                    this.draggingNote = { id: hit.id, startDragX: point.x, startDragY: point.y, origX: hit.x, origY: hit.y };
                    return;
                }
            }

            this.isDrawing = true;
            this.startX = point.x;
            this.startY = point.y;
        },

        handleMouseMove(event) {
            if (!this.canEdit) return;

            const point = this.canvasPoint(event);

            if (this.draggingNote) {
                const ann = this.annotations.find(a => a.id === this.draggingNote.id);
                if (ann) {
                    ann.x = Math.max(0, Math.min(95, this.draggingNote.origX + (point.x - this.draggingNote.startDragX)));
                    ann.y = Math.max(0, Math.min(95, this.draggingNote.origY + (point.y - this.draggingNote.startDragY)));
                    this.renderAnnotations();
                }
                return;
            }

            if (!this.isDrawing) return;

            this.renderAnnotations();

            if (this.activeTool === 'highlight') {
                this.drawHighlight(this.startX, this.startY, point.x - this.startX, point.y - this.startY, true);
            }

            if (this.activeTool === 'note') {
                this.drawNotePreview(this.startX, this.startY, point.x - this.startX, point.y - this.startY);
            }
        },

        async handleMouseUp(event) {
            if (!this.canEdit) return;

            const point = this.canvasPoint(event);

            if (this.draggingNote) {
                const ann = this.annotations.find(a => a.id === this.draggingNote.id);
                const dn = this.draggingNote;
                this.draggingNote = null;
                if (ann) await this.moveAnnotation(ann, dn.origX, dn.origY);
                return;
            }

            if (!this.isDrawing) return;
            this.isDrawing = false;

            if (this.activeTool === 'highlight') {
                const x = Math.min(this.startX, point.x);
                const y = Math.min(this.startY, point.y);
                const w = Math.abs(point.x - this.startX);
                const h = Math.abs(point.y - this.startY);

                if (w < 0.5 || h < 0.5) {
                    this.renderAnnotations();
                    return;
                }

                await this.saveAnnotation({ type: 'highlight', page: this.currentPage, x, y, w, h });
            }

            if (this.activeTool === 'note') {
                const x = Math.min(this.startX, point.x);
                const y = Math.min(this.startY, point.y);
                const w = Math.max(Math.abs(point.x - this.startX), 12);
                const h = Math.max(Math.abs(point.y - this.startY), 10);

                this.renderAnnotations();
                this.pendingNote = { page: this.currentPage, x, y, w, h };
                this.noteText = '';
            }
        },

        async saveNote() {
            if (!this.pendingNote || !this.noteText.trim()) return;

            await this.saveAnnotation({
                ...this.pendingNote,
                type: 'note',
                content: this.noteText.trim(),
                color: '#f59e0b',
            });
            this.pendingNote = null;
            this.noteText = '';
        },

        async saveText() {
            if (!this.pendingText || !this.textContent.trim()) return;

            await this.saveAnnotation({
                ...this.pendingText,
                type: 'text',
                content: this.textContent.trim(),
                color: '#1e3a2f',
            });
            this.pendingText = null;
            this.textContent = '';
        },

        async saveAnnotation(data) {
            if (!this.storeUrl) return;

            this.saving = true;
            try {
                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(data),
                });

                if (!response.ok) {
                    throw new Error('Annotation save failed');
                }

                const annotation = await response.json();
                this.annotations.push(annotation);
                this.renderAnnotations();
                this.lastSaved = true;
                window.setTimeout(() => {
                    this.lastSaved = false;
                }, 1600);
            } catch {
                window.alert('Failed to save annotation. Please try again.');
                this.renderAnnotations();
            } finally {
                this.saving = false;
            }
        },

        async deleteAnnotation(id) {
            if (!this.storeUrl || !window.confirm('Delete this annotation?')) return;

            const ann = this.annotations.find(a => a.id === id);
            if (!ann) return;

            this.annotations = this.annotations.filter(a => a.id !== id);
            this.renderAnnotations();

            try {
                const response = await fetch(`${this.storeUrl}/${id}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                if (!response.ok) throw new Error('Delete failed');
            } catch {
                this.annotations.push(ann);
                this.renderAnnotations();
                window.alert('Failed to delete annotation. Please try again.');
            }
        },

        renderAnnotations() {
            const canvas = this.$refs.annotationCanvas;
            if (!canvas) return;

            const context = canvas.getContext('2d');
            context.clearRect(0, 0, canvas.width, canvas.height);

            this.annotations
                .filter((annotation) => Number(annotation.page) === this.currentPage)
                .forEach((annotation) => {
                    if (annotation.type === 'highlight') {
                        this.drawHighlight(annotation.x, annotation.y, annotation.w || 0, annotation.h || 0);
                    }

                    if (annotation.type === 'text') {
                        this.drawText(annotation);
                    }

                    if (annotation.type === 'note') {
                        this.drawNote(annotation);
                    }
                });
        },

        drawHighlight(x, y, w, h, preview = false) {
            const canvas = this.$refs.annotationCanvas;
            const context = canvas.getContext('2d');
            context.fillStyle = preview ? 'rgba(250,204,21,0.25)' : 'rgba(250,204,21,0.35)';
            context.fillRect((x / 100) * canvas.width, (y / 100) * canvas.height, (w / 100) * canvas.width, (h / 100) * canvas.height);
        },

        drawText(annotation) {
            const canvas = this.$refs.annotationCanvas;
            const context = canvas.getContext('2d');
            context.fillStyle = '#dc2626';
            context.font = 'bold 14px system-ui, sans-serif';
            context.fillText(annotation.content || '', (annotation.x / 100) * canvas.width, (annotation.y / 100) * canvas.height);
        },

        hitTestNote(point) {
            return this.annotations.find(a =>
                a.type === 'note' &&
                Number(a.page) === this.currentPage &&
                point.x >= a.x && point.x <= a.x + (a.w || 20) &&
                point.y >= a.y && point.y <= a.y + (a.h || 15)
            ) || null;
        },

        async moveAnnotation(annotation, origX, origY) {
            if (!this.storeUrl) return;
            try {
                const response = await fetch(`${this.storeUrl}/${annotation.id}`, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ x: annotation.x, y: annotation.y }),
                });
                if (!response.ok) throw new Error('Move failed');
                const updated = await response.json();
                const idx = this.annotations.findIndex(a => a.id === updated.id);
                if (idx !== -1) Object.assign(this.annotations[idx], updated);
            } catch {
                annotation.x = origX;
                annotation.y = origY;
                this.renderAnnotations();
            }
        },

        drawNotePreview(x, y, w, h) {
            const canvas = this.$refs.annotationCanvas;
            const ctx = canvas.getContext('2d');
            const cx = (Math.min(x, x + w) / 100) * canvas.width;
            const cy = (Math.min(y, y + h) / 100) * canvas.height;
            const cw = (Math.abs(w) / 100) * canvas.width;
            const ch = (Math.abs(h) / 100) * canvas.height;

            ctx.fillStyle = 'rgba(254, 240, 138, 0.45)';
            ctx.fillRect(cx, cy, cw, ch);
            ctx.strokeStyle = '#ca8a04';
            ctx.setLineDash([4, 2]);
            ctx.lineWidth = 1.5;
            ctx.strokeRect(cx, cy, cw, ch);
            ctx.setLineDash([]);
        },

        drawNote(annotation) {
            const canvas = this.$refs.annotationCanvas;
            const ctx = canvas.getContext('2d');
            const x = (annotation.x / 100) * canvas.width;
            const y = (annotation.y / 100) * canvas.height;
            const w = ((annotation.w || 20) / 100) * canvas.width;
            const h = ((annotation.h || 15) / 100) * canvas.height;

            ctx.fillStyle = 'rgba(254, 240, 138, 0.88)';
            ctx.fillRect(x, y, w, h);
            ctx.strokeStyle = '#ca8a04';
            ctx.lineWidth = 1;
            ctx.strokeRect(x, y, w, h);

            if (annotation.content) {
                ctx.fillStyle = '#1c1917';
                const fontSize = Math.max(9, Math.min(13, Math.floor(h / 6)));
                ctx.font = `${fontSize}px system-ui, sans-serif`;
                const lineHeight = fontSize + 3;
                const maxWidth = w - 8;
                const words = annotation.content.split(' ');
                let line = '';
                let lineY = y + fontSize + 4;

                for (const word of words) {
                    const testLine = line + word + ' ';
                    if (ctx.measureText(testLine).width > maxWidth && line !== '') {
                        ctx.fillText(line.trim(), x + 4, lineY);
                        line = word + ' ';
                        lineY += lineHeight;
                        if (lineY > y + h - 4) break;
                    } else {
                        line = testLine;
                    }
                }
                if (line.trim() && lineY <= y + h - 4) {
                    ctx.fillText(line.trim(), x + 4, lineY);
                }
            }
        },
    };
});
