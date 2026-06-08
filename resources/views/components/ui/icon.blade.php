@props([
    'name',
    'style' => 'outline',
    'size' => 'sm',
])

@php
    $classAttribute = (string) $attributes->get('class');
    $hasExplicitSize = str_contains($classAttribute, 'h-') || str_contains($classAttribute, 'w-');

    $icon = match ($name) {
        'close' => 'x-mark',
        'download' => 'arrow-down-tray',
        'spinner' => 'arrow-path',
        'document' => 'document-text',
        default => $name,
    };

    $prefix = match ($style) {
        'outline' => 'o',
        'mini' => 'm',
        'micro' => 'c',
        'solid' => 's',
        default => 'o',
    };

    $sizeClass = $hasExplicitSize
        ? null
        : match ($size) {
            '2xs' => 'h-3 w-3',
            'xs' => 'h-3.5 w-3.5',
            'sm' => 'h-4 w-4',
            'md' => 'h-5 w-5',
            'lg' => 'h-6 w-6',
            'xl' => 'h-8 w-8',
            default => 'h-4 w-4',
        };

    $component = "heroicon-{$prefix}-{$icon}";
@endphp

<x-dynamic-component :component="$component" {{ $attributes->merge(['aria-hidden' => 'true'])->class([$sizeClass]) }} />