<x-layouts.app title="Class Attendance">

    <x-layouts.page-header title="Class Attendance" subtitle="Manage and track student attendance per section">
        <x-slot:actions>
            <x-ui.button :href="route('adviser.attendance.create')">
                <x-ui.icon name="plus" class="h-4 w-4 mr-1.5" />
                New Section
            </x-ui.button>
        </x-slot:actions>
    </x-layouts.page-header>

    {{-- Filter bar --}}
    <x-ui.filter-bar
        :action="route('adviser.attendance.index')"
        :clearHref="route('adviser.attendance.index')"
        :hasFilters="request('course_id')"
    >
        <select name="course_id" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">{{ config('ui.placeholders.all_courses') }}</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->name }}</option>
            @endforeach
        </select>
    </x-ui.filter-bar>

    @if($sections->isEmpty())
        <x-ui.empty-state message="No attendance sections found. Create one to get started." />
    @else
        <x-table.wrapper>
            <x-slot:head>
                <x-table.heading>Section</x-table.heading>
                <x-table.heading class="hidden sm:table-cell w-28">Course</x-table.heading>
                <x-table.heading class="hidden md:table-cell w-20">Groups</x-table.heading>
                <x-table.heading class="hidden lg:table-cell w-64">Shared With</x-table.heading>
                <x-table.heading class="hidden sm:table-cell w-28">Created</x-table.heading>
                <x-table.heading class="w-40 text-right"></x-table.heading>
            </x-slot:head>

            @foreach($sections as $section)
                @php $isOwner = $section->created_by === auth()->id(); @endphp
                <tr>
                    <x-table.cell wrap>
                        <a href="{{ route('adviser.attendance.show', $section) }}"
                           class="block text-sm font-semibold leading-snug text-gray-900 hover:text-primary-700">
                            {{ $section->title }}
                        </a>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ $isOwner ? 'Created by you' : 'Shared by ' . ($section->creator ? $section->creator->first_name . ' ' . $section->creator->last_name : 'Unknown') }}
                        </p>
                    </x-table.cell>
                    <x-table.cell class="hidden sm:table-cell" nowrap>
                        <span class="text-sm font-medium text-gray-700">{{ $section->course?->displayCode() ?? 'N/A' }}</span>
                    </x-table.cell>
                    <x-table.cell class="hidden md:table-cell" nowrap>
                        <span class="text-sm text-gray-700">{{ $section->groups->count() }}</span>
                    </x-table.cell>
                    <x-table.cell class="hidden lg:table-cell">
                        @if($section->sharedAdvisers->isEmpty())
                            <span class="text-xs text-gray-400">Not shared</span>
                        @else
                            <div class="flex flex-wrap gap-1">
                                @foreach($section->sharedAdvisers as $adviser)
                                    <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">
                                        {{ $adviser->first_name }} {{ $adviser->last_name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </x-table.cell>
                    <x-table.cell class="hidden sm:table-cell text-xs text-gray-500 tabular-nums" nowrap>
                        <x-ui.date :value="$section->created_at" short />
                    </x-table.cell>
                    <x-table.cell class="text-right" nowrap>
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button :href="route('adviser.attendance.show', $section)" variant="secondary" size="sm">View</x-ui.button>
                            <form method="POST" action="{{ route('adviser.attendance.destroy', $section) }}"
                                  x-data
                                  @submit.prevent="if (confirm('Delete this attendance section and all its data? This cannot be undone.')) $el.submit()">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </x-table.cell>
                </tr>
            @endforeach
        </x-table.wrapper>
    @endif

</x-layouts.app>
