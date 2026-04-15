<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'identity_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'identity_number')->ignore($user?->id),
            ],
            'role' => ['sometimes', Rule::in(['citizen', 'employee', 'manager', 'admin'])],
            'institution_id' => ['sometimes', 'nullable', 'exists:institutions,id'],
            'department_id' => ['sometimes', 'nullable', 'exists:departments,id'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}
