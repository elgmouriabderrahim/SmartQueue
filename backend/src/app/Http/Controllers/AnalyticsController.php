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
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $serviceId = $request->filled('service_id') ? $request->integer('service_id') : null;

        if ($user->role === 'manager') {
            return $this->success(
                $this->analyticsService->getDashboardMetricsByInstitution((int) $user->institution_id),
                'Analytics fetched successfully.'
            );
        }

        return $this->success(
            $this->analyticsService->getDashboardMetrics($serviceId),
            'Analytics fetched successfully.'
        );
    }

    public function sync(SyncAnalyticsRequest $request): JsonResponse
    {
        $this->analyticsService->syncForDate($request->string('date')->toString());

        return $this->success(null, 'Analytics synced successfully.');
    }
}
