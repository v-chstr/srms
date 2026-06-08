@props(['type' => 'success'])

@php
$styles = match($type) {
    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
    'error'   => 'bg-red-50 text-red-800 border-red-200',
    'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
    'info'    => 'bg-blue-50 text-blue-800 border-blue-200',
    default   => 'bg-gray-50 text-gray-800 border-gray-200',
};
@endphp

<div {{ $attributes->merge(['class' => "border rounded-md px-4 py-2.5 text-sm font-sans $styles"]) }}
     x-data="{ show: true }"
     x-show="show"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-between gap-3">
        <p class="min-w-0">{{ $slot }}</p>
        <button type="button" @click="show = false" class="shrink-0 opacity-50 hover:opacity-100" aria-label="Dismiss">
            <x-ui.icon name="x-mark" style="solid" class="h-4 w-4" />
        </button>
    </div>
</div>
