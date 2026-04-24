<?php

namespace App\Http\Controllers;

use App\Http\Requests\Institution\InviteInstitutionEmployeeRequest;
use App\Models\Institution;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitutionStaffController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request, Institution $institution): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess($request, $institution)) {
            return $response;
        }

        $staff = User::query()
            ->where('institution_id', $institution->id)
            ->whereIn('role', ['manager', 'employee'])
            ->latest()
            ->get();

        return $this->success($staff, 'Institution staff fetched successfully.');
    }

    public function invite(InviteInstitutionEmployeeRequest $request, Institution $institution): JsonResponse
    {
        $authUser = $request->user();
        if ($response = $this->forbidIfNoInstitutionAccess($request, $institution)) {
            return $response;
        }

        if ($authUser && $authUser->role === 'employee') {
            return $this->error('Employees cannot invite staff.', 403);
        }

        $candidate = User::query()->where('email', $request->string('email')->toString())->firstOrFail();

        if ($candidate->role !== 'citizen') {
            return $this->error('Only citizens can be invited as employees.', 422);
        }

        $candidate->update([
            'role' => 'employee',
            'institution_id' => $institution->id,
        ]);

        $this->notificationService->createForUser($candidate, 'system_notification', [
            'title' => 'You are now an employee',
            'message' => 'You were invited to join '.$institution->name.' as an employee.',
        ]);

        return $this->success($candidate->fresh(), 'Employee invited successfully.');
    }

    public function remove(Request $request, Institution $institution, User $user): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess($request, $institution)) {
            return $response;
        }

        if ($user->institution_id !== $institution->id || $user->role === 'admin') {
            return $this->error('User does not belong to this institution staff.', 422);
        }

        if ($user->role === 'manager') {
            return $this->error('Manager cannot be removed from this endpoint.', 422);
        }

        $user->update([
            'role' => 'citizen',
            'institution_id' => null,
            'department_id' => null,
        ]);

        return $this->success($user->fresh(), 'Employee removed successfully.');
    }

    public function leave(Request $request, Institution $institution): JsonResponse
    {
        $authUser = $request->user();
        if (! $authUser) {
            return $this->error('Unauthenticated.', 401);
        }

        if ($authUser->institution_id !== $institution->id || ! in_array($authUser->role, ['manager', 'employee'], true)) {
            return $this->error('Forbidden.', 403);
        }

        if ($authUser->role === 'employee') {
            $authUser->update([
                'role' => 'citizen',
                'institution_id' => null,
                'department_id' => null,
            ]);

            return $this->success($authUser->fresh(), 'You have left the institution successfully.');
        }

        $newManagerId = $request->integer('new_manager_user_id');
        if ($newManagerId <= 0) {
            return $this->error('Manager must select a new manager before leaving.', 422);
        }

        $candidate = User::query()
            ->where('id', $newManagerId)
            ->where('institution_id', $institution->id)
            ->where('role', 'employee')
            ->first();

        if (! $candidate) {
            return $this->error('Selected user must be an employee from the same institution.', 422);
        }

        DB::transaction(function () use ($authUser, $candidate): void {
            $candidate->update([
                'role' => 'manager',
            ]);

            $authUser->update([
                'role' => 'citizen',
                'institution_id' => null,
                'department_id' => null,
            ]);
        });

        $this->notificationService->createForUser($candidate->fresh(), 'system_notification', [
            'title' => 'You are now the manager',
            'message' => 'Institution management has been transferred to you.',
        ]);

        return $this->success($authUser->fresh(), 'Manager role transferred and you left the institution successfully.');
    }

    public function transferManager(Request $request, Institution $institution): JsonResponse
    {
        $authUser = $request->user();
        if (! $authUser) {
            return $this->error('Unauthenticated.', 401);
        }

        if ($authUser->role === 'manager' && $authUser->institution_id !== $institution->id) {
            return $this->error('Forbidden.', 403);
        }

        $candidateId = $request->integer('new_manager_user_id');
        if ($candidateId <= 0) {
            return $this->error('new_manager_user_id is required.', 422);
        }

        $currentManager = User::query()
            ->where('institution_id', $institution->id)
            ->where('role', 'manager')
            ->first();

        $candidate = User::query()
            ->where('id', $candidateId)
            ->where('institution_id', $institution->id)
            ->where('role', 'employee')
            ->first();

        if (! $candidate || ! $currentManager) {
            return $this->error('Manager transfer requires one current manager and one employee candidate.', 422);
        }

        DB::transaction(function () use ($currentManager, $candidate): void {
            $currentManager->update(['role' => 'employee']);
            $candidate->update(['role' => 'manager']);
        });

        $this->notificationService->createForUser($candidate->fresh(), 'system_notification', [
            'title' => 'You are now the manager',
            'message' => 'Institution management has been transferred to you.',
        ]);

        return $this->success($candidate->fresh(), 'Manager transferred successfully.');
    }

    private function forbidIfNoInstitutionAccess(Request $request, Institution $institution): ?JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        if ($user->role === 'admin') {
            return null;
        }

        if (! in_array($user->role, ['manager', 'employee'], true) || $user->institution_id !== $institution->id) {
            return $this->error('Forbidden.', 403);
        }

        return null;
    }
}
