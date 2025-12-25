<?php

use Illuminate\Support\Facades\Route;
use Modules\Banners\Http\Controllers\BannersController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('banners', BannersController::class)->names('banners');
});
Route::prefix('v1/front')->group(function () {
    Route::get('banners', [BannersController::class,'index'])->name('bannersFront');
});
