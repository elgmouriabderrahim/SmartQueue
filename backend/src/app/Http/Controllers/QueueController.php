<?php

namespace App\Http\Controllers;

use App\Http\Requests\Queue\StoreQueueRequest;
use App\Http\Requests\Queue\UpdateQueueRequest;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class QueueController extends Controller
{
    public function __construct(private readonly QueueService $queueService)
    {
    }

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
            ->when(
                $request->user() && in_array($request->user()->role, ['manager', 'employee'], true),
                fn ($query) => $query->whereHas('service', fn ($serviceQuery) => $serviceQuery->where('institution_id', $request->user()->institution_id))
            )
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
        $user = $request->user();
        if ($user && $user->role === 'manager') {
            $belongsToManagerInstitution = \App\Models\Service::query()
                ->where('id', $request->integer('service_id'))
                ->where('institution_id', $user->institution_id)
                ->exists();

            if (! $belongsToManagerInstitution) {
                return $this->error('Forbidden.', 403);
            }
        }

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
        if ($response = $this->forbidIfNoInstitutionAccess(request(), $queue)) {
            return $response;
        }

        return $this->success($queue->load(['service', 'entries.appointment', 'appointments']), 'Queue fetched successfully.');
    }

    public function update(UpdateQueueRequest $request, Queue $queue): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess($request, $queue)) {
            return $response;
        }

        $oldPosition = (int) $queue->current_position;
        $queue->update($request->validated());

        if ((int) $queue->current_position !== $oldPosition) {
            $this->queueService->notifyQueueUpdates($queue->id);
        }

        return $this->success($queue->fresh()->load(['service', 'entries']), 'Queue updated successfully.');
    }

    public function destroy(Queue $queue): JsonResponse
    {
        if ($response = $this->forbidIfNoInstitutionAccess(request(), $queue)) {
            return $response;
        }

        $queue->delete();

        return $this->success(null, 'Queue deleted successfully.');
    }

    private function forbidIfNoInstitutionAccess(Request $request, Queue $queue): ?JsonResponse
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

        $belongsToInstitution = $queue->service()->where('institution_id', $user->institution_id)->exists();
        if (! $belongsToInstitution) {
            return $this->error('Forbidden.', 403);
        }

        return null;
    }
}
