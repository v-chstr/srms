@props(['queue'])

@php
    $total = $queue->groups->count();
    $pct   = $total > 0 ? round(($queue->current_position / $total) * 100) : 0;
@endphp

<div class="bg-white border border-gray-200 rounded-md shadow-sm overflow-hidden">

    {{-- Card body --}}
    <div class="px-4 py-3 flex flex-col gap-2">

        {{-- Title row: title left, badge top-right --}}
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <span class="font-semibold text-sm text-gray-900 leading-snug block">{{ $queue->title }}</span>
                <span class="text-xs text-gray-500 mt-0.5 block">{{ $queue->course->name ?? 'N/A' }}</span>
            </div>
            <x-ui.badge :status="$queue->status" class="shrink-0 mt-0.5" />
        </div>

        {{-- Stats row --}}
        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1">
                <x-ui.icon name="user-group" class="h-3.5 w-3.5 shrink-0" />
                {{ $total }} {{ Str::plural('group', $total) }}
            </span>
            <span class="flex items-center gap-1">
                <x-ui.icon name="calendar" class="h-3.5 w-3.5 shrink-0" />
                {{ $queue->created_at->srmsDate() }}
            </span>
        </div>

    </div>

    {{-- Manage footer --}}
    <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50/50">
        <x-ui.button :href="route('queue.show', $queue)" size="sm">
            <x-ui.icon name="play" class="h-3.5 w-3.5 mr-1" />
            Manage
        </x-ui.button>
    </div>

    {{-- Delete footer — full-width red zone, only for pending or completed --}}
    @if($queue->isPending() || $queue->isCompleted())
        <form method="POST" action="{{ route('queue.destroy', $queue) }}"
              x-data
              @submit.prevent="if(confirm('Delete this queue?')) $el.submit()">
            @csrf @method('DELETE')
            <button type="submit"
                    class="w-full px-4 py-2.5 bg-red-50 border-t border-red-100 text-xs font-medium text-red-600 hover:bg-red-100 flex items-center justify-center gap-1.5">
                <x-ui.icon name="trash" class="h-3.5 w-3.5 shrink-0" />
                Delete Queue
            </button>
        </form>
    @endif

    {{-- Progress strip — always visible at bottom, shimmer sweep when active --}}
    <div class="h-2 bg-gray-100 overflow-hidden">
        <div class="h-full transition-all duration-500 {{ $queue->isCompleted() ? 'bg-green-500' : 'bg-primary-500' }} {{ $queue->isActive() ? 'queue-progress-live' : '' }}"
             style="width: {{ $pct }}%"></div>
    </div>

</div>
