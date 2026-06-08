@props([
    'sortable' => false,
])

<th {{ $attributes->merge(['class' => 'px-3 py-2.5 first:pl-4 last:pr-4 text-left align-top text-xs font-semibold text-gray-600 bg-gray-100']) }}>
    {{ $slot }}
</th>
