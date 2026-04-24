<?php

namespace App\Http\Requests\QueueEntry;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQueueEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'queue_id' => ['required', 'exists:queues,id'],
            'appointment_id' => ['required', 'exists:appointments,id', 'unique:queue_entries,appointment_id'],
            'position' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('queue_entries', 'position')->where(
                    fn ($query) => $query->where('queue_id', $this->input('queue_id'))
                ),
            ],
            'estimated_wait_time' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['waiting', 'called', 'serving', 'served', 'skipped', 'transferred'])],
        ];
    }
}
