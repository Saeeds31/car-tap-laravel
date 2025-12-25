<?php

use Illuminate\Support\Facades\Route;
use Modules\StartProject\Http\Controllers\StartProjectController;

Route::prefix('v1/admin')->group(function () {
    // run first time and comment routes
    Route::get('startprojects', [StartProjectController::class, 'startproject'])->name('startproject');
    Route::get('startprojects/permission', [StartProjectController::class, 'setPermissionTable'])->name("setPermissionTable");
    Route::get('startprojects/permission/super-admin', [StartProjectController::class, 'setSuperAdminPermissions'])->name("setSuperAdminPermissions");
});
