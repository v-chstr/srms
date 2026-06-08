{{--
    Announcement actions modal (view / edit / delete).
    Requires: Alpine scope from <x-announcements.management> parent (selected object).
    Usage: <x-announcements.actions-modal :courses="$courses" :manageBaseUrl="$manageBaseUrl" />
--}}
@props([
    'courses',
    'manageBaseUrl',
    'initialMode' => 'view',
])

<x-ui.modal name="announcement-actions" maxWidth="xl">
    <template x-if="selected">
        <x-ui.modal-actions modalName="announcement-actions" :initial-mode="$initialMode">
            <x-slot:title>
                <h2 class="text-sm font-semibold text-white leading-snug break-words" x-text="selected.title"></h2>
                <p class="mt-0.5 text-xs text-white/70 break-words" x-text="selected.course_name + ', ' + selected.created_at"></p>
            </x-slot:title>
            <x-slot:tabs>
                <x-ui.modal-tab key="view" label="View" />
                <template x-if="selected.can_manage">
                    <div class="contents">
                        <x-ui.modal-tab key="edit" label="Edit" />
                        <x-ui.modal-tab key="delete" label="Delete" :danger="true" />
                    </div>
                </template>
            </x-slot:tabs>

            {{-- View panel --}}
            <div x-show="mode === 'view'" x-cloak class="px-5 py-4 space-y-4">
                {{-- Message — primary content --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 mb-1.5">Message</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed break-words" x-text="selected.message"></p>
                </div>
                {{-- Metadata --}}
                <div class="divide-y divide-gray-100 border-t border-gray-100 pt-1">
                    <x-ui.detail-field label="Target">
                        <span x-text="selected.course_name"></span>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Posted by">
                        <span class="font-medium text-gray-900" x-text="selected.poster_name"></span>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Posted on">
                        <span class="tabular-nums" x-text="selected.created_at"></span>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Expires">
                        <template x-if="selected.expires_at_display">
                            <span class="tabular-nums" x-text="selected.expires_at_display"></span>
                        </template>
                        <template x-if="!selected.expires_at_display">
                            <span class="text-gray-400">No expiry</span>
                        </template>
                    </x-ui.detail-field>
                </div>
            </div>

            {{-- Edit panel (own announcements only) --}}
            <div x-show="mode === 'edit' && selected.can_manage" x-cloak>
                <form method="POST" :action="'{{ $manageBaseUrl }}' + '/' + selected.id">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_context_announcement_id" :value="selected.id">
                    <input type="hidden" name="_context_mode" value="edit">
                    <div class="px-6 pt-5 pb-0 space-y-4">
                        <x-form.input name="title" label="Title" :required="true" maxlength="100" x-model="selected.title" />
                        <x-form.textarea name="message" label="Message" :required="true" rows="4" :maxlength="500" x-model="selected.message" />
                        <x-form.select name="course_id" label="Target Course" :options="$courses" :placeholder="config('ui.placeholders.all_courses')" x-model="selected.course_id" />
                        <x-form.expiry-picker />
                    </div>
                    <x-ui.modal-footer modalName="announcement-actions" submitLabel="Update Announcement" />
                </form>
            </div>

            {{-- Delete panel (own announcements only) --}}
            <div x-show="mode === 'delete' && selected.can_manage" x-cloak>
                <form method="POST" :action="'{{ $manageBaseUrl }}' + '/' + selected.id">
                    @csrf
                    @method('DELETE')
                    <div class="px-6 pt-5 pb-0 space-y-4">
                        <div class="bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700 rounded-md">
                            This announcement will be permanently deleted and will no longer be visible to students.
                        </div>
                    </div>
                    <x-ui.modal-footer modalName="announcement-actions" submitLabel="Confirm Delete" submitVariant="danger" />
                </form>
            </div>
        </x-ui.modal-actions>
    </template>
</x-ui.modal>
