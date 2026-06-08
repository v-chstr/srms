@php
    $activeGroupId = session('active_group_id');

    if (!$activeGroupId) {
        if (old('group_id')) {
            $activeGroupId = (int) old('group_id');
        } elseif (old('form_context')) {
            $oldFormContext = (string) old('form_context', '');
            if (str_starts_with($oldFormContext, 'edit-')) {
                $oldRowId = (int) str_replace('edit-', '', $oldFormContext);
                foreach ($section->groups as $g) {
                    if ($g->rows->contains('id', $oldRowId)) {
                        $activeGroupId = $g->id;
                        break;
                    }
                }
            }
        }
    }

    $activeGroupId = $activeGroupId ?: ($section->groups->first()?->id ?? 0);
@endphp

<x-layouts.app :title="$section->title">

    <x-layouts.page-header :title="$section->title">
        <x-slot:actions>
            <x-ui.button :href="route('adviser.attendance.index')" variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="mr-1 h-4 w-4" />
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-layouts.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-y-2 rounded-md border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-600 shadow-sm">
        <div class="flex items-center gap-2 pr-4 md:border-r md:border-gray-200">
            <x-ui.icon name="academic-cap" class="h-4 w-4 shrink-0 text-gray-400" />
            <span class="font-medium text-gray-900">{{ $section->course?->displayCode() }}</span>
            <span class="text-gray-500">{{ $section->course?->name }}</span>
        </div>

        <div class="flex items-center gap-2 px-4 md:border-r md:border-gray-200">
            <x-ui.icon name="user" class="h-4 w-4 shrink-0 text-gray-400" />
            <span>
                Created by
                <span class="font-medium text-gray-900">
                    {{ $section->creator ? $section->creator->first_name . ' ' . $section->creator->last_name : 'Unknown' }}
                </span>
            </span>
        </div>

        @if($section->sharedAdvisers->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2 pl-4">
                <x-ui.icon name="user-group" class="h-4 w-4 shrink-0 text-gray-400" />
                <span class="text-gray-500">Shared with:</span>
                @foreach($section->sharedAdvisers as $adviser)
                    <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 border border-gray-200">
                        {{ $adviser->first_name }} {{ $adviser->last_name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    @if(isset($errors) && $errors->any())
        <div class="mb-4"><x-ui.alert type="error">{{ $errors->first() }}</x-ui.alert></div>
    @endif

    @if($section->groups->isEmpty())
        <x-ui.empty-state message="No groups found in this section." />
    @else
        <div x-data="{ activeGroup: {{ $activeGroupId }} }">
            <div class="mb-6 flex gap-1 overflow-x-auto border-b border-gray-200">
                @foreach($section->groups as $group)
                    <button type="button"
                        @click="activeGroup = {{ $group->id }}"
                        :class="activeGroup === {{ $group->id }}
                            ? 'border-primary-600 bg-white font-semibold text-primary-700'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        class="flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 -mb-px px-4 py-2.5 text-sm transition-colors">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">{{ $loop->iteration }}</span>
                        Group {{ $loop->iteration }}
                        <span class="text-xs text-gray-400">({{ $group->members->count() }})</span>
                    </button>
                @endforeach
            </div>

            @foreach($section->groups as $group)
                @php
                    $sortedRows = $group->rows->sortBy('sort_timestamp');
                @endphp

                <div x-show="activeGroup === {{ $group->id }}" x-cloak>
                    <div class="mb-6 rounded-md border border-gray-200 bg-white p-4 shadow-sm" x-data="{ showAddMember: false }">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Group Members</p>
                            </div>
                            <x-ui.button type="button" variant="secondary" size="sm" @click="showAddMember = !showAddMember">
                                <x-ui.icon name="plus" size="xs" />
                                Add Student
                            </x-ui.button>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @forelse($group->members as $member)
                                <span class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-700">
                                        {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-800">{{ $member->first_name }} {{ $member->last_name }}</span>
                                </span>
                            @empty
                                <span class="text-sm text-gray-400">No members in this group.</span>
                            @endforelse

                            <div x-show="showAddMember" x-cloak class="inline-flex items-center rounded-md border border-primary-200 bg-primary-50/50 p-1">
                                <form method="POST" action="{{ route('adviser.attendance.groups.members.store', $group) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    <input type="hidden" name="group_id" value="{{ $group->id }}">
                                    @php
                                        $assignedStudentIds = $section->groups
                                            ->reject(fn ($sectionGroup) => $sectionGroup->id === $group->id)
                                            ->flatMap(fn ($sectionGroup) => $sectionGroup->members->pluck('id'))
                                            ->unique();
                                        $groupIsFull = $group->members->count() >= 3;
                                        $eligibleStudents = $courseStudents
                                            ->reject(fn ($student) => $group->members->contains('id', $student->id))
                                            ->reject(fn ($student) => $assignedStudentIds->contains($student->id));
                                    @endphp

                                    @if($groupIsFull)
                                        <span class="px-2 py-1 text-xs text-gray-500">Group is full</span>
                                    @elseif($eligibleStudents->isNotEmpty())
                                        <select name="student_id" required class="rounded border-gray-300 bg-white py-1 pl-2 pr-8 text-xs focus:border-primary-500 focus:ring-primary-500">
                                            <option value="" disabled selected>Select student</option>
                                            @foreach($eligibleStudents as $student)
                                                <option value="{{ $student->id }}">{{ $student->last_name }}, {{ $student->first_name }}</option>
                                            @endforeach
                                        </select>
                                        <x-ui.button type="submit" size="xs">Add</x-ui.button>
                                    @else
                                        <span class="px-2 py-1 text-xs text-gray-500">No unassigned active students in course</span>
                                    @endif

                                    <button type="button" @click="showAddMember = false" class="rounded-md p-1 text-gray-400 hover:text-gray-600" title="Cancel">
                                        <x-ui.icon name="x-mark" size="xs" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <x-attendance.timeline-table :group="$group" :can-edit="true" />
                </div>
            @endforeach
        </div>
    @endif

    <script>
        @foreach($section->groups as $group)
            @php
                $editingRowId = null;
                $oldFormContext = (string) old('form_context', '');
                if (str_starts_with($oldFormContext, 'edit-')) {
                    $editingRowId = (int) str_replace('edit-', '', $oldFormContext);
                }
            @endphp

            function attendanceTable_{{ $group->id }}() {
                return {
                    editingRow: @json($editingRowId),
                };
            }

            @foreach($group->rows as $row)
                @php
                    $editContext = 'edit-' . $row->id;
                    $editingDate = old('form_context') === $editContext ? old('date', $row->date) : $row->date;
                    $editingActivities = old('form_context') === $editContext
                        ? old('activities', $row->clean_activities ?? '')
                        : ($row->clean_activities ?? '');
                    $editingRemarks = old('form_context') === $editContext
                        ? old('remarks', $row->remarks ?? '')
                        : ($row->remarks ?? '');
                @endphp

                function attendanceRow_{{ $row->id }}() {
                    return {
                        date: @json($editingDate),
                        customDate: @json(! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $editingDate)),
                        activities: @json($editingActivities),
                        remarks: @json($editingRemarks),
                    };
                }
            @endforeach
        @endforeach
    </script>

</x-layouts.app>
