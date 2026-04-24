<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'service_counter_id' => ['nullable', 'exists:service_counters,id'],
            'appointment_date' => ['required', 'date'],
        ];
    }
}
