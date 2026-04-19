<?php

use App\Models\User;

it('blocks dashboard for unauthenticated users', function () {
    $this->getJson('/api/dashboard')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

it('allows admin dashboard access', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $token = $admin->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['success', 'message', 'data']);
});
