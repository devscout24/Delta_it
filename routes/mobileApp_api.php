<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\CompanyController;
use App\Http\Controllers\Api\Mobile\CollaboratorController;
use App\Http\Controllers\Api\Mobile\ContractController;
use App\Http\Controllers\Api\Mobile\AccessCardController;
use App\Http\Controllers\Api\Mobile\DocumentController;
use App\Http\Controllers\Api\Mobile\TicketController;
use App\Http\Controllers\Api\Mobile\NotificationController;
use App\Http\Controllers\Api\Mobile\MeetingController;
use App\Http\Controllers\Api\Mobile\SpaceController;

// =================================================
// Mobile App Routes
// =================================================

Route::middleware('auth:api')->controller(CompanyController::class)->prefix('mobile/company')->group(function () {
    Route::get('/info', 'info');
    Route::post('/update', 'update');
});

Route::middleware('auth:api')->controller(CollaboratorController::class)->prefix('mobile/collaborators')->group(function () {
    Route::get('/', 'index');
    Route::post('/store', 'store');
    Route::post('/update/{id}', 'update');
    Route::delete('/delete/{id}', 'destroy');
});

Route::middleware('auth:api')->controller(ContractController::class)->prefix('mobile/contracts')->group(function () {
    Route::get('/', 'index');
});

Route::middleware('auth:api')->controller(AccessCardController::class)->prefix('mobile/access-cards')->group(function () {
    Route::get('/', 'index');
});

Route::middleware('auth:api')->controller(DocumentController::class)->prefix('mobile/documents')->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
});

Route::middleware('auth:api')->controller(TicketController::class)->prefix('mobile/tickets')->group(function () {
    Route::get('/', 'mobileIndex');
    Route::post('/', 'mobileStore');
    Route::get('/{id}', 'mobileShow');
    Route::post('/{id}/messages', 'mobileSendMessage');
});

Route::middleware('auth:api')
    ->controller(NotificationController::class)
    ->prefix('mobile/notifications')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/mark-all-read', 'markAllRead');
        Route::post('/mark-all-unread', 'markAllUnread');
        Route::post('/delete-all', 'deleteAll');
        Route::post('/delete', 'deleteNotification');
        Route::post('/mark-read', 'markNotificationRead');
        Route::post('/mark-unread', 'markNotificationUnread');
    });

Route::middleware('auth:api')->controller(MeetingController::class)->prefix('mobile/meetings')->group(function () {
    // ======================
    // EVENTS
    // ======================
    Route::get('/events', 'events');                    // list events
    Route::get('/events/{id}', 'eventDetails');         // details
    // ======================
    // BOOKING FLOW
    // ======================
    Route::get('/events/{id}/dates', 'availableDates'); // available dates
    Route::get('/events/{id}/slots', 'slots');          // slots by date
    Route::post('/bookings', 'book');                   // request booking
    // ======================
    // USER DATA
    // ======================
    Route::get('/my-meetings', 'myMeetings');           // virtual meetings
    Route::get('/my-bookings', 'myBookings');           // physical bookings
});

Route::middleware('auth:api')
    ->controller(SpaceController::class)
    ->prefix('mobile/spaces')
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'details');
        Route::get('/{id}/slots', 'slots');
        Route::post('/book', 'book');
        Route::get('/my-bookings', 'myBookings');
    });
