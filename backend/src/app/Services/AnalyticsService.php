<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PeakHour;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDashboardMetrics(?int $serviceId = null): array
    {
        $appointments = Appointment::query()
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId));

        $total = (clone $appointments)->count();
        $cancelled = (clone $appointments)->where('status', 'cancelled')->count();
        $cancellationRate = $total > 0 ? round(($cancelled / $total) * 100, 2) : 0;

        $averageWaitingTime = $this->averageWaitingTime($serviceId);
        $appointmentsPerDay = $this->appointmentsPerDay($serviceId);
        $peakUsage = $this->peakUsage($serviceId);

        return [
            'average_waiting_time_minutes' => $averageWaitingTime,
            'appointments_per_day' => $appointmentsPerDay,
            'cancellation_rate_percent' => $cancellationRate,
            'peak_usage' => $peakUsage,
        ];
    }

    public function syncPeakHoursForDate(string $date): void
    {
        $appointments = Appointment::query()
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get();

        $grouped = [];

        foreach ($appointments as $appointment) {
            $day = $appointment->appointment_date->toDateString();
            $hour = (int) $appointment->appointment_date->format('H');
            $key = $appointment->service_id.'|'.$day.'|'.$hour;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'service_id' => $appointment->service_id,
                    'date' => $day,
                    'hour' => $hour,
                    'count' => 0,
                ];
            }

            $grouped[$key]['count']++;
        }

        foreach ($grouped as $row) {
            PeakHour::query()->updateOrCreate(
                [
                    'service_id' => $row['service_id'],
                    'date' => $row['date'],
                    'hour' => $row['hour'],
                ],
                [
                    'appointments_count' => $row['count'],
                ]
            );
        }
    }

    private function averageWaitingTime(?int $serviceId = null): float
    {
        $query = Appointment::query()
            ->with(['queueEntry', 'service', 'queue'])
            ->whereNotNull('queue_id')
            ->whereNotIn('status', ['cancelled', 'no_show']);

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $appointments = $query->get();

        if ($appointments->isEmpty()) {
            return 0.0;
        }

        $totalWait = 0;
        $countedAppointments = 0;

        foreach ($appointments as $appointment) {
            if (! $appointment->queueEntry || ! $appointment->service || ! $appointment->queue) {
                continue;
            }

            $remainingAhead = max(
                0,
                $appointment->queueEntry->position - max(1, ((int) $appointment->queue->current_position) + 1)
            );

            $totalWait += $remainingAhead * (int) $appointment->service->estimated_duration;
            $countedAppointments++;
        }

        return round($totalWait / max(1, $countedAppointments), 2);
    }

    private function appointmentsPerDay(?int $serviceId = null): array
    {
        return Appointment::query()
            ->selectRaw('DATE(appointment_date) as day, COUNT(*) as total')
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->groupBy(DB::raw('DATE(appointment_date)'))
            ->orderBy(DB::raw('DATE(appointment_date)'))
            ->get()
            ->map(fn ($row) => ['day' => $row->day, 'total' => (int) $row->total])
            ->all();
    }

    private function peakUsage(?int $serviceId = null): array
    {
        $query = PeakHour::query()->with('service')->orderByDesc('appointments_count');

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $peak = $query->first();

        if (! $peak) {
            return [];
        }

        return [
            'service_id' => $peak->service_id,
            'service_name' => $peak->service?->name,
            'date' => $peak->date?->toDateString(),
            'hour' => $peak->hour,
            'appointments_count' => $peak->appointments_count,
        ];
    }
}
