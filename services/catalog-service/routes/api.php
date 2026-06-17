<?php

// Comentario de mantenimiento: Estas rutas conectan contratos HTTP con controladores del servicio.

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InternalCatalogController;
use App\Http\Controllers\Admin\AdminCatalogController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog Service API Routes
|--------------------------------------------------------------------------
*/

// Sección: portada.
Route::get('/home', [SiteController::class, 'home']);

// Sección: contenido del sitio.
Route::get('/settings', [SiteController::class, 'settings']);
// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
Route::get('/sliders', [SiteController::class, 'sliders']);

// Sección: búsquedas y sugerencias.
Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
Route::get('/search/history', [SearchController::class, 'history']);
// Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
Route::post('/search/history', [SearchController::class, 'storeHistory']);

// Sección: productos.
Route::get('/products', [ProductController::class, 'index']);
// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Sección: categorías y colecciones.
Route::get('/categories', [CategoryController::class, 'index']);
// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
Route::get('/collections', [CategoryController::class, 'collections']);

// Sección: favoritos.
Route::get('/wishlist', [WishlistController::class, 'index']);
// Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);

// Sección: administración.
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    // Productos
    Route::get('/products', [AdminCatalogController::class, 'products']);
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/products/export/csv', [AdminCatalogController::class, 'exportProductsCsv']);
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/products/export/pdf', [AdminCatalogController::class, 'exportProductsPdf']);
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/products/{id}', [AdminCatalogController::class, 'showProduct']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/products', [AdminCatalogController::class, 'storeProduct']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/products/{id}', [AdminCatalogController::class, 'updateProduct']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/products/{id}', [AdminCatalogController::class, 'destroyProduct']);
    // Expone un endpoint PATCH del servicio y delega la operación al controlador correspondiente.
    Route::patch('/products/{id}/status', [AdminCatalogController::class, 'toggleProductStatus']);
    // Categorias
    Route::get('/categories', [AdminCatalogController::class, 'categories']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/categories', [AdminCatalogController::class, 'storeCategory']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/categories/{id}', [AdminCatalogController::class, 'updateCategory']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/categories/{id}', [AdminCatalogController::class, 'destroyCategory']);
    // Colecciones
    Route::get('/collections', [AdminCatalogController::class, 'collections']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/collections', [AdminCatalogController::class, 'storeCollection']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/collections/{id}', [AdminCatalogController::class, 'updateCollection']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/collections/{id}', [AdminCatalogController::class, 'destroyCollection']);
    // Colores
    Route::get('/colors', [AdminCatalogController::class, 'colors']);
    // Tallas
    Route::get('/sizes', [AdminCatalogController::class, 'sizes']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/sizes', [AdminCatalogController::class, 'storeSize']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/sizes/{id}', [AdminCatalogController::class, 'updateSize']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/sizes/{id}', [AdminCatalogController::class, 'destroySize']);
    // Inventario
    Route::get('/inventory', [AdminCatalogController::class, 'inventory']);
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/inventory/history', [AdminCatalogController::class, 'inventoryHistory']);
    // Expone un endpoint PATCH del servicio y delega la operación al controlador correspondiente.
    Route::patch('/inventory/{variantId}/stock', [AdminCatalogController::class, 'adjustStock']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/inventory/transfer', [AdminCatalogController::class, 'transferStock']);
    // Resenas
    Route::get('/reviews', [AdminCatalogController::class, 'reviews']);
    // Expone un endpoint PATCH del servicio y delega la operación al controlador correspondiente.
    Route::patch('/reviews/{id}', [AdminCatalogController::class, 'updateReviewStatus']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/reviews/{id}', [AdminCatalogController::class, 'deleteReview']);
    // Preguntas
    Route::get('/questions', [AdminCatalogController::class, 'questions']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/questions/{id}/answer', [AdminCatalogController::class, 'answerQuestion']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/questions/{id}', [AdminCatalogController::class, 'deleteQuestion']);
    // Sliders
    Route::get('/sliders', [AdminCatalogController::class, 'sliders']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/sliders', [AdminCatalogController::class, 'storeSlider']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/sliders/{id}', [AdminCatalogController::class, 'updateSlider']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/sliders/{id}', [AdminCatalogController::class, 'destroySlider']);
    // Expone un endpoint PATCH del servicio y delega la operación al controlador correspondiente.
    Route::patch('/sliders/{id}/status', [AdminCatalogController::class, 'toggleSliderStatus']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/sliders/reorder', [AdminCatalogController::class, 'reorderSliders']);
    // Configuracion
    Route::get('/settings', [AdminCatalogController::class, 'settings']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/settings', [AdminCatalogController::class, 'updateSettings']);
    // Reportes
    Route::get('/reports/products', [AdminCatalogController::class, 'reportProducts']);
});

// Internal endpoints
Route::prefix('internal')->group(function () {
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/products/{id}', [InternalCatalogController::class, 'product']);
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/variants/{id}', [InternalCatalogController::class, 'variant']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/inventory/commit', [InternalCatalogController::class, 'commitInventory']);
});

// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.

Route::get('/health', HealthController::class);
