<?php

namespace App\Http\Requests\Rating;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'service_id' => ['nullable', 'required_without:institution_id', 'exists:services,id'],
            'institution_id' => ['nullable', 'required_without:service_id', 'exists:institutions,id'],
            'score' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
