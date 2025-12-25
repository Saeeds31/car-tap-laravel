<?php

use Illuminate\Support\Facades\Route;
use Modules\Receipt\Http\Controllers\ReceiptController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('receipts', ReceiptController::class)->names('receipt');
});
