<?php

namespace App\Http\Requests\Messaging;

use Illuminate\Foundation\Http\FormRequest;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'citizen_id' => ['required', 'exists:users,id'],
            'institution_user_id' => ['required', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:255'],
        ];
    }
}
