<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\RoomBookController;
use App\Http\Controllers\Api\AccessCardController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CollaboratorController;
use App\Http\Controllers\Api\InternalNoteController;
use App\Http\Controllers\Api\RoomAppointmentController;
use App\Http\Controllers\ProfileController;

Route::controller(AuthController::class)->group(function () {
    // user login and logout
    Route::post('/user-login', 'login');
    Route::post('/user-logout', 'logout');
    // OTP verify
    Route::post('/send-otp', 'sendOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('reset-password', 'resetPassword');
    // Store FCM Token
    Route::post('/store-user-fcm-token', 'storeFcmToken');
    Route::post('/delete-user-fcm-token', 'deleteFcmToken');

    // user profile
    Route::post('/update-user', 'updateUser');
    Route::post('/delete-account', 'deleteSelfAccount')->middleware('auth:api');
    Route::post('/user/profile/reset-password', 'userResetPassword')->middleware('auth:api');
    // Account create
    Route::post('/create-account', 'createAccount');
});


Route::controller(ProfileController::class)->group(function () {
    Route::get('/user-profile', 'getUserProfile');
    Route::post('/update-profile', 'updateProfile');
    Route::post('/change-password', 'changePassword');
});

Route::controller(RoomController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-room', 'addRoom');
    Route::get('/map/rooms',  'index');
    Route::post('/assign-associate_company', 'assignCompany');
    Route::post('/show-room-details/{id}', 'showRoomDetails');
    Route::get('/room-status-change/{status}/{id}', 'roomStatusChange');
    Route::post('/map/rooms/remove-company',  'removeCompany');
});


Route::controller(CompanyController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-company', 'addCompany');
    Route::post('/get-specific-company', 'getSpecificCompanies');
    Route::post('/add-company/general-data', 'updateCompanyGeneralData');
    Route::post('/delete-company', 'deleteCompany');
    Route::get('/list-companies', 'getAllCompanies');
    Route::get('/list-incubation-types-for_filter', 'getIncubationTypes');;
    // logo
    Route::post('/upload_logo', 'uploadLogo');
    Route::post('/upload-delete', 'deleteLogo');
    // mobile api
    Route::get('/show-company/{id}', 'show');
    Route::post('/update-company', 'update'); // Mobile Using
});


Route::controller(CollaboratorController::class)->group(function () {
    Route::get('/collaborators-list', 'index');
    Route::post('/collaborators-add', 'store');
    Route::post('/collaborators-update', 'update');
    Route::post('/collaborators-delete', 'destroy');
});


Route::controller(ContractController::class)->middleware('auth:api')->group(function () {
    Route::post('/contracts-add', 'store');
    Route::get('/get-single/contract', 'show');
    Route::post('/contracts-update', 'update');
    Route::post('/contracts-delete', 'destroy');
    Route::post('/contracts-singleFile-delete', 'deleteSingleFile');
});

Route::controller(DocumentController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-documents', 'store');
    Route::post('/delete-document', 'deleteDocument');
    Route::get('/get-all-documents', 'allDocuments');
});

Route::controller(BookingController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-all-booking-room', 'index');
});



Route::controller(RoomBookController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-room-booking', 'RoomBook');
});

Route::controller(RoomAppointmentController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-room-appointment', 'RoomAppointment');
});


Route::controller(AccountController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-account', 'store');
    Route::get('/delete-account/{id}', 'destroy');
    Route::post('/update-account', 'update');
});


Route::controller(InternalNoteController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-note', 'store');
    Route::get('/delete-note/{id}', 'destroy');
    Route::post('/update-note/{id}', 'update');
});


Route::controller(ArchiveController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-to-archive', 'addToArchive');
    Route::post('/restore-comapany', 'restoreComapany');
});


Route::controller(AccessCardController::class)->middleware('auth:api')->group(function () {
    Route::post('/access_card/update', 'updateAccessCode');
    // mobile api

    Route::get('/get-cards', 'getCardStats');
});




Route::controller(MeetingController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-meeting', 'store');
    Route::get('/filter-meetings', 'filter');
    Route::post('/filter-meetings-type-room', 'filterMeetingBytype');
    Route::get('/show-single-meeting/{id}', 'singleMeeting');
    Route::get('/get-all-meeting', 'getAllMeeting');
    Route::get('/get-all-events', 'getAllEvents');
    Route::get('/get-all-meeting-request', 'getmeetingRequest');

    // meeting request api
    Route::post('/add/request', 'StoreMeeting');
});





Route::controller(AppointmentController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-appointment', 'addAppointment');
});
