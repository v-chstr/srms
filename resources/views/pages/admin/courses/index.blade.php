<x-layouts.app title="Course Management">

    <x-layouts.page-header title="Course Management" subtitle="Manage academic programs" />

    <div x-data="{
        courses: @js($courses->map(fn($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name, 'papers' => $c->research_papers_count ?? 0])),
        initialCourseId: @js(old('_context_course_id')),
        selected: null,
        init() {
            if (this.initialCourseId) {
                this.$nextTick(() => this.openCourse(this.initialCourseId));
            }
        },
        openCourse(id) {
            id = Number(id);
            const src = this.courses.find(c => c.id === id);
            if (!src) {
                return;
            }

            this.selected = { ...src };
            this.$dispatch('open-modal', 'course-actions');
        }
    }">

    {{-- Filter bar --}}
    <x-ui.filter-bar
        :action="route('admin.courses.index')"
        :clearHref="route('admin.courses.index')"
        :hasFilters="request()->filled('search')"
    >
        <input name="search" type="text" placeholder="Code or name" value="{{ request('search') }}"
               class="border border-gray-300 px-2.5 py-1.5 text-sm rounded-md focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none placeholder:text-gray-400 w-48" />
    </x-ui.filter-bar>

    {{-- Main 2-column: left = table, right = add form --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-start">

        {{-- Left: Courses table --}}
        <div class="lg:col-span-3">
            <x-table.wrapper>
                <x-slot:head>
                    <x-table.heading class="w-24">Code</x-table.heading>
                    <x-table.heading>Name</x-table.heading>
                    <x-table.heading class="w-20">Papers</x-table.heading>
                    <x-table.heading class="w-24 text-right"></x-table.heading>
                </x-slot:head>

                @forelse($courses as $course)
                    <tr>
                        <x-table.cell class="font-medium text-gray-900">{{ $course->code }}</x-table.cell>
                        <x-table.cell>{{ $course->name }}</x-table.cell>
                        <x-table.cell class="tabular-nums text-gray-500">{{ $course->research_papers_count }}</x-table.cell>
                        <x-table.cell>
                            <x-ui.button type="button" variant="secondary" size="sm"
                                x-on:click="openCourse({{ $course->id }})">
                                Manage
                            </x-ui.button>
                        </x-table.cell>
                    </tr>
                @empty
                    <x-table.empty colspan="4" message="No courses found." />
                @endforelse
            </x-table.wrapper>
        </div>

        {{-- Right: Add Course form --}}
        <div class="lg:col-span-1">
            <x-ui.card title="Add Course">
                <form method="POST" action="{{ route('admin.courses.store') }}" class="space-y-4">
                    @csrf

                    <x-form.input
                        name="code"
                        label="Course Code"
                        placeholder="e.g. IT"
                        :value="old('code')"
                        :required="true"
                    />
                    <x-form.input
                        name="name"
                        label="Course Name"
                        placeholder="e.g. Information Technology"
                        :value="old('name')"
                        :required="true"
                    />

                    <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                        Add Course
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>

    </div>

    {{-- Single course actions modal --}}
    <x-ui.modal name="course-actions" maxWidth="md">
        <template x-if="selected">
            <x-ui.modal-actions modalName="course-actions" :initial-mode="old('_context_mode', 'view')">
                <x-slot:title>
                    <h2 class="text-sm font-semibold text-white leading-snug truncate" x-text="selected.code"></h2>
                    <p class="mt-0.5 text-xs text-white/70 truncate" x-text="selected.name"></p>
                </x-slot:title>
                <x-slot:tabs>
                    <x-ui.modal-tab key="view" label="View" />
                    <x-ui.modal-tab key="edit" label="Manage" />
                </x-slot:tabs>

                {{-- View panel --}}
                <div x-show="mode === 'view'" x-cloak class="px-5 py-4">
                    <div class="divide-y divide-gray-100">
                        <x-ui.detail-field label="Code">
                            <span class="font-semibold text-gray-900" x-text="selected.code"></span>
                        </x-ui.detail-field>
                        <x-ui.detail-field label="Name">
                            <span class="font-medium text-gray-900" x-text="selected.name"></span>
                        </x-ui.detail-field>
                        <x-ui.detail-field label="Papers">
                            <span class="tabular-nums" x-text="selected.papers + ' paper(s)'"></span>
                        </x-ui.detail-field>
                    </div>
                </div>

                {{-- Edit panel --}}
                <div x-show="mode === 'edit'" x-cloak>
                    <form method="POST" :action="'{{ url('admin/courses') }}/' + selected.id">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_context_course_id" :value="selected.id">
                        <input type="hidden" name="_context_mode" value="edit">
                        <div class="px-6 pt-5 pb-0 space-y-4">
                            <x-form.input name="code" label="Course Code" :required="true" x-model="selected.code" />
                            <x-form.input name="name" label="Course Name" :required="true" x-model="selected.name" />
                        </div>
                        <x-ui.modal-footer modalName="course-actions" submitLabel="Save Changes" />
                    </form>
                </div>
            </x-ui.modal-actions>
        </template>
    </x-ui.modal>

    </div>

</x-layouts.app>
