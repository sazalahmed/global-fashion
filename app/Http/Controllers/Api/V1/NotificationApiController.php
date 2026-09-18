<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($request->input('per_page', 20));

        $items = collect($notifications->items())->map(fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->data['type'] ?? 'info',
            'icon'       => $n->data['icon'] ?? 'fa-bell',
            'color'      => $n->data['color'] ?? 'primary',
            'title'      => $n->data['title'] ?? '',
            'message'    => $n->data['message'] ?? '',
            'url'        => $n->data['url'] ?? null,
            'read'       => $n->read_at !== null,
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        return $this->success([
            'notifications' => $items,
            'unread_count'  => $request->user()->unreadNotifications()->count(),
        ], 'Notifications retrieved', meta: [
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
            'per_page'     => $notifications->perPage(),
            'total'        => $notifications->total(),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(null, 'Notification marked as read');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(null, 'All notifications marked as read');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'count' => $request->user()->unreadNotifications()->count(),
        ], 'Unread count retrieved');
    }
}
