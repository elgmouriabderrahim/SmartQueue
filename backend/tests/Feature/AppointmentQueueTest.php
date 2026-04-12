<?php

use App\Models\Appointment;
use App\Models\Institution;
use App\Models\Service;
use App\Models\User;

function actingCitizenToken(): array
{
    $user = User::factory()->create([
        'role' => 'citizen',
        'status' => 'active',
    ]);

    return [$user, $user->createToken('test')->plainTextToken];
}

function seedService(): Service
{
    $institution = Institution::query()->create([
        'name' => 'Inst A',
        'slug' => 'inst-a',
        'city' => 'Rabat',
        'region' => 'Rabat-Sale-Kenitra',
        'status' => 'active',
    ]);

    return Service::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Passport',
        'estimated_duration' => 20,
        'max_daily_capacity' => 20,
        'working_hours' => ['start' => '08:30', 'end' => '16:30'],
        'status' => 'active',
    ]);
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
