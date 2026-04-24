<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionRequestController;
use App\Http\Controllers\InstitutionStaffController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
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
Route::get('ratings', [RatingController::class, 'index']);
Route::get('ratings/{rating}', [RatingController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
	Route::get('dashboard', [DashboardController::class, 'index'])->middleware('role:admin,institution');
	Route::get('profile', [ProfileController::class, 'show']);
	Route::put('profile', [ProfileController::class, 'update']);

	Route::get('institution-requests', [InstitutionRequestController::class, 'index']);
	Route::post('institution-requests', [InstitutionRequestController::class, 'store']);
	Route::patch('institution-requests/{institutionRequest}/approve', [InstitutionRequestController::class, 'approve'])->middleware('role:admin');
	Route::patch('institution-requests/{institutionRequest}/reject', [InstitutionRequestController::class, 'reject'])->middleware('role:admin');

	Route::get('institutions/{institution}/staff', [InstitutionStaffController::class, 'index'])->middleware('role:admin,manager');
	Route::post('institutions/{institution}/staff/invite', [InstitutionStaffController::class, 'invite'])->middleware('role:admin,manager');
	Route::delete('institutions/{institution}/staff/{user}', [InstitutionStaffController::class, 'remove'])->middleware('role:admin,manager');
	Route::post('institutions/{institution}/staff/leave', [InstitutionStaffController::class, 'leave'])->middleware('role:institution');
	Route::post('institutions/{institution}/staff/transfer-manager', [InstitutionStaffController::class, 'transferManager'])->middleware('role:admin,manager');

	Route::post('institutions', [InstitutionController::class, 'store'])->middleware('role:admin');
	Route::put('institutions/{institution}', [InstitutionController::class, 'update'])->middleware('role:admin,manager');
	Route::delete('institutions/{institution}', [InstitutionController::class, 'destroy'])->middleware('role:admin');
	Route::patch('institutions/{institution}/approve', [InstitutionController::class, 'approve'])->middleware('role:admin');

	Route::post('services', [ServiceController::class, 'store'])->middleware('role:admin,manager');
	Route::put('services/{service}', [ServiceController::class, 'update'])->middleware('role:admin,manager');
	Route::delete('services/{service}', [ServiceController::class, 'destroy'])->middleware('role:admin,manager');

	Route::apiResource('departments', DepartmentController::class)->middleware('role:admin,manager');
	Route::apiResource('service-counters', ServiceCounterController::class)->middleware('role:admin,institution');
	Route::apiResource('queue-entries', QueueEntryController::class)->middleware('role:admin,institution');
	Route::apiResource('settings', SettingController::class)->middleware('role:admin');
	Route::apiResource('activity-logs', ActivityLogController::class)->except(['store', 'update'])->middleware('role:admin');
	Route::apiResource('users', UserController::class)->middleware('role:admin');

	Route::get('queues', [QueueController::class, 'index'])->middleware('role:admin,institution');
	Route::get('queues/{queue}', [QueueController::class, 'show'])->middleware('role:admin,institution');
	Route::post('queues', [QueueController::class, 'store'])->middleware('role:manager');
	Route::put('queues/{queue}', [QueueController::class, 'update'])->middleware('role:institution');
	Route::delete('queues/{queue}', [QueueController::class, 'destroy'])->middleware('role:manager');

	Route::get('appointments', [AppointmentController::class, 'index'])->middleware('role:citizen,institution');
	Route::post('appointments', [AppointmentController::class, 'store'])->middleware('role:citizen');
	Route::get('appointments/{appointment}', [AppointmentController::class, 'show'])->middleware('role:citizen,institution');
	Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])->middleware('role:citizen');
	Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])->middleware('role:citizen');
	Route::patch('appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->middleware('role:institution');
	Route::get('appointments/{appointment}/queue-position', [AppointmentController::class, 'queuePosition'])->middleware('role:citizen');

	Route::get('messages', [MessageController::class, 'index']);
	Route::post('messages', [MessageController::class, 'store'])->middleware('role:citizen,institution');
	Route::get('messages/{message}', [MessageController::class, 'show']);
	Route::put('messages/{message}', [MessageController::class, 'update'])->middleware('role:institution');
	Route::delete('messages/{message}', [MessageController::class, 'destroy']);

	Route::get('notifications', [NotificationController::class, 'index']);
	Route::patch('notifications/{notificationId}/read', [NotificationController::class, 'markRead']);

	Route::post('ratings', [RatingController::class, 'store'])->middleware('role:citizen');
	Route::put('ratings/{rating}', [RatingController::class, 'update'])->middleware('role:citizen');
	Route::delete('ratings/{rating}', [RatingController::class, 'destroy'])->middleware('role:citizen');

	Route::get('analytics', [AnalyticsController::class, 'index'])->middleware('role:admin,manager');
	Route::post('analytics/sync', [AnalyticsController::class, 'sync'])->middleware('role:admin');

});
