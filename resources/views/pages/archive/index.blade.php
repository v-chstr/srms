@auth
<x-layouts.app title="Research Archive">
    <x-layouts.page-header title="Research Archive" subtitle="Browse approved research papers from SITE" />
    @include('pages.archive._index-inner')
</x-layouts.app>
@else
<x-layouts.stage title="Research Archive">
    @include('pages.archive._index-inner')
</x-layouts.stage>
@endauth
