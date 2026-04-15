<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
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

        if ($user->role !== 'admin') {
            return $this->error('Forbidden. Admin access required.', 403);
        }

        return $this->success($this->analyticsService->getDashboardMetrics(), 'Dashboard metrics fetched successfully.');
    }
}
