{{--
    Archive paper card — used in the archive grid.
    Props:
      - paper: ResearchPaper model with course + authors loaded
--}}
@props(['paper'])

<a href="{{ route('archive.show', $paper->id) }}" class="group flex flex-col rounded-md border border-gray-200 bg-white overflow-hidden hover:border-primary-300 transition-colors">
    {{-- Portrait thumbnail (3:4) --}}
    <div class="relative aspect-[3/4] bg-gray-100 overflow-hidden border-b border-gray-200">
        <x-archive.paper-cover :paper="$paper" logoSize="w-10" />

        {{-- Year badge overlay --}}
        <span class="absolute top-2 right-2 inline-block bg-white/90 text-gray-700 text-xs font-medium px-2 py-0.5 rounded-md tabular-nums">
            {{ $paper->published_year ?? $paper->created_at->year }}
        </span>
    </div>

    {{-- Card body --}}
    <div class="flex flex-col flex-1 px-3 py-2.5">
        @if($paper->course)
            <span class="text-xs font-medium text-primary-700 mb-1">{{ $paper->course->displayCode() }}</span>
        @endif
        <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-3 group-hover:text-primary-700">
            {{ $paper->title }}
        </h3>
        @if($paper->authors->isNotEmpty())
            <p class="mt-1.5 text-xs text-gray-500 line-clamp-1">
                {{ $paper->authors->map(fn ($a) => trim($a->first_name . ' ' . $a->last_name))->implode(', ') }}
            </p>
        @endif
    </div>
</a>
