<?php

use Illuminate\Support\Facades\Route;
use Modules\Cars\Http\Controllers\BrandController;
use Modules\Cars\Http\Controllers\CarsController;
use Modules\Cars\Http\Controllers\CategoryController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('cars', CarsController::class)->names('cars');
    Route::apiResource('categories', CategoryController::class)->names('categories');
    Route::apiResource('brands', BrandController::class)->names('brands');
});

Route::prefix('v1/front')->group(function () {
    Route::get('home-brand', [BrandController::class, 'homeBrand'])->name('homeBrand');
    Route::get('/cars/{id}', [CarsController::class, 'frontDetail'])->name('carDetail');
});
