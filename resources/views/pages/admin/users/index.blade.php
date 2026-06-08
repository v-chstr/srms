<x-layouts.app title="User Management">

    <x-layouts.page-header title="User Management" subtitle="View and manage system accounts" />

    @php
        $serializeUser = fn ($u) => [
            'id'            => $u->id,
            'first_name'    => $u->first_name,
            'last_name'     => $u->last_name,
            'email'         => $u->email,
            'role'          => $u->role,
            'original_role' => $u->role,
            'status'        => $u->status,
            'is_adviser'    => (bool) $u->is_adviser,
            'course_id'     => $u->course_id,
            'course_name'   => $u->course->name ?? 'N/A',
            'created_at'    => $u->created_at->srmsDate(),
        ];

        $allUsersJson = $users->map($serializeUser)->merge($pendingUsers->map($serializeUser));
    @endphp

    <div x-data="{
        allUsers: @js($allUsersJson),
        adviserPapersMap: @js($adviserPapersMap),
        initialUserId: @js(old('_context_user_id')),
        selected: null,
        selectedPapers: [],
        init() {
            if (this.initialUserId) {
                this.$nextTick(() => this.openUser(this.initialUserId));
            }
        },
        openUser(id) {
            id = Number(id);
            const src = this.allUsers.find(u => u.id === id);
            if (!src) {
                return;
            }

            this.selected = { ...src };
            this.selectedPapers = this.adviserPapersMap[id] || [];
            this.$dispatch('open-modal', 'user-actions');
        }
    }">

    {{-- Filter bar --}}
    <x-ui.filter-bar
        :action="route('admin.users.index')"
        :clearHref="route('admin.users.index')"
        :hasFilters="request()->hasAny(['search', 'role'])"
    >
        <input name="search" type="text" placeholder="Name or email" value="{{ request('search') }}"
               class="border border-gray-300 px-2.5 py-1.5 text-sm rounded-md focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none placeholder:text-gray-400 w-48" />
        <select name="role" class="border border-gray-300 pl-2.5 pr-8 py-1.5 text-sm rounded-md bg-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none">
            <option value="">{{ config('ui.placeholders.all_roles') }}</option>
            <option value="admin" @selected(request('role') === 'admin')>Admin</option>
            <option value="adviser" @selected(request('role') === 'adviser')>Adviser</option>
            <option value="student" @selected(request('role') === 'student')>Student</option>
        </select>
    </x-ui.filter-bar>

    {{-- 2-column: left = users + pending, right = create panel --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-start">

        {{-- Active users table + Pending requests --}}
        <div class="lg:col-span-3 space-y-4">

            <x-table.wrapper :paginator="$users">
                <x-slot:head>
                    <x-table.heading>Name</x-table.heading>
                    <x-table.heading class="hidden sm:table-cell w-28">Role</x-table.heading>
                    <x-table.heading class="hidden md:table-cell w-24">Course</x-table.heading>
                    <x-table.heading class="w-28 text-right"></x-table.heading>
                </x-slot:head>

                @forelse($users as $user)
                    <tr>
                        <x-table.cell wrap>
                            <p class="text-sm font-semibold leading-snug text-gray-900">
                                {{ $user->first_name }} {{ $user->last_name }}
                                @if($user->role === 'admin' && $user->is_adviser)
                                    <x-ui.badge status="adviser" class="ml-1" />
                                @endif
                            </p>
                            <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ $user->email }}</p>
                        </x-table.cell>
                        <x-table.cell class="hidden sm:table-cell" nowrap><x-ui.badge :status="$user->role" /></x-table.cell>
                        <x-table.cell class="hidden md:table-cell" nowrap>{{ $user->course?->displayCode() ?? 'N/A' }}</x-table.cell>
                        <x-table.cell class="text-right" nowrap>
                            <x-ui.button type="button" variant="secondary" size="sm"
                                x-on:click="openUser({{ $user->id }})">Manage</x-ui.button>
                        </x-table.cell>
                    </tr>
                @empty
                    <x-table.empty colspan="4" message="No users found." />
                @endforelse
            </x-table.wrapper>

            {{-- Pending requests --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-900">Pending account requests</h3>
                    @if($pendingUsers->isNotEmpty())
                        <span class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                            {{ $pendingUsers->count() }} pending
                        </span>
                    @endif
                </div>

                @if($pendingUsers->isEmpty())
                    <x-ui.empty-state message="No pending account requests." />
                @else
                    <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md">
                        <x-table.wrapper class="border-0 rounded-none shadow-none">
                            <x-slot:head>
                                <x-table.heading>Name</x-table.heading>
                                <x-table.heading class="w-28">Role</x-table.heading>
                                <x-table.heading class="hidden sm:table-cell w-24">Course</x-table.heading>
                                <x-table.heading class="w-28 text-right"></x-table.heading>
                            </x-slot:head>
                            @foreach($pendingUsers as $pUser)
                                <tr>
                                    <x-table.cell wrap>
                                        <p class="text-sm font-semibold leading-snug text-gray-900">{{ $pUser->first_name }} {{ $pUser->last_name }}</p>
                                        <p class="mt-0.5 text-xs leading-relaxed text-gray-500">{{ $pUser->email }}</p>
                                    </x-table.cell>
                                    <x-table.cell nowrap><x-ui.badge :status="$pUser->role" /></x-table.cell>
                                    <x-table.cell class="hidden sm:table-cell" nowrap>{{ $pUser->course?->displayCode() ?? 'N/A' }}</x-table.cell>
                                    <x-table.cell class="text-right" nowrap>
                                        <x-ui.button type="button" variant="secondary" size="sm"
                                            x-on:click="openUser({{ $pUser->id }})">Review</x-ui.button>
                                    </x-table.cell>
                                </tr>
                            @endforeach
                        </x-table.wrapper>
                    </div>
                @endif
            </div>

        </div>

        {{-- Create user sidebar --}}
        <div class="lg:col-span-1">
            <x-admin.user-create-panel :courses="$courses" />
        </div>

    </div>

    {{-- User actions modal --}}
    <x-admin.user-actions-modal :courses="$courses" :initial-mode="old('_context_mode', 'view')" />

    </div>

</x-layouts.app>
