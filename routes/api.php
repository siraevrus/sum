<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DiscrepancyController;
use App\Http\Controllers\Api\ProducerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductTemplateController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

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
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:3,1')
    ->name('register');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');

// Защищенные маршруты (требуют аутентификации)
// Временно без gzip-сжатия, чтобы исключить влияние на клиентов
Route::middleware(['auth:sanctum'])->group(function () {
    // Аутентификация
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Товары
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/stats', [ProductController::class, 'stats']);
        Route::get('/popular', [ProductController::class, 'popular']);
        Route::get('/export', [ProductController::class, 'export']);
        Route::get('/{product}', [ProductController::class, 'showById']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{product}', [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);
    });

    // Продажи
    Route::prefix('sales')->group(function () {
        Route::get('/', [SaleController::class, 'index']);
        Route::get('/stats', [SaleController::class, 'stats']);
        Route::get('/export', [SaleController::class, 'export']);
        Route::get('/{sale}', [SaleController::class, 'showById']);
        Route::post('/', [SaleController::class, 'store']);
        Route::put('/{sale}', [SaleController::class, 'update']);
        Route::delete('/{sale}', [SaleController::class, 'destroy']);
        Route::post('/{sale}/process', [SaleController::class, 'process']);
        Route::post('/{sale}/cancel', [SaleController::class, 'cancel']);
    });

    // Запросы
    Route::prefix('requests')->group(function () {
        Route::get('/', [RequestController::class, 'index']);
        Route::get('/stats', [RequestController::class, 'stats']);
        Route::get('/{request}', [RequestController::class, 'show']);
        Route::post('/', [RequestController::class, 'store']);
        Route::put('/{request}', [RequestController::class, 'update']);
        Route::delete('/{request}', [RequestController::class, 'destroy']);
        Route::post('/{request}/approve', [RequestController::class, 'approve']);
    });

    // Пользователи
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/stats', [UserController::class, 'stats']);
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::post('/{user}/block', [UserController::class, 'block']);
        Route::post('/{user}/unblock', [UserController::class, 'unblock']);
    });

    // Компании
    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::get('/{company}', [CompanyController::class, 'show']);
        Route::put('/{company}', [CompanyController::class, 'update']);
        Route::delete('/{company}', [CompanyController::class, 'destroy']);
        Route::post('/{company}/archive', [CompanyController::class, 'archive']);
        Route::post('/{company}/restore', [CompanyController::class, 'restore']);
        Route::get('/{company}/warehouses', [CompanyController::class, 'warehouses']);
    });

    // Приемка
    Route::prefix('receipts')->middleware(['role:warehouse_worker'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ReceiptController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\ReceiptController::class, 'store']);
        Route::get('/{receipt}', [\App\Http\Controllers\Api\ReceiptController::class, 'show']);
        Route::post('/{receipt}/receive', [\App\Http\Controllers\Api\ReceiptController::class, 'receive']);
    });

    // Алиас для "Товары в пути" (те же обработчики, что и receipts)
    Route::prefix('products-in-transit')->middleware(['role:warehouse_worker'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ReceiptController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\ReceiptController::class, 'store']);
        Route::get('/{receipt}', [\App\Http\Controllers\Api\ReceiptController::class, 'show']);
        Route::post('/{receipt}/receive', [\App\Http\Controllers\Api\ReceiptController::class, 'receive']);
    });

    // Склады
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::get('/stats', [WarehouseController::class, 'statsAll']);
        Route::get('/{warehouse}', [WarehouseController::class, 'show']);
        Route::get('/{warehouse}/stats', [WarehouseController::class, 'stats']);
        Route::get('/{warehouse}/products', [WarehouseController::class, 'products']);
        Route::get('/{warehouse}/employees', [WarehouseController::class, 'employees']);
        Route::post('/', [WarehouseController::class, 'store']);
        Route::put('/{warehouse}', [WarehouseController::class, 'update']);
        Route::delete('/{warehouse}', [WarehouseController::class, 'destroy']);
        Route::post('/{warehouse}/activate', [WarehouseController::class, 'activate']);
        Route::post('/{warehouse}/deactivate', [WarehouseController::class, 'deactivate']);
    });

    // Шаблоны товаров
    Route::prefix('product-templates')->group(function () {
        Route::get('/', [ProductTemplateController::class, 'index']);
        Route::get('/stats', [ProductTemplateController::class, 'stats']);
        Route::get('/units', [ProductTemplateController::class, 'units']);
        Route::get('/{productTemplate}', [ProductTemplateController::class, 'show']);
        Route::get('/{productTemplate}/attributes', [ProductTemplateController::class, 'attributes']);
        Route::get('/{productTemplate}/products', [ProductTemplateController::class, 'products']);
        Route::post('/', [ProductTemplateController::class, 'store']);
        Route::put('/{productTemplate}', [ProductTemplateController::class, 'update']);
        Route::delete('/{productTemplate}', [ProductTemplateController::class, 'destroy']);
        Route::post('/{productTemplate}/activate', [ProductTemplateController::class, 'activate']);
        Route::post('/{productTemplate}/deactivate', [ProductTemplateController::class, 'deactivate']);
        Route::post('/{productTemplate}/test-formula', [ProductTemplateController::class, 'testFormula']);
        Route::post('/{productTemplate}/attributes', [ProductTemplateController::class, 'addAttribute']);
        Route::put('/{productTemplate}/attributes/{attribute}', [ProductTemplateController::class, 'updateAttribute']);
        Route::delete('/{productTemplate}/attributes/{attribute}', [ProductTemplateController::class, 'deleteAttribute']);
    });

    // Остатки товаров
    Route::prefix('stocks')->group(function () {
        Route::get('/', [StockController::class, 'index']);
        Route::get('/{stock}', [StockController::class, 'show']);
    });

    // Производители
    Route::prefix('producers')->group(function () {
        Route::get('/', [ProducerController::class, 'index']);
        Route::post('/', [ProducerController::class, 'store']);
        Route::get('/{producer}', [ProducerController::class, 'show']);
        Route::put('/{producer}', [ProducerController::class, 'update']);
        Route::delete('/{producer}', [ProducerController::class, 'destroy']);
    });

    // Расхождения
    Route::prefix('discrepancies')->group(function () {
        Route::get('/', [DiscrepancyController::class, 'index']);
        Route::post('/', [DiscrepancyController::class, 'store']);
        Route::get('/{discrepancy}', [DiscrepancyController::class, 'show']);
        Route::put('/{discrepancy}', [DiscrepancyController::class, 'update']);
        Route::delete('/{discrepancy}', [DiscrepancyController::class, 'destroy']);
    });

    // Инфопанель (агрегированные данные для мобильного клиента)
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/revenue', [DashboardController::class, 'revenue']);
});
