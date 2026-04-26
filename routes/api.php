<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\AccessCardController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\CollaboratorController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InternalNoteController;
use App\Http\Controllers\Api\InternalContractController;
use App\Http\Controllers\Api\InternalDocumentController;
use App\Http\Controllers\Api\MeetingEventController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TicketAttachmentController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketMessageController;
use App\Http\Controllers\Api\UserController;

// =================================================
// Web APIs Start
// =================================================

// Authentication and User Management Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/admin-login', 'adminLogin');
    Route::post('/admin-logout', 'logout')->middleware('auth:api');
});
// Authentication and User Management Routes End

// Profile API
Route::controller(ProfileController::class)->middleware('auth:api')->group(function () {
    Route::get('/profile-info', 'getUserProfile');
    Route::post('/profile-update', 'updateProfile');
    Route::post('/profile-change-password', 'changePassword');

    // Backward compatibility for existing clients
    Route::get('/user-profile', 'getUserProfile');
    Route::post('/update-profile', 'updateProfile');
    Route::post('/change-password', 'changePassword');
});

// Forgot password entrypoint (admin + mobile)
Route::post('/profile-forgot-password', [AuthController::class, 'sendOtp']);

// Profile API End



Route::controller(DashboardController::class)->group(function () {
    Route::get('/dashboard/stats', 'stats');
});

Route::controller(RoomController::class)->middleware('auth:api')->group(function () {
    Route::get('/map/rooms/stats', 'stats');
    Route::get('/map/rooms', 'index');
    Route::get('/get-rooms', 'index');
    Route::post('/add-room', 'addRoom');
    Route::post('/assign-associate_company', 'assignCompany');
    Route::get('/get-assign-associate_company-info/{id}', 'getCompanyInfo');
    Route::post('/show-room-details/{id}', 'showRoomDetails');
    Route::get('/room-status-change/{status}/{id}', 'roomStatusChange');
    Route::post('/map/rooms/remove-company', 'removeCompany');
});

Route::controller(CompanyController::class)->group(function () {
    Route::get('/get-company', 'getCompany');
    Route::post('/add-company', 'addCompany');
    Route::post('/update-company', 'update');
    Route::post('/update-general-company', 'updateCompanyGeneralData');
    Route::post('/upload_logo', 'uploadLogo');
    Route::post('/upload-delete', 'deleteLogo');
    Route::post('/delete-company', 'deleteCompany');
    Route::post('/get-specific-company', 'getSpecificCompanies');
    Route::get('/show-company/{id}', 'show');
    Route::get('/archive-company/{id}', 'archiveCompany');
    Route::get('/restore-company/{id}', 'restoreCompany');
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
    Route::post('/update-contract-info/{id}', 'update');
    Route::post('/add-contract-file', 'storeFile');
    Route::post('/remove-contract-file', 'destroy');
    Route::get('/get-all-company-contracts', 'allContracts');
});

Route::controller(DocumentController::class)->group(function () {
    Route::get('/get-all-documents/{id}', 'allDocuments');
    Route::post('/add-documents/{id}', 'store');
    Route::post('/delete-document', 'deleteDocument');
});

Route::controller(AccountController::class)->group(function () {
    Route::get('/get-company-user/{id}', 'get');
    Route::get('/get-company-user-details/{id}', 'getDetails');
    Route::post('/add-company-user', 'store');
    Route::post('/update-company-account/{id}', 'update');
    Route::get('/delete-company-account/{id}', 'destroy');
});

Route::controller(AccessCardController::class)->group(function () {
    Route::get('/get-cards/{id}', 'getCardStats');
    Route::post('/access_card/update/{id}', 'updateAccessCode');
});

Route::controller(PaymentController::class)->group(function () {
    Route::get('/company-payments-get', 'index');
    Route::post('/company-payments-add', 'store');
    Route::post('/company-payments-update/{id}', 'update');
    Route::get('/company-payments-yearly-info', 'dataInfo');
    Route::get('/get-all-company-payments-info', 'allPaymentsInfo');
});

Route::controller(InternalNoteController::class)->group(function () {
    Route::get('/get-note/{id}', 'get');
    Route::post('/add-note', 'store');
    Route::post('/update-note/{id}', 'update');
    Route::get('/delete-note/{id}', 'destroy');
});

Route::controller(InternalContractController::class)->group(function () {
    Route::get('/get-internal-contracts', 'index');
    Route::get('/get-internal-contracts-details/{id}', 'show');
    Route::post('/get-internal-contracts-store', 'store');
    Route::post('/get-internal-contracts-update/{id}', 'update');
    Route::get('/get-internal-contracts-destroy/{id}', 'destroy');
});

Route::controller(InternalDocumentController::class)->prefix('internal-documents')->group(function () {
    Route::get('/company', 'index');
    Route::post('/store', 'store');
    Route::get('/show/{id}', 'show');
    Route::get('/delete/{id}', 'destroy');
});

Route::controller(TicketController::class)->group(function () {
    Route::get('/tickets/list', 'index');
    Route::post('/tickets/create', 'store');
    Route::get('/tickets/show/{id}', 'show');
    Route::post('/tickets/update/{id}', 'updateStatus');
    Route::get('/tickets/delete/{id}', 'destroy');
});

Route::controller(TicketMessageController::class)->group(function () {
    Route::get('/tickets/{ticket_id}/messages', 'index');
    Route::post('/tickets/{ticket_id}/messages/send', 'store');
});

Route::controller(TicketAttachmentController::class)->group(function () {
    Route::post('/tickets/messages/{message_id}/upload-file', 'store');
    Route::delete('/tickets/messages/{message_id}/delete-file', 'destroy');
});

Route::controller(MeetingController::class)->group(function () {
    Route::get('/get-meeting', 'index');
    Route::get('/get-meeting/latest', 'latestMeetings');
    Route::get('/get-meeting/mobile', 'index_mobile');
    Route::get('/get-todays-meetings', 'todaysMeetings');
    Route::post('/create-meetings', 'create');
    Route::post('/update-meetings/{id}', 'update');
    Route::get('/meeting-details/{id}', 'details');
    Route::post('/update-meeting-status', 'updateStatus');
    Route::post('/meeting/request', 'requestMeeting');
    Route::get('/meeting/{id}/accept', 'acceptMeeting');
    Route::post('/meeting/{meeting_id}/accept', 'acceptMeeting');
    Route::get('/meeting/{id}/reject', 'rejectMeeting');
    Route::post('/meeting/{meeting_id}/reject', 'rejectMeeting');
    Route::get('/meeting/{id}/cancel', 'cancelMeeting');
    Route::post('/meeting/{meeting_id}/cancel', 'cancelMeeting');
    Route::match(['get', 'post'], '/meeting/{meeting_id}/remove-request', 'removeMeetingRequest');
});

Route::controller(MeetingEventController::class)->group(function () {
    Route::get('/meeting-events', 'index');
    Route::get('/meeting-events-show/{id}', 'show');
    Route::get('/meeting-events/request/list', 'requestList');
    Route::post('/meeting-events/request/create', 'createEventRequest')->middleware('auth:api');
    Route::get('/meeting-events/request/my', 'myRequests')->middleware('auth:api');
    Route::match(['get', 'post'], '/meeting-events/{event_id}/accept', 'acceptEvent');
    Route::match(['get', 'post'], '/meeting-events/{event_id}/reject', 'rejectEvent');
    Route::match(['get', 'post'], '/meeting-events/{event_id}/cancel', 'cancelEvent');
    Route::match(['get', 'post'], '/meeting-events/{event_id}/remove-request', 'removeEventRequest');
});

Route::controller(MeetingEventController::class)->middleware('auth:api')->group(function () {
    Route::post('/meeting-events/create', 'store');
    Route::post('/meeting-events/update/{id}', 'update');
    Route::get('/meeting-events/delete/{id}', 'destroy');
});

Route::controller(BookingController::class)->group(function () {
    Route::get('/meeting-bookings', 'index');
    Route::get('/meeting-bookings-show/{id}', 'show');
    Route::post('/meeting-bookings/create', 'store');
    Route::post('/meeting-bookings/update/{id}', 'update');
    Route::get('/meeting-bookings/delete/{id}', 'destroy');
    Route::get('/meeting-bookings/request/list', 'requestList');
    Route::get('/meeting-bookings/request/details/{id}', 'showRequestDetails')->middleware('auth:api');
    Route::post('/meeting-bookings/request/create', 'createBookingRequest')->middleware('auth:api');
    Route::get('/meeting-bookings/request/my', 'myRequests')->middleware('auth:api');
    Route::match(['get', 'post'], '/meeting-bookings/{booking_id}/accept', 'acceptBooking');
    Route::match(['get', 'post'], '/meeting-bookings/{booking_id}/reject', 'rejectBooking');
    Route::match(['get', 'post'], '/meeting-bookings/{booking_id}/cancel', 'cancelBooking');
    Route::match(['get', 'post'], '/meeting-bookings/{booking_id}/remove-request', 'removeRequest');
    Route::get('/meeting-bookings/cancel/{id}', 'cancelBooking');
});

Route::controller(CalendarController::class)->group(function () {
    Route::get('/calendar/overview', 'index');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/meeting/requests', [MeetingController::class, 'getmeetingRequest']);
    Route::get('/meeting-events/requests', [MeetingEventController::class, 'getEventRequests']);
    Route::get('/bookings/request/admin', [BookingController::class, 'adminRequests']);
});

Route::controller(NotificationController::class)->group(function () {
    Route::get('/notifications', 'getNotifications');
    Route::get('/notifications/unread', 'unread');
    Route::post('/notifications/create', 'create');
    Route::post('/notifications/read', 'markRead');
    Route::post('/notifications/delete', 'delete');
});

Route::controller(UserController::class)->group(function () {
    Route::get('/company-users/list', 'index');
    Route::post('/create-company-user', 'create');
});

// WebApp Is End

// =================================================
// Mobile App Routes
// =================================================


// Auth APIS Start
Route::controller(AuthController::class)->group(function () {
    Route::post('/user-login', 'login');
    Route::post('/user-logout', 'logout')->middleware('auth:api');
    Route::post('/send-otp', 'sendOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset-password', 'resetPassword');
    Route::post('/store-user-fcm-token', 'storeFcmToken')->middleware('auth:api');
    Route::post('/delete-user-fcm-token', 'deleteFcmToken')->middleware('auth:api');
    Route::post('/update-user', 'updateUser')->middleware('auth:api');
    Route::post('/delete-account', 'deleteSelfAccount')->middleware('auth:api');
    Route::post('/user/profile/reset-password', 'userResetPassword')->middleware('auth:api');
    Route::post('/create-account', 'createAccount');
});
// Auth APIS End

// Profile API
Route::controller(ProfileController::class)->middleware('auth:api')->group(function () {
    Route::get('/mobile/profile-info', 'getUserProfile');
    Route::post('/mobile/profile-update', 'updateProfile');
    Route::post('/mobile/profile-change-password', 'changePassword');
});
// Profile API End

// Account Settings APIS
Route::middleware('auth:api')->prefix('mobile/account')->group(function () {
    // General Data (Company Info)
    Route::get('/company-info', [\App\Http\Controllers\Api\CompanyController::class, 'getCompany']);
    Route::post('/company-update', [\App\Http\Controllers\Api\CompanyController::class, 'updateCompanyGeneralData']);

    // Collaborators
    Route::get('/collaborators', [\App\Http\Controllers\Api\CollaboratorController::class, 'index']);
    Route::get('/collaborator/{id}', [\App\Http\Controllers\Api\CollaboratorController::class, 'collaboratorInfo']);
    Route::post('/collaborator-add', [\App\Http\Controllers\Api\CollaboratorController::class, 'store']);
    Route::post('/collaborator-update/{id}', [\App\Http\Controllers\Api\CollaboratorController::class, 'update']);
    Route::post('/collaborator-delete', [\App\Http\Controllers\Api\CollaboratorController::class, 'destroy']);

    // Contracts
    Route::get('/contracts', [\App\Http\Controllers\Api\ContractController::class, 'index']);
    Route::get('/contract/{id}', [\App\Http\Controllers\Api\ContractController::class, 'details']);
    Route::post('/contract-update/{id}', [\App\Http\Controllers\Api\ContractController::class, 'update']);
    Route::post('/contract-file-add', [\App\Http\Controllers\Api\ContractController::class, 'storeFile']);
    // If you have a destroy method for contract files, add it here:
    // Route::post('/contract-file-remove', [\App\Http\Controllers\Api\ContractController::class, 'destroy']);

    // Access Cards
    Route::get('/access-cards/{id}', [\App\Http\Controllers\Api\AccessCardController::class, 'getCardStats']);
    Route::post('/access-cards-update/{id}', [\App\Http\Controllers\Api\AccessCardController::class, 'updateAccessCode']);
});
// Account Settings APIS End

// End of Mobile App Routes
