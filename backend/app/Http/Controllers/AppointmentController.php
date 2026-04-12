<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $appointments = Appointment::query()
            ->with(['user', 'service', 'queue', 'counter', 'queueEntry', 'rating'])
            ->latest('appointment_date')
            ->paginate($perPage);

        return $this->success($appointments, 'Appointments fetched successfully.');
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->create($request->validated());

        return $this->success($appointment, 'Appointment created successfully.', 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return $this->success($appointment->load([
            'user',
            'service',
            'queue',
            'counter',
            'queueEntry',
            'rating',
            'messages',
        ]), 'Appointment fetched successfully.');
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $updated = $this->appointmentService->update($appointment, $request->validated());

        return $this->success($updated, 'Appointment updated successfully.');
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $cancelled = $this->appointmentService->cancel($appointment);

        return $this->success($cancelled, 'Appointment cancelled successfully.');
    }

    public function complete(Appointment $appointment): JsonResponse
    {
        $completed = $this->appointmentService->complete($appointment);

        return $this->success($completed, 'Appointment completed successfully.');
    }
}
