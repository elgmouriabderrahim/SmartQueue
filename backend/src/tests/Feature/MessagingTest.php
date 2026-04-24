<?php

use App\Models\User;

it('sends messages between users and marks them read', function () {
    $citizen = User::factory()->create(['role' => 'citizen']);
    $institutionUser = User::factory()->create(['role' => 'manager']);

    $citizenToken = $citizen->createToken('test')->plainTextToken;

    $send = $this->withHeader('Authorization', 'Bearer '.$citizenToken)
        ->postJson('/api/messages', [
            'recipient_id' => $institutionUser->id,
            'content' => 'Hello, I need help with my appointment.',
        ]);

    $send->assertCreated()->assertJsonPath('success', true);
    $send->assertJsonPath('data.sender_id', $citizen->id);
    $send->assertJsonPath('data.recipient_id', $institutionUser->id);

    $messageId = $send->json('data.id');

    $institutionToken = $institutionUser->createToken('test')->plainTextToken;
    $markRead = $this->withHeader('Authorization', 'Bearer '.$institutionToken)
        ->putJson('/api/messages/'.$messageId, [
            'status' => 'read',
        ]);

    $markRead->assertOk()->assertJsonPath('success', true);
    $markRead->assertJsonPath('data.status', 'read');
});
