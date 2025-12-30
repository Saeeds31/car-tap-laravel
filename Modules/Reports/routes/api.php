<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Http\Controllers\ReportsController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::prefix('reports')->group(function () {
        Route::get('/users', [ReportsController::class, 'usersReport']);
        Route::get('/cars', [ReportsController::class, 'carsReport']);
        Route::get('/car-request', [ReportsController::class, 'carRequestReport']);
    });
});
