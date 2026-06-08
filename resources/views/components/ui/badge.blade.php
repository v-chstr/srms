@props([
    'status' => 'pending',
    'long'   => false,
])

@php
$statusCfg = config("ui.statuses.$status");
$queueCfg  = config("ui.queue_statuses.$status");
$roleCfg   = config("ui.roles.$status");

if ($statusCfg) {
    $color = $statusCfg['bg'] . ' ' . $statusCfg['text'];
    $label = $long ? $statusCfg['label_long'] : $statusCfg['label'];
} elseif ($queueCfg) {
    $color = $queueCfg['bg'] . ' ' . $queueCfg['text'];
    $label = $queueCfg['label'];
} elseif ($roleCfg) {
    $color = $roleCfg['bg'] . ' ' . $roleCfg['text'];
    $label = $roleCfg['label'];
} else {
    $color = match($status) {
        'active' => 'bg-emerald-100 text-emerald-800',
        default  => 'bg-gray-100 text-gray-700',
    };
    $label = ucfirst($status);
}
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium', $color]) }}>
    {{ $label }}
</span>
