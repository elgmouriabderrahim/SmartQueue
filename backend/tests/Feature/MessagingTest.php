<?php

use App\Models\Conversation;
use App\Models\User;

it('starts conversation, sends messages, and marks them read', function () {
    $citizen = User::factory()->create(['role' => 'citizen', 'status' => 'active']);
    $institutionUser = User::factory()->create(['role' => 'manager', 'status' => 'active']);

    $token = $citizen->createToken('test')->plainTextToken;

    $start = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/conversations', [
            'citizen_id' => $citizen->id,
            'institution_user_id' => $institutionUser->id,
            'subject' => 'Appointment inquiry',
        ]);

    $start->assertCreated()->assertJsonPath('success', true);
    $conversationId = $start->json('data.id');

    $send = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/messages', [
            'conversation_id' => $conversationId,
            'sender_id' => $citizen->id,
            'recipient_id' => $institutionUser->id,
            'body' => 'Hello, I need help with my appointment.',
        ]);

    $send->assertCreated()->assertJsonPath('success', true);

    $markRead = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/conversations/read', [
            'conversation_id' => $conversationId,
            'recipient_id' => $institutionUser->id,
        ]);

    $markRead->assertOk()->assertJsonPath('success', true);
    expect(Conversation::query()->count())->toBe(1);
});
