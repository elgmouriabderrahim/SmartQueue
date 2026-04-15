<?php

namespace App\Http\Controllers;

use App\Http\Requests\Analytics\SyncAnalyticsRequest;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $serviceId = $request->filled('service_id') ? $request->integer('service_id') : null;

        return $this->success(
            $this->analyticsService->getDashboardMetrics($serviceId),
            'Analytics fetched successfully.'
        );
    }

    public function sync(SyncAnalyticsRequest $request): JsonResponse
    {
        $this->analyticsService->syncPeakHoursForDate($request->string('date')->toString());

        return $this->success(null, 'Peak hours synced successfully.');
    }
}
