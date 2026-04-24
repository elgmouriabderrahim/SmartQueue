<?php

use App\Models\Institution;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns analytics dashboard metrics for authenticated admin', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/analytics');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_appointments',
                'completed_appointments',
                'cancelled_appointments',
                'total_visitors',
                'average_rating',
                'average_wait_time',
            ],
        ]);
});

it('syncs analytics for a date as admin', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $institution = Institution::query()->create([
        'name' => 'Inst Analytics',
        'slug' => 'inst-analytics',
        'city' => 'Rabat',
        'adress' => 'Center street',
        'description' => 'Public office',
        'opening_time' => '08:00',
        'closing_time' => '16:00',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'max_appointments_per_day' => 100,
        'status' => 'active',
    ]);

    $service = Service::query()->create([
        'institution_id' => $institution->id,
        'name' => 'ID Card',
        'description' => 'ID card service',
        'duration' => 15,
        'capacity' => 25,
        'opening_time' => '08:30',
        'closing_time' => '16:30',
        'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        'status' => 'active',
    ]);

    $citizen = User::factory()->create([
        'role' => 'citizen',
    ]);

    Sanctum::actingAs($citizen);

    $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
        ])
        ->assertCreated();

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/analytics/sync', [
            'date' => now()->addDay()->toDateString(),
        ]);

    $response->assertOk()->assertJsonPath('success', true);
});
