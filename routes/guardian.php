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

        // Dashboard
        Route::get('/dashboard', [GuardianController::class, 'viewStudentGrades'])->name('dashboard');

        // Future guardian routes can be added here
        // Route::get('/profile', [GuardianProfileController::class, 'index'])->name('profile');
        // Route::put('/profile/update', [GuardianProfileController::class, 'update'])->name('profile.update');
        // Route::put('/profile/password', [GuardianProfileController::class, 'updatePassword'])->name('profile.password');
    });
});
