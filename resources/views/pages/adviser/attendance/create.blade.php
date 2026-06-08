<x-layouts.app title="New Attendance Section">

    <x-layouts.page-header title="New Attendance Section" subtitle="Set up groups and assign a timeline sheet">
        <x-slot:actions>
            <x-ui.button :href="route('adviser.attendance.index')" variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4 mr-1" />
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-layouts.page-header>

    <div x-data="{
        step: 1,

        /* ── Step 1: Section Details ── */
        title: '',
        courseId: null,

        /* ── Step 2: Student Groups ── */
        availableStudents: [],
        groups: [],
        pendingGroup: [],
        groupSize: 2,
        studentSearch: '',
        loading: false,

        /* ── Step 3: Shared Advisers ── */
        adviserSearch: '',
        adviserSuggestions: [],
        showAdviserSuggestions: false,
        sharedAdvisers: [],

        /* ── Computed ── */
        get allGroupedIds() {
            const inGroups  = this.groups.flatMap(g => g.members.map(m => m.id));
            const inPending = this.pendingGroup.map(m => m.id);
            return new Set([...inGroups, ...inPending]);
        },
        get ungroupedStudents() {
            const grouped = this.allGroupedIds;
            const q = this.studentSearch.toLowerCase();
            return this.availableStudents.filter(s =>
                !grouped.has(s.id) &&
                (!q || s.full_name.toLowerCase().includes(q))
            );
        },
        get canProceedStep1() {
            return this.title.trim() !== '' && this.courseId;
        },
        get canProceedStep2() {
            return this.groups.length > 0 && this.pendingGroup.length === 0;
        },
        get groupsJson() {
            return JSON.stringify(this.groups.map(g => ({ members: g.members.map(m => m.id) })));
        },
        get advisersJson() {
            return JSON.stringify(this.sharedAdvisers.map(a => a.id));
        },

        /* ── Student fetch ── */
        fetchStudents() {
            this.availableStudents = [];
            this.groups = [];
            this.pendingGroup = [];
            if (!this.courseId) return;
            this.loading = true;
            fetch(`/courses/${this.courseId}/students`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => { this.availableStudents = data; this.loading = false; })
                .catch(() => { this.loading = false; });
        },

        /* ── Group builder ── */
        addToPending(student) {
            if (this.pendingGroup.find(m => m.id === student.id)) return;
            this.pendingGroup.push(student);
            if (this.pendingGroup.length >= this.groupSize) this.commitGroup();
        },
        commitGroup() {
            if (this.pendingGroup.length === 0) return;
            this.groups.push({ id: Date.now(), members: [...this.pendingGroup] });
            this.pendingGroup = [];
        },
        removeFromPending(id) { this.pendingGroup = this.pendingGroup.filter(m => m.id !== id); },
        removeFromGroup(groupId, studentId) {
            const g = this.groups.find(g => g.id === groupId);
            if (!g) return;
            g.members = g.members.filter(m => m.id !== studentId);
            if (g.members.length === 0) this.groups = this.groups.filter(g => g.id !== groupId);
        },

        /* ── Adviser search ── */
        async fetchAdviserSuggestions() {
            const q = this.adviserSearch.trim();
            if (q.length < 2) { this.adviserSuggestions = []; this.showAdviserSuggestions = false; return; }
            try {
                const res = await fetch('/users/search?role=adviser&q=' + encodeURIComponent(q));
                if (!res.ok) { this.adviserSuggestions = []; return; }
                const data = await res.json();
                // Exclude already-added advisers
                const addedIds = new Set(this.sharedAdvisers.map(a => a.id));
                this.adviserSuggestions = data.filter(u => !addedIds.has(u.id));
                this.showAdviserSuggestions = this.adviserSuggestions.length > 0;
            } catch { this.adviserSuggestions = []; this.showAdviserSuggestions = false; }
        },
        addAdviser(adviser) {
            if (this.sharedAdvisers.length >= 5) return;
            if (this.sharedAdvisers.find(a => a.id === adviser.id)) return;
            this.sharedAdvisers.push(adviser);
            this.adviserSearch = '';
            this.adviserSuggestions = [];
            this.showAdviserSuggestions = false;
        },
        removeAdviser(id) { this.sharedAdvisers = this.sharedAdvisers.filter(a => a.id !== id); },

        /* ── Submit ── */
        submitAttempted: false,
        submit() {
            this.submitAttempted = true;
            if (!this.canProceedStep1 || !this.canProceedStep2) return;
            const f = document.getElementById('attendance-create-form');
            f.querySelector('[name=title]').value     = this.title;
            f.querySelector('[name=course_id]').value = this.courseId;
            f.querySelector('[name=groups]').value    = this.groupsJson;
            f.querySelector('[name=advisers]').value  = this.advisersJson;
            f.submit();
        },
    }">

    {{-- ────────────── Step Indicators ────────────── --}}
    <div class="mb-8 flex items-center gap-0">
        @foreach([['1', 'Section Details'], ['2', 'Student Groups'], ['3', 'Share']] as [$num, $label])
            <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                <div class="flex items-center gap-2 shrink-0">
                    <span :class="step >= {{ $num }}
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-100 text-gray-400'"
                        class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-colors">
                        {{ $num }}
                    </span>
                    <span :class="step >= {{ $num }} ? 'text-gray-900 font-semibold' : 'text-gray-400'"
                          class="text-sm transition-colors hidden sm:block">{{ $label }}</span>
                </div>
                @if(!$loop->last)
                    <div class="flex-1 mx-3 h-px bg-gray-200 mt-px"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- STEP 1 — Section Details                --}}
    {{-- ════════════════════════════════════════ --}}
    <div x-show="step === 1" x-transition>
        <div class="max-w-xl space-y-6">
            <x-ui.card title="Section Details">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section Name <span class="text-red-500">*</span></label>
                        <input x-model="title" type="text" maxlength="150"
                               placeholder="e.g. IT Capstone 2, Batch A"
                               class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                        @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course <span class="text-red-500">*</span></label>
                        <select x-model="courseId" @change="fetchStudents()"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">Select course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->displayCode() }} | {{ $course->name }}</option>
                            @endforeach
                        </select>
                        @error('course_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-ui.card>

            <div x-show="submitAttempted && !canProceedStep1" class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800 space-y-1">
                <p class="font-semibold">Complete the following:</p>
                <p x-show="!title.trim()">Enter a section name.</p>
                <p x-show="!courseId">Select a course.</p>
            </div>

            <div class="flex gap-3">
                <x-ui.button type="button" @click="submitAttempted = true; if (canProceedStep1) step = 2">
                    Continue
                    <x-ui.icon name="arrow-right" class="h-4 w-4 ml-1.5" />
                </x-ui.button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- STEP 2 — Student Groups                 --}}
    {{-- ════════════════════════════════════════ --}}
    <div x-show="step === 2" x-transition x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

            {{-- LEFT: Student Picker --}}
            <div class="lg:col-span-2 space-y-4">
                <x-ui.card title="Students per Group">
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <template x-for="n in [1, 2, 3]" :key="n">
                                <button type="button" @click="groupSize = n"
                                    :class="groupSize === n ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="flex-1 rounded-md border px-3 py-2 text-sm font-medium transition-colors"
                                    x-text="n === 1 ? 'Solo' : n + ' students'">
                                </button>
                            </template>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Available Students">
                    <div x-show="loading" class="py-6 text-center text-sm text-gray-400">
                        <x-ui.icon name="arrow-path" class="h-5 w-5 animate-spin mx-auto mb-1" />
                        Loading students…
                    </div>
                    <div x-show="!loading && availableStudents.length === 0" class="py-6 text-center text-sm text-gray-400">
                        No active students found for this course.
                    </div>
                    <div x-show="!loading && availableStudents.length > 0" class="space-y-2">
                        <div class="pb-3 border-b border-gray-100">
                            <input x-model="studentSearch" type="text" placeholder="Search students…"
                                   class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                        </div>
                        <template x-if="ungroupedStudents.length > 0">
                            <div class="space-y-1 max-h-72 overflow-y-auto pr-1">
                                <template x-for="student in ungroupedStudents" :key="student.id">
                                    <div class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-gray-50 group">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-700"
                                             x-text="student.first_name.charAt(0).toUpperCase() + student.last_name.charAt(0).toUpperCase()">
                                        </div>
                                        <span class="flex-1 text-sm text-gray-700 truncate" x-text="student.last_name + ', ' + student.first_name"></span>
                                        <button type="button" @click="addToPending(student)"
                                                class="shrink-0 inline-flex items-center gap-1 rounded-md bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-primary-700 transition-colors">
                                            <x-ui.icon name="plus" class="h-3 w-3" />
                                            Add
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="ungroupedStudents.length === 0 && studentSearch !== ''">
                            <p class="py-3 text-center text-xs text-gray-400">No students match your search.</p>
                        </template>
                        <template x-if="availableStudents.length > 0 && ungroupedStudents.length === 0 && studentSearch === '' && groups.length > 0">
                            <p class="py-3 text-center text-xs text-green-600 font-medium">All students are grouped!</p>
                        </template>
                    </div>
                </x-ui.card>
            </div>

            {{-- RIGHT: Groups Builder --}}
            <div class="lg:col-span-3 space-y-4">

                {{-- Pending group --}}
                <template x-if="pendingGroup.length > 0">
                    <x-ui.card>
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-semibold text-primary-700">Building group…</p>
                            <span class="text-xs text-gray-400" x-text="`${pendingGroup.length} / ${groupSize} students`"></span>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <template x-for="s in pendingGroup" :key="s.id">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-800">
                                    <span x-text="s.first_name + ' ' + s.last_name"></span>
                                    <button type="button" @click="removeFromPending(s.id)" class="hover:text-primary-600">
                                        <x-ui.icon name="x-mark" class="h-3 w-3" />
                                    </button>
                                </span>
                            </template>
                        </div>
                        <x-ui.button type="button" size="sm" variant="secondary" @click="commitGroup()">Save as group</x-ui.button>
                    </x-ui.card>
                </template>

                {{-- Empty state --}}
                <template x-if="groups.length === 0 && pendingGroup.length === 0">
                    <x-ui.card>
                        <div class="py-10 text-center text-sm text-gray-400">
                            <x-ui.icon name="user-group" class="h-8 w-8 mx-auto mb-2 text-gray-300" />
                            <p>Click <strong>Add</strong> on students to build groups.</p>
                        </div>
                    </x-ui.card>
                </template>

                {{-- Groups list --}}
                <template x-if="groups.length > 0">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-gray-500" x-text="`${groups.length} group${groups.length === 1 ? '' : 's'}`"></p>
                        <template x-for="(group, idx) in groups" :key="group.id">
                            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="flex items-center gap-2">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600" x-text="idx + 1"></span>
                                        <span class="text-sm font-semibold text-gray-700" x-text="`Group ${idx + 1}`"></span>
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="s in group.members" :key="s.id">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                            <span x-text="s.first_name + ' ' + s.last_name"></span>
                                            <button type="button" @click="removeFromGroup(group.id, s.id)" class="hover:text-red-500">
                                                <x-ui.icon name="x-mark" class="h-3 w-3" />
                                            </button>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                @error('groups')
                    <x-ui.alert type="error">{{ $message }}</x-ui.alert>
                @enderror

                <div x-show="submitAttempted && !canProceedStep2" class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800 space-y-1">
                    <p class="font-semibold">Complete the following:</p>
                    <p x-show="groups.length === 0 && pendingGroup.length === 0">Add at least one group.</p>
                    <p x-show="pendingGroup.length > 0">Save or discard the group in progress.</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-ui.button type="button" variant="secondary" @click="step = 1">
                        <x-ui.icon name="arrow-left" class="h-4 w-4 mr-1" />
                        Back
                    </x-ui.button>
                    <x-ui.button type="button" @click="submitAttempted = true; if (canProceedStep2) step = 3">
                        Continue
                        <x-ui.icon name="arrow-right" class="h-4 w-4 ml-1.5" />
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- STEP 3 — Share with Advisers            --}}
    {{-- ════════════════════════════════════════ --}}
    <div x-show="step === 3" x-transition x-cloak>
        <div class="max-w-xl space-y-6">
            <x-ui.card title="Share with Advisers">
                <p class="text-sm text-gray-500 mb-4">Optional. Shared advisers can view and edit this section's attendance records. Maximum 5 advisers.</p>

                {{-- Chips --}}
                <div class="space-y-2 mb-4" x-show="sharedAdvisers.length > 0">
                    <template x-for="adviser in sharedAdvisers" :key="adviser.id">
                        <div class="flex items-center justify-between gap-2 bg-gray-50 border border-gray-200 rounded-md px-3 py-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="flex items-center justify-center h-7 w-7 rounded-full bg-primary-100 text-primary-700 text-xs font-semibold shrink-0"
                                     x-text="adviser.first_name.charAt(0).toUpperCase() + adviser.last_name.charAt(0).toUpperCase()"></div>
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="adviser.first_name + ' ' + adviser.last_name"></p>
                            </div>
                            <button type="button" @click="removeAdviser(adviser.id)" class="text-gray-400 hover:text-red-500 shrink-0">
                                <x-ui.icon name="close" size="sm" />
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Search --}}
                <div class="relative" x-show="sharedAdvisers.length < 5" x-on:click.outside="showAdviserSuggestions = false">
                    <input x-model="adviserSearch"
                           @input.debounce.300ms="fetchAdviserSuggestions()"
                           type="text"
                           placeholder="Search advisers by name…"
                           class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md focus:border-primary-500 focus:ring-primary-500 focus:outline-none" />
                    <div x-show="showAdviserSuggestions" x-cloak
                         class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-md shadow-sm z-20 overflow-hidden">
                        <p class="px-3 pt-2 pb-1 text-xs font-medium text-gray-400">Advisers</p>
                        <template x-for="adviser in adviserSuggestions" :key="adviser.id">
                            <button type="button" @click="addAdviser(adviser)"
                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left hover:bg-gray-50 border-t border-gray-100 first:border-0">
                                <span class="flex items-center justify-center h-6 w-6 rounded-full bg-primary-50 text-primary-700 text-xs font-semibold shrink-0"
                                      x-text="adviser.first_name.charAt(0).toUpperCase() + adviser.last_name.charAt(0).toUpperCase()"></span>
                                <span class="text-gray-800" x-text="adviser.display"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <p x-show="sharedAdvisers.length >= 5" class="text-xs text-gray-400 mt-2">Maximum of 5 advisers reached.</p>
            </x-ui.card>

            @error('advisers')
                <x-ui.alert type="error">{{ $message }}</x-ui.alert>
            @enderror

            <div class="flex items-center gap-3">
                <x-ui.button type="button" variant="secondary" @click="step = 2">
                    <x-ui.icon name="arrow-left" class="h-4 w-4 mr-1" />
                    Back
                </x-ui.button>
                <x-ui.button type="button" @click="submit()">
                    <x-ui.icon name="check" class="h-4 w-4 mr-1.5" />
                    Create Section
                </x-ui.button>
                <x-ui.button :href="route('adviser.attendance.index')" variant="ghost">Cancel</x-ui.button>
            </div>
        </div>
    </div>

    {{-- Hidden form --}}
    <form id="attendance-create-form" method="POST" action="{{ route('adviser.attendance.store') }}">
        @csrf
        <input type="hidden" name="title" />
        <input type="hidden" name="course_id" />
        <input type="hidden" name="groups" />
        <input type="hidden" name="advisers" />
    </form>

    </div>{{-- end x-data --}}

</x-layouts.app>
