@props([
    'announcement',
    'index',
    'buttonVariant' => 'secondary',
    'showMetaIcons' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-md border border-gray-200 bg-white px-4 py-3']) }}>
    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-gray-100 text-xs font-semibold tabular-nums text-gray-500">
        {{ $index }}
    </span>

    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium leading-snug text-gray-900">
            {{ $announcement->title }}
        </p>
        <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-500">
            @if($showMetaIcons)
                <x-ui.icon name="academic-cap" size="2xs" class="shrink-0 text-gray-400" />
            @endif
            <span>{{ $announcement->course->name ?? 'All courses' }}</span>
            <span class="text-gray-300">|</span>
            @if($showMetaIcons)
                <x-ui.icon name="user" size="2xs" class="shrink-0 text-gray-400" />
            @endif
            <span>{{ trim(($announcement->poster->first_name ?? '') . ' ' . ($announcement->poster->last_name ?? '')) ?: 'Unknown' }}</span>
        </div>
    </div>

    <span class="hidden shrink-0 whitespace-nowrap text-xs tabular-nums text-gray-400 sm:block">
        <x-ui.date :value="$announcement->created_at" />
    </span>

    <x-ui.button
        type="button"
        :variant="$buttonVariant"
        size="sm"
        class="shrink-0"
        x-on:click="openAnnouncement({{ $announcement->id }})"
    >
        View
    </x-ui.button>
</div>
