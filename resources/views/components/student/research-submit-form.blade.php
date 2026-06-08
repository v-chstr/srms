{{--
    Research submission form — rendered inside <x-ui.modal name="research-create">.
    Opened from the student research index page.

    Props:
      - advisers: Collection of adviser users
      - keywordSuggestions: array of suggested keywords
--}}
@props([
    'advisers',
    'keywordSuggestions' => [],
])

{{-- Header --}}
<div class="flex items-start justify-between border-b border-gray-200 px-5 py-4 shrink-0">
    <div class="min-w-0 pr-4">
        <h2 class="text-sm font-semibold text-gray-900">New Research Submission</h2>
        <p class="text-xs text-gray-500 mt-0.5">Upload your manuscript for adviser review</p>
    </div>
    <button type="button" x-on:click="$dispatch('close-modal', 'research-create')"
            class="shrink-0 mt-0.5 text-gray-400 hover:text-gray-600 transition-colors">
        <x-ui.icon name="close" size="sm" />
    </button>
</div>

<form method="POST" action="{{ route('student.research.store') }}" enctype="multipart/form-data"
      x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="px-5 pt-5 pb-0 space-y-4">
        <x-form.input name="title" label="Research Title" placeholder="Enter the full title of your research" :required="true" />

        <x-form.textarea name="abstract" label="Abstract" placeholder="Provide a summary of your research (max 1500 characters)" rows="4" :maxlength="1500" />

        <x-form.tag-input
            name="keywords"
            label="Keywords"
            placeholder="Type a keyword and press Enter"
            :max="10"
            :suggestions="$keywordSuggestions"
        />

        <x-form.author-input
            name="authors"
            label="Authors"
            :submitter="auth()->user()"
            :required="true"
        />

        <x-form.select
            name="adviser_id"
            label="Research Adviser"
            :options="$advisers->mapWithKeys(fn ($a) => [$a->id => $a->first_name . ' ' . $a->last_name])->toArray()"
            placeholder="Select your adviser"
            :required="true"
        />

        <x-form.file
            name="manuscript"
            label="Manuscript (PDF / DOCX)"
            hint="PDF or DOCX, maximum 20MB"
            :required="true"
        />
    </div>
    <div class="mt-5">
        <x-ui.modal-footer modalName="research-create" submitLabel="Submit Research" submitting="submitting" />
    </div>
</form>
