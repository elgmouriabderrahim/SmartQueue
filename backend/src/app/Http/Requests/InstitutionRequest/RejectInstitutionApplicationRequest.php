<?php

namespace App\Http\Requests\InstitutionRequest;

use Illuminate\Foundation\Http\FormRequest;

class RejectInstitutionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
