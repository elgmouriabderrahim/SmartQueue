<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messaging\SendMessageRequest;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messageService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $messages = Message::query()
            ->with(['sender', 'recipient', 'appointment'])
            ->latest()
            ->paginate(max(1, min(100, $request->integer('per_page', 15))));

        return $this->success($messages, 'Messages fetched successfully.');
    }

    public function store(SendMessageRequest $request): JsonResponse
    {
        $message = $this->messageService->sendMessage($request->validated());

        return $this->success($message, 'Message sent successfully.', 201);
    }

    public function show(Message $message): JsonResponse
    {
        return $this->success($message->load(['sender', 'recipient', 'appointment', 'conversation']), 'Message fetched successfully.');
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        $message->update($request->only(['status', 'read_at']));

        return $this->success($message->fresh()->load(['sender', 'recipient', 'appointment', 'conversation']), 'Message updated successfully.');
    }

    public function destroy(Message $message): JsonResponse
    {
        $message->delete();

        return $this->success(null, 'Message deleted successfully.');
    }
}
