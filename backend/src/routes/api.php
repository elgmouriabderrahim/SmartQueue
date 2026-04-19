<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\QueueEntryController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceCounterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('home', [HomeController::class, 'index'])->name('api.home');

Route::prefix('auth')->group(function (): void {
	Route::post('register', [AuthController::class, 'register']);
	Route::post('login', [AuthController::class, 'login']);
	Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::get('institutions', [InstitutionController::class, 'index']);
Route::get('institutions/map', [InstitutionController::class, 'map']);
Route::get('institutions/{institution}', [InstitutionController::class, 'show']);
Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{service}', [ServiceController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
	Route::get('dashboard', [DashboardController::class, 'index'])->middleware('role:admin');

	Route::post('institutions', [InstitutionController::class, 'store'])->middleware('role:admin');
	Route::put('institutions/{institution}', [InstitutionController::class, 'update'])->middleware('role:admin');
	Route::delete('institutions/{institution}', [InstitutionController::class, 'destroy'])->middleware('role:admin');
	Route::patch('institutions/{institution}/approve', [InstitutionController::class, 'approve'])->middleware('role:admin');

	Route::post('services', [ServiceController::class, 'store'])->middleware('role:admin,institution');
	Route::put('services/{service}', [ServiceController::class, 'update'])->middleware('role:admin,institution');
	Route::delete('services/{service}', [ServiceController::class, 'destroy'])->middleware('role:admin');

	Route::apiResource('departments', DepartmentController::class)->middleware('role:admin,institution');
	Route::apiResource('service-counters', ServiceCounterController::class)->middleware('role:admin,institution');
	Route::apiResource('queue-entries', QueueEntryController::class)->middleware('role:admin,institution');
	Route::apiResource('settings', SettingController::class)->middleware('role:admin');
	Route::apiResource('activity-logs', ActivityLogController::class)->middleware('role:admin');
	Route::apiResource('users', UserController::class)->middleware('role:admin');

	Route::apiResource('queues', QueueController::class);

	Route::get('appointments', [AppointmentController::class, 'index']);
	Route::post('appointments', [AppointmentController::class, 'store']);
	Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
	Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
	Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);
	Route::patch('appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->middleware('role:admin,institution');
	Route::get('appointments/{appointment}/queue-position', [AppointmentController::class, 'queuePosition']);

	Route::apiResource('messages', MessageController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

	Route::get('notifications', [NotificationController::class, 'index']);
	Route::patch('notifications/{notificationId}/read', [NotificationController::class, 'markRead']);

	Route::get('ratings', [RatingController::class, 'index']);
	Route::post('ratings', [RatingController::class, 'store']);
	Route::get('ratings/{rating}', [RatingController::class, 'show']);
	Route::put('ratings/{rating}', [RatingController::class, 'update']);
	Route::delete('ratings/{rating}', [RatingController::class, 'destroy']);

	Route::get('analytics', [AnalyticsController::class, 'index'])->middleware('role:admin,institution');
	Route::post('analytics/sync', [AnalyticsController::class, 'sync'])->middleware('role:admin');

});
