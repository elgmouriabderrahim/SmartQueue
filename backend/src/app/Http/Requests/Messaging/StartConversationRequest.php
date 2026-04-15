<?php

namespace App\Http\Requests\Messaging;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'citizen_id' => ['nullable', 'exists:users,id'],
            'institution_user_id' => ['required', 'exists:users,id', Rule::different('citizen_id')],
            'subject' => ['nullable', 'string', 'max:255'],
        ];
    }
}
