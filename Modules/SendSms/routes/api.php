<?php

use Illuminate\Support\Facades\Route;
use Modules\SendSms\Http\Controllers\SendSmsController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('send-sms', SendSmsController::class)->names('sendsms');
});
