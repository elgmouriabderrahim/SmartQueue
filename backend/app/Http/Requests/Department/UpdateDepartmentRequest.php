<?php

namespace App\Http\Requests\Department;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Department|null $department */
        $department = $this->route('department');
        $institutionId = $this->input('institution_id', $department?->institution_id);

        return [
            'institution_id' => ['sometimes', 'required', 'exists:institutions,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'slug')
                    ->where(fn ($query) => $query->where('institution_id', $institutionId))
                    ->ignore($department?->id),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
