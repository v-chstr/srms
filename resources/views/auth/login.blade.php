<x-layouts.guest title="{{ ($mode ?? 'login') === 'register' ? 'Create Account' : 'Sign In' }}">

    <div class="min-h-screen flex flex-col lg:flex-row" x-data="authPage()">

        {{-- =============================== --}}
        {{-- LEFT PANEL — Brand / Hero       --}}
        {{-- =============================== --}}
        <div class="relative overflow-hidden bg-primary-900 flex flex-col px-8 py-10 lg:w-1/2 lg:min-h-screen lg:px-12 lg:py-14">

            {{-- Subtle dot-grid texture --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 28px 28px;"></div>

            {{-- Depth gradient at bottom --}}
            <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-56 bg-gradient-to-t from-black/25 to-transparent"></div>

            <div class="relative flex flex-col flex-1 justify-between gap-10 lg:gap-0">

                {{-- SECTION 1 — Institutional identity --}}
                <div>
                    <div class="flex items-center gap-5 mb-5">
                        <img src="{{ asset('images/SPUP-final-logo.png') }}" alt="SPUP" class="h-16 w-16 object-contain">
                        <img src="{{ asset('images/spup-site.png') }}" alt="SITE" class="h-16 w-16 object-contain">
                    </div>
                    <h2 class="font-brand text-3xl text-white leading-snug mb-1">St. Paul University Philippines</h2>
                    <p class="text-sm text-primary-300">School of Information Technology and Engineering</p>
                </div>

                {{-- SECTION 2 — System hero + CTA --}}
                <div>
                    <h1 class="text-3xl font-bold text-white leading-snug mb-3">Student Research Management System</h1>
                    <p class="text-sm text-primary-200 leading-relaxed mb-8 max-w-xs">
                        Submit, review, and archive undergraduate research across all SITE programs.
                    </p>

                    {{-- Animated browse CTA --}}
                    <a href="{{ route('archive.index') }}"
                       class="inline-flex items-center gap-2 rounded-md bg-accent-400 px-5 py-3 text-sm font-semibold text-primary-900 animate-cta-pulse">
                        Browse SITE Research Papers Now
                    </a>
                </div>

                {{-- SECTION 3 — Programs + motto --}}
                <div class="pt-6 border-t border-primary-800">
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach(['BSIT', 'BSCpE', 'BSCE', 'BSLIS', 'BSEnSE'] as $prog)
                            <span class="rounded-md bg-primary-800 px-2.5 py-1 text-xs font-medium text-primary-200">{{ $prog }}</span>
                        @endforeach
                    </div>
                    <p class="text-xs italic text-primary-500">Caritas, Veritas, Scientia</p>
                </div>

            </div>
        </div>

        {{-- =============================== --}}
        {{-- RIGHT PANEL — Auth Forms        --}}
        {{-- =============================== --}}
        <div class="flex flex-col bg-white lg:w-1/2">

            <div class="flex flex-1 items-center justify-center px-6 py-12 sm:px-10 min-h-[540px]">
                <div class="w-full max-w-sm">

                    {{-- Login form --}}
                    <div x-show="mode === 'login'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-7">
                            <h2 class="text-2xl font-semibold text-gray-900">Sign in</h2>
                            <p class="mt-1 text-sm text-gray-500">Access your research portal</p>
                        </div>

                        @if(session('status'))
                            <div class="mb-4">
                                <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            <div class="space-y-1">
                                <label for="login_email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input id="login_email" type="text" name="email" x-ref="loginEmail" x-model="email"
                                    placeholder="lastname.firstname@srms.site" required
                                    class="block w-full border border-gray-300 px-3 py-2 text-sm rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none" />
                                <x-form.error name="email" />
                            </div>

                            <div class="space-y-1">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <div class="relative">
                                    <input id="password" name="password" :type="show ? 'text' : 'password'" x-model="password"
                                        placeholder="Enter your password" required
                                        class="block w-full border border-gray-300 px-3 py-2 pr-10 text-sm rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 focus:outline-none" />
                                    <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                                        :aria-label="show ? 'Hide password' : 'Show password'" tabindex="-1">
                                        <x-ui.icon name="eye" x-show="!show" class="h-4 w-4" />
                                        <x-ui.icon name="eye-slash" x-show="show" x-cloak class="h-4 w-4" />
                                    </button>
                                </div>
                                <x-form.error name="password" />
                            </div>

                            <x-ui.button type="submit" class="w-full">Sign In</x-ui.button>
                        </form>

                        <p class="mt-6 text-center text-xs text-gray-500">
                            New to SRMS?
                            <button type="button" @click="mode = 'register'" class="font-medium text-primary-600 hover:text-primary-800">Create an account</button>
                        </p>
                    </div>

                    {{-- Register form --}}
                    <div x-show="mode === 'register'" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-7">
                            <h2 class="text-2xl font-semibold text-gray-900">Create your account</h2>
                            <p class="mt-1 text-sm text-gray-500">Join the SRMS research community</p>
                        </div>

                        <form method="POST" action="{{ route('register') }}" class="space-y-5">
                            @csrf

                            <div class="grid grid-cols-1 min-[380px]:grid-cols-2 gap-4">
                                <x-form.input name="first_name" label="First name" placeholder="Juan" :required="true" />
                                <x-form.input name="last_name" label="Last name" placeholder="Dela Cruz" :required="true" />
                            </div>

                            <x-form.input name="email" type="email" label="Email address" placeholder="you@example.com" :required="true" />

                            <div>
                                <input type="hidden" name="is_student" value="0">
                                <div class="flex items-start gap-2">
                                    <input type="checkbox" id="is_student" name="is_student" value="1" x-model="isStudent"
                                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <label for="is_student" class="text-sm text-gray-700 select-none">I am a student</label>
                                </div>
                            </div>

                            <div x-show="isStudent" x-transition x-cloak>
                                <x-form.select name="course_id" label="Course / Program" :options="$courses" :required="false" x-bind:required="isStudent" />
                            </div>

                            <x-form.input name="password" type="password" label="Password" placeholder="At least 8 characters" :required="true" />
                            <x-form.input name="password_confirmation" type="password" label="Confirm password" placeholder="Re-enter your password" :required="true" />

                            <x-ui.button type="submit" class="w-full">Create Account</x-ui.button>
                        </form>

                        <p class="mt-6 text-center text-xs text-gray-500">
                            Already have an account?
                            <button type="button" @click="mode = 'login'" class="font-medium text-primary-600 hover:text-primary-800">Sign in</button>
                        </p>
                    </div>

                </div>
            </div>

            {{-- Panel footer --}}
            <div class="px-6 py-3 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">
                    <a href="https://spup.edu.ph" target="_blank" rel="noopener" class="hover:text-primary-600 transition-colors">
                        St. Paul University Philippines
                    </a>
                    <span class="mx-1.5 text-gray-300">|</span>
                    Tuguegarao City, Cagayan
                </p>
            </div>

        </div>

    </div>

    @push('scripts')
    <script>
        function authPage() {
            return {
                mode: '{{ $mode ?? "login" }}',
                show: false,
                isStudent: @json($errors->any() ? (bool) old('is_student') : true),
                email: '{{ old('email', '') }}',
                password: '',
            }
        }
    </script>
    @endpush

</x-layouts.guest>
