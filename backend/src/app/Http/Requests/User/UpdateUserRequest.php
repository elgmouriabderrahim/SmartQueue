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
        $role = (string) ($this->input('role') ?? $user?->role ?? 'citizen');
        $institutionRequired = in_array($role, ['employee', 'manager'], true);

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'identity_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'identity_number')->ignore($user?->id),
            ],
            'role' => ['sometimes', Rule::in(['citizen', 'employee', 'manager', 'admin'])],
            'institution_id' => [
                $institutionRequired ? 'required' : 'sometimes',
                'nullable',
                'exists:institutions,id',
            ],
        ];
    }
}
