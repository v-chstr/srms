@props([
    'group',
    'canEdit' => false,
])

@php
    $sortedRows = $group->rows->sortBy('sort_timestamp');
@endphp

<div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm"
     @if($canEdit) x-data="attendanceTable_{{ $group->id }}()" @endif>
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3">
        <h3 class="text-base font-semibold text-gray-900">Project Development Timeline</h3>
    </div>

    <x-table.wrapper class="rounded-none border-0" tableClass="w-full table-fixed divide-y divide-gray-100">
        <x-slot:head>
            <x-table.heading class="w-[14%]">Date</x-table.heading>
            <x-table.heading class="w-[32%]">Activities</x-table.heading>
            <x-table.heading class="w-[18%]">Attendance</x-table.heading>
            <x-table.heading class="w-[15%]">Signature</x-table.heading>
            <x-table.heading class="{{ $canEdit ? 'w-[14%]' : 'w-[21%]' }}">Remarks</x-table.heading>
            @if($canEdit)
                <x-table.heading class="w-[7%] text-right"></x-table.heading>
            @endif
        </x-slot:head>

        @forelse($sortedRows as $row)
            @php
                $editContext = 'edit-' . $row->id;
                $attendanceByStudent = $row->studentAttendances->keyBy('user_id');
            @endphp

            <tr>
                <x-table.cell class="align-top font-medium text-gray-900">
                    {{ $row->formatted_date }}
                </x-table.cell>
                <x-table.cell class="align-top text-gray-800 leading-relaxed">
                    <span class="block whitespace-pre-line">{{ $row->clean_activities ?: 'N/A' }}</span>
                </x-table.cell>
                <x-table.cell class="align-top">
                    <div class="space-y-1.5">
                        @foreach($group->members as $member)
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm text-gray-600" title="{{ $member->first_name }} {{ $member->last_name }}">{{ $member->first_name }}</span>
                                <x-attendance.status-badge :status="$attendanceByStudent->get($member->id)?->status" :compact="true" />
                            </div>
                        @endforeach
                    </div>
                </x-table.cell>
                <x-table.cell class="align-top text-gray-600">
                    @if($row->recorder)
                        <span class="block text-xs text-gray-500">Recorded by</span>
                        <span class="block text-sm font-medium text-gray-800">
                            {{ $row->recorder->first_name }} {{ $row->recorder->last_name }}
                        </span>
                    @else
                        N/A
                    @endif
                </x-table.cell>
                <x-table.cell class="align-top">
                    {{ $row->remarks ?: 'N/A' }}
                </x-table.cell>
                @if($canEdit)
                    <x-table.cell class="align-top text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <button type="button" @click="editingRow = {{ $row->id }}" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-primary-50 hover:text-primary-600" title="Edit">
                                <x-ui.icon name="pencil" size="xs" />
                            </button>
                        </div>
                    </x-table.cell>
                @endif
            </tr>
            @if($canEdit)
                <tr x-data="attendanceRow_{{ $row->id }}()" x-show="editingRow === {{ $row->id }}" x-cloak class="bg-primary-50/30">
                    <td colspan="6" class="px-4 py-4">
                        <form method="POST" action="{{ route('adviser.attendance.rows.update', $row) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="form_context" value="{{ $editContext }}">
                            @include('pages.adviser.attendance.partials.row-form', [
                                'mode' => 'edit',
                                'row' => $row,
                                'submitLabel' => 'Save',
                                'cancelAction' => 'editingRow = null',
                            ])
                        </form>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="{{ $canEdit ? 6 : 5 }}" class="px-4 py-8 text-center text-sm text-gray-500">
                    No timeline rows found for this group.
                </td>
            </tr>
        @endforelse
    </x-table.wrapper>
</div>
