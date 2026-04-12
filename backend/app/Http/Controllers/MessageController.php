<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, $request->integer('per_page', 15)));

        $messages = Message::query()
            ->with(['sender', 'recipient', 'appointment'])
            ->latest()
            ->paginate($perPage);

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['required', 'exists:users,id'],
            'recipient_id' => ['nullable', 'exists:users,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'status' => ['sometimes', Rule::in(['new', 'read', 'in_progress', 'resolved', 'closed'])],
        ]);

        $message = Message::create($validated);

        return response()->json($message->load(['sender', 'recipient', 'appointment']), 201);
    }

    public function show(Message $message): JsonResponse
    {
        return response()->json($message->load(['sender', 'recipient', 'appointment']));
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        $validated = $request->validate([
            'sender_id' => ['sometimes', 'required', 'exists:users,id'],
            'recipient_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'appointment_id' => ['sometimes', 'nullable', 'exists:appointments,id'],
            'status' => ['sometimes', Rule::in(['new', 'read', 'in_progress', 'resolved', 'closed'])],
        ]);

        $message->update($validated);

        return response()->json($message->fresh()->load(['sender', 'recipient', 'appointment']));
    }

    public function destroy(Message $message): JsonResponse
    {
        $message->delete();

        return response()->json(['message' => 'Message deleted successfully.']);
    }
}
