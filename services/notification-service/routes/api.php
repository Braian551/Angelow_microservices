<?php

/**
 * Rutas de la API del servicio de notificaciones.
 * Organizadas por recurso: health, notificaciones, preferencias, anuncios.
 * Las rutas admin están protegidas por el middleware EnsureAdmin.
 */

use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

// Health check para el orquestador de contenedores.
Route::get('/health', HealthController::class);

// CRUD de notificaciones para usuarios.
Route::get('/notifications', [NotificationController::class, 'index']);                          // Listar notificaciones
Route::post('/notifications', [NotificationController::class, 'store']);                         // Crear notificación
Route::post('/notifications/triggers/dispatch', [NotificationController::class, 'dispatchTrigger']); // Disparo masivo interno
Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);        // Marcar todas como leídas
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);          // Marcar una como leída
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);                 // Eliminar notificación

// Preferencias de notificación por usuario.
Route::get('/notification-preferences', [NotificationPreferenceController::class, 'show']);       // Consultar preferencias
Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update']);     // Actualizar preferencias

// Anuncios visibles en la página principal (público).
Route::get('/announcements/home', [AdminNotificationController::class, 'homeAnnouncements']);

// ── Admin (protegido con middleware de autenticación) ──────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/notification-dismissals', [AdminNotificationController::class, 'notificationDismissals']);
    Route::patch('/notification-dismissals', [AdminNotificationController::class, 'storeNotificationDismissals']);
    Route::get('/announcements', [AdminNotificationController::class, 'announcements']);
    Route::post('/announcements', [AdminNotificationController::class, 'storeAnnouncement']);
    Route::put('/announcements/{id}', [AdminNotificationController::class, 'updateAnnouncement']);
    Route::delete('/announcements/{id}', [AdminNotificationController::class, 'destroyAnnouncement']);
});
