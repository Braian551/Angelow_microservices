<?php

use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications', [NotificationController::class, 'store']);
Route::post('/notifications/triggers/dispatch', [NotificationController::class, 'dispatchTrigger']);
Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

Route::get('/notification-preferences', [NotificationPreferenceController::class, 'show']);
Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update']);
Route::get('/announcements/home', [AdminNotificationController::class, 'homeAnnouncements']);

// ── Admin ───────────────────────────────────────────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/announcements', [AdminNotificationController::class, 'announcements']);
    Route::post('/announcements', [AdminNotificationController::class, 'storeAnnouncement']);
    Route::put('/announcements/{id}', [AdminNotificationController::class, 'updateAnnouncement']);
    Route::delete('/announcements/{id}', [AdminNotificationController::class, 'destroyAnnouncement']);
});
