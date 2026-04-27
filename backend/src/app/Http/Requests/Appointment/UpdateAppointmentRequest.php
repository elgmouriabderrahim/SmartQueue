<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $appointment = $this->route('appointment');
        $serviceId = (int) ($this->input('service_id') ?? $appointment?->service_id ?? 0);

        return [
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'service_counter_id' => [
                'sometimes',
                'nullable',
                Rule::exists('service_counter_service', 'service_counter_id')->where(function ($query) use ($serviceId) {
                    if ($serviceId > 0) {
                        $query->where('service_id', $serviceId);
                    }
                }),
            ],
            'appointment_date' => ['sometimes', 'required', 'date'],
        ];
    }
}
