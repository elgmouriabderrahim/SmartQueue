<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Validation\ValidationException;

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

    public function sendMessage(array $data, User $sender): Message
    {
        $conversation = Conversation::query()->findOrFail($data['conversation_id']);

        if (! in_array($sender->id, [$conversation->citizen_id, $conversation->institution_user_id], true)) {
            throw ValidationException::withMessages([
                'conversation_id' => ['You are not part of this conversation.'],
            ]);
        }

        $recipientId = $data['recipient_id'] ?? null;
        if (! $recipientId) {
            $recipientId = $sender->id === $conversation->citizen_id
                ? $conversation->institution_user_id
                : $conversation->citizen_id;
        }

        if (! in_array($recipientId, [$conversation->citizen_id, $conversation->institution_user_id], true) || $recipientId === $sender->id) {
            throw ValidationException::withMessages([
                'recipient_id' => ['Recipient must be the other participant in the conversation.'],
            ]);
        }

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
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
        $conversation = Conversation::query()->findOrFail($conversationId);
        if (! in_array($recipientUserId, [$conversation->citizen_id, $conversation->institution_user_id], true)) {
            throw ValidationException::withMessages([
                'conversation_id' => ['You are not part of this conversation.'],
            ]);
        }

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
