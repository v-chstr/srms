@props([
    'group',          // QueueGroup model instance (with members.user loaded)
    'isCurrent' => false,
    'isCompleted' => false,
])

@php
    $ring = $isCurrent
        ? 'ring-2 ring-primary-500 bg-primary-50'
        : ($isCompleted ? 'opacity-60 bg-gray-50' : 'bg-white');
@endphp

<div {{ $attributes->merge(['class' => "border border-gray-200 rounded-lg p-4 shadow-sm {$ring}"]) }}>
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                {{ $isCurrent ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                {{ $group->position }}
            </span>
            <span class="text-sm font-semibold text-gray-800">Group {{ $group->position }}</span>
        </div>
        @if($isCurrent)
            <span class="inline-flex items-center gap-1 rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700">
                <span class="h-1.5 w-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                Current
            </span>
        @elseif($isCompleted)
            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                Done
            </span>
        @endif
    </div>
    <ul class="space-y-1.5">
        @foreach($group->members as $member)
            <li class="flex items-center gap-2 text-sm text-gray-700">
                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-700">
                    {{ strtoupper(substr($member->user->first_name ?? '?', 0, 1)) }}{{ strtoupper(substr($member->user->last_name ?? '', 0, 1)) }}
                </div>
                {{ $member->user->first_name ?? 'Unknown' }} {{ $member->user->last_name ?? '' }}
            </li>
        @endforeach
    </ul>
</div>
