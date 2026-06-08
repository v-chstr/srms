@props(['title' => 'Research Archive'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | SRMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">

    {{-- Masthead --}}
    <header class="sticky top-0 z-40 bg-primary-900 border-b border-primary-800">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                {{-- Brand: both logos + name --}}
                <a href="{{ route('archive.index') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/SPUP-final-logo.png') }}" alt="SPUP" class="h-10 w-10 object-contain">
                    <img src="{{ asset('images/spup-site.png') }}" alt="SITE" class="h-9 w-9 object-contain">
                    <div class="hidden sm:block ml-0.5">
                        <p class="text-xs font-semibold text-accent-300 leading-tight">St. Paul University Philippines</p>
                        <p class="text-xs text-primary-300 leading-tight">Research Archive</p>
                    </div>
                </a>

                {{-- Right side --}}
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md border border-accent-400/60 bg-accent-400/10 px-3.5 py-1.5 text-xs font-semibold text-accent-300 hover:bg-accent-400/20 transition-colors">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-md border border-accent-400/60 bg-accent-400/10 px-3.5 py-1.5 text-xs font-semibold text-accent-300 hover:bg-accent-400/20 transition-colors">Sign In</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Page content --}}
    <main>
        {{ $slot }}
    </main>

    @stack('scripts')
</body>
</html>
