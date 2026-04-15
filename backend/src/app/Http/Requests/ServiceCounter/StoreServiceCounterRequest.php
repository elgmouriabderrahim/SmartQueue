<?php

namespace App\Http\Requests\ServiceCounter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceCounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'counter_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_counters', 'counter_number')->where(
                    fn ($query) => $query->where('service_id', $this->input('service_id'))
                ),
            ],
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
        ];
    }
}
