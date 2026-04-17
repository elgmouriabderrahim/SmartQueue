<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function create(array $data): Appointment
    {
        $service = Service::query()->findOrFail($data['service_id']);
        $appointmentDate = \Illuminate\Support\Carbon::parse($data['appointment_date']);

        $this->guardAgainstDoubleBooking((int) $data['user_id'], $appointmentDate->toDateTimeString());
        $this->validateServiceAvailability($service, $appointmentDate->toDateString());

        $appointment = Appointment::query()->create([
            'user_id' => $data['user_id'],
            'service_id' => $data['service_id'],
            'service_counter_id' => $data['service_counter_id'] ?? null,
            'appointment_date' => $appointmentDate,
            'reference_code' => $data['reference_code'] ?? $this->generateReferenceCode(),
            'status' => $data['status'] ?? 'confirmed',
        ]);

        $queueEntry = $this->queueService->attachAppointmentToQueue($appointment);

        $user = User::query()->find($appointment->user_id);
        if ($user) {
            $this->notificationService->createForUser($user, 'appointment_reminder', [
                'title' => 'Appointment confirmed',
                'message' => 'Your appointment has been confirmed.',
                'appointment_id' => $appointment->id,
                'reference_code' => $appointment->reference_code,
                'queue_position' => $queueEntry->position,
            ]);
        }

        return $this->enrichWithQueueData($appointment->fresh(['service', 'queueEntry', 'queue']));
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        $newDate = isset($data['appointment_date'])
            ? \Illuminate\Support\Carbon::parse($data['appointment_date'])
            : $appointment->appointment_date;

        $newServiceId = $data['service_id'] ?? $appointment->service_id;
        $newUserId = $data['user_id'] ?? $appointment->user_id;

        if ($newDate->toDateTimeString() !== $appointment->appointment_date->toDateTimeString()
            || $newUserId !== $appointment->user_id) {
            $this->guardAgainstDoubleBooking((int) $newUserId, $newDate->toDateTimeString(), $appointment->id);
        }

        $shouldReattachQueue = $newServiceId !== $appointment->service_id
            || $newDate->toDateString() !== $appointment->appointment_date->toDateString();

        if ($shouldReattachQueue) {
            $service = Service::query()->findOrFail((int) $newServiceId);
            $this->validateServiceAvailability($service, $newDate->toDateString(), $appointment->id);

            $this->queueService->removeAppointmentFromQueue($appointment);
        }

        $appointment->fill($data);
        if (isset($data['appointment_date'])) {
            $appointment->appointment_date = $newDate;
        }
        $appointment->save();

        if ($shouldReattachQueue || ! $appointment->queueEntry()->exists()) {
            $this->queueService->attachAppointmentToQueue($appointment);
        }

        return $this->enrichWithQueueData($appointment->fresh(['service', 'queueEntry', 'queue']));
    }

    public function cancel(Appointment $appointment): Appointment
    {
        $appointment->status = 'cancelled';
        $appointment->save();

        $this->queueService->removeAppointmentFromQueue($appointment);

        $user = User::query()->find($appointment->user_id);
        if ($user) {
            $this->notificationService->createForUser($user, 'queue_status', [
                'title' => 'Appointment cancelled',
                'message' => 'Your appointment has been cancelled.',
                'appointment_id' => $appointment->id,
            ]);
        }

        return $this->enrichWithQueueData($appointment->fresh(['service', 'queueEntry', 'queue']));
    }

    public function complete(Appointment $appointment): Appointment
    {
        $appointment->status = 'completed';
        $appointment->save();

        $this->queueService->markAppointmentCompleted($appointment);

        return $this->enrichWithQueueData($appointment->fresh(['service', 'queueEntry', 'queue']));
    }

    private function validateServiceAvailability(Service $service, string $date, ?int $ignoreAppointmentId = null): void
    {
        $appointmentsCount = Appointment::query()
            ->where('service_id', $service->id)
            ->whereDate('appointment_date', $date)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where('status', '!=', 'cancelled')
            ->count();

        if ($appointmentsCount >= $service->capacity) {
            throw ValidationException::withMessages([
                'appointment_date' => ['No availability for this service on the selected date.'],
            ]);
        }
    }

    private function guardAgainstDoubleBooking(int $userId, string $appointmentDateTime, ?int $ignoreAppointmentId = null): void
    {
        $exists = Appointment::query()
            ->where('user_id', $userId)
            ->where('appointment_date', $appointmentDateTime)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'appointment_date' => ['You already have an appointment at this date and time.'],
            ]);
        }
    }

    private function generateReferenceCode(): string
    {
        do {
            $reference = strtoupper(Str::random(10));
        } while (Appointment::query()->where('reference_code', $reference)->exists());

        return $reference;
    }

    private function enrichWithQueueData(Appointment $appointment): Appointment
    {
        $queueEntry = $appointment->queueEntry;
        $queue = $appointment->queue;
        $service = $appointment->service;

        $appointment->setAttribute('queue_position', $queueEntry?->position);

        if ($queueEntry && $queue && $service) {
            $appointment->setAttribute(
                'estimated_waiting_minutes',
                $this->queueService->estimateWaitingMinutes($service, $queueEntry->position, (int) $queue->current_position)
            );
        } else {
            $appointment->setAttribute('estimated_waiting_minutes', 0);
        }

        return $appointment;
    }
}
