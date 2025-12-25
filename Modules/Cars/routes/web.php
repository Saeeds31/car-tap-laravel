<?php

use Illuminate\Support\Facades\Route;
use Modules\Cars\Http\Controllers\CarsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cars', CarsController::class)->names('cars');
});
