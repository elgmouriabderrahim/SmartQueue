<?php

namespace App\Http\Controllers;

use App\Http\Requests\Messaging\MarkConversationReadRequest;
use App\Http\Requests\Messaging\StartConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private readonly MessageService $messageService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error('Unauthenticated.', 401);
        }

        $conversations = $this->messageService->listConversationsForUser($user);

        return $this->success($conversations, 'Conversations fetched successfully.');
    }

    public function store(StartConversationRequest $request): JsonResponse
    {
        $conversation = $this->messageService->startConversation(
            $request->integer('citizen_id'),
            $request->integer('institution_user_id'),
            $request->input('subject')
        );

        return $this->success($conversation->load(['citizen', 'institutionUser']), 'Conversation started successfully.', 201);
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $messages = $this->messageService->fetchMessages($conversation->id);

        return $this->success([
            'conversation' => $conversation->load(['citizen', 'institutionUser']),
            'messages' => $messages,
        ], 'Conversation details fetched successfully.');
    }

    public function markRead(MarkConversationReadRequest $request): JsonResponse
    {
        $updated = $this->messageService->markConversationAsRead(
            $request->integer('conversation_id'),
            $request->integer('recipient_id')
        );

        return $this->success(['updated_count' => $updated], 'Messages marked as read.');
    }
}
