<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $notifications = $user->notifications()->latest()->paginate(20);

        return $this->success($notifications, 'Notifications fetched successfully.');
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $updated = $this->notificationService->markAsRead((int) $notificationId, $user);

        if (! $updated) {
            return $this->error('Notification not found.', 404);
        }

        return $this->success(null, 'Notification marked as read.');
    }
}
