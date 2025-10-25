<?php

use App\Http\Controllers\Account\AccountController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\BackendController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Settings\SystemController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Dynamic\DynamicPageController;
use App\Http\Controllers\RolePermission\RoleController;
use App\Http\Controllers\RolePermission\UserController;
use App\Http\Controllers\Settings\MailSettingController;
use App\Http\Controllers\Settings\AdminSettingsController;
use App\Http\Controllers\Settings\ProfileSettingController;
use App\Http\Controllers\RolePermission\PermissionController;
use App\Http\Controllers\RolePermission\RolePermissionController;

// Route::get('/', [BackendController::class, 'Page404'])->name('dashboard');
Route::controller(BackendController::class)->middleware('auth.check')->group(function () {
    Route::get('/', 'index')->name('dashboard');
    Route::get('/dashboard-data', 'monthlyData');
});




// Role and Permission Management start
Route::controller(RoleController::class)->middleware('auth.check')->group(function () {
    Route::get('/role/index', 'index')->name('role.index');
    Route::post('/role/store', 'store')->name('role.store');
    Route::get('/role/destroy/{id}', 'destroy')->name('role.destroy');
    Route::get('/role/edit/{id}', 'edit')->name('role.edit');
    Route::put('/role/update/{id}', 'update')->name('role.update');
    Route::get('/permission/edit/{id}', 'editPermission')->name('permission.edit');
});

Route::controller(RolePermissionController::class)->middleware('auth.check')->group(function () {});

Route::controller(UserController::class)->middleware('auth.check')->group(function () {
    Route::get('/user/index', 'index')->name('user.index');
    Route::post('/user/store', 'store')->name('user.store');
    Route::get('/user/edit/{id}', 'edit')->name('user.edit');
    Route::put('/user/update/{id}', 'update')->name('user.update');
    Route::get('/user/destroy/{id}', 'destroy')->name('user.destroy');
    Route::get('/user/show/{id}', 'show')->name('user.show');
    Route::get('/user/role/change/{id}', 'ChangeRole')->name('user.role.change');
    Route::post('/user/role/Update/{id}', 'assignRoleUpate')->name('user.role.update');
});

Route::controller(PermissionController::class)->middleware('auth.check')->group(function () {
    Route::get('/permission/index', 'index')->name('permission.index');
    Route::post('/permission/store', 'store')->name('permission.store');
    // Route::get('/permission/destroy/{id}', 'destroy')->name('permission.destroy');
    Route::post('role/permission/update/{id}', 'UpdatePermissionByRole')->name('role.permission.update');
});


// settings Management start
Route::controller(MailSettingController::class)->middleware('auth.check')->group(function () {
    Route::get('/settings/mail', 'index')->name('mail.index');
    Route::post('/settings/mail-store', 'mailstore')->name('mail.store');
});

Route::controller(ProfileSettingController::class)->middleware('auth.check')->group(function () {
    Route::get('/settings/profile', 'index')->name('profile.index');
    Route::post('/settings/profile-update', 'profileupdate')->name('profile.update');
    Route::post('/settings/profile-password-update', 'PasswordUpdate')->name('profile.password.update');
});

Route::controller(SystemController::class)->middleware('auth.check')->group(function () {
    Route::get('/settings/system', 'index')->name('system.index');
    Route::post('/settings/system-store', 'systemupdate')->name('system.update');
    Route::post('/settings/social-store', 'updateSocials')->name('social.update');
});

Route::controller(AdminSettingsController::class)->middleware('auth.check')->group(function () {
    Route::get('/settings/admin', 'index')->name('admin.setting.index');
    Route::post('/settings/admin/update', 'adminSettingUpdate')->name('admin.setting.update');
});



