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
        $service = $this->route('service');
        $institutionId = $this->filled('institution_id')
            ? $this->integer('institution_id')
            : ($service ? (int) $service->institution_id : null);

        return [
            'institution_id' => ['sometimes', 'required', 'exists:institutions,id'],
            'department_id' => [
                'sometimes',
                'required',
                Rule::exists('departments', 'id')->where(
                    fn ($query) => $query->where('institution_id', $institutionId)
                ),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'duration' => ['sometimes', 'required', 'integer', 'min:1'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'opening_time' => ['sometimes', 'required', 'date_format:H:i'],
            'closing_time' => ['sometimes', 'required', 'date_format:H:i'],
            'working_days' => ['sometimes', 'required', 'array'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
