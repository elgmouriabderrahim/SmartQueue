<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class MessageService
{
    public function startConversation(int $citizenId, int $institutionUserId, ?string $subject = null): Conversation
    {
        return Conversation::query()->firstOrCreate(
            [
                'citizen_id' => $citizenId,
                'institution_user_id' => $institutionUserId,
            ],
            [
                'subject' => $subject,
                'last_message_at' => now(),
            ]
        );
    }

    public function sendMessage(array $data): Message
    {
        $conversation = Conversation::query()->findOrFail($data['conversation_id']);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $data['sender_id'],
            'recipient_id' => $data['recipient_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'body' => $data['body'],
            'status' => 'new',
        ]);

        $conversation->last_message_at = now();
        $conversation->save();

        return $message->fresh(['sender', 'recipient', 'conversation']);
    }

    public function listConversationsForUser(User $user)
    {
        return Conversation::query()
            ->with(['citizen', 'institutionUser'])
            ->where('citizen_id', $user->id)
            ->orWhere('institution_user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->get();
    }

    public function fetchMessages(int $conversationId)
    {
        return Message::query()
            ->with(['sender', 'recipient'])
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at')
            ->get();
    }

    public function markConversationAsRead(int $conversationId, int $recipientUserId): int
    {
        return Message::query()
            ->where('conversation_id', $conversationId)
            ->where('recipient_id', $recipientUserId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'status' => 'read',
            ]);
    }
}
