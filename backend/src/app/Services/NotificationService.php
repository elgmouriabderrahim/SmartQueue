<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function createForUser(User $user, string $type, array $data): DatabaseNotification
    {
        return DatabaseNotification::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => $data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markAsRead(string $notificationId, User $user): bool
    {
        $notification = DatabaseNotification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->read_at = now();
        $notification->save();

        return true;
    }
}
