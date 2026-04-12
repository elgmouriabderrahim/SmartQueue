<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $users = User::query()
            ->with(['institution', 'department'])
            ->latest()
            ->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:255'],
            'identity_number' => ['nullable', 'string', 'max:255', 'unique:users,identity_number'],
            'role' => ['sometimes', Rule::in(['citizen', 'employee', 'manager', 'admin'])],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $user = User::create($validated);

        return response()->json($user->load(['institution', 'department']), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load([
            'institution',
            'department',
            'appointments',
            'ratings',
            'messagesSent',
            'messagesReceived',
            'activityLogs',
        ]));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'identity_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'identity_number')->ignore($user->id),
            ],
            'role' => ['sometimes', Rule::in(['citizen', 'employee', 'manager', 'admin'])],
            'institution_id' => ['sometimes', 'nullable', 'exists:institutions,id'],
            'department_id' => ['sometimes', 'nullable', 'exists:departments,id'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        if (array_key_exists('password', $validated) && $validated['password'] === null) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user->fresh()->load(['institution', 'department']));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
