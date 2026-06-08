@props([
    'paper',
    'pdfUrl',
    'loadUrl',
    'storeUrl' => null,
    'submitUrl' => null,
    'backUrl',
    'canEdit' => false,
])

@php
    $viewerConfig = [
        'pdfUrl' => $pdfUrl,
        'loadUrl' => $loadUrl,
        'storeUrl' => $storeUrl,
        'canEdit' => (bool) $canEdit,
    ];
@endphp

<div
    class="flex h-full min-h-0 bg-gray-100"
    x-data="annotationViewer(@js($viewerConfig))"
>
    <aside class="flex w-64 shrink-0 flex-col overflow-y-auto border-r border-gray-200 bg-gray-50/50">
        <div class="border-b border-gray-200 px-4 py-4 bg-gray-50/20">
            <a href="{{ $backUrl }}" class="mb-4 inline-flex items-center gap-1.5 text-xs font-semibold text-primary-700 hover:text-primary-800 transition-colors">
                <x-ui.icon name="arrow-left" size="xs" />
                Back
            </a>
            <h1 class="line-clamp-3 text-sm font-bold leading-normal text-gray-900" title="{{ $paper->title }}">{{ $paper->title }}</h1>
            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                <x-ui.badge :status="$paper->status" />
            </div>
            <dl class="mt-4 border-t border-gray-200/60 pt-4 space-y-3 text-xs">
                <div class="flex flex-col gap-0.5">
                    <dt class="font-semibold text-gray-500">Student</dt>
                    <dd class="text-sm font-medium text-gray-800">
                        {{ $paper->submitter ? $paper->submitter->first_name . ' ' . $paper->submitter->last_name : 'Unknown' }}
                    </dd>
                </div>
                <div class="flex flex-col gap-0.5">
                    <dt class="font-semibold text-gray-500">Adviser</dt>
                    <dd class="text-sm font-medium text-gray-800">
                        {{ $paper->adviser ? $paper->adviser->first_name . ' ' . $paper->adviser->last_name : 'Not assigned' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="flex-1 px-4 py-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <x-ui.icon name="document-text" size="sm" class="text-gray-400" />
                    Review Notes
                </h2>
                <span class="rounded-md bg-white border border-gray-200/60 px-2.5 py-0.5 text-xs font-semibold text-gray-500 shadow-sm" x-text="notes.length"></span>
            </div>
            <template x-if="notes.length === 0">
                <div class="text-center py-6 border border-dashed border-gray-200 rounded-md bg-white">
                    <x-ui.icon name="document-text" size="md" class="mx-auto text-gray-300 mb-1" />
                    <p class="text-xs text-gray-400 font-medium">No notes on this document</p>
                </div>
            </template>
            {{-- Grouped by page: one collapsible section per unique page number --}}
            <div class="space-y-1.5">
                <template x-for="pageNum in [...new Set(notes.map(n => n.page))].sort((a,b) => a - b)" :key="pageNum">
                    <div x-data="{ open: true }" class="rounded-md border border-gray-200/60 bg-white shadow-sm">
                        {{-- Page group header / toggle --}}
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 rounded-t-md"
                            @click="open = !open"
                        >
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-accent-50 border border-accent-100/60 px-2 py-0.5 text-xs font-semibold text-accent-700" x-text="'Page ' + pageNum"></span>
                            <span class="flex items-center gap-1.5">
                                <span class="text-[10px] font-medium text-gray-400" x-text="notes.filter(n => n.page === pageNum).length + ' note' + (notes.filter(n => n.page === pageNum).length !== 1 ? 's' : '')"></span>
                                <x-ui.icon name="chevron-down" size="xs" class="text-gray-400 transition-transform duration-150" ::class="open ? '' : '-rotate-90'" />
                            </span>
                        </button>
                        {{-- Notes under this page --}}
                        <div x-show="open" class="divide-y divide-gray-100">
                            <template x-for="note in notes.filter(n => n.page === pageNum)" :key="note.id">
                                <div class="group relative">
                                    <button type="button" class="w-full px-3 py-2.5 text-left focus:outline-none" @click="currentPage = note.page; renderPage()">
                                        <p class="line-clamp-3 text-xs leading-relaxed text-gray-600" x-text="note.content"></p>
                                    </button>
                                    @if($canEdit)
                                    <button
                                        type="button"
                                        class="absolute right-2 top-2 rounded-md p-1 text-gray-400 opacity-0 group-hover:opacity-100 hover:text-red-600 hover:bg-red-50 focus:opacity-100 transition-all duration-150"
                                        @click.stop="deleteAnnotation(note.id)"
                                        title="Delete note"
                                    ><x-ui.icon name="trash" size="xs" /></button>
                                    @endif
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </aside>

    <main class="flex min-w-0 flex-1 flex-col">
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4">
            <div class="flex items-center rounded-md border border-gray-200 bg-white p-0.5 shadow-sm">
                <button type="button"
                    class="rounded-l-md p-1.5 text-gray-500 hover:bg-gray-50 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40 transition-colors"
                    @click="prevPage()"
                    :disabled="currentPage <= 1 || rendering"
                >
                    <x-ui.icon name="chevron-left" size="sm" />
                </button>
                <span class="px-3 border-x border-gray-100 text-center text-xs font-semibold text-gray-600 min-w-[90px] select-none">
                    Page <span class="text-gray-900" x-text="currentPage"></span> / <span x-text="totalPages || '-'"></span>
                </span>
                <button type="button"
                    class="rounded-r-md p-1.5 text-gray-500 hover:bg-gray-50 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40 transition-colors"
                    @click="nextPage()"
                    :disabled="currentPage >= totalPages || rendering"
                >
                    <x-ui.icon name="chevron-right" size="sm" />
                </button>
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500">
                <template x-if="saving">
                    <span class="inline-flex items-center gap-1.5 text-gray-400">
                        <x-ui.icon name="spinner" size="xs" class="animate-spin text-gray-400" />
                        Saving changes...
                    </span>
                </template>
                <template x-if="lastSaved">
                    <span x-cloak class="inline-flex items-center gap-1.5 text-primary-600 font-medium">
                        <x-ui.icon name="check-circle" size="xs" class="text-primary-600" />
                        Changes saved
                    </span>
                </template>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-auto px-6 py-6 bg-gray-50">
            <div x-show="loading" class="mx-auto mt-16 max-w-sm rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                <x-ui.icon name="spinner" size="lg" class="mx-auto animate-spin text-primary-600 mb-3" />
                <p class="text-sm font-medium text-gray-700">Loading document...</p>
                <p class="text-xs text-gray-400 mt-1">Please wait while the PDF is parsed.</p>
            </div>
            <div x-show="loadError" x-cloak class="mx-auto mt-16 max-w-sm rounded-lg border border-red-200 bg-red-50 p-6 text-center shadow-sm">
                <x-ui.icon name="exclamation-circle" size="lg" class="mx-auto text-red-600 mb-3" />
                <p class="text-sm font-semibold text-red-800">Unable to load document</p>
                <p class="text-xs text-red-600/80 mt-1">The PDF file could not be fetched or decrypted.</p>
            </div>
            <div x-show="renderError" x-cloak class="mx-auto mt-16 max-w-sm rounded-lg border border-red-200 bg-red-50 p-6 text-center shadow-sm">
                <x-ui.icon name="exclamation-circle" size="lg" class="mx-auto text-red-600 mb-3" />
                <p class="text-sm font-semibold text-red-800">Rendering failed</p>
                <p class="text-xs text-red-600/80 mt-1">The requested page failed to render correctly.</p>
            </div>
            <div x-show="!loading && !loadError && !renderError" x-cloak class="mx-auto w-fit rounded-lg bg-white p-5 shadow-sm border border-gray-200/40">
                <div class="relative">
                    <canvas x-ref="pdfCanvas" class="block"></canvas>
                    <canvas
                        x-ref="annotationCanvas"
                        class="absolute inset-0 block"
                        :class="draggingNote ? 'cursor-move' : (canEdit && activeTool ? 'cursor-crosshair' : 'cursor-default')"
                        @mousedown="handleMouseDown($event)"
                        @mousemove="handleMouseMove($event)"
                        @mouseup="handleMouseUp($event)"
                        @mouseleave="isDrawing = false; if (!draggingNote) renderAnnotations()"
                    ></canvas>

                    {{-- Text annotation: floating input placed directly on the document --}}
                    <template x-if="pendingText">
                        <div
                            class="absolute z-10"
                            :style="`left: ${pendingText.x}%; top: ${pendingText.y}%;`"
                        >
                            <input
                                type="text"
                                x-model="textContent"
                                class="min-w-36 border border-primary-500 bg-white/95 px-2.5 py-1.5 text-xs text-gray-900 shadow-md focus:outline-none rounded-md"
                                placeholder="Type comment, then Enter"
                                @keydown.enter.prevent="saveText()"
                                @keydown.escape="pendingText = null; textContent = ''"
                                @blur="pendingText = null; textContent = ''"
                                x-init="$nextTick(() => $el.focus())"
                            >
                        </div>
                    </template>

                    {{-- Sticky note: sized by drag, textarea appears inside the note box --}}
                    <template x-if="pendingNote">
                        <div
                            class="absolute z-10 flex flex-col overflow-hidden rounded-md border border-amber-400 bg-amber-50 shadow-lg"
                            :style="`left: ${pendingNote.x}%; top: ${pendingNote.y}%; width: ${pendingNote.w}%; height: ${pendingNote.h}%;`"
                        >
                            <textarea
                                x-model="noteText"
                                class="flex-1 resize-none bg-transparent p-2 text-xs text-gray-800 focus:outline-none leading-relaxed"
                                placeholder="Write your note here..."
                                @keydown.escape="pendingNote = null; noteText = ''"
                                x-init="$nextTick(() => $el.focus())"
                            ></textarea>
                            <div class="flex items-center justify-end gap-1.5 border-t border-amber-200 bg-amber-100/80 px-2 py-1">
                                <button type="button" class="text-[10px] font-medium text-amber-700 hover:text-amber-900 transition-colors" @mousedown.prevent @click="pendingNote = null; noteText = ''">Cancel</button>
                                <button type="button" class="rounded bg-amber-600 px-2.5 py-0.5 text-[10px] font-semibold text-white hover:bg-amber-700 transition-colors shadow-sm" @mousedown.prevent @click="saveNote()">Save</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </main>

    @if($canEdit)
    <aside class="flex w-72 shrink-0 flex-col overflow-y-auto border-l border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-4 bg-gray-50/30">
                <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                    <x-ui.icon name="wrench-screwdriver" size="sm" class="text-gray-400" />
                    Annotation Tools
                </h2>
                <p x-show="annotationLoadError" x-cloak class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-800">
                    Saved annotations could not be loaded, but the PDF is still available.
                </p>
                <div class="mt-3 flex flex-col gap-2">
                    <button type="button"
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-sm font-medium transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        :class="activeTool === 'highlight' ? 'border-primary-500 bg-primary-50 text-primary-800 shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50/60'"
                        @click="setTool('highlight')"
                    >
                        <x-ui.icon name="paint-brush" size="sm" />
                        Highlight
                    </button>
                    <button type="button"
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-sm font-medium transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        :class="activeTool === 'text' ? 'border-primary-500 bg-primary-50 text-primary-800 shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50/60'"
                        @click="setTool('text')"
                    >
                        <x-ui.icon name="document-text" size="sm" />
                        Text Comment
                    </button>
                    <button type="button"
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-sm font-medium transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        :class="activeTool === 'note' ? 'border-primary-500 bg-primary-50 text-primary-800 shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50/60'"
                        @click="setTool('note')"
                    >
                        <x-ui.icon name="chat-bubble-left" size="sm" />
                        Sticky Note
                    </button>
                </div>
            </div>

            <div class="border-b border-gray-100 px-4 py-4 bg-gray-50/10">
                <p class="text-xs text-gray-500 leading-normal flex items-start gap-1.5">
                    <x-ui.icon name="information-circle" size="xs" class="text-gray-400 mt-0.5 shrink-0" />
                    <span>Select a tool above, then click or click-and-drag directly on the document view to annotate.</span>
                </p>
            </div>

            <div class="flex-1 px-4 py-4">
                <h3 class="mb-3 text-xs font-semibold text-gray-500 flex items-center justify-between">
                    <span>Annotations, page <span x-text="currentPage"></span></span>
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-500 border border-gray-200/40" x-text="currentPageAnnotations.length"></span>
                </h3>
                <template x-if="currentPageAnnotations.length === 0">
                    <p class="text-xs text-gray-400 italic">No annotations on this page yet.</p>
                </template>
                <div class="space-y-2">
                    <template x-for="ann in currentPageAnnotations" :key="ann.id">
                        <div class="flex items-start gap-2 rounded-md border border-gray-200/60 bg-white px-3 py-2.5 shadow-sm transition-all hover:border-gray-300">
                            <div class="min-w-0 flex-1">
                                <span
                                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold border"
                                    :class="{
                                        'bg-yellow-50 text-yellow-700 border-yellow-100': ann.type === 'highlight',
                                        'bg-red-50 text-red-600 border-red-100': ann.type === 'text',
                                        'bg-amber-50 text-amber-700 border-amber-100': ann.type === 'note',
                                    }"
                                    x-text="ann.type.charAt(0).toUpperCase() + ann.type.slice(1)"
                                ></span>
                                <p x-show="ann.content" class="mt-1.5 text-xs text-gray-600 leading-normal whitespace-pre-wrap" x-text="ann.content"></p>
                            </div>
                            <button type="button" @click="deleteAnnotation(ann.id)" class="mt-0.5 shrink-0 rounded-md p-1 text-gray-300 hover:text-red-600 hover:bg-red-50 focus:text-red-600 focus:bg-red-50 focus:outline-none transition-all duration-150" title="Delete">
                                <x-ui.icon name="trash" size="xs" />
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="space-y-2 border-t border-gray-200 px-4 py-4 bg-gray-50/50">
                @if($submitUrl)
                    <form method="POST" action="{{ $submitUrl }}" class="m-0">
                        @csrf
                        <input type="hidden" name="action" value="send_back">
                        <x-ui.button type="submit" class="w-full justify-center" variant="secondary" size="sm">
                            Send Back for Revision
                        </x-ui.button>
                    </form>
                    <form method="POST" action="{{ $submitUrl }}" class="m-0">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <x-ui.button type="submit" class="w-full justify-center" size="sm">
                            Approve Paper
                        </x-ui.button>
                    </form>
                @endif
            </div>
        </div>
    </aside>
    @endif

</div>
