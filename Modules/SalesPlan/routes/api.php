<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesPlan\Http\Controllers\SalesPlanController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('sales-plan', SalesPlanController::class)->names('salesplan');
});
Route::prefix('v1/front')->group(function () {
    Route::get('/check-sale/{carId}', [SalesPlanController::class, 'checkCarInSale'])->name('checkCarInSale');
    Route::get('/sales-plan/{id}', [SalesPlanController::class, 'salesPlanDetail'])->name('sales-plan-detail');
});
