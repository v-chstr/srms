@auth
<x-layouts.app :title="$paper->title">
    @include('pages.archive._show-inner')
</x-layouts.app>
@else
<x-layouts.stage :title="$paper->title">
    @include('pages.archive._show-inner')
</x-layouts.stage>
@endauth
