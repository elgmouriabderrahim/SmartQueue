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
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', 'exists:services,id'],
            'counter_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('service_counters', 'counter_number'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
        ];
    }
}
