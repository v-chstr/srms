{{--
    Archived announcements section.
    Requires: Alpine scope from <x-announcements.management> parent (openAnnouncement).
--}}
@props([
    'archivedAnnouncements',
    'ownerId' => null,
])

<div class="mt-4">
    <x-ui.card :compact="true"
        title="Archived Announcements"
        maxHeight="max-h-80"
        :empty="$archivedAnnouncements->isEmpty()"
        emptyText="No archived announcements.">
        <x-table.wrapper class="border-0 rounded-none shadow-none">
            <x-slot:head>
                <x-table.heading>Title</x-table.heading>
                <x-table.heading class="hidden sm:table-cell w-48">Target</x-table.heading>
                <x-table.heading class="hidden md:table-cell w-40">Posted By</x-table.heading>
                <x-table.heading class="w-28">Expired On</x-table.heading>
                <x-table.heading class="w-20 text-right"></x-table.heading>
            </x-slot:head>
            @foreach($archivedAnnouncements as $announcement)
                <tr>
                    <x-table.cell class="text-gray-400" wrap>
                        {{ $announcement->title }}
                    </x-table.cell>
                    <x-table.cell class="hidden sm:table-cell text-gray-400" wrap>{{ $announcement->course->name ?? 'All courses' }}</x-table.cell>
                    <x-table.cell class="hidden md:table-cell text-gray-400" wrap>
                        {{ $announcement->poster->first_name ?? '' }} {{ $announcement->poster->last_name ?? '' }}
                    </x-table.cell>
                    <x-table.cell class="tabular-nums text-gray-400" nowrap>
                        <x-ui.date :value="$announcement->expires_at" numeric />
                    </x-table.cell>
                    <x-table.cell class="text-right" nowrap>
                        <x-ui.button type="button" variant="ghost" size="sm"
                            x-on:click="openAnnouncement({{ $announcement->id }})">
                            View
                        </x-ui.button>
                    </x-table.cell>
                </tr>
            @endforeach
        </x-table.wrapper>
    </x-ui.card>
</div>
