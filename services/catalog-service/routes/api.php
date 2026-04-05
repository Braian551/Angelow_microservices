<?php

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

// ── Home ────────────────────────────────────────────────────────────
Route::get('/home', [SiteController::class, 'home']);

// ── Site Content ────────────────────────────────────────────────────
Route::get('/settings', [SiteController::class, 'settings']);
Route::get('/sliders', [SiteController::class, 'sliders']);

// ── Search Suggestions ─────────────────────────────────────────────
Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
Route::get('/search/history', [SearchController::class, 'history']);
Route::post('/search/history', [SearchController::class, 'storeHistory']);

// ── Products ────────────────────────────────────────────────────────
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// ── Categories & Collections ────────────────────────────────────────
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/collections', [CategoryController::class, 'collections']);

// ── Wishlist / Favorites ────────────────────────────────────────────
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);

// ── Admin ───────────────────────────────────────────────────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    // Productos
    Route::get('/products', [AdminCatalogController::class, 'products']);
    Route::get('/products/{id}', [AdminCatalogController::class, 'showProduct']);
    Route::post('/products', [AdminCatalogController::class, 'storeProduct']);
    Route::put('/products/{id}', [AdminCatalogController::class, 'updateProduct']);
    Route::delete('/products/{id}', [AdminCatalogController::class, 'destroyProduct']);
    Route::patch('/products/{id}/status', [AdminCatalogController::class, 'toggleProductStatus']);
    // Categorias
    Route::get('/categories', [AdminCatalogController::class, 'categories']);
    Route::post('/categories', [AdminCatalogController::class, 'storeCategory']);
    Route::put('/categories/{id}', [AdminCatalogController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [AdminCatalogController::class, 'destroyCategory']);
    // Colecciones
    Route::get('/collections', [AdminCatalogController::class, 'collections']);
    Route::post('/collections', [AdminCatalogController::class, 'storeCollection']);
    Route::put('/collections/{id}', [AdminCatalogController::class, 'updateCollection']);
    Route::delete('/collections/{id}', [AdminCatalogController::class, 'destroyCollection']);
    // Colores
    Route::get('/colors', [AdminCatalogController::class, 'colors']);
    // Tallas
    Route::get('/sizes', [AdminCatalogController::class, 'sizes']);
    Route::post('/sizes', [AdminCatalogController::class, 'storeSize']);
    Route::put('/sizes/{id}', [AdminCatalogController::class, 'updateSize']);
    Route::delete('/sizes/{id}', [AdminCatalogController::class, 'destroySize']);
    // Inventario
    Route::get('/inventory', [AdminCatalogController::class, 'inventory']);
    Route::get('/inventory/history', [AdminCatalogController::class, 'inventoryHistory']);
    Route::patch('/inventory/{variantId}/stock', [AdminCatalogController::class, 'adjustStock']);
    Route::post('/inventory/transfer', [AdminCatalogController::class, 'transferStock']);
    // Resenas
    Route::get('/reviews', [AdminCatalogController::class, 'reviews']);
    Route::patch('/reviews/{id}', [AdminCatalogController::class, 'updateReviewStatus']);
    Route::delete('/reviews/{id}', [AdminCatalogController::class, 'deleteReview']);
    // Preguntas
    Route::get('/questions', [AdminCatalogController::class, 'questions']);
    Route::post('/questions/{id}/answer', [AdminCatalogController::class, 'answerQuestion']);
    Route::delete('/questions/{id}', [AdminCatalogController::class, 'deleteQuestion']);
    // Sliders
    Route::get('/sliders', [AdminCatalogController::class, 'sliders']);
    Route::post('/sliders', [AdminCatalogController::class, 'storeSlider']);
    Route::put('/sliders/{id}', [AdminCatalogController::class, 'updateSlider']);
    Route::delete('/sliders/{id}', [AdminCatalogController::class, 'destroySlider']);
    // Configuracion
    Route::get('/settings', [AdminCatalogController::class, 'settings']);
    Route::put('/settings', [AdminCatalogController::class, 'updateSettings']);
    // Reportes
    Route::get('/reports/products', [AdminCatalogController::class, 'reportProducts']);
});

// Internal endpoints
Route::prefix('internal')->group(function () {
    Route::get('/products/{id}', [InternalCatalogController::class, 'product']);
    Route::get('/variants/{id}', [InternalCatalogController::class, 'variant']);
});

Route::get('/health', HealthController::class);
