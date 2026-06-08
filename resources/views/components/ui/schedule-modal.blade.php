@props([
    'courses' => collect(),
    'schedule' => null,
])

@php
    $isEdit    = !is_null($schedule);
    $modalName = $isEdit ? "schedule-edit-{$schedule->id}" : 'schedule-create';
    $minDate   = now()->addDays(3)->format('Y-m-d');
    $maxDate   = now()->addMonths(3)->format('Y-m-d');
    $descLen   = strlen(old('description', $schedule?->description ?? ''));
@endphp

<x-ui.modal :name="$modalName" maxWidth="lg">
    <form method="POST"
          action="{{ $isEdit ? route('schedules.update', $schedule) : route('schedules.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Header --}}
        <div class="flex items-start justify-between bg-primary-700 px-6 py-5">
            <div>
                <h3 class="text-base font-semibold text-white">{{ $isEdit ? 'Edit Schedule' : 'Add Defense Schedule' }}</h3>
                @if(!$isEdit)
                    <p class="text-sm text-white/70 mt-0.5">Set a date, time, and venue for a defense session</p>
                @endif
            </div>
            <button type="button" x-on:click="$dispatch('close-modal', '{{ $modalName }}')"
                    class="-mr-0.5 p-1.5 rounded-lg text-white/60 hover:text-white hover:bg-primary-600 transition-colors">
                <x-ui.icon name="close" size="sm" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-4 space-y-4">

            <x-form.input
                name="title"
                label="Title"
                placeholder="e.g. IT Capstone Defense Day 1"
                :required="true"
                :value="old('title', $schedule?->title)"
            />

            {{-- Description with character counter --}}
            <div x-data="{ descLen: {{ $descLen }} }">
                <div class="flex items-baseline justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <span class="text-xs text-gray-400" x-text="descLen + ' / 120'"></span>
                </div>
                <textarea
                    name="description"
                    rows="3"
                    maxlength="120"
                    placeholder="Panel members, dress code, additional instructions, etc."
                    @input="descLen = $event.target.value.length"
                    class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none @error('description') border-red-300 @enderror"
                >{{ old('description', $schedule?->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="scheduled_date"
                        min="{{ $minDate }}"
                        max="{{ $maxDate }}"
                        value="{{ old('scheduled_date', $schedule?->scheduled_date?->format('Y-m-d')) }}"
                        required
                        class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none @error('scheduled_date') border-red-300 @enderror"
                    />
                    @error('scheduled_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-form.input
                    name="room"
                    label="Room / Venue"
                    placeholder="e.g. Room 301, SITE Building"
                    :required="true"
                    :value="old('room', $schedule?->room)"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form.input
                    name="start_time"
                    label="Start Time"
                    type="time"
                    :required="true"
                    :value="old('start_time', $schedule?->start_time?->format('H:i'))"
                />
                <x-form.input
                    name="end_time"
                    label="End Time"
                    type="time"
                    :value="old('end_time', $schedule?->end_time?->format('H:i'))"
                />
            </div>

            <x-form.select
                name="course_id"
                label="Scope"
                :options="$courses->pluck('name', 'id')->prepend('All Programs (Global)', '')"
                :value="old('course_id', $schedule?->course_id ?? '')"
            />

        </div>

        {{-- Footer --}}
        <x-ui.modal-footer :modalName="$modalName" :submitLabel="$isEdit ? 'Update Schedule' : 'Create Schedule'" />
    </form>
</x-ui.modal>
