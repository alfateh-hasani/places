<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebNotification;
use Illuminate\Http\JsonResponse;

/**
 * Feeds the staff notification bell in the dashboard topbar from the polymorphic
 * `web_notifications` store. Scoped to the authenticated Backpack user; unrelated
 * to the mobile FCM notifications.
 */
class WebNotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $user = backpack_user();

        $notifications = $user->webNotifications()->limit(15)->get()->map(fn (WebNotification $n): array => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'url' => $n->data['action_url'] ?? null,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at?->diffForHumans(),
        ]);

        return response()->json([
            'unread_count' => $user->unreadWebNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $notification = backpack_user()->webNotifications()->whereKey($id)->first();

        $notification?->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        backpack_user()->unreadWebNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
