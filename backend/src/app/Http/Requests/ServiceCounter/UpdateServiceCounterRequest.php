<?php

namespace App\Http\Requests\ServiceCounter;

use App\Models\ServiceCounter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ServiceCounter|null $serviceCounter */
        $serviceCounter = $this->route('serviceCounter') ?? $this->route('service_counter');

        return [
            'service_ids' => ['sometimes', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'distinct', 'exists:services,id'],
            'counter_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('service_counters', 'counter_number')->ignore($serviceCounter?->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
        ];
    }
}
