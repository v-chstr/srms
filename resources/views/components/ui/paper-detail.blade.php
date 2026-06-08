{{--
    Alpine-driven paper detail panel.

    Expects an Alpine 'paper' variable in parent scope with keys:
    id, title, abstract, status, created_at, updated_at,
    course (nullable), adviser (nullable),
    authors (array with is_submitter flag), reviews (array),
    original_filename (nullable)

    No props. Preview and download buttons live in each parent modal footer.
--}}

@php
    $statusMapJson = collect(config('ui.statuses'))->map(fn ($s) => [
        'label' => $s['label_long'],
        'bg'    => $s['bg'],
        'text'  => $s['text'],
    ]);
@endphp

<div class="px-5 py-4">

    {{-- Metadata rows --}}
    <div class="divide-y divide-gray-100">

        {{-- Status --}}
        <x-ui.detail-field label="Status">
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                  :class="(@js($statusMapJson)[paper.status] || { bg: 'bg-gray-100', text: 'text-gray-700' }).bg + ' ' + (@js($statusMapJson)[paper.status] || { text: 'text-gray-700' }).text"
                  x-text="(@js($statusMapJson)[paper.status] || { label: paper.status.charAt(0).toUpperCase() + paper.status.slice(1) }).label"></span>
        </x-ui.detail-field>

        {{-- Course --}}
        <x-ui.detail-field label="Course">
            <template x-if="paper.course">
                <span class="font-medium text-gray-900" x-text="paper.course.name"></span>
            </template>
            <template x-if="!paper.course">
                <span class="text-gray-400">N/A</span>
            </template>
        </x-ui.detail-field>

        {{-- Adviser --}}
        <x-ui.detail-field label="Adviser">
            <template x-if="paper.adviser">
                <span class="font-medium text-gray-900" x-text="paper.adviser.first_name + ' ' + paper.adviser.last_name"></span>
            </template>
            <template x-if="!paper.adviser && paper.adviser_name">
                <span class="font-medium text-gray-900" x-text="paper.adviser_name"></span>
            </template>
            <template x-if="!paper.adviser && !paper.adviser_name">
                <span class="text-gray-400">{{ config('ui.fallbacks.not_assigned') }}</span>
            </template>
        </x-ui.detail-field>

        {{-- Last updated --}}
        <x-ui.detail-field label="Last updated">
            <span class="tabular-nums" x-text="paper.updated_at"></span>
        </x-ui.detail-field>

        {{-- Abstract --}}
        <x-ui.detail-field label="Abstract">
            <template x-if="paper.abstract">
                <span class="whitespace-pre-line leading-relaxed" x-text="paper.abstract"></span>
            </template>
            <template x-if="!paper.abstract">
                <span class="text-gray-400 italic">{{ config('ui.fallbacks.no_abstract') }}</span>
            </template>
        </x-ui.detail-field>

        {{-- Keywords --}}
        <template x-if="paper.keywords && paper.keywords.length > 0">
            <x-ui.detail-field label="Keywords">
                <span x-text="paper.keywords.slice(0, 6).map(kw => kw.replace(/[-_]+/g, ' ').replace(/\b\w/g, char => char.toUpperCase())).join(', ')"></span>
                <template x-if="paper.keywords.length > 6">
                    <span class="ml-1 text-gray-400" x-text="'+' + (paper.keywords.length - 6) + ' more'"></span>
                </template>
            </x-ui.detail-field>
        </template>

        {{-- Authors --}}
        <template x-if="paper.authors && paper.authors.length > 0">
            <x-ui.detail-field label="Authors">
                <span x-text="paper.authors.map(a => a.first_name + ' ' + a.last_name).join(', ')"></span>
            </x-ui.detail-field>
        </template>

    </div>

    {{-- Reviews --}}
    <template x-if="paper.reviews && paper.reviews.length > 0">
        <div class="mt-3 pt-3 border-t border-gray-100">
            <p class="text-xs font-semibold text-gray-500 mb-1.5">Reviews</p>
            <div class="space-y-1.5">
                <template x-for="review in paper.reviews" :key="review.created_at">
                    <div class="rounded-md border overflow-hidden"
                         :class="review.decision === 'approved' ? 'border-emerald-200' : 'border-amber-200'">
                        <div class="flex items-center justify-between gap-2 px-3 py-1.5"
                             :class="review.decision === 'approved' ? 'bg-emerald-50' : 'bg-amber-50'">
                            <span class="text-sm font-medium text-gray-900"
                                  x-text="review.reviewer ? review.reviewer.first_name + ' ' + review.reviewer.last_name : 'Unknown'"></span>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                                      :class="review.decision === 'approved' ? 'bg-status-approved text-status-approved-fg' : 'bg-status-revision text-status-revision-fg'"
                                      x-text="review.decision === 'approved' ? '{{ config('ui.review_decisions.approved') }}' : '{{ config('ui.review_decisions.revision_required') }}'"></span>
                                <span class="text-xs text-gray-400 tabular-nums" x-text="review.created_at"></span>
                            </div>
                        </div>
                        <div class="px-3 py-2 bg-white">
                            <p class="text-sm text-gray-700 whitespace-pre-line" x-text="review.comments || review.comment"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </template>

</div>
