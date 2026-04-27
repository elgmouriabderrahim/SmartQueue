<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $userManagementService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $users = User::query()
            ->with(['institutions'])
            ->latest()
            ->paginate($perPage);

        return $this->success($users, 'Users fetched successfully.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userManagementService->create($request->validated());

        return $this->success($user->load(['institutions']), 'User created successfully.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success($user->load([
            'institutions',
            'appointments',
            'ratings',
            'messagesSent',
            'messagesReceived',
            'activityLogs',
        ]), 'User fetched successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updated = $this->userManagementService->update($user, $request->validated());

        return $this->success($updated->load(['institutions']), 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()?->id === (int) $user->id) {
            return $this->error('You cannot delete your own account.', 422);
        }

        $this->userManagementService->delete($user);

        return $this->success(null, 'User deleted successfully.');
    }
}
