@props([
    'modalName',
    'submitLabel' => 'Save',
    'submitVariant' => 'primary',
    'submitting' => null,
    'loadingLabel' => null,
])

@php
    $resolvedLoadingLabel = $loadingLabel ?? 'Submitting...';
@endphp

<div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50/50 px-5 py-3">
    @isset($note)
        <div class="min-w-0 text-xs text-gray-500">
            {{ $note }}
        </div>
    @else
        <div></div>
    @endisset

    <div class="flex shrink-0 justify-end gap-2">
        <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', '{{ $modalName }}')">Cancel</x-ui.button>
        @if($submitting)
            <x-ui.button
                type="submit"
                :variant="$submitVariant"
                x-bind:disabled="{{ $submitting }}"
                x-bind:class="{{ $submitting }} ? 'opacity-50 cursor-not-allowed' : ''"
            >
                <x-ui.icon name="spinner" size="sm" class="animate-spin shrink-0" x-show="{{ $submitting }}" x-cloak />
                <span x-text="{{ $submitting }} ? '{{ $resolvedLoadingLabel }}' : '{{ $submitLabel }}'"></span>
            </x-ui.button>
        @else
            <x-ui.button type="submit" :variant="$submitVariant">{{ $submitLabel }}</x-ui.button>
        @endif
    </div>
</div>
