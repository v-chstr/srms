@props([
    'title' => null,
    'message' => 'Nothing to show yet.',
    'ctaLabel' => null,
    'cta' => null,
    'compact' => false,
])

{{--
    Compact empty-state placeholder.
    Replaces full-card whitespace when a section has no data.
    Never spans more than a few lines tall.

    Props:
      - title    : optional bold one-liner
      - message  : description sentence
      - cta      : optional href
      - ctaLabel : button label (required if cta given)
      - compact  : tighter vertical padding for sidebar-narrow contexts
--}}

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 rounded-md border border-dashed border-gray-300 bg-gray-50/60 ' . ($compact ? 'px-3 py-2.5' : 'px-4 py-3')]) }}>
    <div class="min-w-0">
        @if($title)
            <p class="text-sm font-semibold text-gray-700 leading-tight">{{ $title }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ $message }}</p>
        @else
            <p class="text-sm text-gray-500">{{ $message }}</p>
        @endif
    </div>
    @if($cta && $ctaLabel)
        <a href="{{ $cta }}" data-nav-link
           class="shrink-0 inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-primary-700 border border-gray-200 hover:bg-primary-50 hover:border-primary-200 transition-colors">
            {{ $ctaLabel }}
        </a>
    @endif
</div>
