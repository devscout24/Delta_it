<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Web\RoomController;
use App\Http\Controllers\Api\Web\CompanyController;
use App\Http\Controllers\Api\Web\CompanyPaymentController;
use App\Http\Controllers\Api\Web\CollaboratorController;
use App\Http\Controllers\Api\Web\ContractController;
use App\Http\Controllers\Api\Web\DocumentController;
use App\Http\Controllers\Api\Web\CompanyUserController;
use App\Http\Controllers\Api\Web\RequestController;
use App\Http\Controllers\Api\Web\AccessCardController;
use App\Http\Controllers\Api\Web\CompanyNoteController;
use App\Http\Controllers\Api\Web\AdminPaymentController;
use App\Http\Controllers\Api\Web\AdminContractController;
use App\Http\Controllers\Api\Web\AdminTicketController;
use App\Http\Controllers\Api\Web\AdminDocumentController;
use App\Http\Controllers\Api\Web\RoomManagementController;
use App\Http\Controllers\Api\Web\MeetingEventController;
use App\Http\Controllers\Api\Web\UserManagementController;
use App\Http\Controllers\Api\Web\DashboardStatsController;
use App\Http\Controllers\Api\Web\CalendarController;

// =================================================
// Dashboard Stats
// =================================================

Route::middleware('auth:api')->controller(DashboardStatsController::class)->prefix('web/dashboard')->group(function () {
    Route::get('/stats', 'stats');
});

Route::controller(CalendarController::class)->prefix('web/calendar')->group(function () {
    Route::get('/overview', 'index');
});

// =================================================
// Web Map & Room Management
// =================================================

Route::middleware('auth:api')->controller(RoomController::class)->prefix('web/map/rooms')->group(function () {
    Route::get('/floors', 'floors');
    Route::get('/stats', 'stats');
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::post('/assign-company', 'assignCompany');
    Route::post('/remove-company', 'removeCompany');
    Route::get('/{id}/details', 'details');
    Route::post('/{id}/status', 'updateStatus');
});

// =================================================
// Web Companies
// =================================================

Route::middleware('auth:api')->prefix('web/companies')->controller(CompanyController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::post('/{id}/logo', 'uploadLogo');
    Route::delete('/{id}/logo', 'deleteLogo');
    Route::patch('/{id}/archive', 'archive');
    Route::patch('/{id}/restore', 'restore');
    Route::get('/list', 'list');
});

// =================================================
// Web Company Payments
// =================================================

Route::middleware('auth:api')->prefix('web/company-payments')->controller(CompanyPaymentController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/init', 'initYear');
    Route::put('/{id}', 'update');
    Route::get('/summary', 'summary');
});

// =================================================
// Web Collaborators
// =================================================

Route::middleware('auth:api')->controller(CollaboratorController::class)->prefix('web/collaborators')->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

// =================================================
// Web Contracts
// =================================================

Route::middleware('auth:api')->prefix('web/contracts')->controller(ContractController::class)->group(function () {
    Route::get('/{company_id}', 'show');
    Route::put('/{company_id}', 'update');
    Route::post('/files', 'uploadFile');
    Route::delete('/files/{id}', 'deleteFile');
});

// =================================================
// Web Documents
// =================================================

Route::middleware('auth:api')->prefix('web/documents')->controller(DocumentController::class)->group(function () {
    Route::get('/tags', 'tags');
    Route::get('/{company_id}', 'index');
    Route::post('/{company_id}', 'store');
    Route::delete('/{id}', 'destroy');
});

// =================================================
// Web Company Users
// =================================================

Route::middleware('auth:api')->prefix('web/company-users')->controller(CompanyUserController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

// =================================================
// Web Requests
// =================================================

Route::middleware('auth:api')->prefix('web/requests')->controller(RequestController::class)->group(function () {
    Route::get('/', 'index');
});

// =================================================
// Web Access Cards
// =================================================

Route::middleware('auth:api')->prefix('web/access-cards')->controller(AccessCardController::class)->group(function () {
    Route::get('/{company_id}', 'show');
    Route::put('/{company_id}', 'update');
});

// =================================================
// Web Company Notes
// =================================================

Route::middleware('auth:api')->prefix('web/company-notes')->controller(CompanyNoteController::class)->group(function () {
    Route::get('/{company_id}', 'index');
    Route::post('/', 'store');
    Route::delete('/{id}', 'destroy');
});

// =================================================
// Web Admin Payments
// =================================================

Route::middleware('auth:api')->controller(AdminPaymentController::class)->prefix('web/payments')->group(function () {
    Route::get('/', 'index');
    Route::get('/summary', 'summary');
});

// =================================================
// Web Admin Contracts
// =================================================

Route::middleware('auth:api')->prefix('web/admin/contracts')->controller(AdminContractController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
    Route::post('/files', 'uploadFile');
    Route::delete('/files/{id}', 'deleteFile');
});

// =================================================
// Web Admin Tickets
// =================================================

Route::middleware('auth:api')->prefix('web/admin/tickets')->controller(AdminTicketController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::post('/{id}/reply', 'reply');
    Route::post('/{id}/status', 'updateStatus');
    Route::get('/companies', 'companies');
    Route::get('/company-users', 'companyUsers');
    Route::get('/rooms', 'rooms');
});

// =================================================
// Web Admin Documents
// =================================================

Route::middleware('auth:api')->prefix('web/admin/documents')->controller(AdminDocumentController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});

// =================================================
// Web Room Management
// =================================================

Route::middleware('auth:api')->controller(RoomManagementController::class)->prefix('web/admin/rooms')->group(function () {
    // SPACES
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');

    // SCHEDULE
    Route::post('/{id}/schedule', 'addSchedule');
    Route::delete('/schedules/{id}', 'deleteSchedule');

    // SLOTS
    Route::get('/{id}/slots', 'getSlots');

    // CALENDAR
    Route::get('/calendar/all', 'calendar');

    // BOOKINGS (IMPORTANT)
    Route::get('/bookings', 'bookings');
    Route::post('/bookings/{id}/approve', 'approveBooking');
    Route::post('/bookings/{id}/reject', 'rejectBooking');
});

// =================================================
// Web Meeting Events
// =================================================

Route::middleware('auth:api')->controller(MeetingEventController::class)->prefix('web/meeting-events')->group(function () {
    // ======================
    // EVENTS
    // ======================
    Route::get('/', 'index');                 // list + filters
    Route::post('/', 'store');                // create
    Route::get('/{id}', 'show');              // details
    Route::put('/{id}', 'update');            // update
    Route::delete('/{id}', 'destroy');        // delete

    // ======================
    // SCHEDULING
    // ======================
    Route::post('/{id}/schedule', 'addSchedule');   // add schedule + generate slots
    Route::get('/{id}/schedules', 'schedules');     // get schedules
    Route::delete('/schedules/{id}', 'deleteSchedule'); // delete schedule

    // ======================
    // SLOTS
    // ======================
    Route::get('/{id}/slots', 'slots');       // get slots by date
    Route::post('/slots/block', 'blockSlot'); // optional admin block

    // ======================
    // CALENDAR
    // ======================
    Route::get('/calendar', 'calendar');      // day/week/month view
    Route::post('/quick-book', 'quickBook');  // manual booking

    // ======================
    // REQUESTS (FROM MOBILE)
    // ======================
    Route::get('/requests', 'requests');                  // list requests
    Route::get('/requests/{id}', 'requestDetails');       // request details
    Route::post('/requests/{id}/approve', 'approve');     // approve
    Route::post('/requests/{id}/reject', 'reject');       // reject

    // ======================
    // SUPPORTING (DROPDOWNS)
    // ======================
    Route::get('/locations', 'locations');   // rooms list
    Route::get('/types', 'types');           // virtual / physical
});

// =================================================
// Web User Management
// =================================================

Route::middleware('auth:api')->controller(UserManagementController::class)->prefix('web/users')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{id}', 'show');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});
