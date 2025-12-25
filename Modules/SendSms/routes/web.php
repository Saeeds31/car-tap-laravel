<?php

use Illuminate\Support\Facades\Route;
use Modules\SendSms\Http\Controllers\SendSmsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sendsms', SendSmsController::class)->names('sendsms');
});
