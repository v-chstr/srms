{{--
    Admin user actions modal (view / edit / approve-reject).
    Requires: Alpine `selected` user object from parent scope (openUser).

    Usage:
        <x-admin.user-actions-modal :courses="$courses" />
--}}
@props([
    'courses',
    'initialMode' => 'view',
])

<x-ui.modal name="user-actions" maxWidth="xl">
    <template x-if="selected">
        <x-ui.modal-actions modalName="user-actions" :initial-mode="$initialMode">
            <x-slot:title>
                <h2 class="text-sm font-semibold text-white leading-snug break-words"
                    x-text="selected.first_name + ' ' + selected.last_name"></h2>
                <p class="mt-0.5 text-xs text-white/70 break-words"
                   x-text="selected.email + ' | Joined ' + selected.created_at"></p>
            </x-slot:title>
            <x-slot:tabs>
                <x-ui.modal-tab key="view" label="View" />
                <template x-if="selected.status !== 'pending'">
                    <x-ui.modal-tab key="edit" label="Edit" />
                </template>
                <template x-if="selected.role === 'adviser' || (selected.role === 'admin' && selected.is_adviser)">
                    <x-ui.modal-tab key="papers" label="Papers" />
                </template>
                <template x-if="selected.status === 'pending'">
                    <x-ui.modal-tab key="actions" label="Actions" :danger="true" />
                </template>
            </x-slot:tabs>

            {{-- View panel --}}
            <div x-show="mode === 'view'" x-cloak class="px-5 py-4">
                <div class="divide-y divide-gray-100">
                    <x-ui.detail-field label="Name">
                        <span class="font-semibold text-gray-900" x-text="selected.first_name + ' ' + selected.last_name"></span>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Email">
                        <span class="text-gray-700 break-all" x-text="selected.email"></span>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Role">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-md"
                                  :class="{
                                      'bg-purple-100 text-purple-800': selected.role === 'admin',
                                      'bg-sky-100 text-sky-800': selected.role === 'adviser',
                                      'bg-indigo-100 text-indigo-800': selected.role === 'student'
                                  }"
                                  x-text="selected.role.charAt(0).toUpperCase() + selected.role.slice(1)"></span>
                            <template x-if="selected.role === 'admin' && selected.is_adviser">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-md bg-accent-100 text-accent-800">Adviser</span>
                            </template>
                        </div>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Course">
                        <span x-text="selected.course_name"></span>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Status">
                        <span :class="selected.status === 'pending' ? 'text-amber-700 font-medium' : 'text-gray-700'"
                              x-text="selected.status ? (selected.status.charAt(0).toUpperCase() + selected.status.slice(1)) : 'Active'"></span>
                    </x-ui.detail-field>
                    <x-ui.detail-field label="Joined">
                        <span class="tabular-nums" x-text="selected.created_at"></span>
                    </x-ui.detail-field>
                </div>
            </div>

            {{-- Edit panel (active users only) --}}
            <div x-show="mode === 'edit' && selected.status !== 'pending'" x-cloak>
                <form method="POST" :action="'{{ url('admin/users') }}/' + selected.id"
                      x-data="{
                          origFirst: selected.first_name,
                          origLast: selected.last_name,
                      }">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_context_user_id" :value="selected.id">
                    <input type="hidden" name="_context_mode" value="edit">
                    <div class="px-6 pt-5 pb-0 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <x-form.input name="first_name" label="First Name" :required="true"
                                maxlength="25" pattern="[A-Za-z\s]+" title="Letters and spaces only"
                                x-model="selected.first_name" />
                            <x-form.input name="last_name" label="Last Name" :required="true"
                                maxlength="25" pattern="[A-Za-z\s]+" title="Letters and spaces only"
                                x-model="selected.last_name" />
                        </div>
                        <x-form.input name="email" type="email" label="Email" :required="true"
                            maxlength="255" x-model="selected.email" />
                        <div class="space-y-1">
                            <template x-if="selected.original_role === 'student'">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Role</label>
                                    <input type="text" value="Student" readonly
                                           class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md shadow-sm bg-gray-50 cursor-not-allowed" />
                                    <input type="hidden" name="role" :value="selected.role" />
                                </div>
                            </template>
                            <template x-if="selected.original_role !== 'student'">
                                <x-form.select name="role" label="Role"
                                    :options="['admin' => 'Admin', 'adviser' => 'Adviser', 'student' => 'Student']"
                                    :required="true"
                                    x-model="selected.role" />
                            </template>
                        </div>
                        <x-form.select name="course_id" label="Course"
                            :options="$courses->pluck('name', 'id')->toArray()"
                            placeholder="None"
                            x-model="selected.course_id" />
                        <div x-show="selected.role === 'admin'" x-cloak>
                            <div class="bg-gray-50 border border-gray-200 rounded-md px-4 py-3">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_adviser" value="1"
                                           :checked="selected.is_adviser"
                                           class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Research Adviser</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Allow this admin to also review and advise student research papers.</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <x-ui.modal-footer modalName="user-actions" submitLabel="Save Changes" />
                </form>
            </div>

            {{-- Papers panel (advisers only) --}}
            <div x-show="mode === 'papers'" x-cloak class="px-6 py-4">
                <template x-if="selectedPapers.length === 0">
                    <div class="py-8 text-center">
                        <p class="text-sm text-gray-400">No papers assigned to this adviser.</p>
                    </div>
                </template>
                <template x-if="selectedPapers.length > 0">
                    <div>
                        <p class="text-xs text-gray-500 mb-3">
                            <span class="font-semibold tabular-nums" x-text="selectedPapers.length"></span> assigned paper(s)
                        </p>
                        <div class="max-h-64 overflow-y-auto rounded-md border border-gray-200">
                            <x-table.wrapper class="border-0 rounded-none shadow-none">
                                <x-slot:head>
                                    <x-table.heading>Title</x-table.heading>
                                    <x-table.heading class="w-28">Program</x-table.heading>
                                    <x-table.heading class="w-28">Status</x-table.heading>
                                </x-slot:head>
                                <template x-for="paper in selectedPapers" :key="paper.id">
                                    <tr>
                                        <x-table.cell class="font-medium text-gray-900" wrap>
                                            <span class="block" x-text="paper.title"></span>
                                        </x-table.cell>
                                        <x-table.cell class="text-gray-500" x-text="paper.course" nowrap></x-table.cell>
                                        <x-table.cell nowrap>
                                            <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-md"
                                                :class="{
                                                    'bg-indigo-50 text-indigo-700': paper.status === 'pending',
                                                    'bg-amber-50 text-amber-700': paper.status === 'revision',
                                                    'bg-emerald-50 text-emerald-700': paper.status === 'approved',
                                                }"
                                                x-text="paper.status.charAt(0).toUpperCase() + paper.status.slice(1)"></span>
                                        </x-table.cell>
                                    </tr>
                                </template>
                            </x-table.wrapper>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Actions panel (pending users only: approve or reject) --}}
            <div x-show="mode === 'actions' && selected.status === 'pending'" x-cloak class="p-5">
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-4 rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                        <div>
                            <p class="text-sm font-semibold text-emerald-800">Approve</p>
                            <p class="mt-1 text-xs text-emerald-700 leading-relaxed">Activate this account and allow the user to sign in.</p>
                        </div>
                        <form method="POST" :action="'{{ url('admin/users') }}/' + selected.id + '/approve'">
                            @csrf
                            <x-ui.button type="submit" variant="primary" size="sm" class="w-full justify-center">Approve Account</x-ui.button>
                        </form>
                    </div>
                    <div class="flex flex-col gap-4 rounded-lg border border-red-100 bg-red-50 p-4">
                        <div>
                            <p class="text-sm font-semibold text-red-800">Reject</p>
                            <p class="mt-1 text-xs text-red-700 leading-relaxed">Delete this request permanently. The user must re-register.</p>
                        </div>
                        <form method="POST" :action="'{{ url('admin/users') }}/' + selected.id"
                              x-on:submit.prevent="if (confirm('Reject and permanently delete this account request?')) $el.submit()">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" size="sm" class="w-full justify-center">Reject</x-ui.button>
                        </form>
                    </div>
                </div>
            </div>
        </x-ui.modal-actions>
    </template>
</x-ui.modal>
