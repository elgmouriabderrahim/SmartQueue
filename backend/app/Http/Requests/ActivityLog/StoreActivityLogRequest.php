<?php

namespace App\Http\Requests\ActivityLog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'action' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['success', 'failed'])],
        ];
    }
}
