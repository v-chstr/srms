@props([
    'colspan' => 1,
    'message' => 'No records found.',
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-6 text-center text-sm text-gray-400">
        {{ $message }}
    </td>
</tr>
