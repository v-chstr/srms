@props([
    'label'       => null,
    'name',
    'options'     => [],
    'placeholder' => 'Select one',
    'required'    => false,
    'selected'    => null,
])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'block w-full border border-gray-300 pl-3 pr-9 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none']) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected(old($name, $selected) == $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>

    <x-form.error :name="$name" />
</div>
