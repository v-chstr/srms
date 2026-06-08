@props([
    'href',
    'title',
    'description',
    'icon' => null,
    'variant' => 'default',
])

@php
    $classes = match ($variant) {
        'primary' => 'border-primary-200 hover:border-primary-300 bg-primary-50/70',
        default => 'border-gray-200 bg-white hover:border-primary-300 hover:bg-primary-50/40',
    };
@endphp

<a href="{{ $href }}" data-nav-link {{ $attributes->merge(['class' => "flex flex-col gap-1.5 rounded-md border p-4 shadow-sm transition-colors {$classes}"]) }}>
    <span class="flex items-start justify-between gap-2">
        <span class="text-sm font-semibold {{ $variant === 'primary' ? 'text-primary-900' : 'text-gray-800' }}">{{ $title }}</span>
        @if($icon)
            <x-ui.icon :name="$icon" class="h-4 w-4 shrink-0 {{ $variant === 'primary' ? 'text-primary-700' : 'text-primary-600' }}" />
        @endif
    </span>
    <span class="text-xs leading-relaxed {{ $variant === 'primary' ? 'text-primary-900' : 'text-gray-500' }}">{{ $description }}</span>
</a>
