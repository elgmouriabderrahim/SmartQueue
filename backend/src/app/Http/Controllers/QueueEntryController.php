<?php

namespace App\Http\Controllers;

use App\Http\Requests\QueueEntry\StoreQueueEntryRequest;
use App\Http\Requests\QueueEntry\UpdateQueueEntryRequest;
use App\Models\QueueEntry;
use App\Services\QueueEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueEntryController extends Controller
{
    public function __construct(private readonly QueueEntryService $queueEntryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $entries = QueueEntry::query()
            ->with(['queue', 'appointment'])
            ->when(
                $request->user() && in_array($request->user()->role, ['manager', 'employee'], true),
                fn ($query) => $query->whereHas('queue.service', fn ($serviceQuery) => $serviceQuery->where('institution_id', $request->user()->institution_id))
            )
            ->latest()
            ->paginate($perPage);

        return $this->success($entries, 'Queue entries fetched successfully.');
    }

    public function store(StoreQueueEntryRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($user && in_array($user->role, ['manager', 'employee'], true)) {
            $belongsToInstitution = \App\Models\Queue::query()
                ->where('id', $request->integer('queue_id'))
                ->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('institution_id', $user->institution_id))
                ->exists();

            if (! $belongsToInstitution) {
                return $this->error('Forbidden.', 403);
            }
        }

        $entry = $this->queueEntryService->create($request->validated());

        return $this->success($entry->load(['queue', 'appointment']), 'Queue entry created successfully.', 201);
    }

    public function show(QueueEntry $queueEntry): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess(request(), $queueEntry)) {
            return $response;
        }

        return $this->success($queueEntry->load(['queue', 'appointment']), 'Queue entry fetched successfully.');
    }

    public function update(UpdateQueueEntryRequest $request, QueueEntry $queueEntry): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess($request, $queueEntry)) {
            return $response;
        }

        $updated = $this->queueEntryService->update($queueEntry, $request->validated());

        return $this->success($updated->load(['queue', 'appointment']), 'Queue entry updated successfully.');
    }

    public function destroy(QueueEntry $queueEntry): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess(request(), $queueEntry)) {
            return $response;
        }

        $this->queueEntryService->delete($queueEntry);

        return $this->success(null, 'Queue entry deleted successfully.');
    }

    private function forbidIfNoInstitutionAccess(Request $request, QueueEntry $queueEntry): ?JsonResponse
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

        $belongsToInstitution = $queueEntry->queue()
            ->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('institution_id', $user->institution_id))
            ->exists();

        if (! $belongsToInstitution) {
            return $this->error('Forbidden.', 403);
        }

        return null;
    }
}
