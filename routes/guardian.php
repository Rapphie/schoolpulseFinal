<?php

use App\Http\Controllers\Guardian\GuardianController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guardian Routes
|--------------------------------------------------------------------------
|
| Here are all routes for guardian users. These routes are protected by
| the 'role:guardian' middleware and are prefixed with 'guardian'.
|
*/

Route::group(['middleware' => ['auth', 'password.force-change', 'role:guardian']], function () {
    Route::prefix('guardian')->name('guardian.')->group(function () {
        Route::get('/dashboard', [GuardianController::class, 'dashboard'])->name('dashboard');
        Route::get('/grades', [GuardianController::class, 'grades'])->name('grades');
        Route::get('/attendance', [GuardianController::class, 'attendance'])->name('attendance');
    });
});
