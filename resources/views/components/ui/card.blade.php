@props([
    'title' => null,
    'compact' => false,
    'viewAllRoute' => null,
    'maxHeight' => null,
    'empty' => false,
    'emptyText' => 'No data available.',
    'accent' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-md shadow-sm' . ($compact ? ' overflow-hidden h-full' : '') . ($accent ? ' ring-1 ring-primary-100' : '')]) }}>
    @if($title)
        @if($compact)
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>
                @if($viewAllRoute)
                    <a href="{{ $viewAllRoute }}" data-nav-link
                       class="shrink-0 text-xs font-medium text-primary-700 hover:text-primary-900">View all</a>
                @endif
            </div>
        @else
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
            </div>
        @endif
    @endif

    @if($empty)
        <div class="px-4 py-6 text-center">
            <p class="text-sm text-gray-400">{{ $emptyText }}</p>
        </div>
    @else
        <div class="{{ $compact ? '' : 'p-4' }} {{ $maxHeight ? "overflow-y-auto {$maxHeight}" : '' }}">
            {{ $slot }}
        </div>
    @endif

    @isset($footer)
        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50">
            {{ $footer }}
        </div>
    @endisset
</div>
