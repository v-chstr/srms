{{--
    Research paper actions modal — view details + edit/resubmit.
    Wraps the Alpine-driven paper detail view and resubmit form.

    This component is rendered once on the student research index page.
    The parent scope must provide `paper` (Alpine reactive object) via openPaper().

    Props:
      - advisers: Collection of adviser users
--}}
@props([
    'advisers' => collect(),
    'initialMode' => 'view',
])

<x-ui.modal name="research-actions" maxWidth="2xl">
    <template x-if="paper">
        <x-ui.modal-actions modalName="research-actions" :initial-mode="$initialMode">
            <x-slot:title>
                <h2 class="text-sm font-semibold text-white leading-snug break-words" x-text="paper.title"></h2>
                <p class="mt-0.5 text-xs text-white/70 break-words"
                   x-text="(paper.course ? paper.course.code : 'N/A') + ', ' + paper.created_at"></p>
            </x-slot:title>
            <x-slot:tabs>
                <x-ui.modal-tab key="view" label="View Details" />
                <template x-if="paper.status !== 'approved'">
                    <x-ui.modal-tab key="edit" label="Edit / Resubmit" />
                </template>
            </x-slot:tabs>

            {{-- View panel --}}
            <div x-show="mode === 'view'" x-cloak>
                <x-ui.paper-detail />
                <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-100">
                    <template x-if="!paper.original_filename || !paper.original_filename.toLowerCase().endsWith('.docx')">
                        <x-ui.button x-bind:href="'/student/research/' + paper.id + '/preview'" target="_blank" variant="secondary" size="sm">
                            Preview
                        </x-ui.button>
                    </template>
                    <x-ui.button x-bind:href="'/student/research/' + paper.id + '/download'" size="sm">
                        <x-ui.icon name="download" size="2xs" />
                        Download
                    </x-ui.button>
                    <template x-if="paper.status === 'revision' && paper.is_pdf">
                        <x-ui.button x-bind:href="paper.annotate_url" variant="secondary" size="sm">
                            <x-ui.icon name="pencil-square" size="2xs" />
                            View Annotations
                        </x-ui.button>
                    </template>
                </div>
            </div>

            {{-- Edit / Resubmit panel --}}
            <div x-show="mode === 'edit'" x-cloak>
                <template x-if="paper.status === 'revision'">
                    <div class="px-6 pt-4 pb-0">
                        <x-ui.alert type="warning">
                            Your paper requires revision. Please update your manuscript and resubmit.
                        </x-ui.alert>
                    </div>
                </template>
                <div
                    x-data="{
                        submitting: false,
                        replaceFile: @js($errors->has('manuscript')),
                        keywords: (paper.keywords ?? []).slice(),
                        authors:  (paper.authors  ?? []).map(a => ({...a})),
                        kwInput:    '',
                        firstName:  '',
                        lastName:   '',
                        addKeyword() {
                            let tag = this.kwInput.trim().toLowerCase();
                            if (tag && !this.keywords.includes(tag) && this.keywords.length < 10) {
                                this.keywords.push(tag);
                            }
                            this.kwInput = '';
                        },
                        removeKeyword(i) { this.keywords.splice(i, 1); },
                        handleKwKeydown(e) {
                            if (e.key === 'Enter') { e.preventDefault(); this.addKeyword(); }
                            if (e.key === 'Backspace' && this.kwInput === '' && this.keywords.length > 0) { this.keywords.pop(); }
                        },
                        addAuthor() {
                            let fn = this.firstName.trim(), ln = this.lastName.trim();
                            if (fn && ln) {
                                this.authors.push({ first_name: fn, last_name: ln, is_submitter: false });
                                this.firstName = '';
                                this.lastName  = '';
                            }
                        },
                        removeAuthor(i) {
                            if (this.authors[i]?.is_submitter) return;
                            this.authors.splice(i, 1);
                        }
                    }"
                >
                <form method="POST" :action="'{{ url('student/research') }}/' + paper.id" enctype="multipart/form-data" @submit="submitting = true">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_context_paper_id" :value="paper.id">
                    <input type="hidden" name="_context_mode" value="edit">
                    <div class="px-6 pt-5 pb-0 space-y-4">
                        <x-form.input name="title" label="Research Title" :required="true" x-model="paper.title" />

                        <x-form.textarea name="abstract" label="Abstract" rows="4" :maxlength="1500" x-model="paper.abstract" />

                        {{-- Keywords inline chip input --}}
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">Keywords</label>
                            <template x-for="(tag, i) in keywords" :key="i">
                                <input type="hidden" :name="'keywords[' + i + ']'" :value="tag" />
                            </template>
                            <div class="flex flex-wrap items-center gap-1.5 border border-gray-300 rounded-md px-2 py-1.5 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 min-h-[38px] bg-white">
                                <template x-for="(tag, index) in keywords" :key="index">
                                    <span class="inline-flex items-center gap-1 bg-primary-50 text-primary-700 text-xs font-medium px-2 py-0.5 rounded-md">
                                        <span x-text="tag"></span>
                                        <button type="button" @click="removeKeyword(index)" class="text-primary-400 hover:text-primary-700">
                                            <x-ui.icon name="close" size="2xs" />
                                        </button>
                                    </span>
                                </template>
                                <input
                                    x-model="kwInput"
                                    x-show="keywords.length < 10"
                                    @keydown="handleKwKeydown($event)"
                                    type="text"
                                    placeholder="Type keyword, press Enter"
                                    class="flex-1 min-w-[120px] border-0 p-0 text-sm focus:ring-0 focus:outline-none bg-transparent"
                                />
                            </div>
                        </div>

                        {{-- Authors inline chip input --}}
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Authors *</label>
                            <template x-for="(author, i) in authors" :key="i">
                                <div>
                                    <input type="hidden" :name="'authors[' + i + '][first_name]'" :value="author.first_name" />
                                    <input type="hidden" :name="'authors[' + i + '][last_name]'"  :value="author.last_name" />
                                    <input type="hidden" :name="'authors[' + i + '][is_submitter]'" :value="author.is_submitter ? '1' : '0'" />
                                </div>
                            </template>
                            <div class="space-y-1.5" x-show="authors.length > 0">
                                <template x-for="(author, index) in authors" :key="index">
                                    <div class="flex items-center justify-between gap-2 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="flex items-center justify-center h-7 w-7 rounded-full bg-primary-100 text-primary-700 text-xs font-semibold shrink-0"
                                                 x-text="author.first_name.charAt(0).toUpperCase() + author.last_name.charAt(0).toUpperCase()"></div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate" x-text="author.first_name + ' ' + author.last_name"></p>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeAuthor(index)" x-show="!author.is_submitter" class="text-gray-400 hover:text-red-500 shrink-0">
                                            <x-ui.icon name="close" size="sm" />
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <div class="flex items-end gap-2">
                                <input x-model="firstName" @keydown.enter.prevent="addAuthor()" type="text" placeholder="First name"
                                       class="flex-1 block border border-gray-300 px-3 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-primary-500 focus:outline-none" />
                                <input x-model="lastName"  @keydown.enter.prevent="addAuthor()" type="text" placeholder="Last name"
                                       class="flex-1 block border border-gray-300 px-3 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-primary-500 focus:outline-none" />
                                <button type="button" @click="addAuthor()"
                                        class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-md hover:bg-primary-100 shrink-0">
                                    <x-ui.icon name="plus" size="sm" />
                                    Add
                                </button>
                            </div>
                            <p class="text-xs text-gray-400">Your account remains the primary author. Add co-authors if needed.</p>
                            <x-form.error name="authors" />
                        </div>

                        {{-- Adviser typeahead (inline — initialized from Alpine paper state) --}}
                        <div
                            x-data="{
                                query:       paper.adviser ? (paper.adviser.first_name + ' ' + paper.adviser.last_name) : (paper.adviser_name ?? ''),
                                adviserId:   paper.adviser_id ?? null,
                                suggestions: [],
                                showSuggestions: false,
                                async fetchSuggestions() {
                                    const q = this.query.trim();
                                    if (q.length < 2) { this.suggestions = []; this.showSuggestions = false; return; }
                                    try {
                                        const res = await fetch('/users/search?q=' + encodeURIComponent(q) + '&role=adviser');
                                        if (!res.ok) { this.suggestions = []; return; }
                                        this.suggestions = await res.json();
                                        this.showSuggestions = this.suggestions.length > 0;
                                    } catch { this.suggestions = []; this.showSuggestions = false; }
                                },
                                selectSuggestion(user) {
                                    this.query = user.display; this.adviserId = user.id;
                                    this.suggestions = []; this.showSuggestions = false;
                                },
                                onInput() { this.adviserId = null; this.fetchSuggestions(); },
                                onBlur()  { setTimeout(() => { this.showSuggestions = false; }, 160); },
                            }"
                            class="space-y-1 relative"
                        >
                            <label class="block text-sm font-medium text-gray-700">Research Adviser</label>
                            <input type="hidden" name="adviser_id"   :value="adviserId ?? ''">
                            <input type="hidden" name="adviser_name" :value="query">
                            <input type="text" x-model="query" @input="onInput()" @blur="onBlur()"
                                   placeholder="Type to search, or enter a former adviser name"
                                   autocomplete="off"
                                   class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
                            <div x-show="showSuggestions" x-cloak
                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-md max-h-48 overflow-y-auto">
                                <template x-for="user in suggestions" :key="user.id">
                                    <button type="button" @mousedown.prevent="selectSuggestion(user)"
                                            class="w-full text-left px-3 py-2 text-sm text-gray-900 hover:bg-primary-50 hover:text-primary-700"
                                            x-text="user.display"></button>
                                </template>
                            </div>
                            <p class="text-xs text-gray-400">Select an active adviser, or type a former adviser's name.</p>
                            <x-form.error name="adviser_id" />
                        </div>
                        {{-- Current file display + intentional replace toggle --}}
                        <div class="space-y-1.5">
                            <label class="block text-sm font-medium text-gray-700">Manuscript (PDF / DOCX)</label>

                            {{-- Current file indicator (hidden once replace is triggered) --}}
                            <div x-show="!replaceFile" class="flex items-center justify-between gap-3 bg-gray-50 border border-gray-200 rounded-md px-3 py-2.5">
                                <div class="flex items-center gap-2 min-w-0">
                                    <x-ui.icon name="document" style="mini" size="md" class="text-red-400 shrink-0" />
                                    <span class="text-sm text-gray-700 truncate" x-text="paper.original_filename || 'Submitted manuscript'"></span>
                                    <a :href="paper.download_url" target="_blank"
                                       class="shrink-0 text-xs text-primary-600 hover:text-primary-800 hover:underline"
                                       x-on:click.stop>
                                        Verify download
                                    </a>
                                </div>
                                <button type="button" @click="replaceFile = true"
                                        class="shrink-0 text-xs font-medium text-amber-600 hover:text-amber-800">
                                    Replace file
                                </button>
                            </div>

                            {{-- Replacement input — only mounted when intentionally replacing --}}
                            <template x-if="replaceFile">
                                <div class="space-y-1.5">
                                    <x-form.file
                                        id="manuscript_edit"
                                        name="manuscript"
                                        hint="PDF or DOCX, maximum 20MB"
                                    />
                                    <button type="button" @click="replaceFile = false"
                                            class="text-xs text-gray-500 hover:text-gray-700 underline">
                                        Keep current file instead
                                    </button>
                                </div>
                            </template>
                            <x-form.error name="manuscript" />
                        </div>
                    </div>
                    <x-ui.modal-footer modalName="research-actions" submitLabel="Update and Resubmit" submitting="submitting" />
                </form>
                </div>
            </div>
        </x-ui.modal-actions>
    </template>
</x-ui.modal>
