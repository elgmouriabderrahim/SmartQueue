<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messaging\SendMessageRequest;
use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $isInstitutionStaff = in_array($user->role, ['manager', 'employee'], true);

        $messages = Message::query()
            ->with([
                'sender', 
                'recipient', 
                'appointment',
                'institution'
            ])
            ->where(function ($query) use ($user, $isInstitutionStaff): void {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);

                if ($isInstitutionStaff && $user->institution_id) {
                    $query->orWhere('institution_id', $user->institution_id);
                }
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
                required: ['content'],
                properties: [
                    new OA\Property(property: 'recipient_id', type: 'integer', nullable: true, example: 2),
                    new OA\Property(property: 'institution_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'content', type: 'string', example: 'Hello, I need support.'),
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

        return $this->success($message->load(['sender', 'recipient', 'appointment', 'institution']), 'Message fetched successfully.');
    }

    public function update(Request $request, Message $message): JsonResponse
    {
        if (! $request->user()) {
            return $this->error('Unauthenticated.', 401);
        }

        if (! $this->canAccessMessage($request, $message)) {
            return $this->error('Forbidden.', 403);
        }

        $request->validate([
            'status' => ['required', Rule::in(['new', 'read', 'in_progress', 'resolved', 'closed'])],
        ]);

        $nextStatus = $request->string('status')->toString();

        $message->update(['status' => $nextStatus]);

        return $this->success($message->fresh()->load(['sender', 'recipient', 'appointment', 'institution']), 'Message updated successfully.');
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

        $userId = (int) $user->id;

        if ((int) $message->sender_id === $userId || (int) $message->recipient_id === $userId) {
            return true;
        }

        if (in_array($user->role, ['manager', 'employee'], true)
            && $user->institution_id
            && (int) $message->institution_id === (int) $user->institution_id) {
            return true;
        }

        return false;
    }
}