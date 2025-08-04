<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Публичные маршруты (без аутентификации)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Защищенные маршруты (требуют аутентификации)
Route::middleware(['auth:sanctum', 'compress'])->group(function () {
    // Аутентификация
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Товары
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/stats', [ProductController::class, 'stats']);
        Route::get('/popular', [ProductController::class, 'popular']);
        Route::get('/{product}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{product}', [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);
    });

    // Продажи
    Route::prefix('sales')->group(function () {
        Route::get('/', [SaleController::class, 'index']);
        Route::get('/stats', [SaleController::class, 'stats']);
        Route::get('/{sale}', [SaleController::class, 'show']);
        Route::post('/', [SaleController::class, 'store']);
        Route::put('/{sale}', [SaleController::class, 'update']);
        Route::delete('/{sale}', [SaleController::class, 'destroy']);
        Route::post('/{sale}/process', [SaleController::class, 'process']);
        Route::post('/{sale}/cancel', [SaleController::class, 'cancel']);
    });
}); 