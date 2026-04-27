<?php

use App\Models\Institution;
use App\Models\InstitutionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

function makeCitizen(array $overrides = []): User
{
    return User::factory()->create(array_merge(['role' => 'citizen'], $overrides));
}

it('allows citizen to submit institution request and cancel it before review', function () {
    $citizen = makeCitizen();

    Sanctum::actingAs($citizen);

    $createResponse = $this->postJson('/api/institution-requests', [
        'name' => 'Citizen Office',
        'slug' => 'citizen-office',
        'city' => 'Rabat',
        'adress' => 'Central square',
        'description' => 'Institution created by citizen request',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 120,
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending');

    $requestId = $createResponse->json('data.id');

    $cancelResponse = $this->patchJson('/api/institution-requests/'.$requestId.'/cancel');

    $cancelResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'cancelled');

    expect(InstitutionRequest::query()->find($requestId)?->status)->toBe('cancelled');
});

it('allows citizen to submit institution request and admin to approve with manager promotion', function () {
    $citizen = makeCitizen();
    $admin = User::factory()->create(['role' => 'admin']);

    Sanctum::actingAs($citizen);

    $createResponse = $this->postJson('/api/institution-requests', [
        'name' => 'Citizen Office',
        'slug' => 'citizen-office',
        'city' => 'Rabat',
        'adress' => 'Central square',
        'description' => 'Institution created by citizen request',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 120,
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending');

    $requestId = $createResponse->json('data.id');

    Sanctum::actingAs($admin);

    $approveResponse = $this->patchJson('/api/institution-requests/'.$requestId.'/approve');

    $approveResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'approved');

    $institution = Institution::query()->where('slug', 'citizen-office')->first();
    expect($institution)->not()->toBeNull();
    expect($institution?->status)->toBe('active');

    $citizen->refresh();
    expect($citizen->role)->toBe('manager');
    expect($citizen->institution_id)->toBe($institution?->id);

    $pivotInstitutionId = DB::table('institution_user')
        ->where('user_id', $citizen->id)
        ->value('institution_id');
    expect((int) $pivotInstitutionId)->toBe((int) $institution?->id);
});

it('allows manager to invite a citizen and the citizen accepts the invitation', function () {
    $institution = Institution::query()->create([
        'name' => 'Manager Inst',
        'slug' => 'manager-inst',
        'city' => 'Casablanca',
        'adress' => 'Main avenue',
        'description' => 'Institution for staff invite test',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 80,
        'status' => 'active',
    ]);

    $manager = User::factory()->create([
        'role' => 'manager',
    ]);
    $manager->institutions()->attach($institution->id);

    $citizen = makeCitizen();

    Sanctum::actingAs($manager);

    $response = $this->postJson('/api/institutions/'.$institution->id.'/staff/invite', [
        'email' => $citizen->email,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.email', $citizen->email);

    expect($citizen->fresh()->role)->toBe('citizen');
    expect($citizen->fresh()->institution_id)->toBeNull();

    Sanctum::actingAs($citizen);

    $acceptResponse = $this->patchJson('/api/institution-invitations/'.$response->json('data.id').'/accept');

    $acceptResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.role', 'employee')
        ->assertJsonPath('data.institution_id', $institution->id);

    $pivotInstitutionId = DB::table('institution_user')
        ->where('user_id', $citizen->id)
        ->value('institution_id');
    expect((int) $pivotInstitutionId)->toBe((int) $institution->id);
});

it('registers users as citizens only even when role is passed', function () {
    $response = $this->postJson('/api/auth/register', [
        'first_name' => 'New',
        'last_name' => 'User',
        'email' => 'forced-citizen@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.role', 'citizen');
});
