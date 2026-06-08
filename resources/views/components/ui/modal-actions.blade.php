{{--
    Inner shell for all single-instance Alpine-driven action modals.
    Provides: x-data="{ mode: 'view' }", the dynamic header (Alpine bindings),
    the tab nav sidebar, and the panels area.

    Use inside <template x-if="selected"> (or x-if="paper", etc.)

    Named slots:
      - title  : <h2> + optional <p> with x-text or plain text
      - tabs   : <x-ui.modal-tab> elements
      - (default slot) : panel divs

    Props:
      - modalName : string — used for the close button dispatch
      - initialMode : string — initial active tab when the modal is reopened after validation
--}}
@props([
    'modalName',
    'initialMode' => 'view',
])

<div x-data="{ mode: @js($initialMode) }">
    {{-- Header --}}
    <div class="flex items-start justify-between bg-primary-900 px-5 py-4 shrink-0">
        <div class="min-w-0 flex-1 pr-4">
            {{ $title }}
        </div>
        <button type="button"
                x-on:click="$dispatch('close-modal', '{{ $modalName }}')"
                class="shrink-0 -mr-0.5 p-1.5 rounded-md text-white/60 hover:text-white hover:bg-primary-800 transition-colors">
            <x-ui.icon name="close" size="sm" />
        </button>
    </div>

    <div class="flex">
        {{-- Tab nav sidebar --}}
        <nav class="w-32 shrink-0 border-r border-gray-100 py-2 flex flex-col gap-0.5 px-2">
            {{ $tabs }}
        </nav>

        {{-- Panel area --}}
        <div class="flex-1 min-w-0">
            {{ $slot }}
        </div>
    </div>
</div>
