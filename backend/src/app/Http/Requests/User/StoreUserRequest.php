<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = (string) $this->input('role', 'citizen');
        $institutionRequired = in_array($role, ['employee', 'manager'], true);

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'identity_number' => ['nullable', 'string', 'max:255', 'unique:users,identity_number'],
            'role' => ['sometimes', Rule::in(['citizen', 'employee', 'manager', 'admin'])],
            'institution_id' => [
                $institutionRequired ? 'required' : 'nullable',
                'exists:institutions,id',
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where(function ($query): void {
                    $institutionId = (int) $this->input('institution_id');
                    if ($institutionId > 0) {
                        $query->where('institution_id', $institutionId);
                    }
                }),
            ],
        ];
    }
}
