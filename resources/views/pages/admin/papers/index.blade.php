<x-layouts.app title="Research Papers">

    <x-layouts.page-header title="Research Papers" subtitle="Review and manage all research paper submissions" />

    @php
        $papersJson = $papers->map(function($p) {
            return [
                'id' => $p->id,
                'title' => $p->title,
                'abstract' => $p->abstract,
                'status' => $p->status,
                'created_at' => $p->created_at->srmsDate(),
                'updated_at' => $p->updated_at->srmsDate(),
                'submitter' => $p->submitter ? ['first_name' => $p->submitter->first_name, 'last_name' => $p->submitter->last_name] : null,
                'course' => $p->course ? ['name' => $p->course->name, 'code' => $p->course->displayCode()] : null,
                'adviser' => $p->adviser ? ['first_name' => $p->adviser->first_name, 'last_name' => $p->adviser->last_name] : null,
                'adviser_name' => $p->adviser_name,
                'keywords' => $p->keywords ?? [],
                'original_filename' => $p->original_filename,
                'authors' => $p->authors->map(fn($a) => ['first_name' => $a->first_name, 'last_name' => $a->last_name, 'is_submitter' => $a->is_submitter])->values()->toArray(),
                'reviews' => $p->reviews->map(fn($r) => [
                    'reviewer' => $r->reviewer ? ['first_name' => $r->reviewer->first_name, 'last_name' => $r->reviewer->last_name] : null,
                    'decision' => $r->decision,
                    'comments' => $r->comments,
                    'created_at' => $r->created_at->srmsDate(),
                ])->values()->toArray(),
            ];
        });
    @endphp

    <div x-data="{
        papers: @js($papersJson),
        paper: null,
        openPaper(id) {
            const src = this.papers.find(p => p.id === id);
            this.paper = { ...src, authors: [...(src.authors || [])], reviews: [...(src.reviews || [])] };
            this.$dispatch('open-modal', 'paper-actions');
        }
    }">

    {{-- Filters --}}
    <x-ui.filter-bar
        :action="route('admin.papers.index')"
        :clearHref="route('admin.papers.index')"
        :hasFilters="request()->hasAny(['status', 'course_id'])"
    >
        <select name="status" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">{{ config('ui.placeholders.all_statuses') }}</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="revision" @selected(request('status') === 'revision')>Revision</option>
            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
        </select>
        <select name="course_id" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">{{ config('ui.placeholders.all_courses') }}</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->name }}</option>
            @endforeach
        </select>
    </x-ui.filter-bar>

    {{-- Action bar --}}
    <div class="mb-4 flex items-center justify-between gap-3">
        <x-ui.button type="button" x-data x-on:click="$dispatch('open-modal', 'paper-upload')">
            <x-ui.icon name="plus" class="h-4 w-4 mr-1.5" />
            Upload Paper
        </x-ui.button>
    </div>

    {{-- Papers table --}}
    <x-table.wrapper :paginator="$papers">
        <x-slot:head>
            <x-table.heading>Paper</x-table.heading>
            <x-table.heading class="hidden sm:table-cell w-28">Course</x-table.heading>
            <x-table.heading class="hidden md:table-cell w-48">Adviser</x-table.heading>
            <x-table.heading class="w-28">Status</x-table.heading>
            <x-table.heading class="hidden lg:table-cell w-28">Submitted</x-table.heading>
            <x-table.heading class="w-24 text-right"></x-table.heading>
        </x-slot:head>

        @if($papers->isEmpty())
            <x-table.empty :colspan="6" message="No submissions found. Try adjusting your filters." />
        @else
            @foreach($papers as $p)
                <tr class="cursor-pointer hover:bg-gray-50 transition-colors" x-on:click="openPaper({{ $p->id }})">
                    <x-table.cell wrap>
                        <p class="text-sm font-semibold leading-snug text-gray-900">{{ $p->title }}</p>
                        @php
                            $submitterAuthor = $p->authors->firstWhere('is_submitter', true);
                            $submitterName = $p->submitter
                                ? $p->submitter->first_name . ' ' . $p->submitter->last_name
                                : ($submitterAuthor ? $submitterAuthor->first_name . ' ' . $submitterAuthor->last_name : null);
                        @endphp
                        @if($submitterName)
                            <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ $submitterName }}</p>
                        @endif
                    </x-table.cell>
                    <x-table.cell class="hidden sm:table-cell" nowrap>
                        <span class="text-sm font-medium text-gray-700">{{ $p->course?->displayCode() ?? 'N/A' }}</span>
                    </x-table.cell>
                    <x-table.cell class="hidden md:table-cell">
                        @if($p->adviser)
                            <span class="block text-sm font-medium text-gray-900">{{ $p->adviser->first_name }} {{ $p->adviser->last_name }}</span>
                        @elseif($p->adviser_name)
                            <span class="block text-sm font-medium text-gray-900">{{ $p->adviser_name }}</span>
                        @else
                            <span class="block text-sm text-gray-400">{{ config('ui.fallbacks.not_assigned') }}</span>
                        @endif
                    </x-table.cell>
                    <x-table.cell nowrap>
                        <x-ui.badge :status="$p->status" />
                    </x-table.cell>
                    <x-table.cell class="hidden lg:table-cell text-xs text-gray-500 tabular-nums" nowrap>
                        <x-ui.date :value="$p->created_at" short />
                    </x-table.cell>
                    <x-table.cell class="text-right" nowrap>
                        <x-ui.button type="button" variant="secondary" size="sm" x-on:click.stop="openPaper({{ $p->id }})">View</x-ui.button>
                    </x-table.cell>
                </tr>
            @endforeach
        @endif
    </x-table.wrapper>

    {{-- Paper detail modal (view only for non-advisers) --}}
    <x-ui.modal name="paper-actions" maxWidth="2xl">
        <template x-if="paper">
            <div>
                <div class="flex items-start justify-between bg-primary-900 px-5 py-4 shrink-0">
                    <div class="min-w-0 flex-1 pr-4">
                        <h2 class="text-sm font-semibold text-white leading-snug break-words" x-text="paper.title"></h2>
                        <p class="mt-0.5 text-xs text-white/70 break-words"
                           x-text="(paper.submitter ? paper.submitter.first_name + ' ' + paper.submitter.last_name : 'N/A') + ' | ' + (paper.course ? paper.course.name : 'N/A')"></p>
                    </div>
                    <button type="button" x-on:click="$dispatch('close-modal', 'paper-actions')"
                            class="shrink-0 mt-0.5 text-white/60 hover:text-white hover:bg-primary-800 p-1 rounded-md transition-colors">
                        <x-ui.icon name="x-mark" class="w-4 h-4" />
                    </button>
                </div>
                <x-ui.paper-detail />

                <div class="flex items-center justify-between gap-3 px-5 py-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <template x-if="!paper.original_filename || !paper.original_filename.toLowerCase().endsWith('.docx')">
                            <x-ui.button x-bind:href="'/admin/papers/' + paper.id + '/preview'" target="_blank" variant="secondary" size="sm">
                                Preview
                            </x-ui.button>
                        </template>
                        <x-ui.button x-bind:href="'/admin/papers/' + paper.id + '/download'" size="sm">
                            <x-ui.icon name="download" size="2xs" />
                            Download
                        </x-ui.button>
                    </div>
                    <form method="POST" :action="'/admin/papers/' + paper.id"
                          x-data="{ confirm: false }"
                          @submit.prevent="if(confirm) $el.submit(); else confirm = true">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                :class="confirm ? 'bg-red-600 hover:bg-red-700 text-white' : 'text-red-600 hover:bg-red-50'"
                                class="px-3 py-1.5 text-sm font-medium rounded-md border border-red-200 transition-colors"
                                x-text="confirm ? 'Click again to confirm' : 'Delete Paper'">
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </x-ui.modal>

    </div>

    {{-- Upload paper modal --}}
    <x-ui.modal name="paper-upload" :show="$errors->hasAny(['title', 'abstract', 'course_id', 'published_year', 'adviser_id', 'manuscript', 'keywords', 'authors'])" maxWidth="2xl">
        <x-admin.paper-upload-modal :courses="$courses" :keyword-suggestions="$keywordSuggestions" :advisers="$advisers" />
    </x-ui.modal>

</x-layouts.app>
