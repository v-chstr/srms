@props([
    'label'       => null,
    'name',
    'type'        => 'text',
    'placeholder' => '',
    'required'    => false,
    'value'       => null,
])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        value="{{ old($name, $value) }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'block w-full border border-gray-300 px-3 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none placeholder:text-gray-400']) }}
    />

    <x-form.error :name="$name" />
</div>
