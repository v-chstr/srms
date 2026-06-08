@props([
    'label'       => null,
    'name',
    'placeholder' => '',
    'required'    => false,
    'rows'        => 4,
    'value'       => null,
    'maxlength'   => null,
])

<div class="space-y-1.5" @if($maxlength) x-data="{ count: {{ strlen(old($name, $value ?? '')) }} }" @endif>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($maxlength) maxlength="{{ $maxlength }}" x-init="count = $el.value.length" x-on:input="count = $el.value.length" @endif
        {{ $attributes->merge(['class' => 'block w-full border border-gray-300 px-3 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none placeholder:text-gray-400']) }}
    >{{ old($name, $value) }}</textarea>

    @if($maxlength)
        <p class="text-xs text-right" :class="count > {{ $maxlength }} ? 'text-red-500 font-semibold' : (count > {{ $maxlength * 0.9 }} ? 'text-amber-500' : 'text-gray-400')">
            <span x-text="count"></span> / {{ $maxlength }}
        </p>
    @endif

    <x-form.error :name="$name" />
</div>
