<?php

namespace App\Http\Controllers;

use App\Http\Requests\Queue\StoreQueueRequest;
use App\Http\Requests\Queue\UpdateQueueRequest;
use App\Models\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class QueueController extends Controller
{
    #[OA\Get(
        path: '/queues',
        tags: ['Queue'],
        summary: 'List queues',
        responses: [new OA\Response(response: 200, description: 'Queues fetched')]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $queues = Queue::query()
            ->with(['service', 'entries'])
            ->latest('date')
            ->paginate($perPage);

        return $this->success($queues, 'Queues fetched successfully.');
    }

    #[OA\Post(
        path: '/queues',
        tags: ['Queue'],
        summary: 'Create queue',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['service_id', 'date'],
                properties: [
                    new OA\Property(property: 'service_id', type: 'integer', example: 1),
                    new OA\Property(property: 'date', type: 'string', example: '2026-05-10'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Queue created')]
    )]
    public function store(StoreQueueRequest $request): JsonResponse
    {
        $queue = Queue::query()->create($request->validated());

        return $this->success($queue->load(['service', 'entries']), 'Queue created successfully.', 201);
    }

    #[OA\Get(
        path: '/queues/{queue}',
        tags: ['Queue'],
        summary: 'Get queue',
        parameters: [new OA\Parameter(name: 'queue', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Queue fetched')]
    )]
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
