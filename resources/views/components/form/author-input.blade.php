@props([
    'label'    => 'Authors',
    'name'     => 'authors',
    'required' => false,
    'value'    => [],
    'submitter' => null,
])

@php
    // Submitter is auto-added as first author — passed from the modal.
    $submitterData = $submitter ? [
        'first_name' => $submitter->first_name,
        'last_name'  => $submitter->last_name,
        'is_submitter' => true,
    ] : null;
@endphp

<div
    x-data="{
        authors: @js(old($name) ?? $value),
        firstName: '',
        lastName: '',
        submitter: @js($submitterData),
        init() {
            // Auto-add submitter as first author if not already present
            if (this.submitter && this.authors.length === 0) {
                this.authors.push({
                    first_name: this.submitter.first_name,
                    last_name: this.submitter.last_name,
                    is_submitter: true
                });
            }
        },
        addAuthor() {
            let fn = this.firstName.trim();
            let ln = this.lastName.trim();
            if (fn && ln) {
                this.authors.push({ first_name: fn, last_name: ln, is_submitter: false });
                this.firstName = '';
                this.lastName = '';
                this.suggestions = [];
                this.showSuggestions = false;
                this.$refs.firstNameInput.focus();
            }
        },
        removeAuthor(index) {
            if (this.authors[index]?.is_submitter) return;
            this.authors.splice(index, 1);
        },
        handleKeydown(e) {
            if (e.key === 'Escape') { this.showSuggestions = false; return; }
            if (e.key === 'Enter') {
                e.preventDefault();
                this.addAuthor();
            }
        },
        suggestions: [],
        showSuggestions: false,
        async fetchSuggestions() {
            const q = (this.firstName + ' ' + this.lastName).trim();
            if (q.replace(/\s/g, '').length < 2) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }
            try {
                const res = await fetch('/users/search?q=' + encodeURIComponent(q));
                if (!res.ok) { this.suggestions = []; return; }
                this.suggestions = await res.json();
                this.showSuggestions = this.suggestions.length > 0;
            } catch {
                this.suggestions = [];
                this.showSuggestions = false;
            }
        },
        selectSuggestion(user) {
            this.firstName = user.first_name;
            this.lastName  = user.last_name;
            this.suggestions = [];
            this.showSuggestions = false;
            this.$refs.firstNameInput.focus();
        },
    }"
    class="space-y-1"
>
    @if($label)
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    {{-- Hidden inputs for form submission --}}
    <template x-for="(author, i) in authors" :key="i">
        <div>
            <input type="hidden" :name="'{{ $name }}[' + i + '][first_name]'" :value="author.first_name" />
            <input type="hidden" :name="'{{ $name }}[' + i + '][last_name]'" :value="author.last_name" />
            <input type="hidden" :name="'{{ $name }}[' + i + '][is_submitter]'" :value="author.is_submitter ? '1' : '0'" />
        </div>
    </template>

    {{-- Author chips --}}
    <div class="space-y-2" x-show="authors.length > 0">
        <template x-for="(author, index) in authors" :key="index">
            <div class="flex items-center justify-between gap-2 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="flex items-center justify-center h-7 w-7 rounded-full bg-primary-100 text-primary-700 text-xs font-semibold shrink-0" x-text="author.first_name.charAt(0).toUpperCase() + author.last_name.charAt(0).toUpperCase()"></div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="author.first_name + ' ' + author.last_name"></p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="removeAuthor(index)"
                    x-show="!author.is_submitter"
                    class="text-gray-400 hover:text-red-500 shrink-0"
                >
                    <x-ui.icon name="close" size="sm" />
                </button>
            </div>
        </template>
    </div>

    {{-- Add author row + autocomplete --}}
    <div class="relative" x-on:click.outside="showSuggestions = false">
        <div class="flex items-end gap-2">
            <div class="flex-1">
                <input
                    x-ref="firstNameInput"
                    x-model="firstName"
                    @keydown="handleKeydown($event)"
                    @input.debounce.300ms="fetchSuggestions()"
                    type="text"
                    placeholder="First name"
                    class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                />
            </div>
            <div class="flex-1">
                <input
                    x-model="lastName"
                    @keydown="handleKeydown($event)"
                    @input.debounce.300ms="fetchSuggestions()"
                    type="text"
                    placeholder="Last name"
                    class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                />
            </div>
            <button
                type="button"
                @click="addAuthor()"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-md hover:bg-primary-100 shrink-0"
            >
                <x-ui.icon name="plus" size="sm" />
                Add
            </button>
        </div>

        {{-- Suggestions dropdown --}}
        <div
            x-show="showSuggestions"
            x-cloak
            class="absolute left-0 right-12 top-full mt-1 bg-white border border-gray-200 rounded-md shadow-sm z-20 overflow-hidden"
        >
            <p class="px-3 pt-2 pb-1 text-xs font-medium text-gray-400">SRMS users</p>
            <template x-for="user in suggestions" :key="user.id">
                <button
                    type="button"
                    @click="selectSuggestion(user)"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-gray-50 border-t border-gray-100 first:border-0"
                >
                    <span class="flex items-center justify-center h-6 w-6 rounded-full bg-primary-50 text-primary-700 text-xs font-semibold shrink-0"
                          x-text="user.first_name.charAt(0).toUpperCase() + user.last_name.charAt(0).toUpperCase()"></span>
                    <span class="text-gray-800" x-text="user.display"></span>
                </button>
            </template>
        </div>
    </div>

    @if($submitter)
        <p class="text-xs text-gray-400">Your account is added automatically as the primary author. Add co-authors if needed.</p>
    @else
        <p class="text-xs text-gray-400">Add at least one author before submitting.</p>
    @endif

    <x-form.error :name="$name" />
</div>
