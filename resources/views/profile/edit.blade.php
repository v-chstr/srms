<x-layouts.app title="Profile Settings">

    <x-layouts.page-header title="Profile Settings" subtitle="Manage your personal information and account" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ── LEFT COLUMN ─────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Account overview --}}
            <x-ui.card title="Account Overview">
                    <div class="divide-y divide-gray-50">
                    <x-ui.detail-field label="Role">
                        @if($user->isAdmin() && $user->is_adviser)
                            <span class="text-sm font-medium text-gray-900">Admin / Research Adviser</span>
                        @else
                            <x-ui.badge :status="$user->role" />
                        @endif
                    </x-ui.detail-field>

                    @if(!($user->isAdmin() && $user->is_adviser))
                        <x-ui.detail-field label="Status">
                            <x-ui.badge :status="$user->status" />
                        </x-ui.detail-field>
                    @endif

                    @if($user->course)
                        <x-ui.detail-field label="Course">
                            <p class="text-sm font-medium text-gray-900">{{ $user->course->displayCode() }} / {{ $user->course->name }}</p>
                        </x-ui.detail-field>
                    @endif

                    <x-ui.detail-field label="Member since">
                        <p class="text-sm text-gray-700"><x-ui.date :value="$user->created_at" /></p>
                    </x-ui.detail-field>
                </div>
            </x-ui.card>

            {{-- Personal information --}}
            <x-ui.card title="Personal Information">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-2 gap-4">
                        <x-form.input
                            name="first_name"
                            label="First name"
                            :value="$user->first_name"
                            :required="true"
                            maxlength="25"
                            pattern="[A-Za-z\s]+"
                            title="Letters and spaces only"
                        />
                        <x-form.input
                            name="last_name"
                            label="Last name"
                            :value="$user->last_name"
                            :required="true"
                            maxlength="25"
                            pattern="[A-Za-z\s]+"
                            title="Letters and spaces only"
                        />
                    </div>

                    <x-form.input
                        name="email"
                        type="email"
                        label="Email address"
                        :value="$user->email"
                        :required="true"
                        maxlength="255"
                    />

                    <div class="flex items-center gap-3">
                        <x-ui.button type="submit">Save Changes</x-ui.button>

                        @if(session('status') === 'profile-updated')
                            <p class="text-sm text-emerald-600">Saved.</p>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            {{-- Delete account --}}
            <x-ui.card title="Delete Account">
                <p class="text-sm text-gray-600 mb-4">
                    Once your account is deleted, all of its data will be permanently removed. Please be certain.
                </p>
                <x-ui.button
                    variant="danger"
                    size="sm"
                    x-data=""
                    @click="$dispatch('open-modal', 'confirm-delete-account')"
                >
                    Delete Account
                </x-ui.button>
            </x-ui.card>

        </div>

        {{-- ── RIGHT COLUMN ────────────────────────────────────── --}}
        <div class="lg:col-span-1">
            <x-ui.card title="Change Password">
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    @foreach(['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $fieldName => $fieldLabel)
                    <div class="space-y-1" x-data="{ show: false }">
                        <label for="{{ $fieldName }}" class="block text-sm font-medium text-gray-700">{{ $fieldLabel }} *</label>
                        <div class="relative">
                            <input
                                id="{{ $fieldName }}"
                                name="{{ $fieldName }}"
                                :type="show ? 'text' : 'password'"
                                required
                                class="block w-full border border-gray-300 px-3 py-2 pr-9 text-sm rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none"
                            />
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400 hover:text-gray-600">
                                <x-ui.icon x-show="!show" name="eye" size="sm" />
                                <x-ui.icon x-show="show" x-cloak name="eye-slash" size="sm" />
                            </button>
                        </div>
                        <x-form.error :name="$fieldName" />
                    </div>
                    @endforeach

                    <div class="flex items-center gap-3">
                        <x-ui.button type="submit">Update Password</x-ui.button>

                        @if(session('status') === 'password-updated')
                            <p class="text-sm text-emerald-600">Updated.</p>
                        @endif
                    </div>
                </form>
            </x-ui.card>
        </div>

    </div>

    {{-- Delete confirmation modal --}}
    <x-ui.modal name="confirm-delete-account" title="Delete Account">
        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
            @csrf
            @method('DELETE')

            <p class="text-sm text-gray-700">
                Enter your password to confirm you want to permanently delete your account.
            </p>

            <x-form.input
                name="password"
                type="password"
                label="Password"
                :required="true"
            />

            <x-ui.modal-footer modalName="confirm-delete-account" submitLabel="Delete Account" submitVariant="danger" />
        </form>
    </x-ui.modal>

</x-layouts.app>
