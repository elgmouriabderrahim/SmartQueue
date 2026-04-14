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
        $isCitizen = $this->user()?->role === 'citizen';

        return [
            'user_id' => [Rule::requiredIf(! $isCitizen), 'exists:users,id'],
            'service_id' => ['required', 'exists:services,id'],
            'service_counter_id' => ['nullable', 'exists:service_counters,id'],
            'appointment_date' => ['required', 'date'],
            'reference_code' => ['nullable', 'string', 'max:255', 'unique:appointments,reference_code'],
            'status' => ['sometimes', Rule::in(['pending', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled'])],
        ];
    }
}
