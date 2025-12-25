<?php

use Illuminate\Support\Facades\Route;
use Modules\CarRequest\Http\Controllers\CarRequestController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('carrequests', CarRequestController::class)->names('carrequest');
});
