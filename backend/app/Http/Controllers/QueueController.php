<?php

namespace App\Http\Controllers;

use App\Http\Requests\Queue\StoreQueueRequest;
use App\Http\Requests\Queue\UpdateQueueRequest;
use App\Models\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $queues = Queue::query()
            ->with(['service', 'entries'])
            ->latest('date')
            ->paginate($perPage);

        return $this->success($queues, 'Queues fetched successfully.');
    }

    public function store(StoreQueueRequest $request): JsonResponse
    {
        $queue = Queue::query()->create($request->validated());

        return $this->success($queue->load(['service', 'entries']), 'Queue created successfully.', 201);
    }

    public function show(Queue $queue): JsonResponse
    {
        return $this->success($queue->load(['service', 'entries.appointment', 'appointments']), 'Queue fetched successfully.');
    }

    public function update(UpdateQueueRequest $request, Queue $queue): JsonResponse
    {
        $queue->update($request->validated());

        return $this->success($queue->fresh()->load(['service', 'entries']), 'Queue updated successfully.');
    }

    public function destroy(Queue $queue): JsonResponse
    {
        $queue->delete();

        return $this->success(null, 'Queue deleted successfully.');
    }
}
