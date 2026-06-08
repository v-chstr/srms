@props([
    'variant' => 'primary',
    'type'    => 'button',
    'size'    => 'md',
    'href'    => null,
])

@php
// Variant → color treatment
// primary   : solid forest-green fill — main CTAs (Upload, Submit, New Submission)
// secondary : green-tinted border + text on white — secondary actions (View, Review, Manage, Cancel)
// ghost     : text-only with green-tinted hover — low-emphasis utility (Clear filter, inline View)
// danger    : solid red fill — destructive actions (Delete, Reject)
$classes = match($variant) {
    'primary'   => 'bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-600',
    'secondary' => 'bg-white border border-primary-300 text-primary-700 hover:bg-primary-50 hover:border-primary-400 focus:ring-primary-500',
    'danger'    => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'ghost'     => 'text-primary-600 hover:text-primary-800 hover:bg-primary-50 focus:ring-primary-500',
    default     => 'bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-600',
};

// Size → padding + type size
// xs  : ultra-compact for tight spaces
// sm  : table-row actions and filter bar
// md  : standard page-level buttons
// lg  : prominent CTAs
$sizeClasses = match($size) {
    'xs'    => 'px-2.5 py-1 text-xs',
    'sm'    => 'px-3 py-1.5 text-xs',
    'lg'    => 'px-5 py-2.5 text-base',
    default => 'px-3.5 py-2 text-sm',
};

$base = "inline-flex items-center justify-center gap-1.5 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-1 transition-colors duration-150 $classes $sizeClasses";
$isLink = $href || $attributes->has('x-bind:href');
@endphp

@if($isLink)
    <a @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $base]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>
        {{ $slot }}
    </button>
@endif
