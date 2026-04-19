<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function createForUser(User $user, string $type, array $data): Notification
    {
        return Notification::query()->create([
            'user_id' => $user->id,
            'title' => (string) ($data['title'] ?? ucfirst(str_replace('_', ' ', str_replace('.', ' ', $type)))),
            'message' => (string) ($data['message'] ?? 'You have a new notification.'),
            'type' => $type,
            'is_read' => false,
        ]);
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = Notification::query()
            ->where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->is_read = true;
        $notification->save();

        return true;
    }
}
