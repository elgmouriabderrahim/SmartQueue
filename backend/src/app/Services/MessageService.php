<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Institution;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function sendMessage(array $data, User $sender): Message
    {
        if ($sender->role === 'admin') {
            throw ValidationException::withMessages([
                'recipient_id' => ['Admins cannot send operational messages.'],
            ]);
        }

        $recipientId = isset($data['recipient_id']) ? (int) $data['recipient_id'] : 0;

        $institutionId = isset($data['institution_id']) ? (int) $data['institution_id'] : null;

        if ($sender->role === 'citizen' && $institutionId && $institutionId > 0) {
            $recipientId = $this->resolveInstitutionConversationRecipientId($sender, $institutionId);
        }

        if (in_array($sender->role, ['manager', 'employee'], true) && ! $institutionId) {
            $institutionId = (int) ($sender->currentInstitutionId() ?? 0);
        }

        if ($recipientId === $sender->id) {
            throw ValidationException::withMessages([
                'recipient_id' => ['You cannot send a message to yourself.'],
            ]);
        }

        $recipient = User::query()->find($recipientId);
        if (! $recipient) {
            throw ValidationException::withMessages([
                'recipient_id' => ['Recipient not found.'],
            ]);
        }

        if ($sender->role === 'citizen' && ! in_array($recipient->role, ['manager', 'employee'], true)) {
            throw ValidationException::withMessages([
                'recipient_id' => ['Citizens can only contact institution staff.'],
            ]);
        }

        if (in_array($sender->role, ['manager', 'employee'], true) && $recipient->role !== 'citizen') {
            throw ValidationException::withMessages([
                'recipient_id' => ['Institution staff can only send replies to citizens.'],
            ]);
        }

        $message = Message::query()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'institution_id' => $institutionId ?: null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'content' => $data['content'],
            'status' => 'new',
        ]);

        MessageSent::dispatch($message);

        return $message->fresh(['sender', 'recipient', 'appointment', 'institution']);
    }

    private function resolveInstitutionRecipientId(int $institutionId): int
    {
        $institution = Institution::query()->find($institutionId);
        if (! $institution) {
            throw ValidationException::withMessages([
                'institution_id' => ['Institution not found.'],
            ]);
        }

        $manager = User::query()
            ->where('institution_id', $institutionId)
            ->where('role', 'manager')
            ->orderBy('id')
            ->first();

        if ($manager) {
            return (int) $manager->id;
        }

        $employee = User::query()
            ->where('institution_id', $institutionId)
            ->where('role', 'employee')
            ->orderBy('id')
            ->first();

        if ($employee) {
            return (int) $employee->id;
        }

        throw ValidationException::withMessages([
            'institution_id' => ['No available manager or employee found for this institution.'],
        ]);
    }

    private function resolveInstitutionConversationRecipientId(User $sender, int $institutionId): int
    {
        $existingMessage = Message::query()
            ->where(function (Builder $query) use ($sender, $institutionId): void {
                $query->where('sender_id', $sender->id)
                    ->whereHas('recipient', function (Builder $recipientQuery) use ($institutionId): void {
                        $recipientQuery
                            ->where('institution_id', $institutionId)
                            ->whereIn('role', ['manager', 'employee']);
                    });
            })
            ->orWhere(function (Builder $query) use ($sender, $institutionId): void {
                $query->where('recipient_id', $sender->id)
                    ->whereHas('sender', function (Builder $senderQuery) use ($institutionId): void {
                        $senderQuery
                            ->where('institution_id', $institutionId)
                            ->whereIn('role', ['manager', 'employee']);
                    });
            })
            ->latest('id')
            ->first();

        if ($existingMessage) {
            $otherUserId = (int) ($existingMessage->sender_id === $sender->id
                ? $existingMessage->recipient_id
                : $existingMessage->sender_id);

            if ($otherUserId > 0) {
                return $otherUserId;
            }
        }

        return $this->resolveInstitutionRecipientId($institutionId);
    }
}
