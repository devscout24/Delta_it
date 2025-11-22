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
use App\Http\Controllers\Api\MeetingEventController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoomAppointmentController;
use App\Http\Controllers\Api\TicketAttachmentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketMessageController;
use App\Http\Controllers\MeetingBookingController;

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
    Route::get('/map/rooms/stats',  'stats');

    Route::get('/map/rooms',  'index');
    Route::get('/get-rooms',  'index');

    Route::post('/add-room', 'addRoom');
    Route::post('/assign-associate_company', 'assignCompany');


    Route::get('/get-assign-associate_company-info/{id}', 'getCompanyInfo');
    Route::post('/show-room-details/{id}', 'showRoomDetails');

    Route::get('/room-status-change/{status}/{id}', 'roomStatusChange');
    Route::post('/map/rooms/remove-company',  'removeCompany');
});


Route::controller(CompanyController::class)->group(function () {
    Route::get('/get-company', 'getCompany');
    Route::post('/add-company', 'addCompany');
    Route::post('/update-company', 'update'); // For Mobile
    Route::post('/update-general-company', 'updateCompanyGeneralData');
    Route::post('/upload_logo', 'uploadLogo');
    Route::post('/upload-delete', 'deleteLogo');
    Route::post('/delete-company', 'deleteCompany');
    Route::post('/get-specific-company', 'getSpecificCompanies');
    Route::get('/show-company/{id}', 'show');
    Route::get('/archive-company/{id}', 'archiveCompany');
});

Route::controller(PaymentController::class)->group(function () {
    Route::get('/company-payments-get', 'index');
    Route::post('/company-payments-add', 'store');
    Route::post('/company-payments-update/{id}', 'update');
    Route::get('/company-payments-yearly-info', 'dataInfo');
    Route::get('/get-all-company-payments-info', 'allPaymentsInfo');
});

Route::controller(CollaboratorController::class)->group(function () {
    Route::get('/collaborators-list', 'index');
    Route::get('/collaborator-info/{id}', 'collaboratorInfo');
    Route::post('/collaborators-add', 'store');
    Route::post('/collaborators-update/{id}', 'update');
    Route::post('/collaborators-delete', 'destroy');
});

Route::controller(ContractController::class)->group(function () {
    Route::get('/get-company-contracts', 'index');
    Route::get('/get-company-contracts-details/{id}', 'details');
    Route::post('/update-contract-info', 'update');
    Route::post('/add-contract-file', 'storeFile');
    Route::post('/remove-contract-file', 'destroy');

    Route::get('/get-all-company-contracts', 'allContracts');
});

Route::controller(DocumentController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-documents', 'store');
    Route::post('/delete-document', 'deleteDocument');
    Route::get('/get-all-documents', 'allDocuments');
});

Route::controller(AccountController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-company-user', 'get');
    Route::post('/add-company-user', 'store');
    Route::post('/update-company-account', 'update');
    Route::get('/delete-company-account/{id}', 'destroy');
});

Route::controller(AccessCardController::class)->middleware('auth:api')->group(function () {
    Route::get('/get-cards', 'getCardStats');
    Route::post('/access_card/update', 'updateAccessCode');
});

Route::controller(InternalNoteController::class)->middleware('auth:api')->group(function () {
    Route::post('/add-note', 'store');
    Route::post('/update-note/{id}', 'update');
    Route::get('/delete-note/{id}', 'd     estroy');
});



// Ticket Controller
Route::controller(TicketController::class)->group(function () {
    Route::get('/tickets/list', 'index');
    Route::post('/tickets/create', 'store');
    Route::get('/tickets/show/{id}', 'show');
    Route::post('/tickets/update/{id}', 'updateStatus');
});

// Ticket Message Controller (Chat)
Route::controller(TicketMessageController::class)->group(function () {
    Route::get('/tickets/{ticket_id}/messages', 'index');
    Route::post('/tickets/{ticket_id}/messages/send', 'store');
});

// Ticket File Controller (Attachments)
Route::controller(TicketAttachmentController::class)->group(function () {
    Route::post('/tickets/messages/{message_id}/upload-file', ' ');
    Route::delete('/tickets/messages/{message_id}/delete-file', 'destroy');
});

Route::controller(MeetingController::class)->group(function () {
    Route::get('/get-meeting', 'index');
    Route::post('/create-meetings', 'create');
    Route::post('/update-meetings', 'update');
    Route::post('/update-meeting-status', 'updateStatus');

    Route::post('/meeting/request', 'requestMeeting');
    Route::get('/meeting/{id}/accept', 'acceptMeeting');
    Route::get('/meeting/{id}/reject', 'rejectMeeting');
    Route::get('/meeting/{id}/cancel', 'cancelMeeting');
});

Route::controller(MeetingEventController::class)->group(function () {
    Route::get('/meeting-events', 'index');
    Route::get('/meeting-events/{id}', 'show');
    Route::post('/meeting-events/create', 'store');
    Route::post('/meeting-events/update/{id}', 'update');
    Route::delete('/meeting-events/delete/{id}', 'destroy');
});

Route::controller(BookingController::class)->group(function () {
    Route::get('/meeting-', 'index');
    Route::get('/meeting-bookings/{id}', 'show');
    Route::post('/meeting-bookings/create', 'store');
    Route::post('/meeting-bookings/update/{id}', 'update');
    Route::delete('/meeting-bookings/delete/{id}', 'destroy');
});
Route::controller(MeetingBookingController::class)->group(function () {
    Route::get('/bookings/list', 'index');
    Route::get('/bookings/details/{id}', 'details');
    Route::post('/bookings/create', 'createBooking');
    Route::get('/bookings/request/list', 'requestList');
    Route::get('/bookings/cancel/{id}', 'cancelBooking');
});


Route::controller(NotificationController::class)->group(function () {
    Route::get('/notifications', 'getNotifications');
    Route::get('/notifications/unread', 'unread');
    Route::post('/notifications/create', 'create');
    Route::post('/notifications/read', 'markRead');
    Route::post('/notifications/delete', 'delete');
});

