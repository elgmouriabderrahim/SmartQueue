<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $departments = Department::query()
            ->with(['institution', 'services'])
            ->latest()
            ->paginate($perPage);

        return response()->json($departments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'slug')->where(
                    fn ($query) => $query->where('institution_id', $request->input('institution_id'))
                ),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        $department = Department::create($validated);

        return response()->json($department->load(['institution', 'services']), 201);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json($department->load(['institution', 'services', 'users']));
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $institutionId = $request->input('institution_id', $department->institution_id);

        $validated = $request->validate([
            'institution_id' => ['sometimes', 'required', 'exists:institutions,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'slug')
                    ->where(fn ($query) => $query->where('institution_id', $institutionId))
                    ->ignore($department->id),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        $department->update($validated);

        return response()->json($department->fresh()->load(['institution', 'services']));
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully.']);
    }
}
