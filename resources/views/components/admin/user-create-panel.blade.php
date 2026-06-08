{{--
    Admin create user sidebar panel.
    Shows a live email preview as the admin types first/last name.
    Email format: lastname.firstname@srms.site

    Usage: <x-admin.user-create-panel :courses="$courses" />
--}}
@props(['courses'])

<x-ui.card title="Create User">
    <div x-data="{
        createRole: '{{ old('role', '') }}',
    }">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf

            <x-form.input
                name="first_name"
                label="First Name"
                :value="old('first_name')"
                :required="true"
            />
            <x-form.input
                name="last_name"
                label="Last Name"
                :value="old('last_name')"
                :required="true"
            />

            <x-form.input
                name="email"
                type="email"
                label="Email"
                :value="old('email')"
                placeholder="user@example.com"
                :required="true"
            />

            <x-form.select
                name="role"
                label="Role"
                :options="['admin' => 'Admin', 'adviser' => 'Adviser', 'student' => 'Student']"
                :selected="old('role')"
                :required="true"
                placeholder="Select role"
                x-on:change="createRole = $event.target.value"
            />

            {{-- Course: shown for all roles, required only for students --}}
            <x-form.select
                name="course_id"
                label="Course"
                :options="$courses->pluck('name', 'id')->toArray()"
                :selected="old('course_id')"
                placeholder="Select a course"
                x-bind:required="createRole === 'student'"
            />

            <x-form.input
                name="password"
                type="password"
                label="Password"
                :required="true"
            />

            <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                Create Account
            </x-ui.button>
        </form>
    </div>
</x-ui.card>
