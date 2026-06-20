<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de API del servicio de auditoría (audit-service)
|--------------------------------------------------------------------------
|
| Todos los endpoints son de solo lectura (GET) y devuelven
| registros de trazabilidad. El prefijo /api se agrega automáticamente
| desde bootstrap/app.php mediante ->withRouting(... api: ...).
|
| Endpoints:
|   GET /api/health          — Health check del servicio
|   GET /api/audits/orders   — Auditoría de pedidos
|   GET /api/audits/users    — Auditoría de usuarios
|   GET /api/audits/products — Auditoría de productos
|
*/

// Health check para monitoreo y orquestación Docker
Route::get('/health', HealthController::class);

// Rutas de consulta de auditoría
Route::get('/audits/orders', [AuditController::class, 'orders']);
Route::get('/audits/users', [AuditController::class, 'users']);
Route::get('/audits/products', [AuditController::class, 'products']);
