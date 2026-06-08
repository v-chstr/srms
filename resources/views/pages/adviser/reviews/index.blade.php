<x-layouts.app title="Review Queue">

    <x-layouts.page-header title="Review Queue" subtitle="Research papers available for review" />

    @php
        $papersJson = $papers->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'abstract' => $p->abstract,
            'status' => $p->status,
            'created_at' => $p->created_at->srmsDate(),
            'updated_at' => $p->updated_at->srmsDate(),
            'course' => $p->course ? ['code' => $p->course->displayCode(), 'name' => $p->course->name] : null,
            'adviser' => $p->adviser ? ['first_name' => $p->adviser->first_name, 'last_name' => $p->adviser->last_name] : null,
            'adviser_name' => $p->adviser_name,
            'keywords' => $p->keywords ?? [],
            'original_filename' => $p->original_filename,
            'is_pdf' => str_ends_with(strtolower($p->original_filename ?? $p->file_path ?? ''), '.pdf'),
            'annotate_url' => route('adviser.reviews.annotate', $p->id),
            'authors' => $p->authors->map(fn ($a) => ['first_name' => $a->first_name, 'last_name' => $a->last_name, 'is_submitter' => $a->is_submitter])->values()->toArray(),
            'reviews' => $p->reviews->map(fn ($r) => [
                'decision' => $r->decision,
                'comments' => $r->comments,
                'created_at' => $r->created_at->srmsDate(),
                'reviewer' => $r->reviewer ? ['first_name' => $r->reviewer->first_name, 'last_name' => $r->reviewer->last_name] : null,
            ])->values()->toArray(),
        ]);
    @endphp

    <div x-data="{
        papers: @js($papersJson),
        initialPaperId: @js(old('_context_paper_id')),
        paper: null,
        init() {
            if (this.initialPaperId) {
                this.$nextTick(() => this.openPaper(this.initialPaperId));
            }
        },
        openPaper(id) {
            id = Number(id);
            this.paper = null;
            this.$nextTick(() => {
                const src = this.papers.find(p => p.id === id);
                if (!src) {
                    return;
                }

                this.paper = { ...src, authors: [...(src.authors || [])], reviews: [...(src.reviews || [])] };
                this.$dispatch('open-modal', 'review-actions');
            });
        }
    }">

    {{-- Filters --}}
    <x-ui.filter-bar
        :action="route('adviser.reviews.index')"
        :clearHref="route('adviser.reviews.index')"
        :hasFilters="request('course_id') || request('status') || $autoFiltered"
    >
        <select name="course_id" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">{{ config('ui.placeholders.all_courses') }}</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected($courseFilter == $course->id)>{{ $course->name }}</option>
            @endforeach
        </select>
        <select name="status" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">{{ config('ui.placeholders.all_statuses') }}</option>
            @foreach(collect(config('ui.statuses'))->pluck('label') as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ui.filter-bar>

    <x-table.wrapper :paginator="$papers">
        <x-slot:head>
            <x-table.heading>Title</x-table.heading>
            <x-table.heading class="hidden sm:table-cell w-20">Course</x-table.heading>
            <x-table.heading class="hidden lg:table-cell w-44">Adviser</x-table.heading>
            <x-table.heading class="w-28">Status</x-table.heading>
            <x-table.heading class="hidden md:table-cell w-28">Submitted</x-table.heading>
            <x-table.heading class="w-44 text-right"></x-table.heading>
        </x-slot:head>

        @if($papers->isEmpty())
            <x-table.empty :colspan="6" message="No papers found matching your filters." />
        @else
            @foreach($papers as $paper)
                <tr>
                    <x-table.cell wrap>
                        <button type="button" class="block text-left text-sm font-semibold leading-snug text-gray-900 hover:text-primary-700" x-on:click="openPaper({{ $paper->id }})">
                            {{ $paper->title }}
                        </button>
                        @php
                            $submitterAuthor = $paper->authors->firstWhere('is_submitter', true);
                            $submitterName = $paper->submitter
                                ? $paper->submitter->first_name . ' ' . $paper->submitter->last_name
                                : ($submitterAuthor ? $submitterAuthor->first_name . ' ' . $submitterAuthor->last_name : null);
                        @endphp
                        @if($submitterName)
                            <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ $submitterName }}</p>
                        @endif
                    </x-table.cell>
                    <x-table.cell class="hidden sm:table-cell" nowrap>
                        <span class="text-sm font-medium text-gray-700">{{ $paper->course?->displayCode() ?? 'N/A' }}</span>
                    </x-table.cell>
                    <x-table.cell class="hidden lg:table-cell">
                        @if($paper->adviser)
                            <span class="block text-sm font-medium text-gray-900">{{ $paper->adviser->first_name }} {{ $paper->adviser->last_name }}</span>
                        @elseif($paper->adviser_name)
                            <span class="block text-sm font-medium text-gray-900">{{ $paper->adviser_name }}</span>
                        @else
                            <span class="block text-sm text-gray-400">{{ config('ui.fallbacks.not_assigned') }}</span>
                        @endif
                    </x-table.cell>
                    <x-table.cell nowrap><x-ui.badge :status="$paper->status" /></x-table.cell>
                    <x-table.cell class="hidden md:table-cell text-xs text-gray-500 tabular-nums" nowrap>
                        <x-ui.date :value="$paper->created_at" short />
                    </x-table.cell>
                    <x-table.cell class="text-right" nowrap>
                        <div class="inline-flex items-center gap-1.5">
                            @if($paper->status !== 'approved' && str_ends_with(strtolower($paper->original_filename ?? $paper->file_path ?? ''), '.pdf'))
                                <x-ui.button :href="route('adviser.reviews.annotate', $paper->id)" variant="secondary" size="sm">Annotate</x-ui.button>
                            @endif
                            <x-ui.button type="button" variant="secondary" size="sm" x-on:click="openPaper({{ $paper->id }})">Manage</x-ui.button>
                        </div>
                    </x-table.cell>
                </tr>
            @endforeach
        @endif
    </x-table.wrapper>

    {{-- Single review actions modal --}}
    <x-ui.modal name="review-actions" maxWidth="2xl">
        <template x-if="paper">
            <x-ui.modal-actions modalName="review-actions" :initial-mode="old('_context_mode', 'view')">
                <x-slot:title>
                    <h2 class="text-sm font-semibold leading-snug text-white break-words" x-text="paper.title"></h2>
                    <p class="mt-0.5 text-xs text-white/70 break-words"
                       x-text="paper.course ? paper.course.code : 'N/A'"></p>
                </x-slot:title>
                <x-slot:tabs>
                    <x-ui.modal-tab key="view" label="View Details" />
                    <template x-if="paper.status !== 'approved'">
                        <x-ui.modal-tab key="review" label="Submit Review" />
                    </template>
                </x-slot:tabs>

                {{-- View panel --}}
                <div x-show="mode === 'view'" x-cloak>
                    <x-ui.paper-detail />
                    <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100">
                        <template x-if="!paper.original_filename || !paper.original_filename.toLowerCase().endsWith('.docx')">
                            <x-ui.button x-bind:href="'/adviser/reviews/' + paper.id + '/preview'" target="_blank" variant="secondary" size="sm">
                                Preview
                            </x-ui.button>
                        </template>
                        <x-ui.button x-bind:href="'/adviser/reviews/' + paper.id + '/download'" size="sm">
                            <x-ui.icon name="download" size="2xs" />
                            Download
                        </x-ui.button>
                        <template x-if="paper.status !== 'approved' && paper.is_pdf">
                            <x-ui.button x-bind:href="paper.annotate_url" variant="secondary" size="sm">
                                <x-ui.icon name="pencil-square" size="2xs" />
                                Annotate
                            </x-ui.button>
                        </template>
                    </div>
                </div>

                {{-- Review panel --}}
                <div x-show="mode === 'review'" x-cloak>
                    <form method="POST" :action="'{{ url('adviser/reviews') }}/' + paper.id"
                          x-data="{ submitting: false }" @submit="submitting = true">
                        @csrf
                        <input type="hidden" name="_context_paper_id" :value="paper.id">
                        <input type="hidden" name="_context_mode" value="review">
                        <div class="px-6 pt-5 pb-0 space-y-4">
                            <x-form.textarea
                                name="comments"
                                label="Review Comments"
                                placeholder="Provide your feedback on this research paper"
                                :required="true"
                                rows="5"
                            />
                            <x-form.select
                                name="decision"
                                label="Decision"
                                :required="true"
                                :options="config('ui.review_decisions')"
                                placeholder="Select a decision"
                            />
                        </div>
                        <x-ui.modal-footer modalName="review-actions" submitLabel="Submit Review" submitting="submitting" />
                    </form>
                </div>
            </x-ui.modal-actions>
        </template>
    </x-ui.modal>

    </div>

</x-layouts.app>
