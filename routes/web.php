<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmailController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// use App\Http\Controllers\DevAttendanceController; // Commented out - controller doesn't exist

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// Authentication Routes (Public)
Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('login', [LoginController::class, 'authenticate'])->name('authenticate');
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout-account');

// Email Routes
Route::get('/teacher/welcome-email', [EmailController::class, 'sendTeacherWelcomeEmail'])->name('teacher.send.welcome');

// Main Dashboard Route & Password Change (Authenticated)
Route::middleware(['auth', 'password.force-change'])->group(function () {

    // Password Change Routes
    Route::get('/change-password', [ChangePasswordController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');

    // Main Dashboard Route - Redirects to appropriate role dashboard
    Route::get('/', function () {
        if (Auth::check()) {
            if (Auth::user()->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            } elseif (Auth::user()->hasRole('teacher')) {
                return redirect()->route('teacher.dashboard');
            } else {
                return redirect()->route('guardian.dashboard');
            }
        }

        return redirect()->route('login');
    })->name('dashboard');

});

/*
|--------------------------------------------------------------------------
| Include Role-Specific Route Files
|--------------------------------------------------------------------------
|
| The following files contain routes organized by user roles for better
| maintainability and readability.
|
*/

// Load role-specific routes
require __DIR__.'/admin.php';      // Admin routes
require __DIR__.'/teacher.php';    // Teacher routes
require __DIR__.'/guardian.php';   // Guardian routes
require __DIR__.'/shared.php';     // Shared routes (profile, settings, reports)

// Fallback route for unmatched URLs -> returns 404 view
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
