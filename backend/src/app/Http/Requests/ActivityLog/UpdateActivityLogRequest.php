<?php

namespace App\Http\Requests\ActivityLog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'institution_id' => ['sometimes', 'nullable', 'exists:institutions,id'],
            'action' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
