<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesPlan\Http\Controllers\SalesPlanController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('sales-plan', SalesPlanController::class)->names('salesplan');
});
