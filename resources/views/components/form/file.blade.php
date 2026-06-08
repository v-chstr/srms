@props([
    'name',
    'label'    => null,
    'id'       => null,
    'hint'     => null,
    'required' => false,
    'accept'   => '.pdf,.docx',
])

@php $inputId = $id ?? $name; @endphp

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    <input
        type="file"
        id="{{ $inputId }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'block w-full text-sm text-gray-700 file:mr-3 file:py-1.5 file:px-3 file:border-0 file:rounded-md file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100']) }}
    />

    @if($hint)
        <p class="text-xs text-gray-500">{{ $hint }}</p>
    @endif

    <x-form.error :name="$name" />
</div>
