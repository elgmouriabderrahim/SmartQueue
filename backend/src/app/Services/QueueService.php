<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Queue;
use App\Models\QueueEntry;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class QueueService
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function createOrGetQueue(int $serviceId, string $date): Queue
    {
        return Queue::query()->firstOrCreate(
            ['service_id' => $serviceId, 'date' => $date],
            ['current_position' => 0, 'total_count' => 0, 'status' => 'active']
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

            $estimatedWait = $this->estimateWaitingMinutes($appointment->service, $nextPosition, (int) $queue->current_position);

            $queue->total_count = max((int) $queue->total_count, $nextPosition);
            $queue->save();

            $entry = QueueEntry::query()->create([
                'queue_id' => $queue->id,
                'appointment_id' => $appointment->id,
                'position' => $nextPosition,
                'estimated_wait_time' => $estimatedWait,
                'status' => 'waiting',
            ]);

            $this->notifyQueueUpdates($queue->id);

            return $entry;
        });
    }

    public function removeAppointmentFromQueue(Appointment $appointment): void
    {
        $entry = $appointment->queueEntry()->first();

        if (! $entry) {
            return;
        }

        DB::transaction(function () use ($appointment, $entry) {
            $queueId = $entry->queue_id;
            $removedPosition = $entry->position;

            $entry->delete();

            QueueEntry::query()
                ->where('queue_id', $queueId)
                ->where('position', '>', $removedPosition)
                ->orderBy('position')
                ->each(function (QueueEntry $queueEntry): void {
                    $queueEntry->position -= 1;
                    $serviceDuration = (int) ($queueEntry->appointment?->service?->duration ?? 0);
                    $queueEntry->estimated_wait_time = max(0, ($queueEntry->position - 1) * $serviceDuration);
                    $queueEntry->save();
                });

            $queue = Queue::query()->find($queueId);
            if ($queue && $queue->current_position >= $removedPosition) {
                $queue->current_position = max(0, $queue->current_position - 1);
            }

            if ($queue) {
                $queue->total_count = (int) QueueEntry::query()->where('queue_id', $queueId)->count();
                $queue->save();

                $this->notifyQueueUpdates($queue->id);
            }

            $appointment->queue_id = null;
            $appointment->save();
            $appointment->unsetRelation('queueEntry');
            $appointment->unsetRelation('queue');
        });
    }

    public function markAppointmentCompleted(Appointment $appointment): void
    {
        $entry = $appointment->queueEntry()->first();

        if (! $entry) {
            return;
        }

        DB::transaction(function () use ($entry) {
            $entry->status = 'served';
            $entry->save();

            $queue = $entry->queue;
            $queue->current_position = max($queue->current_position, $entry->position);
            $queue->save();

            $this->notifyQueueUpdates($queue->id);
        });
    }

    public function notifyQueueUpdates(int $queueId): void
    {
        $queue = Queue::query()->find($queueId);
        if (! $queue) {
            return;
        }

        $entries = QueueEntry::query()
            ->where('queue_id', $queueId)
            ->where('status', 'waiting')
            ->with('appointment.user')
            ->orderBy('position')
            ->get();

        foreach ($entries as $entry) {
            $user = $entry->appointment?->user;
            if (! $user) {
                continue;
            }

            $isTurn = $entry->position === ((int) $queue->current_position + 1);

            $this->notificationService->createForUser($user, 'queue_status', [
                'title' => $isTurn ? 'It is your turn' : 'Queue position updated',
                'message' => $isTurn
                    ? 'Please proceed, it is now your turn.'
                    : 'Your queue position is now #'.$entry->position.'.',
            ]);
        }
    }

    public function estimateWaitingMinutes(Service $service, int $position, int $currentPosition = 0): int
    {
        $remainingAhead = max(0, $position - max(1, $currentPosition + 1));

        return max(0, $remainingAhead * $service->duration);
    }
}
