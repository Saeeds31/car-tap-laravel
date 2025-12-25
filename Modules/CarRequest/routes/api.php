<?php

use Illuminate\Support\Facades\Route;
use Modules\CarRequest\Http\Controllers\CarRequestController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('carrequests', CarRequestController::class)->names('carrequest');
});
