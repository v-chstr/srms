<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // SRMS date formatting macros — single source of truth for all date displays.
        // Standard format: Apr 12, 2026 2:30 PM (academic readable).
        // Use these in @php blocks instead of hardcoding format strings.
        // In Blade output, use <x-ui.date> instead.
        Carbon::macro('srmsDate',     fn () => $this->format('M j, Y g:i A'));    // Apr 12, 2026 2:30 PM
        Carbon::macro('srmsDateTime', fn () => $this->format('M j, Y g:i A'));    // Apr 12, 2026 2:30 PM (same as srmsDate)
        Carbon::macro('srmsShort',    fn () => $this->format('M j, Y'));          // Apr 12, 2026
        Carbon::macro('srmsNumeric',  fn () => $this->format('M j, Y g:i A'));    // Apr 12, 2026 2:30 PM (always with time)
        Carbon::macro('srmsTime',     fn () => $this->format('g:i A'));           // 2:30 PM

        // Provide $unreadCount and $drawerNotifications to the app layout.
        // Provide $unreadCount and $drawerNotifications to the app layout on every page.
        View::composer('components.layouts.app', function ($view) {
            $unreadCount         = 0;
            $researchNotifCount  = 0;
            $reviewNotifCount    = 0;
            $drawerNotifications = collect();
            $queueTurnAlert      = null;

            if (Auth::check()) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $unreadCount         = $user->unreadNotifications()->count();
                $drawerNotifications = $user->notifications()->latest()->limit(15)->get();

                // Latest unread queue-turn notification for the auto-popup modal
                $queueTurnAlert = $user->unreadNotifications()
                    ->where('data->type', 'queue_turn')
                    ->latest()
                    ->first();

                // Badge count for the Submit Research sidebar link (students only)
                if ($user->isStudent()) {
                    $researchNotifCount = $user->unreadNotifications()
                        ->where('data->type', 'research_reviewed')
                        ->count();
                }

                // Badge count for the Review Papers sidebar link (advisers only)
                if ($user->canAdvise()) {
                    $reviewNotifCount = $user->unreadNotifications()
                        ->where('data->type', 'research_submitted')
                        ->count();
                }
            }

            $view->with('unreadCount', $unreadCount);
            $view->with('researchNotifCount', $researchNotifCount);
            $view->with('reviewNotifCount', $reviewNotifCount);
            $view->with('drawerNotifications', $drawerNotifications);
            $view->with('queueTurnAlert', $queueTurnAlert);
        });
    }
}
