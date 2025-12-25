<?php

use Illuminate\Support\Facades\Route;
use Modules\SalesPlan\Http\Controllers\SalesPlanController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('salesplans', SalesPlanController::class)->names('salesplan');
});
