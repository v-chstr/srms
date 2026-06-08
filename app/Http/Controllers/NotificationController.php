<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Show all notifications for the authenticated user.
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return view('pages.notifications.index', compact('notifications'));
    }

    /**
     * Mark a single notification as read, then redirect to its URL (if any).
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        // Bust the cached unread count so the bell badge updates immediately.
        cache()->forget("srms_unread_{$request->user()->id}");

        $url = $notification->data['url'] ?? route('dashboard');

        return redirect($url);
    }

    /**
     * Mark all notifications as read.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        // Bust the cached unread count so the bell badge updates immediately.
        cache()->forget("srms_unread_{$request->user()->id}");

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->findOrFail($id)
            ->delete();

        cache()->forget("srms_unread_{$request->user()->id}");

        return back()->with('success', 'Notification removed.');
    }

    /**
     * Delete all notifications for the authenticated user.
     */
    public function clearAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        cache()->forget("srms_unread_{$request->user()->id}");

        return back()->with('success', 'All notifications cleared.');
    }
}
