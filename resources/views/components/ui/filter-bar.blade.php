@props([
    'action',
    'clearHref' => null,
    'hasFilters' => false,
])

<div class="mb-4">
    <div class="bg-white border border-gray-200 rounded-md px-3 py-2.5">
        <form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3">
            {{ $slot }}
            <x-ui.button type="submit" variant="secondary" size="sm">Filter</x-ui.button>
            @if($hasFilters && $clearHref)
                <x-ui.button :href="$clearHref" variant="ghost" size="sm">Clear</x-ui.button>
            @endif
        </form>
        {{ $footer ?? '' }}
    </div>
</div>
