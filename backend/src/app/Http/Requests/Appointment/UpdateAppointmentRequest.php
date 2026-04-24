<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'service_counter_id' => ['sometimes', 'nullable', 'exists:service_counters,id'],
            'appointment_date' => ['sometimes', 'required', 'date'],
        ];
    }
}
