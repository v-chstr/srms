@props(['group'])

@php
    $queue         = $group->queue;
    $total         = $queue->groups->count();
    $current       = $queue->current_position;
    $pct           = $total > 0 ? round(($current / $total) * 100) : 0;
    $isDone        = $queue->isCompleted() || ($queue->isActive() && $current > $group->position);
    $isMyTurn      = !$isDone && $queue->isActive() && $group->position === $current;
    $groupsAhead   = (!$isDone && $queue->isActive() && !$isMyTurn) ? max($group->position - $current - 1, 0) : 0;
    $isNextUp      = !$isDone && $queue->isActive() && !$isMyTurn && $groupsAhead === 0;
    $memberNames   = $group->members
        ->map(fn ($m) => trim($m->user->first_name . ' ' . $m->user->last_name))
        ->filter()
        ->implode(', ');
    // Left panel shows the LIVE current group, not the student's own group position.
    $displayNumber = $queue->isPending() ? '0' : ($queue->isCompleted() ? $total : $current);
    $displayLabel  = $queue->isPending() ? 'waiting' : ($queue->isCompleted() ? 'complete' : 'of ' . $total);
@endphp

{{--
    Layout: split-ticket — large group number on a primary-700 panel (left),
    queue info on the right. No top header strip (deliberate: announcement uses that).
    Indicator: square animate-pulse dot (not a circle animate-ping — distinct from announcement).
--}}
<div class="bg-white border {{ $isDone ? 'border-green-200' : 'border-accent-200' }} rounded-md shadow-sm overflow-hidden">

    {{-- Ticket body: left number panel + right info --}}
    <div class="flex">

        {{-- Left: LIVE current group number — updates as the queue advances --}}
        <div class="{{ $isDone ? 'bg-green-700' : 'bg-primary-700' }} w-20 shrink-0 flex flex-col items-center justify-center gap-0.5 px-3 py-4">
            <span class="text-3xl font-bold text-white tabular-nums leading-none">{{ $displayNumber }}</span>
            <span class="text-xs {{ $isDone ? 'text-green-300' : 'text-primary-300' }}">{{ $displayLabel }}</span>
        </div>

        {{-- Right: queue info --}}
        <div class="flex-1 min-w-0 px-3 py-3 flex flex-col gap-1">

            {{-- Title row: indicator dot + title + status badge --}}
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-1.5 min-w-0">
                    {{-- Square dot — green when done, gold pulsing when active --}}
                    <span class="inline-block h-2 w-2 shrink-0 {{ $isDone ? 'bg-green-500' : 'bg-accent-400 ' . ($queue->isActive() ? 'animate-pulse' : '') }}"></span>
                    <span class="text-sm font-semibold text-gray-900 truncate">{{ $queue->title }}</span>
                </div>
                <x-ui.badge :status="$queue->status" class="shrink-0 mt-0.5" />
            </div>

            {{-- Course label + student's own group slot --}}
            <div class="flex items-center gap-2">
                @if($queue->course)
                    <p class="text-xs text-gray-500">{{ $queue->course->name }}</p>
                @endif
                <p class="text-xs text-gray-400">Group <span class="font-semibold text-gray-600">#{{ $group->position }}</span></p>
            </div>

            {{-- Status message --}}
            @if($isDone)
                <p class="text-sm font-semibold text-green-700">Congratulations, you did great!</p>
                <p class="text-xs text-gray-400">Your group has completed its defense.</p>
            @elseif($isMyTurn)
                <p class="text-sm font-semibold text-green-700">Your group is presenting now!</p>
            @elseif($isNextUp)
                <p class="text-sm text-gray-700">You are <span class="font-semibold text-gray-900">next up.</span> Get ready.</p>
            @elseif($queue->isActive())
                <p class="text-sm text-gray-600">
                    <span class="font-semibold text-gray-800">{{ $groupsAhead }} {{ Str::plural('group', $groupsAhead) }}</span>
                    ahead of you.
                </p>
            @elseif($queue->isPending())
                <p class="text-sm text-gray-500">Queue has not started yet.</p>
            @endif

            {{-- Member names --}}
            @if($memberNames)
                <p class="text-xs text-gray-400">{{ $memberNames }}</p>
            @endif

        </div>

    </div>

    {{-- Progress strip — label row + animated fill bar --}}
    <div class="px-3 py-2 border-t border-gray-100 flex items-center gap-3">
        <div class="flex-1 bg-gray-100 rounded-full h-2.5 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-700
                        {{ ($isDone || $queue->isCompleted()) ? 'bg-green-500' : 'bg-primary-500' }}
                        queue-progress-live"
                 style="width: {{ $queue->isCompleted() ? '100' : max($pct, $queue->isActive() ? 6 : 0) }}%">
            </div>
        </div>
        <span class="text-xs tabular-nums shrink-0
                     {{ $queue->isCompleted() ? 'text-green-600 font-semibold' : 'text-gray-400' }}">
            {{ $queue->isCompleted() ? '100%' : $pct . '%' }}
        </span>
    </div>

</div>
