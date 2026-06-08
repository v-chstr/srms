import './bootstrap';

import Alpine from 'alpinejs';

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';

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

/**
 * Navigation Manager for full document navigations.
 *
 * This app uses normal Blade page loads, not Inertia/Turbo-style requests.
 * Once a document navigation has started, we cannot reliably "switch" it to
 * a newer target from JavaScript. Let the first click use the browser's
 * native navigation flow and block follow-up nav clicks from piling up.
 */
Alpine.data('navManager', () => ({
    navigating: false,

    handleNavClick(e) {
        const link = e.target.closest('a[data-nav-link]');
        if (!link) return;

        const url = link.getAttribute('href');
        if (!url || url === '#' || url === window.location.href) return;

        if (e.button !== 0) return;

        // Allow modifier keys for open-in-new-tab behavior
        if (e.ctrlKey || e.metaKey || e.shiftKey) return;

        // First click: show loading state and let the browser navigate natively.
        // Later clicks while the document is still loading just create more
        // pending requests in php artisan serve, so ignore them.
        if (this.navigating) {
            e.preventDefault();
            return;
        }

        this.navigating = true;
    },
}));

/**
 * Defense Calendar — Alpine.js component wrapping FullCalendar.
 * Renders a month-view calendar with global (purple) and course-scoped (gold) events.
 */
Alpine.data('defenseCalendar', (events, canCreate) => ({
    calendar: null,
    events: events || [],
    canCreate: canCreate || false,
    currentMonth: '',
    showDetail: false,
    selectedEvent: null,
    detailPos: { top: 0, left: 0 },
    showDeleteConfirm: false,
    pendingDeleteId: null,
    pendingDeleteTitle: '',

    init() {
        this.$nextTick(() => {
            this.calendar = new Calendar(this.$refs.calendarEl, {
                plugins: [dayGridPlugin],
                initialView: 'dayGridMonth',
                headerToolbar: false, // We use our own custom header
                height: 'auto',
                fixedWeekCount: false,
                dayMaxEvents: 3,
                moreLinkClick: 'popover',
                events: this.events,
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    this.openDetail(info.event, info.jsEvent);
                },
                datesSet: (info) => {
                    // Update the month title when the view changes
                    const midDate = new Date((info.start.getTime() + info.end.getTime()) / 2);
                    this.currentMonth = midDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                },
            });
            this.calendar.render();

            // Re-render whenever the container is resized — handles DevTools viewport
            // simulation, sidebar appearing/disappearing at the lg breakpoint, and any
            // other CSS-driven container width change that does NOT fire window.resize.
            if (typeof ResizeObserver !== 'undefined') {
                const ro = new ResizeObserver(() => {
                    this.calendar?.updateSize();
                });
                ro.observe(this.$el);
                this.$cleanup(() => ro.disconnect());
            }
        });
    },

    prev() {
        this.calendar?.prev();
    },

    next() {
        this.calendar?.next();
    },

    today() {
        this.calendar?.today();
    },

    openDetail(event, jsEvent) {
        this.selectedEvent = {
            id: event.id,
            title: event.title,
            extendedProps: event.extendedProps,
        };

        // Position popover near click, but keep it within the viewport
        const rect = this.$el.getBoundingClientRect();
        let top = jsEvent.clientY + 8;
        let left = jsEvent.clientX - 140;

        // Keep popover within viewport bounds
        if (left + 320 > window.innerWidth) left = window.innerWidth - 340;
        if (left < 10) left = 10;
        if (top + 300 > window.innerHeight) top = jsEvent.clientY - 300;
        if (top < 10) top = 10;

        this.detailPos = { top, left };
        this.showDetail = true;
    },

    editEvent() {
        if (!this.selectedEvent) return;
        this.showDetail = false;
        // Dispatch to open edit modal — the schedule-edit modal needs to be rendered in the page.
        // For now, forward to a full page form via navigation or use the create modal with different data.
        // This uses the schedule-edit-{id} modal pattern.
        this.$dispatch('open-modal', 'schedule-edit-' + this.selectedEvent.extendedProps.schedule_id);
    },

    deleteEvent() {
        if (!this.selectedEvent) return;
        this.pendingDeleteId = this.selectedEvent.extendedProps.schedule_id;
        this.pendingDeleteTitle = this.selectedEvent.title;
        this.showDeleteConfirm = true;
        this.showDetail = false;
    },

    confirmDelete() {
        if (!this.pendingDeleteId) return;
        const id = this.pendingDeleteId;
        this.showDeleteConfirm = false;
        this.pendingDeleteId = null;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/schedules/' + id;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]').content}"><input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    },

    cancelDelete() {
        this.showDeleteConfirm = false;
        this.pendingDeleteId = null;
        this.pendingDeleteTitle = '';
    },
}));

import './annotation-viewer';
window.Alpine = Alpine;

Alpine.start();
