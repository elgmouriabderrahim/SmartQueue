<?php

namespace App\Http\Requests\InstitutionRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstitutionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:institutions,slug',
                Rule::unique('institution_requests', 'slug')->where(fn ($query) => $query->where('status', 'pending')),
            ],
            'city' => ['required', 'string', 'max:255'],
            'adress' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }
}
