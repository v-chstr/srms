@props([
    'events' => [],
    'canCreate' => false,
])

{{--
    Academic Defense Calendar — FullCalendar month view with Alpine.js.
    Events: global (purple) and course-scoped (gold).
    Click event → detail popover. Admin/Adviser get "Add Schedule" button.
--}}
<div
    x-data="defenseCalendar(@js($events), @js($canCreate))"
    x-init="init()"
    class="rounded-md border border-gray-200 bg-white shadow-sm"
>
    {{-- Calendar header --}}
    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center gap-x-3 gap-y-2 justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <h2 class="text-sm font-semibold text-gray-900 shrink-0">Defense schedule</h2>
            <div class="hidden sm:flex items-center gap-3 ml-1">
                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="w-2 h-2 rounded-full bg-primary-600"></span>All programs
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="w-2 h-2 rounded-full bg-accent-500"></span>Course
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            {{-- Navigation --}}
            <div class="flex items-center gap-1">
                <button type="button" @click="prev()" class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-50" title="Previous month">
                    <x-ui.icon name="chevron-left" class="w-4 h-4" />
                </button>
                <button type="button" @click="today()" class="px-2 py-0.5 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-md">Today</button>
                <button type="button" @click="next()" class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-50" title="Next month">
                    <x-ui.icon name="chevron-right" class="w-4 h-4" />
                </button>
            </div>
            <span x-text="currentMonth" class="text-sm font-semibold text-gray-700 min-w-[110px] text-center"></span>

            @if($canCreate)
                <button type="button"
                    x-on:click="$dispatch('open-modal', 'schedule-create')"
                    class="ml-1 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-md">
                    <x-ui.icon name="plus" class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Add schedule</span>
                    <span class="sm:hidden">Add</span>
                </button>
            @endif
        </div>
    </div>

    {{-- FullCalendar mount point — overflow-x-auto allows horizontal scroll on narrow screens --}}
    <div class="overflow-x-auto">
        <div x-ref="calendarEl" class="srms-calendar"></div>
    </div>

    {{-- Event detail popover (shown on click) --}}
    <div
        x-show="showDetail"
        x-cloak
        @click.outside="showDetail = false"
        @keydown.escape.window="showDetail = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :style="'position: fixed; z-index: 1100; top: ' + detailPos.top + 'px; left: ' + detailPos.left + 'px;'"
        class="w-80 bg-white rounded-md shadow-lg border border-gray-200 overflow-hidden"
    >
        <template x-if="selectedEvent">
            <div>
                {{-- Popover header --}}
                <div class="px-4 py-3 border-b border-gray-100" :class="selectedEvent.extendedProps.is_global ? 'bg-primary-50' : 'bg-accent-50'">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-800 leading-snug" x-text="selectedEvent.title"></h3>
                            <p class="text-xs mt-0.5" :class="selectedEvent.extendedProps.is_global ? 'text-primary-600' : 'text-accent-700'"
                               x-text="selectedEvent.extendedProps.is_global ? 'All Programs' : selectedEvent.extendedProps.course_code + ' - ' + selectedEvent.extendedProps.course_name">
                            </p>
                        </div>
                        <button @click="showDetail = false" class="p-0.5 text-gray-400 hover:text-gray-600 rounded">
                            <x-ui.icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Popover body --}}
                <div class="px-4 py-3 space-y-2.5">
                    {{-- Date & Time --}}
                    <div class="flex items-start gap-2.5">
                        <x-ui.icon name="calendar-days" class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <div>
                            <p class="text-sm text-gray-700" x-text="selectedEvent.extendedProps.scheduled_date"></p>
                            <p class="text-xs text-gray-500">
                                <span x-text="selectedEvent.extendedProps.start_time"></span>
                                <template x-if="selectedEvent.extendedProps.end_time">
                                    <span> to <span x-text="selectedEvent.extendedProps.end_time"></span></span>
                                </template>
                            </p>
                        </div>
                    </div>

                    {{-- Room --}}
                    <div class="flex items-start gap-2.5">
                        <x-ui.icon name="map-pin" class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <p class="text-sm text-gray-700" x-text="selectedEvent.extendedProps.room"></p>
                    </div>

                    {{-- Description --}}
                    <template x-if="selectedEvent.extendedProps.description">
                        <div class="flex items-start gap-2.5">
                            <x-ui.icon name="bars-3-bottom-left" class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                            <p class="text-sm text-gray-600 leading-relaxed" x-text="selectedEvent.extendedProps.description"></p>
                        </div>
                    </template>

                    {{-- Created by --}}
                    <div class="flex items-start gap-2.5">
                        <x-ui.icon name="user" class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
                        <p class="text-xs text-gray-500">Posted by <span class="font-medium text-gray-600" x-text="selectedEvent.extendedProps.creator_name"></span></p>
                    </div>
                </div>

                {{-- Edit/Delete actions (admin/adviser only) --}}
                @if($canCreate)
                    <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50 flex items-center justify-end gap-2">
                        <button type="button"
                            @click="editEvent()"
                            class="text-xs font-medium text-primary-600 hover:text-primary-800 px-2 py-1 rounded-md hover:bg-primary-50">
                            Edit
                        </button>
                        <button type="button"
                            @click="deleteEvent()"
                            class="text-xs font-medium text-red-600 hover:text-red-800 px-2 py-1 rounded-md hover:bg-red-50">
                            Delete
                        </button>
                    </div>
                @endif
            </div>
        </template>
    </div>

    {{-- Delete confirmation dialog --}}
    <template x-if="showDeleteConfirm">
        <div class="fixed inset-0 z-[1200] flex items-center justify-center bg-black/30"
             @keydown.escape.window="cancelDelete()">
            <div class="bg-white rounded-md shadow-xl border border-gray-200 w-full max-w-sm mx-4 overflow-hidden">
                <div class="px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">Delete schedule?</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        <span x-text="pendingDeleteTitle"></span> will be permanently removed.
                    </p>
                </div>
                <div class="px-5 pb-4 flex items-center justify-end gap-3">
                    <button type="button" @click="cancelDelete()"
                            class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 rounded-md hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="button" @click="confirmDelete()"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
