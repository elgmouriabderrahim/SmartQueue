<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['sometimes', 'required', 'exists:institutions,id'],
            'department_id' => ['sometimes', 'nullable', 'exists:departments,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'duration' => ['sometimes', 'required', 'integer', 'min:1'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'working_hours' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
