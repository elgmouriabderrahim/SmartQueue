<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departmentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $departments = Department::query()
            ->with(['institution', 'services'])
            ->latest()
            ->paginate($perPage);

        return $this->success($departments, 'Departments fetched successfully.');
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->role === 'manager' && $request->integer('institution_id') !== (int) $user->institution_id) {
            return $this->error('Forbidden.', 403);
        }

        $department = $this->departmentService->create($request->validated());

        return $this->success($department->load(['institution', 'services']), 'Department created successfully.', 201);
    }

    public function show(Department $department): JsonResponse
    {
        return $this->success($department->load(['institution', 'services', 'users']), 'Department fetched successfully.');
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->role === 'manager' && $department->institution_id !== (int) $user->institution_id) {
            return $this->error('Forbidden.', 403);
        }

        $updated = $this->departmentService->update($department, $request->validated());

        return $this->success($updated->load(['institution', 'services']), 'Department updated successfully.');
    }

    public function destroy(Department $department): JsonResponse
    {
        $user = request()->user();
        if ($user && $user->role === 'manager' && $department->institution_id !== (int) $user->institution_id) {
            return $this->error('Forbidden.', 403);
        }

        $this->departmentService->delete($department);

        return $this->success(null, 'Department deleted successfully.');
    }
}
