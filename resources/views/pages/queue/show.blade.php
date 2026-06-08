<x-layouts.app title="{{ $queue->course->name ?? $queue->title }}">

    <x-layouts.page-header :title="$queue->course->name ?? $queue->title" :subtitle="$queue->title">
        <x-slot:actions>
            <x-ui.button :href="route('queue.index')" variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4 mr-1" />
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-layouts.page-header>

    @php
        $totalGroups     = $queue->groups->count();
        $currentPosition = $queue->current_position;
    @endphp

    {{-- Status/control banner --}}
    <div class="mb-5">
        <x-queue.status-banner :queue="$queue" :totalGroups="$totalGroups" :currentPosition="$currentPosition" />
    </div>

    {{-- Flash result modal --}}
    <x-queue.result-modal />

    {{-- All groups — completed ones remain visible with checked/done styling --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($queue->groups as $group)
            @php
                $isCurrent   = $queue->isActive() && $group->position === $currentPosition;
                $isCompleted = $group->position < $currentPosition || $queue->isCompleted();
            @endphp
            <x-queue.group-card
                :group="$group"
                :isCurrent="$isCurrent"
                :isCompleted="$isCompleted"
            />
        @endforeach
    </div>

</x-layouts.app>
