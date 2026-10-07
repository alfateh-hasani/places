<?php

namespace App\Services;

use App\Models\WebNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Encapsulates in-app (web) notification operations for any recipient model that
 * uses the HasWebNotifications trait (Customer, User). Centralises ownership
 * scoping so controllers stay thin and a recipient can never read, read-mark, or
 * delete another recipient's notifications.
 */
class WebNotificationService
{
    /**
     * Paginated notifications for the recipient (newest first — the relation is
     * already ordered by latest()).
     */
    public function paginate(Model $notifiable, int $perPage = 15): LengthAwarePaginator
    {
        return $notifiable->webNotifications()->paginate($perPage);
    }

    public function unreadCount(Model $notifiable): int
    {
        return $notifiable->unreadWebNotifications()->count();
    }

    /**
     * Fetch a single notification scoped to its owner (null if it doesn't belong
     * to this recipient — prevents cross-account access).
     */
    public function find(Model $notifiable, int $id): ?WebNotification
    {
        return $notifiable->webNotifications()->whereKey($id)->first();
    }

    public function markAsRead(Model $notifiable, int $id): void
    {
        $this->find($notifiable, $id)?->markAsRead();
    }

    public function markAllAsRead(Model $notifiable): void
    {
        $notifiable->unreadWebNotifications()->update(['read_at' => now()]);
    }

    public function delete(Model $notifiable, int $id): void
    {
        $this->find($notifiable, $id)?->delete();
    }
}
