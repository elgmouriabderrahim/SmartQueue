<?php

namespace App\Http\Controllers;

use App\Http\Requests\Institution\StoreInstitutionRequest;
use App\Http\Requests\Institution\UpdateInstitutionRequest;
use App\Models\Institution;
use App\Services\InstitutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function __construct(private readonly InstitutionService $institutionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $city = trim($request->string('city')->toString());
        $search = trim($request->string('q')->toString());

        $institutions = Institution::query()
            ->with(['departments', 'services'])
            ->when($city !== '', fn ($query) => $query->where('city', 'like', '%'.$city.'%'))
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            }))
            ->latest()
            ->paginate($perPage);

        return $this->success($institutions, 'Institutions fetched successfully.');
    }

    public function map(Request $request): JsonResponse
    {
        $institutions = Institution::query()
            ->select(['id', 'name', 'slug', 'city', 'adress', 'status', 'opening_time', 'closing_time'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy('name')
            ->get();

        return $this->success($institutions, 'Institutions map data fetched successfully.');
    }

    public function store(StoreInstitutionRequest $request): JsonResponse
    {
        $institution = $this->institutionService->create($request->validated());

        return $this->success($institution->load(['departments', 'services']), 'Institution created successfully.', 201);
    }

    public function show(Institution $institution): JsonResponse
    {
        return $this->success($institution->load([
            'departments',
            'services',
            'users',
            'analytics',
            'activityLogs',
        ]), 'Institution fetched successfully.');
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->role === 'manager' && $user->institution_id !== $institution->id) {
            return $this->error('Forbidden.', 403);
        }

        $updated = $this->institutionService->update($institution, $request->validated());

        return $this->success($updated->load(['departments', 'services']), 'Institution updated successfully.');
    }

    public function destroy(Institution $institution): JsonResponse
    {
        $this->institutionService->delete($institution);

        return $this->success(null, 'Institution deleted successfully.');
    }

    public function approve(Institution $institution): JsonResponse
    {
        if ($institution->status === 'active') {
            return $this->success($institution, 'Institution is already active.');
        }

        $institution->status = 'active';
        $institution->save();

        return $this->success($institution->fresh(), 'Institution approved successfully.');
    }
}
