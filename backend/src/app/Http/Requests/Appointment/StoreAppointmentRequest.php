<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $serviceId = (int) $this->input('service_id');

        return [
            'service_id' => ['required', 'exists:services,id'],
            'service_counter_id' => [
                'nullable',
                Rule::exists('service_counters', 'id')->where(function ($query) use ($serviceId) {
                    if ($serviceId > 0) {
                        $query->where('service_id', $serviceId);
                    }
                }),
            ],
            'appointment_date' => ['required', 'date'],
        ];
    }
}
