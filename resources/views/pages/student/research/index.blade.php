<x-layouts.app title="My Research">

    <x-layouts.page-header title="My Research" subtitle="View and manage your research submissions" />

    @php
        $papersJson = $papers->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'abstract' => $p->abstract,
            'status' => $p->status,
            'adviser_id'   => $p->adviser_id,
            'adviser_name' => $p->adviser_name,
            'created_at' => $p->created_at->srmsDate(),
            'updated_at' => $p->updated_at->srmsDate(),
            'course' => $p->course ? ['code' => $p->course->displayCode(), 'name' => $p->course->name] : null,
            'adviser' => $p->adviser ? ['first_name' => $p->adviser->first_name, 'last_name' => $p->adviser->last_name] : null,
            'keywords'          => $p->keywords ?? [],
            'original_filename' => $p->original_filename,
            'file_name'         => $p->file_path ? basename($p->file_path) : null,
            'download_url'      => route('student.research.download', $p->id),
            'is_pdf'            => str_ends_with(strtolower($p->original_filename ?? $p->file_path ?? ''), '.pdf'),
            'annotate_url'      => route('student.research.annotate', $p->id),
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
                this.$dispatch('open-modal', 'research-actions');
            });
        }
    }">

    {{-- Action bar --}}
    <div class="mb-4 flex items-center justify-between gap-3">
        <x-ui.button type="button" x-on:click="$dispatch('open-modal', 'research-create')">
            <x-ui.icon name="plus" size="sm" />
            New Submission
        </x-ui.button>
        @if(!$papers->isEmpty())
            <p class="text-xs text-gray-500">{{ $papers->total() ?? $papers->count() }} {{ Str::plural('submission', $papers->total() ?? $papers->count()) }}</p>
        @endif
    </div>

    {{-- Empty state --}}
    @if($papers->isEmpty())
        <x-ui.empty-state title="No research papers yet" message="Submit your first research paper to get started." :cta="route('student.research.index')" ctaLabel="Submit paper" x-on:click.prevent="$dispatch('open-modal', 'research-create')" />
    @else
        {{-- Paper cards --}}
        <div class="space-y-3">
            @foreach($papers as $paper)
                <x-student.research-card :paper="$paper" />
            @endforeach
        </div>

        <x-ui.pagination :paginator="$papers" class="mt-4" />
    @endif

    {{-- New submission modal --}}
    <x-ui.modal name="research-create" :show="$errors->hasAny(['title', 'abstract', 'manuscript', 'adviser_id', 'adviser_name', 'keywords', 'authors'])" maxWidth="2xl">
        <x-student.research-submit-form :keywordSuggestions="$keywordSuggestions" :advisers="$advisers" />
    </x-ui.modal>

    {{-- View + Edit/Resubmit modal --}}
    <x-student.research-actions-modal :initial-mode="old('_context_mode', 'view')" />

    </div>

</x-layouts.app>
