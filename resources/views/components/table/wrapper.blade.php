@props(['paginator' => null, 'tableClass' => 'w-full table-auto divide-y divide-gray-100'])

<div {{ $attributes->merge(['class' => 'overflow-hidden border border-gray-200 rounded-md bg-white shadow-sm']) }}>
    <table class="{{ $tableClass }}">
        @isset($head)
            <thead>
                <tr>{{ $head }}</tr>
            </thead>
        @endisset
        <tbody class="bg-white divide-y divide-gray-100 [&_tr:hover]:bg-gray-50">
            {{ $slot }}
        </tbody>
        @if($paginator && $paginator->total() > 0)
            <tfoot>
                <tr>
                    <td colspan="100" class="px-3 py-2 bg-gray-50 border-t border-gray-200">
                        <x-ui.pagination :paginator="$paginator" />
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
