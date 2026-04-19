<?php

use App\Models\Appointment;
use App\Models\Institution;
use App\Models\Service;
use App\Models\User;

function actingCitizenToken(): array
{
    $user = User::factory()->create([
        'role' => 'citizen',
    ]);

    return [$user, $user->createToken('test')->plainTextToken];
}

function seedService(): Service
{
    $institution = Institution::query()->create([
        'name' => 'Inst A',
        'slug' => 'inst-a',
        'city' => 'Rabat',
        'adress' => 'Main avenue',
        'description' => 'Public institution',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 120,
        'status' => 'active',
    ]);

    return Service::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Passport',
        'description' => 'Passport service',
        'duration' => 20,
        'capacity' => 20,
        'opening_time' => '08:30',
        'closing_time' => '16:30',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'status' => 'active',
    ]);
}

function actingAdminToken(): array
{
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    return [$user, $user->createToken('test')->plainTextToken];
}

it('creates appointment and auto assigns queue position', function () {
    [$user, $token] = actingCitizenToken();
    $service = seedService();

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/appointments', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->setHour(9)->setMinute(0)->format('Y-m-d H:i:s'),
        ]);

    $response->assertCreated()->assertJsonPath('success', true);
    expect(Appointment::query()->count())->toBe(1);
    expect(Appointment::query()->first()->queueEntry)->not->toBeNull();
});

it('assigns sequential queue positions and estimates waiting time', function () {
    [$userOne, $tokenOne] = actingCitizenToken();
    [$userTwo, $tokenTwo] = actingCitizenToken();
    $service = seedService();

    $dateTime = now()->addDays(3)->setHour(9)->setMinute(0)->format('Y-m-d H:i:s');

    $first = $this->withHeader('Authorization', 'Bearer '.$tokenOne)
        ->postJson('/api/appointments', [
            'user_id' => $userOne->id,
            'service_id' => $service->id,
            'appointment_date' => $dateTime,
        ]);

    $second = $this->withHeader('Authorization', 'Bearer '.$tokenTwo)
        ->postJson('/api/appointments', [
            'user_id' => $userTwo->id,
            'service_id' => $service->id,
            'appointment_date' => $dateTime,
        ]);

    $first->assertCreated()->assertJsonPath('data.queue_position', 1);
    $second->assertCreated()->assertJsonPath('data.queue_position', 2);
    $second->assertJsonPath('data.estimated_waiting_minutes', 20);
});

it('prevents double booking at same datetime for same user', function () {
    [$user, $token] = actingCitizenToken();
    $service = seedService();

    $dateTime = now()->addDays(2)->setHour(10)->setMinute(0)->format('Y-m-d H:i:s');

    $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/appointments', [
        'user_id' => $user->id,
        'service_id' => $service->id,
        'appointment_date' => $dateTime,
    ])->assertCreated();

    $second = $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/appointments', [
        'user_id' => $user->id,
        'service_id' => $service->id,
        'appointment_date' => $dateTime,
    ]);

    $second->assertStatus(422);
});

it('cancels appointment and removes queue entry', function () {
    [$user, $token] = actingCitizenToken();
    $service = seedService();

    $create = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/appointments', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->setHour(11)->setMinute(0)->format('Y-m-d H:i:s'),
        ])->assertCreated();

    $id = $create->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->deleteJson('/api/appointments/'.$id)
        ->assertOk()
        ->assertJsonPath('success', true);

    $appointment = Appointment::query()->findOrFail($id);
    expect($appointment->status)->toBe('cancelled');
    expect($appointment->queueEntry)->toBeNull();
});

it('forbids citizen from accessing another citizen appointment', function () {
    [$owner, $ownerToken] = actingCitizenToken();
    [$otherCitizen, $otherToken] = actingCitizenToken();
    $service = seedService();

    $create = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
        ->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->setHour(12)->setMinute(0)->format('Y-m-d H:i:s'),
        ])
        ->assertCreated();

    $appointmentId = $create->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.$otherToken)
        ->getJson('/api/appointments/'.$appointmentId)
        ->assertStatus(403);

    $this->withHeader('Authorization', 'Bearer '.$otherToken)
        ->putJson('/api/appointments/'.$appointmentId, [
            'appointment_date' => now()->addDays(2)->setHour(13)->setMinute(0)->format('Y-m-d H:i:s'),
        ])
        ->assertStatus(403);

    $this->withHeader('Authorization', 'Bearer '.$otherToken)
        ->deleteJson('/api/appointments/'.$appointmentId)
        ->assertStatus(403);
});

it('completes appointment and updates queue progress', function () {
    [$citizen, $citizenToken] = actingCitizenToken();
    [, $adminToken] = actingAdminToken();
    $service = seedService();

    $create = $this->withHeader('Authorization', 'Bearer '.$citizenToken)
        ->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2)->setHour(9)->setMinute(30)->format('Y-m-d H:i:s'),
        ])
        ->assertCreated();

    $appointmentId = $create->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.$adminToken)
        ->patchJson('/api/appointments/'.$appointmentId.'/complete')
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $appointment = Appointment::query()->with(['queueEntry', 'queue'])->findOrFail($appointmentId);

    expect($appointment->queueEntry)->not->toBeNull();
    expect($appointment->queueEntry->status)->toBe('served');
    expect($appointment->queue)->not->toBeNull();
    expect($appointment->queue->current_position)->toBeGreaterThanOrEqual(1);
});

it('shifts queue positions after cancelling first appointment', function () {
    [$citizenOne, $tokenOne] = actingCitizenToken();
    [$citizenTwo, $tokenTwo] = actingCitizenToken();
    $service = seedService();

    $dateTime = now()->addDays(4)->setHour(10)->setMinute(0)->format('Y-m-d H:i:s');

    $first = $this->withHeader('Authorization', 'Bearer '.$tokenOne)
        ->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => $dateTime,
        ])
        ->assertCreated();

    $second = $this->withHeader('Authorization', 'Bearer '.$tokenTwo)
        ->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => $dateTime,
        ])
        ->assertCreated();

    $firstId = $first->json('data.id');
    $secondId = $second->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.$tokenOne)
        ->deleteJson('/api/appointments/'.$firstId)
        ->assertOk();

    $remaining = Appointment::query()->with('queueEntry')->findOrFail($secondId);
    expect($remaining->queueEntry)->not->toBeNull();
    expect($remaining->queueEntry->position)->toBe(1);
});

it('returns queue position details for appointment owner', function () {
    [$citizen, $token] = actingCitizenToken();
    $service = seedService();

    $create = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->setHour(9)->setMinute(0)->format('Y-m-d H:i:s'),
        ])
        ->assertCreated();

    $appointmentId = $create->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/appointments/'.$appointmentId.'/queue-position')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.appointment_id', $appointmentId);
});

it('forbids citizen from reading another appointment queue position', function () {
    [$owner, $ownerToken] = actingCitizenToken();
    [, $otherToken] = actingCitizenToken();
    $service = seedService();

    $create = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
        ->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2)->setHour(9)->setMinute(30)->format('Y-m-d H:i:s'),
        ])
        ->assertCreated();

    $appointmentId = $create->json('data.id');

    $this->withHeader('Authorization', 'Bearer '.$otherToken)
        ->getJson('/api/appointments/'.$appointmentId.'/queue-position')
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});
