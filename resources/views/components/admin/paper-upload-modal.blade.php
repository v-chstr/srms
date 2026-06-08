{{--
    Admin direct upload form — rendered inside <x-ui.modal name="paper-upload">.
    Upload existing/old papers directly with approved status.

    Props:
      - courses: Collection of courses
      - advisers: Collection of adviser users
--}}
@props([
    'courses',
    'advisers',
    'keywordSuggestions' => [],
])

{{-- Header --}}
<div class="flex items-start justify-between bg-primary-700 px-6 py-5 shrink-0">
    <div class="min-w-0 pr-4">
        <h2 class="text-base font-semibold text-white">Upload Research Paper</h2>
        <p class="text-sm text-white/70 mt-0.5">Add an existing paper directly to the archive</p>
    </div>
    <button type="button" x-on:click="$dispatch('close-modal', 'paper-upload')"
            class="shrink-0 -mr-0.5 p-1.5 rounded-lg text-white/60 hover:text-white hover:bg-primary-600 transition-colors">
        <x-ui.icon name="close" size="sm" />
    </button>
</div>

<form method="POST" action="{{ route('admin.papers.store') }}" enctype="multipart/form-data"
      x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <div class="px-5 pt-5 pb-0 space-y-4 max-h-[60vh] overflow-y-auto">
        <x-form.input name="title" label="Research Title" placeholder="Enter the full title of the research" :required="true" />

        <x-form.textarea name="abstract" label="Abstract" placeholder="Provide a summary of the research (max 1500 characters)" rows="4" :maxlength="1500" />

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
            :required="true"
        />

        <div class="grid grid-cols-2 gap-4">
            <x-form.select
                name="course_id"
                label="Program"
                :options="$courses->pluck('name', 'id')->toArray()"
                placeholder="Select program"
                :required="true"
            />

            <x-form.input
                name="published_year"
                label="Year Published"
                type="number"
                :value="old('published_year', date('Y'))"
                :required="true"
            />
        </div>

        <x-form.select
            name="adviser_id"
            label="Adviser (optional)"
            :options="$advisers->mapWithKeys(fn ($a) => [$a->id => $a->first_name . ' ' . $a->last_name . ($a->status !== 'active' ? ' (Former)' : '')])->toArray()"
            placeholder="Select adviser"
        />

        <x-form.file
            name="manuscript"
            label="Manuscript (PDF / DOCX)"
            hint="PDF or DOCX, maximum 20MB"
            :required="true"
        />

    </div>
    <div class="mt-5">
        <x-ui.modal-footer modalName="paper-upload" submitLabel="Upload Paper" submitting="submitting" loadingLabel="Uploading...">
            <x-slot:note>
                Uploaded as <span class="font-semibold text-gray-700">approved</span> and immediately visible in the archive.
            </x-slot:note>
        </x-ui.modal-footer>
    </div>
</form>
