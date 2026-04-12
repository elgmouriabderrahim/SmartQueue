<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Institution;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        return response()->json([
            'users_count' => User::query()->count(),
            'institutions_count' => Institution::query()->count(),
            'services_count' => Service::query()->count(),
            'appointments_total' => Appointment::query()->count(),
            'appointments_today' => Appointment::query()->whereDate('appointment_date', now()->toDateString())->count(),
            'appointments_pending' => Appointment::query()->where('status', 'pending')->count(),
            'appointments_in_progress' => Appointment::query()->where('status', 'in_progress')->count(),
        ]);
    }
}
