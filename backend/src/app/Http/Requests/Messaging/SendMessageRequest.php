<?php

namespace App\Http\Requests\Messaging;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => ['nullable', 'required_without:institution_id', 'exists:users,id'],
            'institution_id' => ['nullable', 'required_without:recipient_id', 'exists:institutions,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'content' => ['required', 'string'],
        ];
    }
}
