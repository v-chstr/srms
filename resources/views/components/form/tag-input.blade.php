@props([
    'label'       => null,
    'name'        => 'keywords',
    'placeholder' => 'Type a keyword and press Enter',
    'required'    => false,
    'value'       => [],
    'max'         => 10,
    'suggestions' => [],
])

<div
    x-data="{
        tags: @js(old($name, $value) ?? []),
        suggestions: @js($suggestions),
        input: '',
        max: {{ $max }},
        filteredSuggestions() {
            let query = this.input.trim().toLowerCase();
            let available = this.suggestions.filter(s => ! this.tags.includes(s));

            if (!query) {
                return [];
            }

            return available.filter(s => s.toLowerCase().includes(query));
        },
        selectSuggestion(tag) {
            if (this.tags.length < this.max && ! this.tags.includes(tag)) {
                this.tags.push(tag);
            }
            this.input = '';
            this.$nextTick(() => this.$refs.tagInput?.focus());
        },
        addTag() {
            let tag = this.input.trim().toLowerCase();
            if (tag && !this.tags.includes(tag) && this.tags.length < this.max) {
                this.tags.push(tag);
            }
            this.input = '';
        },
        removeTag(index) {
            this.tags.splice(index, 1);
        },
        handleKeydown(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.addTag();
            }
            if (e.key === 'Backspace' && this.input === '' && this.tags.length > 0) {
                this.tags.pop();
            }
        }
    }"
    class="space-y-1"
>
    @if($label)
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>
    @endif

    {{-- Hidden inputs for form submission --}}
    <template x-for="(tag, i) in tags" :key="i">
        <input type="hidden" :name="'{{ $name }}[' + i + ']'" :value="tag" />
    </template>

    {{-- Tag display + input --}}
    <div class="flex flex-wrap items-center gap-1.5 border border-gray-300 rounded-md px-2 py-1.5 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 min-h-[38px] bg-white">
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center gap-1 bg-primary-50 text-primary-700 text-xs font-medium px-2 py-0.5 rounded-md">
                <span x-text="tag"></span>
                <button type="button" @click="removeTag(index)" class="text-primary-400 hover:text-primary-700">
                    <x-ui.icon name="close" size="2xs" />
                </button>
            </span>
        </template>

        <input
            x-ref="tagInput"
            x-model="input"
            x-show="tags.length < max"
            @keydown="handleKeydown($event)"
            type="text"
            placeholder="{{ $placeholder }}"
            class="flex-1 min-w-[120px] border-0 p-0 text-sm focus:ring-0 focus:outline-none bg-transparent"
        />
    </div>

    <p class="text-xs text-gray-400" x-show="tags.length > 0">
        <span x-text="tags.length"></span>/<span>{{ $max }}</span> keywords selected
    </p>

    {{-- Suggestion panel --}}
    <template x-if="suggestions.length > 0">
        <div class="space-y-1">

            <template x-if="filteredSuggestions().length > 0">
                <div class="max-h-28 overflow-y-auto rounded-md border border-gray-200 bg-gray-50 p-1.5">
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="s in filteredSuggestions()" :key="s">
                            <button type="button"
                                    @click="selectSuggestion(s)"
                                    class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-md border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors">
                                <x-ui.icon name="plus" size="2xs" />
                                <span x-text="s"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <p class="text-xs text-gray-400" x-show="input.trim() !== '' && filteredSuggestions().length === 0">
                No matching suggestions.
            </p>
        </div>
    </template>

    <x-form.error :name="$name" />
</div>
