<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string'],
            'type' => ['sometimes', Rule::in(['string', 'integer', 'boolean', 'json'])],
            'description' => ['nullable', 'string'],
        ];
    }
}
