<?php

use Illuminate\Support\Facades\Route;
use Modules\Team\Http\Controllers\TeamController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('teams', TeamController::class)->names('team');
});
Route::prefix('v1/front')->group(function () {
    Route::get('teams', [TeamController::class, 'index'])->name('teamFront');
});
