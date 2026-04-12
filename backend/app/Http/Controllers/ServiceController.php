<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use App\Services\ServiceCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceCatalogService $serviceCatalogService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $services = Service::query()
            ->with(['institution', 'department', 'counters'])
            ->latest()
            ->paginate($perPage);

        return $this->success($services, 'Services fetched successfully.');
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->serviceCatalogService->create($request->validated());

        return $this->success($service->load(['institution', 'department', 'counters']), 'Service created successfully.', 201);
    }

    public function show(Service $service): JsonResponse
    {
        return $this->success($service->load([
            'institution',
            'department',
            'counters',
            'queues',
            'appointments',
            'ratings',
            'analytics',
        ]), 'Service fetched successfully.');
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $updated = $this->serviceCatalogService->update($service, $request->validated());

        return $this->success($updated->load(['institution', 'department', 'counters']), 'Service updated successfully.');
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return $this->success(null, 'Service deleted successfully.');
    }
}
