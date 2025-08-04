<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\SaleExportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/products/export', [ProductExportController::class, 'export'])
    ->name('products.export')
    ->middleware('auth');

Route::get('/sales/export', [SaleExportController::class, 'export'])
    ->name('sales.export')
    ->middleware('auth');
