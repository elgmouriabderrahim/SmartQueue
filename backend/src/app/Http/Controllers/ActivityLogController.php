<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLog\StoreActivityLogRequest;
use App\Http\Requests\ActivityLog\UpdateActivityLogRequest;
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

    public function store(StoreActivityLogRequest $request): JsonResponse
    {
        $log = $this->activityLogService->create($request->validated());

        return $this->success($log->load(['user', 'institution']), 'Activity log created successfully.', 201);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        return $this->success($activityLog->load(['user', 'institution']), 'Activity log fetched successfully.');
    }

    public function update(UpdateActivityLogRequest $request, ActivityLog $activityLog): JsonResponse
    {
        $updated = $this->activityLogService->update($activityLog, $request->validated());

        return $this->success($updated->load(['user', 'institution']), 'Activity log updated successfully.');
    }

    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        $this->activityLogService->delete($activityLog);

        return $this->success(null, 'Activity log deleted successfully.');
    }
}
