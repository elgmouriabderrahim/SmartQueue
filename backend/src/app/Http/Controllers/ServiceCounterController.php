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
            ->when(
                $request->user() && in_array($request->user()->role, ['manager', 'employee'], true),
                fn ($query) => $query->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('institution_id', $request->user()->institution_id))
            )
            ->latest()
            ->paginate($perPage);

        return $this->success($counters, 'Service counters fetched successfully.');
    }

    public function store(StoreServiceCounterRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user && in_array($user->role, ['manager', 'employee'], true)) {
            $belongsToInstitution = \App\Models\Service::query()
                ->where('id', $request->integer('service_id'))
                ->where('institution_id', $user->institution_id)
                ->exists();

            if (! $belongsToInstitution) {
                return $this->error('Forbidden.', 403);
            }
        }

        $counter = $this->serviceCounterService->create($request->validated());

        return $this->success($counter->load('service'), 'Service counter created successfully.', 201);
    }

    public function show(ServiceCounter $serviceCounter): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess(request(), $serviceCounter)) {
            return $response;
        }

        return $this->success($serviceCounter->load(['service', 'appointments']), 'Service counter fetched successfully.');
    }

    public function update(UpdateServiceCounterRequest $request, ServiceCounter $serviceCounter): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess($request, $serviceCounter)) {
            return $response;
        }

        $updated = $this->serviceCounterService->update($serviceCounter, $request->validated());

        return $this->success($updated->load('service'), 'Service counter updated successfully.');
    }

    public function destroy(ServiceCounter $serviceCounter): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess(request(), $serviceCounter)) {
            return $response;
        }

        $this->serviceCounterService->delete($serviceCounter);

        return $this->success(null, 'Service counter deleted successfully.');
    }

    private function forbidIfNoInstitutionAccess(Request $request, ServiceCounter $serviceCounter): ?JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        if ($user->role === 'admin') {
            return null;
        }

        if (! in_array($user->role, ['manager', 'employee'], true)) {
            return $this->error('Forbidden.', 403);
        }

        $belongsToInstitution = $serviceCounter->service()->where('institution_id', $user->institution_id)->exists();
        if (! $belongsToInstitution) {
            return $this->error('Forbidden.', 403);
        }

        return null;
    }
}
