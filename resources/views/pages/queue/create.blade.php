<x-layouts.app title="Create Queue">

    <x-layouts.page-header title="Create Queue" subtitle="Build a randomized student presentation queue">
        <x-slot:actions>
            <x-ui.button :href="route('queue.index')" variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4 mr-1" />
                Back
            </x-ui.button>
        </x-slot:actions>
    </x-layouts.page-header>

    <div x-data="{
        courseId: null,
        title: '',
        groupSize: 2,
        availableStudents: [],
        groups: [],
        pendingGroup: [],
        loading: false,
        studentSearch: '',

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

        fetchStudents() {
            this.availableStudents = [];
            this.groups = [];
            this.pendingGroup = [];
            if (!this.courseId) return;
            this.loading = true;
            fetch(`/courses/${this.courseId}/students`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => { this.availableStudents = data; this.loading = false; })
            .catch(() => { this.loading = false; });
        },

        addToPending(student) {
            if (this.pendingGroup.find(m => m.id === student.id)) return;
            this.pendingGroup.push(student);
            if (this.pendingGroup.length >= this.groupSize) {
                this.commitGroup();
            }
        },

        commitGroup() {
            if (this.pendingGroup.length === 0) return;
            this.groups.push({ id: Date.now(), members: [...this.pendingGroup] });
            this.pendingGroup = [];
        },

        removeFromPending(id) {
            this.pendingGroup = this.pendingGroup.filter(m => m.id !== id);
        },

        removeFromGroup(groupId, studentId) {
            const g = this.groups.find(g => g.id === groupId);
            if (!g) return;
            g.members = g.members.filter(m => m.id !== studentId);
            if (g.members.length === 0) {
                this.groups = this.groups.filter(g => g.id !== groupId);
            }
        },

        randomize() {
            for (let i = this.groups.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [this.groups[i], this.groups[j]] = [this.groups[j], this.groups[i]];
            }
        },

        get groupsJson() {
            return JSON.stringify(this.groups.map(g => ({ members: g.members.map(m => m.id) })));
        },

        get canSubmit() {
            return this.title.trim() !== ''
                && this.courseId
                && this.groups.length > 0
                && this.pendingGroup.length === 0;
        },

        submitAttempted: false,

        initFromErrors() {
            @if($errors->any())
                this.errors = @js($errors->toArray());
            @endif
        }
    }" x-init="initFromErrors()">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

        {{-- LEFT: Configuration + students --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Queue details --}}
            <x-ui.card title="Queue Details">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Queue Title <span class="text-red-500">*</span></label>
                        <input
                            x-model="title"
                            type="text"
                            placeholder="e.g. IT Capstone Defense Batch 1"
                            maxlength="150"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                        <x-form.error name="title" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Course <span class="text-red-500">*</span></label>
                        <select
                            x-model="courseId"
                            @change="fetchStudents()"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">Select course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->displayCode() }} | {{ $course->name }}</option>
                            @endforeach
                        </select>
                        <x-form.error name="course_id" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Students per group</label>
                        <div class="flex gap-2">
                            <template x-for="n in [1, 2, 3]" :key="n">
                                <button
                                    type="button"
                                    @click="groupSize = n"
                                    :class="groupSize === n
                                        ? 'bg-primary-600 text-white border-primary-600'
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="flex-1 rounded-md border px-3 py-2 text-sm font-medium transition-colors"
                                    x-text="n === 1 ? 'Solo' : n + ' students'"
                                ></button>
                            </template>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Students list --}}
            <x-ui.card title="Students">
                {{-- Loading state --}}
                <div x-show="loading" class="py-6 text-center text-sm text-gray-400">
                    <x-ui.icon name="arrow-path" class="h-5 w-5 animate-spin mx-auto mb-1" />
                    Loading students…
                </div>

                {{-- No course selected --}}
                <div x-show="!loading && !courseId" class="py-6 text-center text-sm text-gray-400">
                    Select a course to see available students.
                </div>

                {{-- No students --}}
                <div x-show="!loading && courseId && availableStudents.length === 0" class="py-6 text-center text-sm text-gray-400">
                    No active students found for this course.
                </div>

                {{-- Student list --}}
                <div x-show="!loading && availableStudents.length > 0" class="space-y-2">
                    {{-- Search --}}
                    <div class="pb-3 border-b border-gray-100">
                        <input
                            x-model="studentSearch"
                            type="text"
                            placeholder="Search students…"
                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-1.5"
                        />
                    </div>

                    {{-- Ungrouped students with Add buttons --}}
                    <template x-if="ungroupedStudents.length > 0">
                        <div class="space-y-1 max-h-72 overflow-y-auto pr-1">
                            <template x-for="student in ungroupedStudents" :key="student.id">
                                <div class="flex items-center gap-2 rounded-md px-2 py-1.5 hover:bg-gray-50 group">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-700"
                                         x-text="student.first_name.charAt(0).toUpperCase() + student.last_name.charAt(0).toUpperCase()">
                                    </div>
                                    <span class="flex-1 text-sm text-gray-700 min-w-0 truncate"
                                          x-text="student.last_name + ', ' + student.first_name"></span>
                                    <button
                                        type="button"
                                        @click="addToPending(student)"
                                        class="shrink-0 inline-flex items-center gap-1 rounded-md bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-primary-700 transition-colors"
                                    >
                                        <x-ui.icon name="plus" class="h-3 w-3" />
                                        Add
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- No search results --}}
                    <template x-if="ungroupedStudents.length === 0 && studentSearch !== ''">
                        <p class="py-3 text-center text-xs text-gray-400">No students match your search.</p>
                    </template>

                    {{-- All grouped message --}}
                    <template x-if="availableStudents.length > 0 && ungroupedStudents.length === 0 && studentSearch === '' && groups.length > 0">
                        <p class="py-3 text-center text-xs text-green-600 font-medium">All students are grouped!</p>
                    </template>
                </div>
            </x-ui.card>

        </div>

        {{-- RIGHT: Groups builder --}}
        <div class="lg:col-span-3 space-y-4">

            {{-- Pending group (being built) --}}
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
                    <x-ui.button type="button" size="sm" variant="secondary" @click="commitGroup()">
                        Save as group
                    </x-ui.button>
                </x-ui.card>
            </template>

            {{-- No groups yet --}}
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
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-semibold text-gray-500"
                           x-text="`${groups.length} group${groups.length === 1 ? '' : 's'}`"></p>
                        <x-ui.button type="button" size="sm" @click="randomize()" x-show="groups.length > 1">
                            <x-ui.icon name="arrows-right-left" class="h-3.5 w-3.5 mr-1" />
                            Randomize Queue
                        </x-ui.button>
                    </div>

                    <template x-for="(group, idx) in groups" :key="group.id">
                        <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600"
                                          x-text="idx + 1"></span>
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

            {{-- Validation checklist (visible after first failed submit attempt) --}}
            <div x-show="submitAttempted && !canSubmit"
                 x-transition
                 class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-xs text-amber-800 space-y-1">
                <p class="font-semibold">Complete the following to create the queue:</p>
                <p x-show="!title.trim()">• Enter a queue title</p>
                <p x-show="!courseId">• Select a course</p>
                <p x-show="courseId && groups.length === 0 && pendingGroup.length === 0">• Add at least one group</p>
                <p x-show="pendingGroup.length > 0">• Save or discard the group in progress</p>
            </div>

            {{-- Hidden form (always present) --}}
            <form id="queue-create-form" method="POST" action="{{ route('queue.store') }}">
                @csrf
                <input type="hidden" name="title" />
                <input type="hidden" name="course_id" />
                <input type="hidden" name="groups" />
            </form>

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button
                    type="button"
                    @click="
                        submitAttempted = true;
                        if (!canSubmit) return;
                        const f = document.getElementById('queue-create-form');
                        f.querySelector('[name=title]').value = title;
                        f.querySelector('[name=course_id]').value = courseId;
                        f.querySelector('[name=groups]').value = groupsJson;
                        f.submit();
                    "
                >
                    <x-ui.icon name="check" class="h-4 w-4 mr-1.5" />
                    Create Queue
                </x-ui.button>
                <x-ui.button :href="route('queue.index')" variant="ghost">Cancel</x-ui.button>
            </div>

        </div>
    </div>

    </div>{{-- end x-data --}}

</x-layouts.app>
