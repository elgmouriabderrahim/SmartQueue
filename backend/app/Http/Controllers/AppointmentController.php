<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $appointments = Appointment::query()
            ->with(['user', 'service', 'queue', 'counter', 'queueEntry', 'rating'])
            ->latest('appointment_date')
            ->paginate($perPage);

        return response()->json($appointments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'service_id' => ['required', 'exists:services,id'],
            'queue_id' => ['nullable', 'exists:queues,id'],
            'service_counter_id' => ['nullable', 'exists:service_counters,id'],
            'reference_code' => ['nullable', 'string', 'max:255', 'unique:appointments,reference_code'],
            'appointment_date' => ['required', 'date'],
            'status' => [
                'sometimes',
                Rule::in(['pending', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled']),
            ],
        ]);

        if (empty($validated['reference_code'])) {
            $validated['reference_code'] = $this->generateReferenceCode();
        }

        $appointment = Appointment::create($validated);

        return response()->json($appointment->load(['user', 'service', 'queue', 'counter']), 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json($appointment->load([
            'user',
            'service',
            'queue',
            'counter',
            'queueEntry',
            'rating',
            'messages',
        ]));
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'required', 'exists:users,id'],
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'queue_id' => ['sometimes', 'nullable', 'exists:queues,id'],
            'service_counter_id' => ['sometimes', 'nullable', 'exists:service_counters,id'],
            'reference_code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('appointments', 'reference_code')->ignore($appointment->id),
            ],
            'appointment_date' => ['sometimes', 'required', 'date'],
            'status' => [
                'sometimes',
                Rule::in(['pending', 'confirmed', 'in_progress', 'completed', 'no_show', 'cancelled']),
            ],
        ]);

        $appointment->update($validated);

        return response()->json($appointment->fresh()->load(['user', 'service', 'queue', 'counter']));
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully.']);
    }

    private function generateReferenceCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (Appointment::query()->where('reference_code', $code)->exists());

        return $code;
    }
}
