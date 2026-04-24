<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $logs = ActivityLog::query()
            ->with(['user', 'institution'])
            ->latest()
            ->paginate($perPage);

        return $this->success($logs, 'Activity logs fetched successfully.');
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        return $this->success($activityLog->load(['user', 'institution']), 'Activity log fetched successfully.');
    }

    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        $this->activityLogService->delete($activityLog);

        return $this->success(null, 'Activity log deleted successfully.');
    }
}
