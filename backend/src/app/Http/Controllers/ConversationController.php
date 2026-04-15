<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messaging\MarkConversationReadRequest;
use App\Http\Requests\Messaging\StartConversationRequest;
use App\Models\Conversation;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ConversationController extends Controller
{
    public function __construct(private readonly MessageService $messageService)
    {
    }

    #[OA\Get(
        path: '/conversations',
        tags: ['Messaging'],
        summary: 'List conversations for current user',
        responses: [new OA\Response(response: 200, description: 'Conversations fetched')]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $conversations = $this->messageService->listConversationsForUser($user);

        return $this->success($conversations, 'Conversations fetched successfully.');
    }

    #[OA\Post(
        path: '/conversations',
        tags: ['Messaging'],
        summary: 'Start conversation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'citizen_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'institution_user_id', type: 'integer', example: 2),
                    new OA\Property(property: 'subject', type: 'string', nullable: true, example: 'Appointment inquiry'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Conversation created')]
    )]
    public function store(StartConversationRequest $request): JsonResponse
    {
        $authUser = $request->user();
        if (! $authUser) {
            return $this->error('Unauthenticated.', 401);
        }

        $isCitizen = $authUser->role === 'citizen';
        $citizenId = $isCitizen ? $authUser->id : $request->integer('citizen_id');
        $institutionUserId = $isCitizen ? $request->integer('institution_user_id') : $authUser->id;

        if (! $citizenId) {
            throw ValidationException::withMessages([
                'citizen_id' => ['The citizen_id field is required when an institution user starts the conversation.'],
            ]);
        }

        $conversation = $this->messageService->startConversation(
            $citizenId,
            $institutionUserId,
            $request->input('subject')
        );

        return $this->success($conversation->load(['citizen', 'institutionUser']), 'Conversation started successfully.', 201);
    }

    #[OA\Get(
        path: '/conversations/{conversation}',
        tags: ['Messaging'],
        summary: 'Get conversation details',
        parameters: [new OA\Parameter(name: 'conversation', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Conversation details fetched')]
    )]
    public function show(Conversation $conversation): JsonResponse
    {
        $authUser = request()->user();
        if (! $authUser) {
            return $this->error('Unauthenticated.', 401);
        }

        if (! in_array($authUser->id, [$conversation->citizen_id, $conversation->institution_user_id], true)) {
            return $this->error('Forbidden.', 403);
        }

        $messages = $this->messageService->fetchMessages($conversation->id);

        return $this->success([
            'conversation' => $conversation->load(['citizen', 'institutionUser']),
            'messages' => $messages,
        ], 'Conversation details fetched successfully.');
    }

    #[OA\Post(
        path: '/conversations/read',
        tags: ['Messaging'],
        summary: 'Mark messages as read in conversation',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['conversation_id'],
                properties: [
                    new OA\Property(property: 'conversation_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Messages marked as read')]
    )]
    public function markRead(MarkConversationReadRequest $request): JsonResponse
    {
        $authUser = $request->user();
        if (! $authUser) {
            return $this->error('Unauthenticated.', 401);
        }

        $updated = $this->messageService->markConversationAsRead(
            $request->integer('conversation_id'),
            $authUser->id
        );

        return $this->success(['updated_count' => $updated], 'Messages marked as read.');
    }
}
