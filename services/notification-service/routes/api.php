<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications', [NotificationController::class, 'store']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
