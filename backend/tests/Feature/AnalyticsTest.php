<?php

use App\Models\Institution;
use App\Models\Service;
use App\Models\User;

it('returns analytics dashboard metrics for authenticated admin', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/analytics');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'average_waiting_time_minutes',
                'appointments_per_day',
                'cancellation_rate_percent',
                'peak_usage',
            ],
        ]);
});

it('syncs peak hours for a date as admin', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $institution = Institution::query()->create([
        'name' => 'Inst Analytics',
        'slug' => 'inst-analytics',
        'city' => 'Rabat',
        'region' => 'Rabat-Sale-Kenitra',
        'status' => 'active',
    ]);

    $service = Service::query()->create([
        'institution_id' => $institution->id,
        'name' => 'ID Card',
        'estimated_duration' => 15,
        'max_daily_capacity' => 25,
        'working_hours' => ['start' => '08:30', 'end' => '16:30'],
        'status' => 'active',
    ]);

    $citizen = User::factory()->create([
        'role' => 'citizen',
        'status' => 'active',
    ]);

    $citizenToken = $citizen->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$citizenToken)
        ->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
        ])
        ->assertCreated();

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/analytics/sync', [
            'date' => now()->addDay()->toDateString(),
        ]);

    $response->assertOk()->assertJsonPath('success', true);
});
