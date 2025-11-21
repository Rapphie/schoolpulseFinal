<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\ReportCardOutputController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared Routes
|--------------------------------------------------------------------------
|
| Here are routes that can be accessed by multiple user roles.
| These routes are protected by authentication but not role-specific.
|
*/

Route::middleware(['auth', 'password.force-change'])->group(function () {

    // Profile Management (accessible by all authenticated users)
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::put('/profile/update', [AdminController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AdminController::class, 'updatePassword'])->name('profile.password');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [AdminController::class, 'settings'])->name('index');
        Route::post('/general', [AdminController::class, 'updateGeneralSettings'])->name('general.update');
        Route::post('/email', [AdminController::class, 'updateEmailSettings'])->name('email.update');
        Route::post('/system', [AdminController::class, 'updateSystemSettings'])->name('system.update');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('enrollees', [AdminReportController::class, 'enrollees'])->name('enrollees');
        Route::get('attendance', [AdminReportController::class, 'attendance'])->name('attendance');
        Route::get('grades', [AdminReportController::class, 'grades'])->name('grades');
        Route::get('least-learned', [AdminReportController::class, 'leastLearned'])->name('least-learned');
        Route::get('cumulative', [AdminReportController::class, 'cumulative'])->name('cumulative');

        // Export Reports
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('enrollees/export', [AdminReportController::class, 'exportEnrollees'])->name('enrollees');
            Route::get('attendance', [AdminReportController::class, 'exportAttendance'])->name('attendance');
            Route::get('grades', [AdminReportController::class, 'exportGrades'])->name('grades');
        });
    });

    // Analytics accessible to admin and teacher
    Route::get('/analytics/absenteeism', [\App\Http\Controllers\Teacher\AnalyticsController::class, 'absenteeismAnalytics'])
        ->middleware('role:teacher|admin')
        ->name('analytics.absenteeism');


});
