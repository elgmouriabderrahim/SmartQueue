<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:institutions,slug'],
            'city' => ['required', 'string', 'max:255'],
            'adress' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i'],
            'working_days' => ['required', 'array'],
            'max_appointments_per_day' => ['required', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'maintenance'])],
        ];
    }
}
