<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $queues = Queue::query()
            ->with(['service', 'entries'])
            ->latest('date')
            ->paginate($perPage);

        return response()->json($queues);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date'],
            'current_position' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'paused', 'closed'])],
        ]);

        $queue = Queue::create($validated);

        return response()->json($queue->load(['service', 'entries']), 201);
    }

    public function show(Queue $queue): JsonResponse
    {
        return response()->json($queue->load(['service', 'entries.appointment', 'appointments']));
    }

    public function update(Request $request, Queue $queue): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['sometimes', 'required', 'exists:services,id'],
            'date' => ['sometimes', 'required', 'date'],
            'current_position' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['active', 'paused', 'closed'])],
        ]);

        $queue->update($validated);

        return response()->json($queue->fresh()->load(['service', 'entries']));
    }

    public function destroy(Queue $queue): JsonResponse
    {
        $queue->delete();

        return response()->json(['message' => 'Queue deleted successfully.']);
    }
}
