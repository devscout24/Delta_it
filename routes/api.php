<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;



Route::controller(AuthController::class)->group(function () {

    // Login
    Route::post('/admin-login', 'adminLogin');
    Route::post('/login', 'login');

    // Logout
    Route::post('/logout', 'logout')->middleware('auth:api');

    // Forgot Password (OTP)
    Route::post('/forgot-password', 'sendOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset-password', 'resetPassword');
});

Route::controller(ProfileController::class)->middleware('auth:api')->group(function () {
    Route::get('/profile', 'getUserProfile');
    Route::post('/profile/update', 'updateProfile');
    Route::post('/profile/change-password', 'changePassword');
    Route::delete('/profile/delete', 'deleteAccount');
});


require __DIR__ . '/webApp_api.php';
require __DIR__ . '/mobileApp_api.php';
