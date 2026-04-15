<?php

namespace App\Http\Requests\QueueEntry;

use App\Models\QueueEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQueueEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var QueueEntry|null $queueEntry */
        $queueEntry = $this->route('queueEntry') ?? $this->route('queue_entry');
        $queueId = $this->input('queue_id', $queueEntry?->queue_id);

        return [
            'queue_id' => ['sometimes', 'required', 'exists:queues,id'],
            'appointment_id' => [
                'sometimes',
                'required',
                'exists:appointments,id',
                Rule::unique('queue_entries', 'appointment_id')->ignore($queueEntry?->id),
            ],
            'position' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('queue_entries', 'position')
                    ->where(fn ($query) => $query->where('queue_id', $queueId))
                    ->ignore($queueEntry?->id),
            ],
            'status' => ['sometimes', Rule::in(['waiting', 'called', 'serving', 'served', 'skipped', 'transferred'])],
        ];
    }
}
