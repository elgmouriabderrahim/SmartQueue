<?php

use App\Models\User;

it('registers a new user and returns token', function () {
    $response = $this->postJson('/api/auth/register', [
        'first_name' => 'Citizen',
        'last_name' => 'One',
        'email' => 'citizen@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'citizen',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['user', 'token'],
        ]);

    expect(User::query()->where('email', 'citizen@example.com')->exists())->toBeTrue();
});

it('logs in and logs out with sanctum token', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
        'role' => 'admin',
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ]);

    $login->assertOk()->assertJsonPath('success', true);

    $token = $login->json('data.token');

    $logout = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout');

    $logout->assertOk()->assertJsonPath('success', true);
});
