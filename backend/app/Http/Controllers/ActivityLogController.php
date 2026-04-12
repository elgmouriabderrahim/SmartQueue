<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $logs = ActivityLog::query()
            ->with(['user', 'institution'])
            ->latest()
            ->paginate($perPage);

        return response()->json($logs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'action' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['success', 'failed'])],
        ]);

        $log = ActivityLog::create($validated);

        return response()->json($log->load(['user', 'institution']), 201);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        return response()->json($activityLog->load(['user', 'institution']));
    }

    public function update(Request $request, ActivityLog $activityLog): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'institution_id' => ['sometimes', 'nullable', 'exists:institutions,id'],
            'action' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['success', 'failed'])],
        ]);

        $activityLog->update($validated);

        return response()->json($activityLog->fresh()->load(['user', 'institution']));
    }

    public function destroy(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->delete();

        return response()->json(['message' => 'Activity log deleted successfully.']);
    }
}
