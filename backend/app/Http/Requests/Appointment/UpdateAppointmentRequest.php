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
        $isCitizen = $this->user()?->role === 'citizen';

        return [
            'user_id' => ['sometimes', Rule::requiredIf(! $isCitizen), 'exists:users,id'],
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'service_counter_id' => ['sometimes', 'nullable', 'exists:service_counters,id'],
            'appointment_date' => ['sometimes', 'required', 'date'],
            'reference_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('appointments', 'reference_code')->ignore($appointment?->id)],
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled'])],
        ];
    }
}
