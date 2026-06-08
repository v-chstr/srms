@props(['unreadCount' => 0, 'researchNotifCount' => 0, 'reviewNotifCount' => 0])

@php
    $user = auth()->user();
    $role = $user?->role;
    $isAdmin = $role === 'admin';
    $canAdvise = $user?->canAdvise() ?? false;

    $activeLink   = 'bg-white/[.13] text-white font-semibold';
    $inactiveLink = 'text-primary-200 hover:bg-white/[.07] hover:text-white';
    $activeIcon   = 'text-accent-300';
    $inactiveIcon = 'text-primary-300 group-hover:text-primary-200';
@endphp

{{-- Mobile overlay --}}
<div x-show="sidebarOpen" x-cloak
     x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-black/50 lg:hidden"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-primary-900 text-primary-100 transition-transform duration-200 lg:translate-x-0">

    {{-- Brand --}}
    <div class="shrink-0 relative bg-black/20 px-4 py-3 border-b border-black/20">
        <button @click="sidebarOpen = false" class="lg:hidden absolute top-2.5 right-3 text-primary-300 hover:text-white transition-colors">
            <x-ui.icon name="x-mark" class="h-5 w-5" />
        </button>
        <a href="{{ route('dashboard') }}" data-nav-link class="flex items-center gap-3 pr-8 lg:pr-0">
            <div class="h-11 w-11 shrink-0 rounded-md bg-black/25 ring-1 ring-accent-400/20 flex items-center justify-center">
                <img src="{{ asset('images/spup-site.png') }}" alt="SPUP SITE" class="h-9 w-9 object-contain" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold leading-tight text-white">Student <span class="text-accent-400">Research</span></p>
                <p class="text-sm font-semibold leading-tight text-primary-200">Management System</p>
                <p class="text-xs text-primary-400 mt-0.5">SPUP-SITE</p>
            </div>
        </a>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">

        <a href="{{ route('dashboard') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? $activeLink : $inactiveLink }}">
            <x-ui.icon name="squares-2x2" class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? $activeIcon : $inactiveIcon }}" />
            Dashboard
        </a>

        @if($isAdmin)
            <p class="px-2.5 pt-5 pb-1 text-xs font-semibold text-primary-400">Manage</p>

            <a href="{{ route('admin.users.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.users.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="user-group" class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.users.*') ? $activeIcon : $inactiveIcon }}" />
                Users
            </a>

            <a href="{{ route('admin.courses.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.courses.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="academic-cap" class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.courses.*') ? $activeIcon : $inactiveIcon }}" />
                Courses
            </a>

            <a href="{{ route('admin.papers.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.papers.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="document" class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.papers.*') ? $activeIcon : $inactiveIcon }}" />
                Research Papers
            </a>

            <a href="{{ route('admin.announcements.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.announcements.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="megaphone" class="w-4 h-4 shrink-0 {{ request()->routeIs('admin.announcements.*') ? $activeIcon : $inactiveIcon }}" />
                Announcements
            </a>
        @endif

        @if($canAdvise)
            <p class="px-2.5 pt-5 pb-1 text-xs font-semibold text-primary-400">Advising</p>

            <a href="{{ route('adviser.reviews.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('adviser.reviews.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="clipboard-document-list" class="w-4 h-4 shrink-0 {{ request()->routeIs('adviser.reviews.*') ? $activeIcon : $inactiveIcon }}" />
                <span class="flex-1">Review Papers</span>
                @if($reviewNotifCount > 0)
                    <span class="flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-accent-400 px-1 text-[10px] font-bold text-primary-900">{{ $reviewNotifCount > 9 ? '9+' : $reviewNotifCount }}</span>
                @endif
            </a>

            <a href="{{ route('queue.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('queue.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="queue-list" class="w-4 h-4 shrink-0 {{ request()->routeIs('queue.*') ? $activeIcon : $inactiveIcon }}" />
                Defense Queue
            </a>

            <a href="{{ route('adviser.attendance.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('adviser.attendance.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="clipboard-document-check" class="w-4 h-4 shrink-0 {{ request()->routeIs('adviser.attendance.*') ? $activeIcon : $inactiveIcon }}" />
                Class Attendance
            </a>

            @if(!$isAdmin)
                <a href="{{ route('adviser.announcements.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('adviser.announcements.*') ? $activeLink : $inactiveLink }}">
                    <x-ui.icon name="megaphone" class="w-4 h-4 shrink-0 {{ request()->routeIs('adviser.announcements.*') ? $activeIcon : $inactiveIcon }}" />
                    Announcements
                </a>
            @endif
        @endif

        @if($role === 'student')
            <p class="px-2.5 pt-5 pb-1 text-xs font-semibold text-primary-400">Research</p>

            <a href="{{ route('student.research.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.research.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="book-open" class="w-4 h-4 shrink-0 {{ request()->routeIs('student.research.*') ? $activeIcon : $inactiveIcon }}" />
                <span class="flex-1">Submit Research</span>
                @if($researchNotifCount > 0)
                    <span class="flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-accent-400 px-1 text-[10px] font-bold text-primary-900">{{ $researchNotifCount > 9 ? '9+' : $researchNotifCount }}</span>
                @endif
            </a>

            <a href="{{ route('student.attendance.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.attendance.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="clipboard-document-check" class="w-4 h-4 shrink-0 {{ request()->routeIs('student.attendance.*') ? $activeIcon : $inactiveIcon }}" />
                My Attendance
            </a>

            <a href="{{ route('announcements.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('announcements.*') ? $activeLink : $inactiveLink }}">
                <x-ui.icon name="megaphone" class="w-4 h-4 shrink-0 {{ request()->routeIs('announcements.*') ? $activeIcon : $inactiveIcon }}" />
                Announcements
            </a>
        @endif

        <p class="px-2.5 pt-5 pb-1 text-xs font-semibold text-primary-400">Library</p>

        <a href="{{ route('archive.index') }}" data-nav-link class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('archive.*') ? $activeLink : $inactiveLink }}">
            <x-ui.icon name="archive-box" class="w-4 h-4 shrink-0 {{ request()->routeIs('archive.*') ? $activeIcon : $inactiveIcon }}" />
            Archive
        </a>
    </nav>

    {{-- Profile footer --}}
    @auth
    <div class="shrink-0 border-t border-white/5 p-2.5 relative" x-data="{ open: false }">
        <div class="flex items-center gap-1.5">
            <button @click="open = !open" @click.outside="open = false" class="flex flex-1 min-w-0 items-center gap-2.5 rounded-md px-2 py-1.5 hover:bg-white/5 transition-colors">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary-800 ring-1 ring-accent-300/30 text-xs font-bold text-accent-300">
                    {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0 text-left">
                    <p class="text-sm font-semibold text-white truncate leading-tight">{{ $user->first_name }} {{ $user->last_name }}</p>
                    <p class="text-xs text-primary-300 truncate leading-tight mt-0.5">{{ ucfirst($user->role) }}</p>
                </div>
                <x-ui.icon name="chevron-up-down" class="h-4 w-4 shrink-0 text-primary-400" />
            </button>
            <button @click="$dispatch('open-modal', 'notifications')" class="relative shrink-0 p-2 rounded-md text-primary-300 hover:text-white hover:bg-white/5 transition-colors" aria-label="Notifications">
                <x-ui.icon name="bell" class="h-4 w-4" />
                @if($unreadCount > 0)
                <span class="absolute top-0.5 right-0.5 flex h-3.5 min-w-[0.875rem] items-center justify-center rounded-full bg-accent-400 px-0.5 text-[9px] font-bold text-primary-900 ring-2 ring-primary-900">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>
        </div>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="absolute bottom-16 left-2.5 right-2.5 rounded-md border border-gray-200 bg-white py-1 shadow-xl z-10">
            <div class="px-3 py-2 border-b border-gray-100">
                <p class="text-sm font-semibold text-gray-900 truncate">{{ $user->first_name }} {{ $user->last_name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
            </div>
            <a href="{{ route('profile.edit') }}" data-nav-link class="block px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Profile settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-1.5 text-sm text-red-600 hover:bg-red-50">Sign out</button>
            </form>
        </div>
    </div>
    @else
    <div class="shrink-0 border-t border-white/5 p-3">
        <a href="{{ route('login') }}" data-nav-link class="flex items-center justify-center rounded-md px-3 py-2 text-sm font-semibold text-primary-900 bg-accent-400 hover:bg-accent-300 transition-colors">
            Sign in
        </a>
    </div>
    @endauth
</aside>
