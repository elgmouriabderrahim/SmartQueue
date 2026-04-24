<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Institution;
use App\Models\Message;
use App\Models\User;
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

        if ($sender->role === 'citizen' && isset($data['institution_id'])) {
            $institutionId = (int) $data['institution_id'];
            $recipientId = $this->resolveInstitutionRecipientId($institutionId);
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
            'appointment_id' => $data['appointment_id'] ?? null,
            'content' => $data['content'],
            'status' => 'new',
        ]);

        MessageSent::dispatch($message);

        return $message->fresh(['sender', 'recipient', 'appointment']);
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
}
