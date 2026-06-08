{{--
    Archive paper list row — used in the archive list view toggle.
    Props:
      - paper: ResearchPaper model with course + authors loaded
--}}
@props(['paper'])

<a href="{{ route('archive.show', $paper->id) }}"
   class="flex items-start gap-4 px-4 py-3 hover:bg-gray-50">

    {{-- Portrait thumbnail --}}
    <div class="relative w-12 h-16 shrink-0 overflow-hidden rounded-md border border-gray-200">
        <x-archive.paper-cover :paper="$paper" logoSize="w-6" />
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-0.5">
            @if($paper->course)
                <span class="text-xs font-medium text-primary-700">{{ $paper->course->displayCode() }}</span>
                <span class="text-gray-300 select-none">|</span>
            @endif
            <span class="text-xs text-gray-400 tabular-nums">{{ $paper->published_year ?? $paper->created_at->year }}</span>
        </div>
        <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2">
            {{ $paper->title }}
        </h3>
        @if($paper->authors->isNotEmpty())
            <p class="mt-0.5 text-xs text-gray-500 line-clamp-1">
                {{ $paper->authors->map(fn ($a) => trim($a->first_name . ' ' . $a->last_name))->implode(', ') }}
            </p>
        @endif
        @if($paper->abstract)
            <p class="mt-1 text-xs text-gray-400 line-clamp-2 leading-relaxed">{{ $paper->abstract }}</p>
        @endif
    </div>
</a>
