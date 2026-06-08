@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'mb-5']) }}>
    <div class="bg-white border border-gray-200 rounded-md shadow-sm px-5 py-4 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold text-gray-900 leading-tight">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-0.5 text-sm text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex items-center gap-2 shrink-0">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
