<?php

namespace App\Http\Controllers;

use App\Models\Analytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $analytics = Analytics::query()
            ->with(['institution', 'service'])
            ->latest('date')
            ->paginate($perPage);

        return response()->json($analytics);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'date' => ['required', 'date'],
            'total_appointments' => ['required', 'integer', 'min:0'],
            'average_wait_time' => ['required', 'numeric', 'min:0'],
        ]);

        $analytics = Analytics::create($validated);

        return response()->json($analytics->load(['institution', 'service']), 201);
    }

    public function show(Analytics $analytic): JsonResponse
    {
        return response()->json($analytic->load(['institution', 'service']));
    }

    public function update(Request $request, Analytics $analytic): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['sometimes', 'nullable', 'exists:institutions,id'],
            'service_id' => ['sometimes', 'nullable', 'exists:services,id'],
            'date' => ['sometimes', 'required', 'date'],
            'total_appointments' => ['sometimes', 'required', 'integer', 'min:0'],
            'average_wait_time' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        $analytic->update($validated);

        return response()->json($analytic->fresh()->load(['institution', 'service']));
    }

    public function destroy(Analytics $analytic): JsonResponse
    {
        $analytic->delete();

        return response()->json(['message' => 'Analytics record deleted successfully.']);
    }
}
