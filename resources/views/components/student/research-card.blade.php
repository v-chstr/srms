{{--
    Single research paper card — used in the student research index list.
    Expects Blade-rendered data (not Alpine). Dispatches openPaper() via Alpine parent scope.

    Props:
      - paper: ResearchPaper model instance (eager-loaded with course, adviser)
--}}
@props(['paper'])

<div class="bg-white border border-gray-200 rounded-lg px-5 py-4 cursor-pointer hover:bg-gray-50 transition-colors"
     x-on:click="openPaper({{ $paper->id }})">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $paper->title }}</h3>
            <div class="mt-2 flex items-center gap-2 text-xs text-gray-400">
                <span>{{ $paper->course->name ?? 'N/A' }}</span>
                @if($paper->adviser)
                    <span class="text-gray-300">|</span>
                    <span>{{ $paper->adviser->first_name }} {{ $paper->adviser->last_name }}</span>
                @elseif($paper->adviser_name)
                    <span class="text-gray-300">|</span>
                    <span>{{ $paper->adviser_name }}</span>
                @endif
                <span class="text-gray-300">|</span>
                <span><x-ui.date :value="$paper->created_at" /></span>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <x-ui.badge :status="$paper->status" :long="true" />
            <x-ui.button type="button" variant="ghost" size="sm" x-on:click.stop="openPaper({{ $paper->id }})">View</x-ui.button>
        </div>
    </div>
</div>
