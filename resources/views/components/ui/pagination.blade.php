@props(['paginator'])

@if($paginator && $paginator->total() > 0)
<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3']) }}>
    <p class="text-xs text-gray-500 tabular-nums">
        Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </p>
    @if($paginator->hasPages())
    <div class="flex items-center gap-0.5">
        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-300 cursor-not-allowed" aria-disabled="true">
                <x-ui.icon name="chevron-left" size="sm" />
            </span>
        @else
            <a href="{{ $paginator->withQueryString()->previousPageUrl() }}"
               class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors"
               aria-label="Previous page">
                <x-ui.icon name="chevron-left" size="sm" />
            </a>
        @endif

        {{-- Page numbers --}}
        @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-primary-600 text-white text-xs font-semibold tabular-nums">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->withQueryString()->url($page) }}"
                   class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-600 hover:bg-gray-100 hover:text-gray-900 text-xs tabular-nums transition-colors">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->withQueryString()->nextPageUrl() }}"
               class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors"
               aria-label="Next page">
                <x-ui.icon name="chevron-right" size="sm" />
            </a>
        @else
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-300 cursor-not-allowed" aria-disabled="true">
                <x-ui.icon name="chevron-right" size="sm" />
            </span>
        @endif
    </div>
    @endif
</div>
@endif
