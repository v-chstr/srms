@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | SRMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased" x-data="{ sidebarOpen: false, ...navManager() }" @click="handleNavClick($event)">

    {{-- Loading bar --}}
    <div x-show="navigating" x-cloak class="fixed top-0 left-0 right-0 z-[100] h-0.5 bg-primary-100">
        <div class="h-full bg-primary-500 animate-pulse" style="width: 90%;"></div>
    </div>

    {{-- Sidebar (desktop: fixed, mobile: slide-over) --}}
    <x-layouts.sidebar :unread-count="$unreadCount" :research-notif-count="$researchNotifCount" :review-notif-count="$reviewNotifCount" />

    {{-- Main content area --}}
    <div class="lg:pl-64">
        {{-- Mobile top bar --}}
        <header class="sticky top-0 z-30 flex h-12 items-center gap-3 border-b border-gray-200 bg-white px-4 lg:hidden">
            <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700">
                <x-ui.icon name="bars-3" class="h-5 w-5" />
            </button>
            <span class="text-sm font-semibold text-gray-800">{{ $title }}</span>

            @auth
            <button @click="$dispatch('open-modal', 'notifications')" class="relative ml-auto p-1.5 text-gray-500 hover:text-gray-700">
                <x-ui.icon name="bell" class="h-4 w-4" />
                @if($unreadCount > 0)
                <span class="absolute -top-0.5 -right-0.5 flex h-3.5 min-w-[0.875rem] items-center justify-center rounded-full bg-red-500 px-0.5 text-[9px] font-bold text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>
            @endauth
        </header>

        {{-- Page content --}}
        <main class="px-4 py-5 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-5xl">
            @if(session('success'))
                <div class="mb-4"><x-ui.alert type="success">{{ session('success') }}</x-ui.alert></div>
            @endif
            @if(session('error'))
                <div class="mb-4"><x-ui.alert type="error">{{ session('error') }}</x-ui.alert></div>
            @endif

            {{ $slot }}
            </div>
        </main>
    </div>

    {{-- Notifications modal --}}
    @auth
    <x-ui.modal name="notifications" maxWidth="md">
        {{-- Header --}}
        <div class="flex shrink-0 items-center justify-between bg-primary-900 px-5 py-4">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold text-white">Notifications</h2>
                @if($unreadCount > 0)
                <span class="inline-flex items-center justify-center h-4 min-w-[1rem] px-1 rounded-full bg-white/20 text-[10px] font-bold text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-white/80 hover:text-white">Mark all read</button>
                    </form>
                @endif
                @if($drawerNotifications->isNotEmpty())
                    <form method="POST" action="{{ route('notifications.clear-all') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-medium text-white/60 hover:text-white/80">Clear all</button>
                    </form>
                @endif
                <button @click="$dispatch('close-modal', 'notifications')" class="p-1.5 rounded-md text-white/60 hover:text-white hover:bg-primary-800 transition-colors">
                    <x-ui.icon name="x-mark" class="h-4 w-4" />
                </button>
            </div>
        </div>

        {{-- Notification list --}}
        <div class="overflow-y-auto max-h-[60vh] divide-y divide-gray-100">
            @if($drawerNotifications->isEmpty())
                <p class="px-4 py-10 text-sm text-gray-400 text-center">No notifications yet.</p>
            @else
                @foreach($drawerNotifications as $notif)
                    @php
                        $isUnread = is_null($notif->read_at);
                        $notifData = $notif->data;
                        $notifType = $notifData['type'] ?? 'announcement';
                    @endphp
                    <div class="flex items-start gap-3 px-5 py-3 {{ $isUnread ? 'bg-primary-50' : '' }}">
                        <form method="POST" action="{{ route('notifications.read', $notif->id) }}" class="flex-1 min-w-0 flex items-start gap-3">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="flex-1 min-w-0 flex items-start gap-3 text-left hover:opacity-80">
                                <div class="shrink-0 mt-0.5 flex h-7 w-7 items-center justify-center rounded-full {{ $isUnread ? 'bg-primary-100' : 'bg-gray-100' }}">
                                    @if($notifType === 'research_submitted')
                                        <x-ui.icon name="document-plus" class="h-3.5 w-3.5 {{ $isUnread ? 'text-primary-600' : 'text-gray-500' }}" />
                                    @elseif($notifType === 'research_reviewed')
                                        <x-ui.icon name="check-circle" class="h-3.5 w-3.5 {{ $isUnread ? 'text-primary-600' : 'text-gray-500' }}" />
                                    @elseif($notifType === 'defense_scheduled')
                                        <x-ui.icon name="calendar-days" class="h-3.5 w-3.5 {{ $isUnread ? 'text-primary-600' : 'text-gray-500' }}" />
                                    @elseif($notifType === 'queue_turn')
                                        <x-ui.icon name="queue-list" class="h-3.5 w-3.5 {{ $isUnread ? 'text-accent-600' : 'text-gray-500' }}" />
                                    @else
                                        <x-ui.icon name="megaphone" class="h-3.5 w-3.5 {{ $isUnread ? 'text-primary-600' : 'text-gray-500' }}" />
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm leading-snug {{ $isUnread ? 'font-medium text-gray-900' : 'text-gray-600' }}">
                                        {{ $notifData['message'] ?? $notifData['title'] ?? 'Notification' }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                                </div>
                                @if($isUnread)
                                    <span class="shrink-0 mt-2 h-1.5 w-1.5 rounded-full bg-primary-500"></span>
                                @endif
                            </button>
                        </form>
                        <form method="POST" action="{{ route('notifications.destroy', $notif->id) }}" class="shrink-0 mt-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 text-gray-300 hover:text-red-500 rounded-md" title="Remove">
                                <x-ui.icon name="x-mark" class="h-3.5 w-3.5" />
                            </button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    </x-ui.modal>
    @endauth

    {{-- Queue Turn Alert — students only --}}
    @auth
    @if($queueTurnAlert && auth()->user()->isStudent())
        @php $qd = $queueTurnAlert->data; @endphp
        <div x-data x-init="setTimeout(() => $dispatch('open-modal', 'queue-turn-alert'), 400)"></div>

        <x-ui.modal name="queue-turn-alert" maxWidth="sm">
            {{-- Header --}}
            <div class="flex items-center gap-3 bg-primary-900 px-5 py-4">
                <x-ui.icon name="queue-list" class="h-5 w-5 text-white/80 shrink-0" />
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-white">It's your turn</h2>
                    <p class="text-xs text-white/70 mt-0.5">{{ $qd['course'] ?? '' }}</p>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-5 py-4 space-y-4">
                <div class="divide-y divide-gray-100 rounded-md border border-gray-200 overflow-hidden text-sm">
                    <div class="flex justify-between items-center px-4 py-2.5 bg-gray-50">
                        <span class="text-gray-500">Queue</span>
                        <span class="font-medium text-gray-900">{{ $qd['queue_title'] ?? '' }}</span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5 bg-white">
                        <span class="text-gray-500">Group</span>
                        <span class="text-lg font-bold text-primary-700"># {{ $qd['group_no'] ?? '' }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('notifications.read', $queueTurnAlert->id) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1 transition-colors">
                        Got it, I'm ready
                    </button>
                </form>
            </div>
        </x-ui.modal>
    @endif
    @endauth

    @stack('scripts')
</body>
</html>
