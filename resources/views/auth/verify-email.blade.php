<x-layouts.guest title="Verify Email">
    <div class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-md rounded-md border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h1 class="text-xl font-semibold text-gray-900">Verify your email</h1>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                    Before continuing, please check your email for a verification link. You can request another link if the first one did not arrive.
                </p>
            </div>

            @if(session('status') === 'verification-link-sent')
                <div class="mb-4">
                    <x-ui.alert type="success">A new verification link has been sent to your email address.</x-ui.alert>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-ui.button type="submit">Resend verification email</x-ui.button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button type="submit" variant="ghost">Sign out</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.guest>
