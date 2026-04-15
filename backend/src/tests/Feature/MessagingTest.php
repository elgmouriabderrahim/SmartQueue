<?php

use App\Models\Conversation;
use App\Models\User;

it('starts conversation, sends messages, and marks them read', function () {
    $citizen = User::factory()->create(['role' => 'citizen', 'status' => 'active']);
    $institutionUser = User::factory()->create(['role' => 'manager', 'status' => 'active']);

    $citizenToken = $citizen->createToken('test')->plainTextToken;
    $institutionToken = $institutionUser->createToken('test')->plainTextToken;

    $start = $this->withHeader('Authorization', 'Bearer '.$citizenToken)
        ->postJson('/api/conversations', [
            'institution_user_id' => $institutionUser->id,
            'subject' => 'Appointment inquiry',
        ]);

    $start->assertCreated()->assertJsonPath('success', true);
    $conversationId = $start->json('data.id');

    $send = $this->withHeader('Authorization', 'Bearer '.$citizenToken)
        ->postJson('/api/messages', [
            'conversation_id' => $conversationId,
            'body' => 'Hello, I need help with my appointment.',
        ]);

    $send->assertCreated()->assertJsonPath('success', true);
    $send->assertJsonPath('data.sender_id', $citizen->id);
    $send->assertJsonPath('data.recipient_id', $institutionUser->id);

    $markRead = $this->withHeader('Authorization', 'Bearer '.$institutionToken)
        ->postJson('/api/conversations/read', [
            'conversation_id' => $conversationId,
        ]);

    $markRead->assertOk()->assertJsonPath('success', true);
    $markRead->assertJsonPath('data.updated_count', 1);
    expect(Conversation::query()->count())->toBe(1);
});
