{{--
    Announcement create modal.
    Usage: <x-announcements.create-modal :courses="$courses" :storeRoute="$storeRoute" />
--}}
@props([
    'courses',
    'storeRoute',
    'show' => false,
])

<x-ui.modal name="announcement-create" :show="$show" maxWidth="xl">
    <div class="divide-y divide-gray-100">
        <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4 shrink-0">
            <div class="min-w-0 flex-1 pr-4">
                <h2 class="text-sm font-semibold text-gray-900 leading-snug truncate">New Announcement</h2>
            </div>
            <button type="button"
                    x-on:click="$dispatch('close-modal', 'announcement-create')"
                    class="shrink-0 mt-0.5 text-gray-400 hover:text-gray-600 transition-colors">
                <x-ui.icon name="close" size="sm" />
            </button>
        </div>
        <form method="POST" action="{{ route($storeRoute) }}">
            @csrf
            <div class="px-6 pt-5 pb-0 space-y-4">
                <x-form.input name="title" label="Title" placeholder="Announcement title" :required="true" maxlength="100" />
                <x-form.textarea name="message" label="Message" placeholder="Write your announcement here" :required="true" rows="4" :maxlength="500" />
                <x-form.select name="course_id" label="Target Course" :options="$courses" :placeholder="config('ui.placeholders.all_courses')" />
                <x-form.expiry-picker />
            </div>
            <x-ui.modal-footer modalName="announcement-create" submitLabel="Post Announcement" />
        </form>
    </div>
</x-ui.modal>
