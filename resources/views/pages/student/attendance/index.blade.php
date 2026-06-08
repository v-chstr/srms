<x-layouts.app title="My Attendance">

    <x-layouts.page-header title="My Attendance">
        <x-slot:actions>
            <x-ui.button :href="route('dashboard')" variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="mr-1 h-4 w-4" />
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-layouts.page-header>

    @if(!$group || !$group->section)
        <x-ui.empty-state message="You are not assigned to any attendance section or group yet." />
    @else
        @php
            $section = $group->section;
            $summaryItems = [
                ['label' => 'Present', 'value' => $statusTotals[\App\Models\AttendanceRecord::STATUS_PRESENT], 'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
                ['label' => 'Absent', 'value' => $statusTotals[\App\Models\AttendanceRecord::STATUS_ABSENT], 'classes' => 'border-rose-200 bg-rose-50 text-rose-700'],
                ['label' => 'Not Recorded', 'value' => $statusTotals['not_recorded'], 'classes' => 'border-gray-200 bg-gray-50 text-gray-600'],
            ];
        @endphp

        <div class="space-y-4">
            {{-- Section Metadata Bar --}}
            <div class="flex flex-wrap items-center gap-y-2 rounded-md border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-600 shadow-sm">
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

                <div class="flex items-center gap-2 px-4 md:border-r md:border-gray-200">
                    <span class="text-gray-500">Group:</span>
                    <span class="font-semibold text-gray-800">Group {{ $group->position }}</span>
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

            {{-- Summary Cards --}}
            <x-ui.card title="Attendance Summary">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($summaryItems as $item)
                        <div class="rounded-md border px-4 py-3 {{ $item['classes'] }}">
                            <p class="text-xs font-medium">{{ $item['label'] }}</p>
                            <p class="mt-1 text-2xl font-semibold">{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            {{-- Reusable Timeline Component (Read-Only) --}}
            <x-attendance.timeline-table :group="$group" :can-edit="false" />
        </div>
    @endif

</x-layouts.app>
