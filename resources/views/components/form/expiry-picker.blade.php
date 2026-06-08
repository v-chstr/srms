@props([
    'name' => 'expires_at',
    'label' => 'Expires On',
    'value' => null,
])

@php
    $resolvedValue = old($name, $value);
    $hasValue = filled($resolvedValue);
@endphp

<div
    x-data="{
        preset: @js($hasValue ? 'custom' : ''),
        expiresAt: @js($hasValue ? \Illuminate\Support\Carbon::parse($resolvedValue)->format('Y-m-d') : ''),
        updatePreset(val) {
            if (!val || val === 'custom') { this.expiresAt = ''; return; }
            const d = new Date();
            d.setDate(d.getDate() + parseInt(val));
            this.expiresAt = d.toISOString().split('T')[0];
        }
    }">
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select
        :value="preset"
        @change="preset = $event.target.value; updatePreset(preset)"
        class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
        <option value="">Never expires</option>
        <option value="7">1 week from now</option>
        <option value="14">2 weeks from now</option>
        <option value="30">1 month from now</option>
        <option value="90">3 months from now</option>
        <option value="custom" :selected="preset === 'custom'">Custom date</option>
    </select>
    <div x-show="preset === 'custom'" x-cloak class="mt-2">
        <input
            type="date"
            x-model="expiresAt"
            min="{{ now()->addDay()->format('Y-m-d') }}"
            max="{{ now()->addMonths(3)->format('Y-m-d') }}"
            class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none"
        />
    </div>
    <input type="hidden" name="{{ $name }}" :value="expiresAt" />
    <x-form.error :name="$name" />
</div>
