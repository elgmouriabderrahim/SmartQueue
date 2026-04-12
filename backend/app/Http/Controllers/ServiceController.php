<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $services = Service::query()
            ->with(['institution', 'department', 'counters'])
            ->latest()
            ->paginate($perPage);

        return response()->json($services);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'estimated_duration' => ['required', 'integer', 'min:1'],
            'max_daily_capacity' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        $service = Service::create($validated);

        return response()->json($service->load(['institution', 'department', 'counters']), 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json($service->load([
            'institution',
            'department',
            'counters',
            'queues',
            'appointments',
            'ratings',
            'analytics',
        ]));
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['sometimes', 'required', 'exists:institutions,id'],
            'department_id' => ['sometimes', 'nullable', 'exists:departments,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'estimated_duration' => ['sometimes', 'required', 'integer', 'min:1'],
            'max_daily_capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        $service->update($validated);

        return response()->json($service->fresh()->load(['institution', 'department', 'counters']));
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(['message' => 'Service deleted successfully.']);
    }
}
