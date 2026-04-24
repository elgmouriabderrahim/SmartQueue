<?php

use App\Models\Institution;
use App\Models\User;

function createAdminTokenForModulesTest(): string
{
    $admin = User::factory()->create([
        'role' => 'admin',
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
        'adress' => 'Central boulevard',
        'description' => 'City administration',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 90,
        'status' => 'active',
    ]);

    $departmentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/departments', [
            'institution_id' => $institution->id,
            'name' => 'Civil Status',
            'slug' => 'civil-status',
            'description' => 'Department for civil status operations',
            'location' => 'Building A',
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
    ]);

    $token = $institutionUser->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/users')
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

it('returns public institutions map data', function () {
    Institution::query()->create([
        'name' => 'Municipality A',
        'slug' => 'municipality-a',
        'city' => 'Rabat',
        'adress' => 'Avenue Hassan II',
        'description' => 'Public services A',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 120,
        'status' => 'active',
    ]);

    $this->getJson('/api/institutions/map')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.slug', 'municipality-a');
});

it('allows admin to approve an institution', function () {
    $token = createAdminTokenForModulesTest();

    $institution = Institution::query()->create([
        'name' => 'Registry Office',
        'slug' => 'registry-office',
        'city' => 'Casablanca',
        'adress' => 'Main street',
        'description' => 'Registry services',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 80,
        'status' => 'inactive',
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson('/api/institutions/'.$institution->id.'/approve')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'active');
});
