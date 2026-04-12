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
        $serviceId = $this->input('service_id', $serviceCounter?->service_id);

        return [
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'counter_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('service_counters', 'counter_number')
                    ->where(fn ($query) => $query->where('service_id', $serviceId))
                    ->ignore($serviceCounter?->id),
            ],
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
        ];
    }
}
