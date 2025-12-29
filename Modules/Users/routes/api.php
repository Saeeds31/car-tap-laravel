<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\AuthController;
use Modules\Users\Http\Controllers\PermissionController;
use Modules\Users\Http\Controllers\RolesController;
use Modules\Users\Http\Controllers\UsersController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('users', UsersController::class)->names('users');
    Route::apiResource('roles', RolesController::class)->names('roles');
    Route::get('/all-roles', [RolesController::class, 'allRole'])->name("allRole");
    Route::get('/user-managers', [UsersController::class, 'managerIndex'])->name("managerIndex");
    Route::post('/user-managers/assign-roles', [RolesController::class, 'assignRoles'])->name("assignRoles");
    Route::get('/all-permissions', [RolesController::class, 'allPermissions'])->name("allPermissions");
    Route::get('/admin-info', [UsersController::class, 'adminInfo'])->name("adminInfo");
    Route::post('/save-permissions', [RolesController::class, 'savePermissions'])->name("savePermissions");
    Route::get('/users/{userId}/reject-for-referral', [UsersController::class, 'validityReject'])->name("validityReject");
    Route::post('/users/{userId}/set-validity', [UsersController::class, 'setValidity'])->name("setValidity");
    // 
    Route::get('/user-managers/city-permission', [PermissionController::class, 'CityIndex'])->name("CityIndex");
    Route::post('/user-managers/city-permission', [PermissionController::class, 'CityStore'])->name("CityStore-permission");
    Route::delete('/user-managers/city-permission/{id}', [PermissionController::class, 'CityDelete'])->name("CityDelete-permission");
});
Route::post('v1/admin/login-verify', [AuthController::class, 'adminLogin'])->name("adminLogin");
Route::post('v1/admin/send-token', [AuthController::class, 'adminSendToken'])->name("adminSendToken");

Route::middleware(['auth:sanctum'])->prefix('v1/front')->group(function () {
    Route::post('/user/profile/edit', [AuthController::class, 'update'])->name("update-user-auth");
    Route::get('/user/profile', [UsersController::class, 'userProfile'])->name("userProfile");
    Route::get('/user/validity', [UsersController::class, 'userValidity'])->name("userValidity");
    Route::get('/supervisor-role', [RolesController::class, 'supervisorRole'])->name("supervisorRole");
});
Route::prefix('v1/front')->group(function () {
    Route::post('/check-mobile', [AuthController::class, 'checkMobile']);
    Route::post('/send-otp', [AuthController::class, 'sendOtpAgain']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
});
