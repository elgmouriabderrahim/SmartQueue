<?php

use App\Models\Institution;
use App\Models\User;

function createAdminTokenForModulesTest(): string
{
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    return $admin->createToken('test')->plainTextToken;
}

it('blocks admin modules for unauthenticated users', function () {
    $this->getJson('/api/users')
        ->assertStatus(401)
        ->assertJsonPath('success', false);
});

it('allows admin to manage departments and settings', function () {
    $token = createAdminTokenForModulesTest();

    $institution = Institution::query()->create([
        'name' => 'City Hall',
        'slug' => 'city-hall',
        'city' => 'Casablanca',
        'region' => 'Casablanca-Settat',
        'status' => 'active',
    ]);

    $departmentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/departments', [
            'institution_id' => $institution->id,
            'name' => 'Civil Status',
            'slug' => 'civil-status',
            'status' => 'active',
        ]);

    $departmentResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'civil-status');

    $settingResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/settings', [
            'institution_id' => $institution->id,
            'key' => 'queue.max_wait_minutes',
            'value' => '45',
            'type' => 'integer',
        ]);

    $settingResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.key', 'queue.max_wait_minutes');
});

it('forbids institution role from admin only users endpoint', function () {
    $institutionUser = User::factory()->create([
        'role' => 'manager',
        'status' => 'active',
    ]);

    $token = $institutionUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/users')
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});
