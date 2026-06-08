@props(['queue', 'totalGroups', 'currentPosition'])

<div class="bg-white border border-gray-200 rounded-md shadow-sm">

    {{-- Main content row --}}
    <div class="px-4 py-3 flex flex-wrap items-center justify-between gap-3">

        {{-- Status info --}}
        <div class="flex items-center gap-3 min-w-0">
            <x-ui.badge :status="$queue->status" />
            @if($queue->isPending())
                <span class="text-sm text-gray-500">{{ $totalGroups }} {{ Str::plural('group', $totalGroups) }} waiting to start</span>
            @elseif($queue->isActive())
                <span class="text-sm text-gray-600">Group <span class="font-semibold text-gray-800">{{ $currentPosition }}</span> of {{ $totalGroups }}</span>
            @else
                <span class="text-sm font-medium text-green-700">All {{ $totalGroups }} groups complete</span>
            @endif
        </div>

        {{-- Actions: Start/Next + Delete on the same line --}}
        <div class="flex items-center gap-2 shrink-0">
            @if(!$queue->isCompleted())
                <form method="POST" action="{{ route('queue.next', $queue) }}"
                      x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    @if($queue->isPending())
                        <x-ui.button type="submit" size="sm" x-bind:disabled="submitting">
                            <x-ui.icon name="play" class="h-3.5 w-3.5 mr-1" />
                            <span x-text="submitting ? 'Starting…' : 'Start Queue'">Start Queue</span>
                        </x-ui.button>
                    @else
                        <x-ui.button type="submit" size="sm" x-bind:disabled="submitting">
                            <x-ui.icon name="forward" class="h-3.5 w-3.5 mr-1" />
                            <span x-text="submitting ? 'Advancing…' : 'Next Group'">Next Group</span>
                        </x-ui.button>
                    @endif
                </form>
            @else
                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-green-700">
                    <x-ui.icon name="check-circle" class="h-4 w-4" />
                    Queue Complete
                </span>
            @endif

            @if($queue->isPending())
                <form method="POST" action="{{ route('queue.destroy', $queue) }}"
                      x-data @submit.prevent="if(confirm('Delete this queue?')) $el.submit()">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="secondary" size="sm" class="text-red-600 border-red-200 hover:bg-red-50">
                        <x-ui.icon name="trash" class="h-3.5 w-3.5 mr-1" />
                        Delete
                    </x-ui.button>
                </form>
            @endif
        </div>

    </div>


</div>
