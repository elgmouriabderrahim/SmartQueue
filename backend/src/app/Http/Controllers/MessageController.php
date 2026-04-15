<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messaging\SendMessageRequest;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messageService)
    {
    }

    #[OA\Get(
        path: '/messages',
        tags: ['Messaging'],
        summary: 'List messages for current user',
        responses: [new OA\Response(response: 200, description: 'Messages fetched')]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $messages = Message::query()
            ->with(['sender', 'recipient', 'appointment'])
            ->where(function ($query) use ($user): void {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id)
                    ->orWhereHas('conversation', function ($conversationQuery) use ($user): void {
                        $conversationQuery->where('citizen_id', $user->id)
                            ->orWhere('institution_user_id', $user->id);
                    });
            })
            ->latest()
            ->paginate(max(1, min(100, $request->integer('per_page', 15))));

        return $this->success($messages, 'Messages fetched successfully.');
    }

    #[OA\Post(
        path: '/messages',
        tags: ['Messaging'],
        summary: 'Send message',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['conversation_id', 'body'],
                properties: [
                    new OA\Property(property: 'conversation_id', type: 'integer', example: 1),
                    new OA\Property(property: 'recipient_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'body', type: 'string', example: 'Hello, I need support.'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Message sent')]
    )]
    public function store(SendMessageRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $message = $this->messageService->sendMessage($request->validated(), $user);

        return $this->success($message, 'Message sent successfully.', 201);
    }

    #[OA\Get(
        path: '/messages/{message}',
        tags: ['Messaging'],
        summary: 'Get message details',
        parameters: [new OA\Parameter(name: 'message', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Message fetched')]
    )]
    public function show(Message $message): JsonResponse
    {
        if (! $this->canAccessMessage(request(), $message)) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success($message->load(['sender', 'recipient', 'appointment', 'conversation']), 'Message fetched successfully.');
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        if (! $this->canAccessMessage($request, $message)) {
            return $this->error('Forbidden.', 403);
        }

        $message->update($request->only(['status', 'read_at']));

        return $this->success($message->fresh()->load(['sender', 'recipient', 'appointment', 'conversation']), 'Message updated successfully.');
    }

    public function destroy(Message $message): JsonResponse
    {
        if (! $this->canAccessMessage(request(), $message)) {
            return $this->error('Forbidden.', 403);
        }

        $message->delete();

        return $this->success(null, 'Message deleted successfully.');
    }

    private function canAccessMessage(Request $request, Message $message): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($message->sender_id === $user->id || $message->recipient_id === $user->id) {
            return true;
        }

        if (! $message->conversation) {
            return false;
        }

        return in_array($user->id, [$message->conversation->citizen_id, $message->conversation->institution_user_id], true);
    }
}
