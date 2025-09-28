<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CollaboratorController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContractController;

Route::controller(AuthController::class)->group(function () {
    // user login and logout
    Route::post('/user-login', 'login');
    Route::post('/user-signup', 'signup');
    Route::post('/user-logout', 'logout');
    // user otp verify
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('verify/email_otp', [AuthController::class, 'verifyEmailOtp']);
    // user profile
    Route::post('/update-user', [AuthController::class, 'updateUser']);
    Route::post('/delete-account', [AuthController::class, 'deleteSelfAccount'])->middleware('auth:api');
    Route::post('/user/profile/reset-password', [AuthController::class, 'userResetPassword'])->middleware('auth:api');
});

Route::controller(CompanyController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-company', 'addCompany');
    Route::post('/get-specific-company', 'getSpecificCompanies');
    Route::post('/add-company/general-data', 'updateCompanyGeneralData');
    Route::post('/delete-company', 'deleteCompany');
    Route::get('/list-companies', 'getAllCompanies');
    Route::get('/list-incubation-types-for_filter', 'getIncubationTypes');
});


Route::controller(CollaboratorController::class)->middleware('auth:api')->group(function () {
    Route::get('/collaborators-list', 'index');
    Route::post('/collaborators-add', 'store');
    Route::post('/collaborators-update', 'update');
    Route::post('/collaborators-delete', 'destroy');
});


Route::controller(ContractController::class)->middleware('auth:api')->group(function () {
    Route::post('/contracts-add', 'store');
    Route::post('/contracts-update', 'update');
});
