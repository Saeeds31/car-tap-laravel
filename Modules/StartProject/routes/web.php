<?php

use Illuminate\Support\Facades\Route;
use Modules\StartProject\Http\Controllers\StartProjectController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('startprojects', StartProjectController::class)->names('startproject');
});
