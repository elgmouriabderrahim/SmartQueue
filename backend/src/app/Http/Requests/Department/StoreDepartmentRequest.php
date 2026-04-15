<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'exists:institutions,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'slug')->where(
                    fn ($query) => $query->where('institution_id', $this->input('institution_id'))
                ),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
