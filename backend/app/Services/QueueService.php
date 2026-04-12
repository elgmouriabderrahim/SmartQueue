<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class QueueService
{
    public function createOrGetQueue(int $serviceId, string $date): Queue
    {
        return Queue::query()->firstOrCreate(
            ['service_id' => $serviceId, 'date' => $date],
            ['current_position' => 0, 'status' => 'active']
        );
    }

    public function attachAppointmentToQueue(Appointment $appointment): QueueEntry
    {
        return DB::transaction(function () use ($appointment) {
            $queue = $this->createOrGetQueue(
                $appointment->service_id,
                $appointment->appointment_date->toDateString()
            );

            $nextPosition = ((int) $queue->entries()->max('position')) + 1;

            $appointment->queue_id = $queue->id;
            $appointment->save();

            return QueueEntry::query()->create([
                'queue_id' => $queue->id,
                'appointment_id' => $appointment->id,
                'position' => $nextPosition,
                'status' => 'waiting',
            ]);
        });
    }

    public function removeAppointmentFromQueue(Appointment $appointment): void
    {
        if (! $appointment->queueEntry) {
            return;
        }

        DB::transaction(function () use ($appointment) {
            $entry = $appointment->queueEntry;
            $queueId = $entry->queue_id;
            $removedPosition = $entry->position;

            $entry->delete();

            QueueEntry::query()
                ->where('queue_id', $queueId)
                ->where('position', '>', $removedPosition)
                ->orderBy('position')
                ->each(function (QueueEntry $queueEntry): void {
                    $queueEntry->position -= 1;
                    $queueEntry->save();
                });
        });
    }

    public function markAppointmentCompleted(Appointment $appointment): void
    {
        if (! $appointment->queueEntry) {
            return;
        }

        DB::transaction(function () use ($appointment) {
            $entry = $appointment->queueEntry;
            $entry->status = 'served';
            $entry->save();

            $queue = $entry->queue;
            $queue->current_position = max($queue->current_position, $entry->position);
            $queue->save();
        });
    }

    public function estimateWaitingMinutes(Service $service, int $position): int
    {
        return max(0, ($position - 1) * $service->estimated_duration);
    }
}
