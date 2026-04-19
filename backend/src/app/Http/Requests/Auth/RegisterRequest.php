<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'identity_number' => ['nullable', 'string', 'max:255', 'unique:users,identity_number'],
            'role' => ['sometimes', Rule::in(['citizen', 'institution', 'admin'])],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
    }
}
