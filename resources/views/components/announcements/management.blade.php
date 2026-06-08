{{--
    Reusable announcements management surface.
    Used by both admin and adviser via the single pages/announcements/manage.blade.php view.

    Props set by AnnouncementManageController based on user role:
        filterRoute   â€” named route for the GET filter form
        storeRoute    â€” named route for POST create
        manageBaseUrl â€” URL prefix for PUT/DELETE (e.g. url('admin/announcements'))
        ownerId       â€” null = manage all (admin); int user id = manage own only (adviser)
--}}
@props([
    'announcements',
    'archivedAnnouncements',
    'courses',
    'filterRoute',
    'storeRoute',
    'manageBaseUrl',
    'ownerId' => null,
])

@php
    $canManage = fn($a) => is_null($ownerId) || $a->posted_by === $ownerId;

    $serializeAnnouncement = fn($a) => [
        'id'                 => $a->id,
        'title'              => $a->title,
        'message'            => $a->message,
        'course_id'          => $a->course_id,
        'course_name'        => $a->course->name ?? 'All courses',
        'poster_name'        => $a->poster
                                    ? (trim($a->poster->first_name . ' ' . $a->poster->last_name) ?: $a->poster->email)
                                    : 'Unknown',
        'created_at'         => $a->created_at->srmsDateTime(),
        'expires_at'         => $a->expires_at?->format('Y-m-d'),
        'expires_at_display' => $a->expires_at?->srmsDate(),
        'can_manage'         => $canManage($a),
    ];

    $allAnnouncementsJson = $announcements->map($serializeAnnouncement)
        ->merge($archivedAnnouncements->map($serializeAnnouncement));
@endphp

<div x-data="{
    allAnnouncements: @js($allAnnouncementsJson),
    initialAnnouncementId: @js(old('_context_announcement_id')),
    selected: null,
    init() {
        if (this.initialAnnouncementId) {
            this.$nextTick(() => this.openAnnouncement(this.initialAnnouncementId));
        }
    },
    openAnnouncement(id) {
        id = Number(id);
        this.selected = null;
        this.$nextTick(() => {
            const src = this.allAnnouncements.find(a => a.id === id);
            if (!src) {
                return;
            }

            this.selected = { ...src };
            this.$dispatch('open-modal', 'announcement-actions');
        });
    }
}">

    {{-- Filter bar --}}
    <x-ui.filter-bar
        :action="route($filterRoute)"
        :clearHref="route($filterRoute)"
        :hasFilters="request()->hasAny(['search', 'course'])"
    >
        <div class="flex-1 min-w-[200px]">
            <x-form.input
                name="search"
                label="Search"
                placeholder="Announcement title"
                :value="request('search')"
            />
        </div>
        <div class="w-52">
            <x-form.select
                name="course"
                label="Target Course"
                :options="['global' => 'Global only'] + $courses->toArray()"
                :selected="request('course')"
                placeholder="All announcements"
            />
        </div>
    </x-ui.filter-bar>

    {{-- Main 2-column: left = active table, right = post card --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-start">

        {{-- Left: Active announcements card grid --}}
        <div class="lg:col-span-3 space-y-3">
            @forelse($announcements as $announcement)
                @if($loop->first)
                <div class="space-y-2">
                @endif

                    <x-announcements.item
                        :announcement="$announcement"
                        :index="$loop->iteration"
                        buttonVariant="ghost"
                        :showMetaIcons="true"
                    />

                @if($loop->last)
                </div>
                @endif
            @empty
                <div class="bg-white border border-gray-200 rounded-lg px-6 py-10 text-center">
                    <p class="text-sm text-gray-400">No active announcements found.</p>
                </div>
            @endforelse

            {{-- Pagination --}}
            <x-ui.pagination :paginator="$announcements" class="pt-1" />
        </div>

        {{-- Right: Post new announcement --}}
        <div class="lg:col-span-1">
            <x-ui.card title="Post Announcement">
                <div class="space-y-3">
                    <p class="text-sm text-gray-500">
                        Announcements can be sent to all students or targeted to a specific course.
                    </p>
                    <x-ui.button
                        type="button"
                        variant="primary"
                        class="w-full justify-center"
                        x-on:click="$dispatch('open-modal', 'announcement-create')">
                        <x-ui.icon name="megaphone" class="w-4 h-4 mr-1.5" />
                        New Announcement
                    </x-ui.button>
                </div>
            </x-ui.card>
        </div>

    </div>

    {{-- Archived section --}}
    <x-announcements.archive
        :archivedAnnouncements="$archivedAnnouncements"
        :ownerId="$ownerId"
    />

    {{-- Modals --}}
    <x-announcements.create-modal
        :courses="$courses"
        :storeRoute="$storeRoute"
        :show="$errors->hasAny(['title', 'message', 'course_id', 'expires_at'])"
    />
    <x-announcements.actions-modal :courses="$courses" :manageBaseUrl="$manageBaseUrl" :initial-mode="old('_context_mode', 'view')" />

</div>

