{{--
    Shared inner content for archive/index.
    Included by both @auth and @else branches of index.blade.php.
    Variables: $papers, $courses, $years
--}}
<div x-data="{
    view: localStorage.getItem('archive-view') ?? 'grid',
    setView(v) { this.view = v; localStorage.setItem('archive-view', v); }
}">

{{-- Constrained content area --}}
<div class="{{ auth()->check() ? '' : 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8' }} py-6">

    {{-- Section bar: result info + view toggle --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $papers->total() }} {{ Str::plural('paper', $papers->total()) }}
                @if(request('search')) for &ldquo;{{ request('search') }}&rdquo;@endif
            </p>
        </div>
        {{-- Grid / List toggle --}}
        <div class="flex items-center rounded-md border border-gray-200 bg-white overflow-hidden shrink-0">
            <button type="button"
                @click="setView('grid')"
                :class="view === 'grid' ? 'bg-primary-700 text-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="flex items-center justify-center p-1.5 transition-colors"
                title="Grid view">
                <x-ui.icon name="squares-2x2" class="h-4 w-4" />
            </button>
            <button type="button"
                @click="setView('list')"
                :class="view === 'list' ? 'bg-primary-700 text-white' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                class="flex items-center justify-center p-1.5 transition-colors"
                title="List view">
                <x-ui.icon name="list-bullet" class="h-4 w-4" />
            </button>
        </div>
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('archive.index') }}"
          class="mb-6 grid grid-cols-2 gap-2 rounded-md border border-gray-200 bg-white p-3 sm:grid-cols-12">

        {{-- Search --}}
        <div class="relative col-span-2 sm:col-span-5">
            <x-ui.icon name="magnifying-glass" class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search title, abstract, keywords"
                   class="w-full rounded-md border border-gray-300 py-2 pl-8 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
        </div>

        {{-- Program --}}
        <div class="sm:col-span-3">
            <select name="course_id" class="block w-full rounded-md border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <option value="">All programs</option>
                @foreach($courses as $id => $name)
                    <option value="{{ $id }}" @selected(request('course_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Year --}}
        <div class="sm:col-span-2">
            <select name="year" class="block w-full rounded-md border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <option value="">All years</option>
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        {{-- Sort --}}
        <div class="sm:col-span-2">
            <select name="sort" class="block w-full rounded-md border border-gray-300 py-2 pl-3 pr-8 text-sm text-gray-900 focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option>
                <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
                <option value="title" @selected(request('sort') === 'title')>Title (A-Z)</option>
            </select>
        </div>

        {{-- Actions --}}
        <div class="col-span-2 flex items-center gap-2 sm:col-span-12 sm:justify-end">
            <button type="submit" class="inline-flex items-center px-3.5 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700">Apply filters</button>
            @if(request()->hasAny(['search', 'course_id', 'year', 'sort']))
                <a href="{{ route('archive.index') }}" class="text-xs font-medium text-gray-500 hover:text-gray-900">Clear all</a>
            @endif
        </div>
    </form>

    {{-- Results --}}
    @if($papers->isEmpty())
        <x-ui.empty-state
            message="No papers found matching your criteria."
            ctaLabel="Clear filters"
            :cta="route('archive.index')"
        />
    @else
        {{-- Grid view --}}
        <div x-show="view === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($papers as $paper)
                <x-archive.paper-card :paper="$paper" />
            @endforeach
        </div>

        {{-- List view --}}
        <div x-show="view === 'list'" class="rounded-md border border-gray-200 bg-white overflow-hidden divide-y divide-gray-100">
            @foreach($papers as $paper)
                <x-archive.paper-row :paper="$paper" />
            @endforeach
        </div>

        <div class="mt-6">
            {{ $papers->links() }}
        </div>
    @endif

</div>{{-- /content area --}}
</div>

