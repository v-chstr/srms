<x-layouts.app title="Queue Management">

    <x-layouts.page-header title="Queue Management" subtitle="Manage student presentation queues">
        <x-slot:actions>
            <x-ui.button :href="route('queue.create')">
                <x-ui.icon name="plus" class="h-4 w-4 mr-1.5" />
                New Queue
            </x-ui.button>
        </x-slot:actions>
    </x-layouts.page-header>

    {{-- Filters --}}
    <x-ui.filter-bar
        :action="route('queue.index')"
        :clearHref="route('queue.index')"
        :hasFilters="request()->hasAny(['course_id', 'status'])"
    >
        <select name="course_id" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">{{ config('ui.placeholders.all_courses') }}</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->name }}</option>
            @endforeach
        </select>
        <select name="status" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">All statuses</option>
            @foreach(config('ui.queue_statuses') as $key => $cfg)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $cfg['label'] }}</option>
            @endforeach
        </select>
    </x-ui.filter-bar>

    {{-- Queue cards — 2-column bento grid --}}
    @if($queues->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($queues as $queue)
                <x-queue.queue-card :queue="$queue" />
            @endforeach
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm px-5 py-12 text-center">
            <x-ui.icon name="queue-list" class="h-8 w-8 mx-auto mb-2 text-gray-300" />
            <p class="text-sm text-gray-400">No queues found. <a href="{{ route('queue.create') }}" class="text-primary-600 hover:underline">Create one</a>.</p>
        </div>
    @endif



</x-layouts.app>
