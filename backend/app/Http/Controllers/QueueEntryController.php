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
            ->latest()
            ->paginate($perPage);

        return $this->success($entries, 'Queue entries fetched successfully.');
    }

    public function store(StoreQueueEntryRequest $request): JsonResponse
    {
        $entry = $this->queueEntryService->create($request->validated());

        return $this->success($entry->load(['queue', 'appointment']), 'Queue entry created successfully.', 201);
    }

    public function show(QueueEntry $queueEntry): JsonResponse
    {
        return $this->success($queueEntry->load(['queue', 'appointment']), 'Queue entry fetched successfully.');
    }

    public function update(UpdateQueueEntryRequest $request, QueueEntry $queueEntry): JsonResponse
    {
        $updated = $this->queueEntryService->update($queueEntry, $request->validated());

        return $this->success($updated->load(['queue', 'appointment']), 'Queue entry updated successfully.');
    }

    public function destroy(QueueEntry $queueEntry): JsonResponse
    {
        $this->queueEntryService->delete($queueEntry);

        return $this->success(null, 'Queue entry deleted successfully.');
    }
}
