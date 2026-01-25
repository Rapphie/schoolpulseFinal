<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\ProfileController;
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
    Route::get('/profile', [ProfileController::class, 'profile'])->name('profile');
    Route::put('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('enrollees', [AdminReportController::class, 'enrollees'])->name('enrollees');
        Route::get('enrollees/detail/{type}', [AdminReportController::class, 'enrolleesDetail'])
            ->name('enrollees.detail')
            ->where('type', 'students|sections|average|largest');
        Route::get('attendance', [AdminReportController::class, 'attendance'])->name('attendance');
        Route::get('attendance/detail/{type}', [AdminReportController::class, 'attendanceDetail'])
            ->name('attendance.detail')
            ->where('type', 'records|present|absent|late');
        Route::get('grades', [AdminReportController::class, 'grades'])->name('grades');
        Route::get('grades/detail/{type}', [AdminReportController::class, 'gradesDetail'])
            ->name('grades.detail')
            ->where('type', 'records|passing|highest|average');
        Route::get('least-learned', [AdminReportController::class, 'leastLearned'])->name('least-learned');
        Route::get('cumulative', [AdminReportController::class, 'cumulative'])->name('cumulative');

        // Export Reports
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('enrollees/export', [AdminReportController::class, 'exportEnrollees'])->name('enrollees');
            Route::get('attendance', [AdminReportController::class, 'exportAttendance'])->name('attendance');
            Route::get('grades', [AdminReportController::class, 'exportGrades'])->name('grades');
            Route::get('cumulative', [AdminReportController::class, 'exportCumulative'])->name('cumulative');
        });
    });

    // Analytics accessible to admin and teacher
    Route::get('/analytics/absenteeism', [\App\Http\Controllers\Teacher\AnalyticsController::class, 'absenteeismAnalytics'])
        ->middleware('role:teacher|admin')
        ->name('analytics.absenteeism');

    Route::get('/analytics/classes-by-grade', [\App\Http\Controllers\Teacher\AnalyticsController::class, 'classesByGrade'])
        ->middleware('role:teacher|admin')
        ->name('analytics.classes-by-grade');
});
