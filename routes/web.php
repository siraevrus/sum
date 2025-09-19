<?php

use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\ProductWebController;
use App\Http\Controllers\SaleExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/products/export', [ProductExportController::class, 'export'])
    ->name('products.export')
    ->middleware('auth');

Route::get('/sales/export', [SaleExportController::class, 'export'])
    ->name('sales.export')
    ->middleware('auth');

// Поддержка отправки формы создания товара в тестах
Route::post('/admin/products', [ProductWebController::class, 'store'])
    ->middleware('auth');
