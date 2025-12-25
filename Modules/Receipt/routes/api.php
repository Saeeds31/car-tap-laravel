<?php

use Illuminate\Support\Facades\Route;
use Modules\Receipt\Http\Controllers\ReceiptController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('receipts', ReceiptController::class)->names('receipt');
    Route::post('receipts/{receiptId}/accept', [ReceiptController::class,'acceptReceipt'])->name('acceptReceipt');
    Route::post('receipts/{receiptId}/reject', [ReceiptController::class,'rejectReceipt'])->name('acceptReceipt');
    
});
Route::middleware(['auth:sanctum'])->prefix('v1/front')->group(function () {
    Route::get('receipts', [ReceiptController::class,'userReceipts'])->name('userReceipts');
    Route::post('receipts', [ReceiptController::class,'storeUserReceipt'])->name('storeUserReceipt');
});
