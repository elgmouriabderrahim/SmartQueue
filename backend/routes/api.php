<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\QueueEntryController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceCounterController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('home', [HomeController::class, 'index'])->name('api.home');
Route::get('dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

Route::apiResource('institutions', InstitutionController::class);
Route::apiResource('departments', DepartmentController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('service-counters', ServiceCounterController::class);
Route::apiResource('queues', QueueController::class);
Route::apiResource('appointments', AppointmentController::class);
Route::apiResource('queue-entries', QueueEntryController::class);
Route::apiResource('ratings', RatingController::class);
Route::apiResource('messages', MessageController::class);
Route::apiResource('settings', SettingController::class);
Route::apiResource('analytics', AnalyticsController::class);
Route::apiResource('activity-logs', ActivityLogController::class);
Route::apiResource('users', UserController::class);
