<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\Appointment;
use App\Models\Rating;
use App\Models\Service;

class AnalyticsService
{
    public function getDashboardMetrics(?int $serviceId = null): array
    {
        $appointments = Appointment::query()->when($serviceId, fn ($q) => $q->where('service_id', $serviceId));

        $totalAppointments = (clone $appointments)->count();
        $completedAppointments = (clone $appointments)->where('status', 'completed')->count();
        $cancelledAppointments = (clone $appointments)->where('status', 'cancelled')->count();
        $totalVisitors = (clone $appointments)->distinct('user_id')->count('user_id');

        $averageRating = (float) (Rating::query()
            ->when($serviceId, fn ($q) => $q->where('service_id', $serviceId))
            ->avg('score') ?? 0);

        $averageWaitTime = (float) (Appointment::query()
            ->join('queue_entries', 'queue_entries.appointment_id', '=', 'appointments.id')
            ->when($serviceId, fn ($q) => $q->where('appointments.service_id', $serviceId))
            ->avg('queue_entries.estimated_wait_time') ?? 0);

        return [
            'total_appointments' => $totalAppointments,
            'completed_appointments' => $completedAppointments,
            'cancelled_appointments' => $cancelledAppointments,
            'total_visitors' => $totalVisitors,
            'average_rating' => round($averageRating, 2),
            'average_wait_time' => round($averageWaitTime, 2),
        ];
    }

    public function syncForDate(string $date): void
    {
        $serviceIds = Appointment::query()
            ->whereDate('appointment_date', $date)
            ->distinct()
            ->pluck('service_id');

        foreach ($serviceIds as $serviceId) {
            $service = Service::query()->find($serviceId);
            if (! $service) {
                continue;
            }

            $metrics = $this->getDashboardMetrics((int) $serviceId);

            Analytics::query()->updateOrCreate(
                [
                    'institution_id' => $service->institution_id,
                    'service_id' => $service->id,
                ],
                $metrics
            );
        }
    }
}
