<?php

use Illuminate\Support\Facades\Route;
use Modules\CarRequest\Http\Controllers\CarRequestController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('carrequests', CarRequestController::class)->names('carrequest');
});

Route::prefix('v1/front')->group(function () {
    Route::post('/car-request/{saleId}', [CarRequestController::class,'storeRequest'])->name('carrequest');
});
