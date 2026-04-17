<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function sendMessage(array $data, User $sender): Message
    {
        $recipientId = (int) $data['recipient_id'];

        if ($recipientId === $sender->id) {
            throw ValidationException::withMessages([
                'recipient_id' => ['You cannot send a message to yourself.'],
            ]);
        }

        $recipientExists = User::query()->whereKey($recipientId)->exists();
        if (! $recipientExists) {
            throw ValidationException::withMessages([
                'recipient_id' => ['Recipient not found.'],
            ]);
        }

        $message = Message::query()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'appointment_id' => $data['appointment_id'] ?? null,
            'content' => $data['content'],
            'status' => 'new',
        ]);

        return $message->fresh(['sender', 'recipient', 'appointment']);
    }
}
