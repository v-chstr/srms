<div class="grid grid-cols-12 gap-4">
    <!-- Left Column: Date, Remarks, Signature -->
    <div class="col-span-12 md:col-span-4 space-y-4">
        <div>
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <label class="block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                <button type="button" @click="customDate = !customDate" class="text-xs font-medium text-primary-700 hover:text-primary-800">
                    <span x-show="!customDate">Custom</span>
                    <span x-show="customDate" x-cloak>Calendar</span>
                </button>
            </div>
            <template x-if="!customDate">
                <input type="date" name="date" x-model="date" required
                       class="block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500" />
            </template>
            <template x-if="customDate">
                <input type="text" name="date" x-model="date" required placeholder="Feb 18-25, 2026"
                       class="block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500" />
            </template>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Remarks</label>
            <input type="text" name="remarks" maxlength="191" placeholder="Add remarks" x-model="remarks"
                   class="block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Signature</label>
            <div class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600">
                <span class="block text-xs text-gray-500">Recorded by</span>
                <span class="block font-medium text-gray-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            </div>
        </div>
    </div>

    <!-- Right Column: Activities -->
    <div class="col-span-12 md:col-span-8 flex flex-col">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Activities</label>
        <textarea name="activities" placeholder="Describe the activities" x-model="activities"
                  class="block w-full flex-1 rounded-md border-gray-300 text-sm leading-relaxed focus:border-primary-500 focus:ring-primary-500 resize-y min-h-[148px] md:min-h-[190px]"></textarea>
    </div>

    <!-- Bottom: Attendance by Student -->
    <div class="col-span-12 mt-2">
        <div class="rounded-md border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-4 py-3">
                <p class="text-sm font-semibold text-gray-900">Attendance by Student</p>
            </div>

            @php
                $editingRow = $row ?? null;
            @endphp
            <div class="grid gap-3 px-4 py-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($group->members as $member)
                    @php
                        $statusInputId = 'student-status-' . ($editingRow?->id ?? 'new') . '-' . $member->id;
                        $selectedStatus = old(
                            "student_statuses.{$member->id}",
                            $editingRow?->studentAttendances->firstWhere('user_id', $member->id)?->status
                        );
                    @endphp
                    <div class="flex items-center justify-between gap-4 rounded-md border border-gray-100 bg-gray-50/50 p-2.5">
                        <span class="text-sm font-medium text-gray-800 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-700">
                                {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                            </span>
                            {{ $member->first_name }} {{ $member->last_name }}
                        </span>
                        <select id="{{ $statusInputId }}" name="student_statuses[{{ $member->id }}]"
                                class="rounded-md border-gray-300 bg-white py-1 pl-2.5 pr-8 text-xs focus:border-primary-500 focus:ring-primary-500 w-36">
                            <option value="">Not recorded</option>
                            <option value="present" @selected($selectedStatus === 'present')>Present</option>
                            <option value="absent" @selected($selectedStatus === 'absent')>Absent</option>
                        </select>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Buttons -->
    <div class="col-span-12 flex items-center justify-end gap-2 mt-2">
        <x-ui.button type="submit" size="sm">{{ $submitLabel }}</x-ui.button>
        <x-ui.button type="button" variant="secondary" size="sm" @click="{{ $cancelAction }}">Cancel</x-ui.button>
    </div>
</div>
