<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['sometimes', 'nullable', 'exists:institutions,id'],
            'key' => ['sometimes', 'required', 'string', 'max:255'],
            'value' => ['sometimes', 'required', 'string'],
            'type' => ['sometimes', Rule::in(['string', 'integer', 'boolean', 'json'])],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
