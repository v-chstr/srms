<x-layouts.app title="Announcements">

    <x-layouts.page-header title="Announcements" subtitle="Current announcements and notices" />

    @php
        $announcementsJson = $announcements->map(fn ($a) => [
            'id'                 => $a->id,
            'title'              => $a->title,
            'message'            => $a->message,
            'course_id'          => $a->course_id,
            'course_name'        => $a->course->name ?? 'All courses',
            'poster_name'        => $a->poster
                                        ? trim($a->poster->first_name . ' ' . $a->poster->last_name)
                                        : 'Unknown',
            'created_at'         => $a->created_at->srmsDateTime(),
            'expires_at'         => $a->expires_at?->format('Y-m-d'),
            'expires_at_display' => $a->expires_at?->srmsDate(),
            'can_manage'         => false,
        ]);
    @endphp

    <div x-data="{
        allAnnouncements: @js($announcementsJson),
        selected: null,
        openAnnouncement(id) {
            this.selected = null;
            this.$nextTick(() => {
                this.selected = { ...this.allAnnouncements.find(a => a.id === id) };
                this.$dispatch('open-modal', 'announcement-actions');
            });
        }
    }">

        {{-- Filter bar --}}
        <x-ui.filter-bar
            :action="route('announcements.index')"
            :clearHref="route('announcements.index')"
            :hasFilters="request()->filled('search')"
        >
            <div class="flex-1 min-w-[200px]">
                <x-form.input name="search" label="Search" placeholder="Announcement title" :value="request('search')" />
            </div>
        </x-ui.filter-bar>

        {{-- Announcement list --}}
        <div class="space-y-2">
            @forelse($announcements as $announcement)
                <x-announcements.item :announcement="$announcement" :index="$loop->iteration" />
            @empty
                <x-ui.empty-state message="No announcements found." />
            @endforelse
        </div>

        {{-- Pagination --}}
        <x-ui.pagination :paginator="$announcements" class="pt-3" />

        {{-- View modal (view-only: can_manage is always false) --}}
        <x-announcements.actions-modal :courses="collect()" manageBaseUrl="" />

    </div>

</x-layouts.app>
