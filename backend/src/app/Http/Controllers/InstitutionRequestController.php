<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstitutionRequest\RejectInstitutionApplicationRequest;
use App\Http\Requests\InstitutionRequest\StoreInstitutionApplicationRequest;
use App\Models\Institution;
use App\Models\InstitutionRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitutionRequestController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $user = $request->user();

        $items = InstitutionRequest::query()
            ->with(['user', 'reviewer'])
            ->when($user && $user->role !== 'admin', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate($perPage);

        return $this->success($items, 'Institution requests fetched successfully.');
    }

    public function store(StoreInstitutionApplicationRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || $user->role !== 'citizen') {
            return $this->error('Only citizens can submit institution requests.', 403);
        }

        $hasPendingRequest = InstitutionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingRequest) {
            return $this->error('You already have a pending institution request.', 422);
        }

        $item = InstitutionRequest::query()->create([
            ...$request->validated(),
            'opening_time' => '08:00',
            'closing_time' => '16:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'max_appointments_per_day' => 100,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        return $this->success($item->load('user'), 'Institution request submitted successfully.', 201);
    }

    public function approve(Request $request, InstitutionRequest $institutionRequest): JsonResponse
    {
        if ($institutionRequest->status !== 'pending') {
            return $this->error('Only pending requests can be approved.', 422);
        }

        $reviewer = $request->user();

        DB::transaction(function () use ($institutionRequest, $reviewer): void {
            $institution = Institution::query()->create([
                'name' => $institutionRequest->name,
                'slug' => $institutionRequest->slug,
                'city' => $institutionRequest->city,
                'adress' => $institutionRequest->adress,
                'description' => $institutionRequest->description,
                'opening_time' => $institutionRequest->opening_time,
                'closing_time' => $institutionRequest->closing_time,
                'working_days' => $institutionRequest->working_days,
                'max_appointments_per_day' => $institutionRequest->max_appointments_per_day,
                'status' => 'active',
            ]);

            $owner = User::query()->findOrFail($institutionRequest->user_id);
            $owner->update([
                'role' => 'manager',
                'institution_id' => $institution->id,
                'department_id' => null,
            ]);

            $institutionRequest->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->notificationService->createForUser($owner, 'system_notification', [
                'title' => 'Institution request approved',
                'message' => 'Your institution request was approved. You are now a manager.',
            ]);
        });

        return $this->success($institutionRequest->fresh(['user', 'reviewer']), 'Institution request approved successfully.');
    }

    public function reject(RejectInstitutionApplicationRequest $request, InstitutionRequest $institutionRequest): JsonResponse
    {
        if ($institutionRequest->status !== 'pending') {
            return $this->error('Only pending requests can be rejected.', 422);
        }

        $institutionRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->string('reason')->toString(),
        ]);

        $owner = User::query()->find($institutionRequest->user_id);
        if ($owner) {
            $this->notificationService->createForUser($owner, 'system_notification', [
                'title' => 'Institution request rejected',
                'message' => $institutionRequest->rejection_reason,
            ]);
        }

        return $this->success($institutionRequest->fresh(['user', 'reviewer']), 'Institution request rejected successfully.');
    }
}
