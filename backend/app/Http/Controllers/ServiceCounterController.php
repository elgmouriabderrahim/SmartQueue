<?php

namespace App\Http\Controllers;

use App\Models\ServiceCounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceCounterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $counters = ServiceCounter::query()
            ->with(['service'])
            ->latest()
            ->paginate($perPage);

        return response()->json($counters);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'counter_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('service_counters', 'counter_number')->where(
                    fn ($query) => $query->where('service_id', $request->input('service_id'))
                ),
            ],
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
        ]);

        $counter = ServiceCounter::create($validated);

        return response()->json($counter->load('service'), 201);
    }

    public function show(ServiceCounter $serviceCounter): JsonResponse
    {
        return response()->json($serviceCounter->load(['service', 'appointments']));
    }

    public function update(Request $request, ServiceCounter $serviceCounter): JsonResponse
    {
        $serviceId = $request->input('service_id', $serviceCounter->service_id);

        $validated = $request->validate([
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'counter_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('service_counters', 'counter_number')
                    ->where(fn ($query) => $query->where('service_id', $serviceId))
                    ->ignore($serviceCounter->id),
            ],
            'status' => ['sometimes', Rule::in(['available', 'busy', 'offline'])],
        ]);

        $serviceCounter->update($validated);

        return response()->json($serviceCounter->fresh()->load('service'));
    }

    public function destroy(ServiceCounter $serviceCounter): JsonResponse
    {
        $serviceCounter->delete();

        return response()->json(['message' => 'Service counter deleted successfully.']);
    }
}
