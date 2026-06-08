@props([
    'status' => null,
    'compact' => false,
])

@php
    $normalizedStatus = \App\Models\AttendanceRecord::normalizeStatus($status);

    [$label, $classes, $dotClass] = match ($normalizedStatus) {
        \App\Models\AttendanceRecord::STATUS_PRESENT => ['Present', 'border-emerald-200 bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        \App\Models\AttendanceRecord::STATUS_ABSENT => ['Absent', 'border-rose-200 bg-rose-50 text-rose-700', 'bg-rose-500'],
        \App\Models\AttendanceRecord::STATUS_LATE => ['Late', 'border-amber-200 bg-amber-50 text-amber-700', 'bg-amber-500'],
        \App\Models\AttendanceRecord::STATUS_EXCUSED => ['Excused', 'border-sky-200 bg-sky-50 text-sky-700', 'bg-sky-500'],
        default => [$compact ? 'N/A' : 'Not recorded', 'border-gray-200 bg-gray-50 text-gray-600', 'bg-gray-400'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium ' . $classes]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
    {{ $label }}
</span>

