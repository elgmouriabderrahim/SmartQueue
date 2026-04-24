<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});
