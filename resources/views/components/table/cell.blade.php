@props([
    'truncate' => false,
    'wrap' => false,
    'nowrap' => false,
])

<td {{ $attributes->class([
    'px-3 py-2.5 first:pl-4 last:pr-4 align-top text-sm leading-relaxed text-gray-700',
    'break-words' => $wrap,
    'whitespace-nowrap' => $nowrap,
]) }}>
    @if($truncate)
        <span class="block truncate">{{ $slot }}</span>
    @else
        {{ $slot }}
    @endif
</td>
