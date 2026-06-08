@if(session('success') || session('info'))
    @php
        $isComplete = session()->has('info');
        $flashMsg   = session('success') ?? session('info');
    @endphp

    <div x-data x-init="setTimeout(() => $dispatch('open-modal', 'queue-next-result'), 200)"></div>

    <x-ui.modal name="queue-next-result" maxWidth="sm">

        {{-- Header --}}
        <div class="flex items-center justify-between {{ $isComplete ? 'bg-emerald-700' : 'bg-primary-700' }} px-6 py-5">
            <div class="flex items-center gap-3">
                @if($isComplete)
                    <x-ui.icon name="check-badge" class="h-5 w-5 text-white/80 shrink-0" />
                @else
                    <x-ui.icon name="forward" class="h-5 w-5 text-white/80 shrink-0" />
                @endif
                <div>
                    <h2 class="text-base font-semibold text-white">
                        {{ $isComplete ? 'Queue Complete!' : 'Group Called!' }}
                    </h2>
                    <p class="text-xs text-white/70 mt-0.5">{{ $flashMsg }}</p>
                </div>
            </div>
            <button type="button"
                    @click="$dispatch('close-modal', 'queue-next-result')"
                    class="-mr-0.5 p-1.5 rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition-colors">
                <x-ui.icon name="close" size="sm" />
            </button>
        </div>

        {{-- CTA --}}
        <div class="px-6 py-5">
            <button type="button"
                    @click="$dispatch('close-modal', 'queue-next-result')"
                    class="w-full inline-flex justify-center items-center rounded-lg
                           {{ $isComplete ? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500' : 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500' }}
                           px-4 py-2.5 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors">
                {{ $isComplete ? 'Done' : 'OK' }}
            </button>
        </div>

    </x-ui.modal>
@endif
