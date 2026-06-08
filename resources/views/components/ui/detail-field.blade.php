@props(['label'])

<div class="flex items-start gap-x-4 py-1.5" {{ $attributes }}>
    <span class="w-24 shrink-0 text-xs font-semibold text-gray-500 leading-5">{{ $label }}</span>
    <div class="flex-1 min-w-0 text-sm text-gray-700">{{ $slot }}</div>
</div>
