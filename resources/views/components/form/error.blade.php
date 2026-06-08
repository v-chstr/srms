@props(['name'])

@error($name)
    <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
@enderror
