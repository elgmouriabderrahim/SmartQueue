<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $institutions = Institution::query()
            ->with(['departments', 'services'])
            ->latest()
            ->paginate($perPage);

        return response()->json($institutions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:institutions,slug'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $institution = Institution::create($validated);

        return response()->json($institution->load(['departments', 'services']), 201);
    }

    public function show(Institution $institution): JsonResponse
    {
        return response()->json($institution->load([
            'departments',
            'services',
            'users',
            'settings',
            'analytics',
            'activityLogs',
        ]));
    }

    public function update(Request $request, Institution $institution): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('institutions', 'slug')->ignore($institution->id),
            ],
            'city' => ['sometimes', 'required', 'string', 'max:255'],
            'region' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        $institution->update($validated);

        return response()->json($institution->fresh()->load(['departments', 'services']));
    }

    public function destroy(Institution $institution): JsonResponse
    {
        $institution->delete();

        return response()->json(['message' => 'Institution deleted successfully.']);
    }
}
