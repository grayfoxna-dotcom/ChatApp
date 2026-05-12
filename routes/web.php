<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth Routes
    Route::get('login', [\App\Http\Controllers\Admin\AuthController::class, 'loginShow'])->name('login');
    Route::post('login', [\App\Http\Controllers\Admin\AuthController::class, 'login']);
    Route::get('register', [\App\Http\Controllers\Admin\AuthController::class, 'registerShow'])->name('register');
    Route::post('register', [\App\Http\Controllers\Admin\AuthController::class, 'register']);
    Route::post('register/send-otp', [\App\Http\Controllers\Admin\AuthController::class, 'registerResendOtp'])->name('register.send_otp');
    Route::post('logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('logout');

    // Password Reset (Forgot Password - Before Login)
    Route::get('forgot-password', [\App\Http\Controllers\Admin\AuthController::class, 'forgotPasswordShow'])->name('password.forgot');
    Route::post('forgot-password/send-otp', [\App\Http\Controllers\Admin\AuthController::class, 'forgotPasswordSendOtp'])->name('password.forgot.send');
    Route::post('forgot-password/reset', [\App\Http\Controllers\Admin\AuthController::class, 'forgotPasswordReset'])->name('password.forgot.reset');

    // Protected Routes
    Route::middleware(['admin'])->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        // Quản lý Người dùng
        Route::middleware(['permission:users.view'])->group(function() {
            Route::get('users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        });
        Route::middleware(['permission:users.create'])->group(function() {
            Route::get('users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
            Route::post('users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
        });
        Route::middleware(['permission:users.edit'])->group(function() {
            Route::get('users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
            Route::post('users/{user}/update-active', [\App\Http\Controllers\Admin\UserController::class, 'updateActive'])->name('users.update_active');
        });
        Route::middleware(['permission:users.delete'])->group(function() {
            Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
        });
        
        // Quản lý Admin
        Route::middleware(['permission:admins.view'])->group(function() {
            Route::get('admins', [\App\Http\Controllers\Admin\AdminController::class, 'index'])->name('admins.index');
        });
        Route::middleware(['permission:admins.create'])->group(function() {
            Route::get('admins/create', [\App\Http\Controllers\Admin\AdminController::class, 'create'])->name('admins.create');
            Route::post('admins', [\App\Http\Controllers\Admin\AdminController::class, 'store'])->name('admins.store');
        });
        Route::middleware(['permission:admins.edit'])->group(function() {
            Route::get('admins/{admin}/edit', [\App\Http\Controllers\Admin\AdminController::class, 'edit'])->name('admins.edit');
            Route::put('admins/{admin}', [\App\Http\Controllers\Admin\AdminController::class, 'update'])->name('admins.update');
            Route::post('admins/{admin}/update-status', [\App\Http\Controllers\Admin\AdminController::class, 'updateStatus'])->name('admins.update_status');
        });
        Route::middleware(['permission:admins.delete'])->group(function() {
            Route::delete('admins/{admin}', [\App\Http\Controllers\Admin\AdminController::class, 'destroy'])->name('admins.destroy');
        });

        // Quản lý Vai trò & Quyền hạn
        Route::middleware(['permission:roles.view'])->group(function() {
            Route::get('roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
        });
        Route::middleware(['permission:roles.create'])->group(function() {
            Route::get('roles/create', [\App\Http\Controllers\Admin\RoleController::class, 'create'])->name('roles.create');
            Route::post('roles', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
        });
        Route::middleware(['permission:roles.edit'])->group(function() {
            Route::get('roles/{role}/edit', [\App\Http\Controllers\Admin\RoleController::class, 'edit'])->name('roles.edit');
            Route::put('roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');
        });
        Route::middleware(['permission:roles.delete'])->group(function() {
            Route::delete('roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');
        });
        
        // Profile cá nhân 
        Route::get('profile', [\App\Http\Controllers\Admin\AdminController::class, 'profile'])->name('profile');
        Route::post('profile', [\App\Http\Controllers\Admin\AdminController::class, 'profileUpdate'])->name('profile.update');

        // Change Password (After Login)
        Route::get('change-password', [\App\Http\Controllers\Admin\AuthController::class, 'changePasswordShow'])->name('password.change');
        Route::post('change-password/send-otp', [\App\Http\Controllers\Admin\AuthController::class, 'changePasswordSendOtp'])->name('password.change.send');
        Route::post('change-password', [\App\Http\Controllers\Admin\AuthController::class, 'changePasswordUpdate'])->name('password.update');
    });
});
