<?php

use Illuminate\Support\Facades\Route;
use Modules\Locations\Http\Controllers\CitiesController;
use Modules\Locations\Http\Controllers\ProvincesController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('provinces', ProvincesController::class)->names('provinces');
    Route::apiResource('cities', CitiesController::class)->names('cities');
    Route::get('all-city', [CitiesController::class, "cityAll"])->name('cityAll');
    Route::get('all-province', [CitiesController::class, "provinceAll"])->name('provinceAll');
});
Route::prefix('v1/front')->group(function () {
    Route::get('provinces', [ProvincesController::class, "index"])->name('provincesFrontIndex');
    Route::get('cities', [CitiesController::class, "index"])->name('citiesFrontIndex');
});
