<?php

use App\Http\Controllers\ProfileController;
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

    // Analytics accessible to admin and teacher
    Route::get('/analytics/absenteeism', [\App\Http\Controllers\Teacher\AnalyticsController::class, 'absenteeismAnalytics'])
        ->middleware('role:teacher|admin')
        ->name('analytics.absenteeism');

    Route::get('/analytics/classes-by-grade', [\App\Http\Controllers\Teacher\AnalyticsController::class, 'classesByGrade'])
        ->middleware('role:teacher|admin')
        ->name('analytics.classes-by-grade');
});
