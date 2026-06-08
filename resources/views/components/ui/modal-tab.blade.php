{{--
    A single tab button inside <x-ui.modal-actions>.
    Reads and sets `mode` from the parent x-data scope.

    Props:
      - key    : string — the mode value this tab activates (e.g. 'view', 'edit')
      - label  : string — visible button text
      - danger : bool   — use red active/hover colours instead of primary (default false)
--}}
@props([
    'key',
    'label',
    'danger' => false,
])

<button
    type="button"
    x-on:click="mode = '{{ $key }}'"
    @if($danger)
    :class="mode === '{{ $key }}' ? 'bg-red-50 text-red-700 font-semibold' : 'text-red-500 hover:bg-red-50 hover:text-red-600'"
    @else
    :class="mode === '{{ $key }}' ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800'"
    @endif
    class="w-full text-left px-3 py-2 text-xs font-medium rounded-md transition-colors leading-snug">
    {{ $label }}
</button>
