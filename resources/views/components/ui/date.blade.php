@props([
    'value',
    'withTime' => false, // M j, Y g:i A (same as default — kept for backwards compat)
    'short'    => false, // M j, Y       (date only, no time)
    'numeric'  => false, // M j, Y g:i A (date + time, same as default)
    'timeOnly' => false, // g:i A        (time only)
])

@php
    use Illuminate\Support\Carbon;
    $carbon = $value instanceof Carbon ? $value : Carbon::parse($value);

    $formatted = match (true) {
        (bool) $timeOnly => $carbon->srmsTime(),
        (bool) $short    => $carbon->srmsShort(),
        default          => $carbon->srmsDate(),
    };

    echo $formatted;
@endphp
