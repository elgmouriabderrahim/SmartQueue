<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceCounter\StoreServiceCounterRequest;
use App\Http\Requests\ServiceCounter\UpdateServiceCounterRequest;
use App\Models\ServiceCounter;
use App\Services\ServiceCounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceCounterController extends Controller
{
    public function __construct(private readonly ServiceCounterService $serviceCounterService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $counters = ServiceCounter::query()
            ->with(['service'])
            ->latest()
            ->paginate($perPage);

        return $this->success($counters, 'Service counters fetched successfully.');
    }

    public function store(StoreServiceCounterRequest $request): JsonResponse
    {
        $counter = $this->serviceCounterService->create($request->validated());

        return $this->success($counter->load('service'), 'Service counter created successfully.', 201);
    }

    public function show(ServiceCounter $serviceCounter): JsonResponse
    {
        return $this->success($serviceCounter->load(['service', 'appointments']), 'Service counter fetched successfully.');
    }

    public function update(UpdateServiceCounterRequest $request, ServiceCounter $serviceCounter): JsonResponse
    {
        $updated = $this->serviceCounterService->update($serviceCounter, $request->validated());

        return $this->success($updated->load('service'), 'Service counter updated successfully.');
    }

    public function destroy(ServiceCounter $serviceCounter): JsonResponse
    {
        $this->serviceCounterService->delete($serviceCounter);

        return $this->success(null, 'Service counter deleted successfully.');
    }
}
