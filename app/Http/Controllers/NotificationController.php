<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get notifications — JSON for AJAX, Blade page for browser.
     */
    public function index(Request $request)
    {
        // AJAX request — return JSON for the dropdown
        if ($request->ajax() || $request->wantsJson()) {
            $notifications = $request->user()
                ->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($n) => [
                    'id'         => $n->id,
                    'type'       => $n->data['type'] ?? 'info',
                    'icon'       => $n->data['icon'] ?? 'fa-bell',
                    'color'      => $n->data['color'] ?? 'primary',
                    'title'      => $n->data['title'] ?? '',
                    'message'    => $n->data['message'] ?? '',
                    'url'        => $n->data['url'] ?? null,
                    'read'       => $n->read_at !== null,
                    'created_at' => $n->created_at->diffForHumans(),
                ]);

            return response()->json([
                'notifications' => $notifications,
                'unread_count'  => $request->user()->unreadNotifications()->count(),
            ]);
        }

        // Browser request — return full page
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
