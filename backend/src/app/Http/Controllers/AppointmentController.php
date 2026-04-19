<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService)
    {
    }

    #[OA\Get(
        path: '/appointments',
        tags: ['Appointments'],
        summary: 'List appointments',
        responses: [new OA\Response(response: 200, description: 'Appointments fetched')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $user = $request->user();

        $appointments = Appointment::query()
            ->with(['user', 'service', 'queue', 'counter', 'queueEntry', 'rating'])
            ->when($user && $user->role === 'citizen', fn ($query) => $query->where('user_id', $user->id))
            ->latest('appointment_date')
            ->paginate($perPage);

        return $this->success($appointments, 'Appointments fetched successfully.');
    }

    #[OA\Post(
        path: '/appointments',
        tags: ['Appointments'],
        summary: 'Create appointment',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['service_id', 'appointment_date'],
                properties: [
                    new OA\Property(property: 'service_id', type: 'integer', example: 1),
                    new OA\Property(property: 'appointment_date', type: 'string', example: '2026-05-10 09:00:00'),
                    new OA\Property(property: 'user_id', type: 'integer', nullable: true, example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Appointment created'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $authUser = $request->user();
        if ($authUser && $authUser->role === 'citizen') {
            $data['user_id'] = $authUser->id;
        }

        $appointment = $this->appointmentService->create($data);

        return $this->success($appointment, 'Appointment created successfully.', 201);
    }

    #[OA\Get(
        path: '/appointments/{appointment}',
        tags: ['Appointments'],
        summary: 'Get appointment',
        parameters: [new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Appointment fetched'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function show(Appointment $appointment): JsonResponse
    {
        if ($response = $this->denyIfCitizenNotOwner(request(), $appointment)) {
            return $response;
        }

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

    #[OA\Put(
        path: '/appointments/{appointment}',
        tags: ['Appointments'],
        summary: 'Update appointment',
        parameters: [new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Appointment updated')]
    )]
    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        if ($response = $this->denyIfCitizenNotOwner($request, $appointment)) {
            return $response;
        }

        $data = $request->validated();
        $authUser = $request->user();
        if ($authUser && $authUser->role === 'citizen') {
            $data['user_id'] = $authUser->id;
        }

        $updated = $this->appointmentService->update($appointment, $data);

        return $this->success($updated, 'Appointment updated successfully.');
    }

    #[OA\Delete(
        path: '/appointments/{appointment}',
        tags: ['Appointments'],
        summary: 'Cancel appointment',
        parameters: [new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Appointment cancelled')]
    )]
    public function destroy(Appointment $appointment): JsonResponse
    {
        if ($response = $this->denyIfCitizenNotOwner(request(), $appointment)) {
            return $response;
        }

        $cancelled = $this->appointmentService->cancel($appointment);

        return $this->success($cancelled, 'Appointment cancelled successfully.');
    }

    #[OA\Patch(
        path: '/appointments/{appointment}/complete',
        tags: ['Appointments'],
        summary: 'Complete appointment',
        parameters: [new OA\Parameter(name: 'appointment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Appointment completed')]
    )]
    public function complete(Appointment $appointment): JsonResponse
    {
        $completed = $this->appointmentService->complete($appointment);

        return $this->success($completed, 'Appointment completed successfully.');
    }

    public function queuePosition(Request $request, Appointment $appointment): JsonResponse
    {
        if ($response = $this->denyIfCitizenNotOwner($request, $appointment)) {
            return $response;
        }

        $appointment->load(['queue', 'queueEntry', 'service']);

        return $this->success([
            'appointment_id' => $appointment->id,
            'status' => $appointment->status,
            'queue_id' => $appointment->queue_id,
            'queue_current_position' => $appointment->queue?->current_position,
            'queue_position' => $appointment->queue_position,
            'estimated_waiting_minutes' => $appointment->estimated_waiting_minutes,
        ], 'Appointment queue position fetched successfully.');
    }

    private function denyIfCitizenNotOwner(Request $request, Appointment $appointment): ?JsonResponse
    {
        $user = $request->user();

        if ($user && $user->role === 'citizen' && $appointment->user_id !== $user->id) {
            return $this->error('Forbidden.', 403);
        }

        return null;
    }
}
