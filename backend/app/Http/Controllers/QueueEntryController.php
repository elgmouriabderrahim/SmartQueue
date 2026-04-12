<?php

namespace App\Http\Controllers;

use App\Models\QueueEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QueueEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $entries = QueueEntry::query()
            ->with(['queue', 'appointment'])
            ->latest()
            ->paginate($perPage);

        return response()->json($entries);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'queue_id' => ['required', 'exists:queues,id'],
            'appointment_id' => ['required', 'exists:appointments,id', 'unique:queue_entries,appointment_id'],
            'position' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('queue_entries', 'position')->where(
                    fn ($query) => $query->where('queue_id', $request->input('queue_id'))
                ),
            ],
            'status' => ['sometimes', Rule::in(['waiting', 'called', 'serving', 'served', 'skipped', 'transferred'])],
        ]);

        $entry = QueueEntry::create($validated);

        return response()->json($entry->load(['queue', 'appointment']), 201);
    }

    public function show(QueueEntry $queueEntry): JsonResponse
    {
        return response()->json($queueEntry->load(['queue', 'appointment']));
    }

    public function update(Request $request, QueueEntry $queueEntry): JsonResponse
    {
        $queueId = $request->input('queue_id', $queueEntry->queue_id);

        $validated = $request->validate([
            'queue_id' => ['sometimes', 'required', 'exists:queues,id'],
            'appointment_id' => [
                'sometimes',
                'required',
                'exists:appointments,id',
                Rule::unique('queue_entries', 'appointment_id')->ignore($queueEntry->id),
            ],
            'position' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('queue_entries', 'position')
                    ->where(fn ($query) => $query->where('queue_id', $queueId))
                    ->ignore($queueEntry->id),
            ],
            'status' => ['sometimes', Rule::in(['waiting', 'called', 'serving', 'served', 'skipped', 'transferred'])],
        ]);

        $queueEntry->update($validated);

        return response()->json($queueEntry->fresh()->load(['queue', 'appointment']));
    }

    public function destroy(QueueEntry $queueEntry): JsonResponse
    {
        $queueEntry->delete();

        return response()->json(['message' => 'Queue entry deleted successfully.']);
    }
}
