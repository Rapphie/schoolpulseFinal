<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\GradeLevelController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Teacher\EnrollmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here are all routes for admin users. These routes are protected by
| the 'role:admin' middleware and are prefixed with 'admin'.
|
*/

Route::group(['middleware' => ['auth', 'password.force-change', 'role:admin']], function () {
    Route::prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/chart-data/', [AdminDashboardController::class, 'getChartData'])->name('chart-data');
        Route::get('/attendance/records', [AdminDashboardController::class, 'attendanceReport'])->name('records');
        Route::post('/school-year/store', [AdminDashboardController::class, 'storeSchoolYear'])->name('school-year.store');
        Route::put('/school-year/{id}', [AdminDashboardController::class, 'updateSchoolYear'])->name('school-year.update');
        Route::delete('/school-year/{id}', [AdminDashboardController::class, 'deleteSchoolYear'])->name('school-year.delete');

        // Class Management
        Route::post('/classes/{class}/assign-adviser', [SectionController::class, 'assignAdviser'])->name('sections.adviser.assign');
        Route::post('/classes/{class}/store-schedule', [SectionController::class, 'storeSchedule'])->name('sections.schedule.store');
        Route::post('/classes/{class}/enroll', [EnrollmentController::class, 'store'])->name('enrollment.store');

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

        // Subjects
        Route::resource('subjects', SubjectController::class)->only([
            'index',
            'store',
            'edit',
            'update',
            'destroy'
        ]);
        Route::get('subjects/grade/{gradeLevel}', [SubjectController::class, 'getSubjectsByGradeLevel'])->name('subjects.by_grade_level');

        // Analytics
        Route::get('analytics', [AdminController::class, 'dashboard'])->name('analytics.show');

        // Sections Management
        Route::prefix('sections')->name('sections.')->group(function () {
            Route::get('/', [SectionController::class, 'index'])->name('index');
            Route::get('/create', [SectionController::class, 'create'])->name('create');
            Route::post('/', [SectionController::class, 'store'])->name('store');
            Route::get('students/{section}', [SectionController::class, 'show'])->name('show');
            Route::get('/{section}/edit', [SectionController::class, 'edit'])->name('edit');
            Route::get('/{section}/data', [SectionController::class, 'getSectionData'])->name('data');
            Route::get('/{section}/manage', [SectionController::class, 'manage'])->name('manage');
            Route::put('/{section}', [SectionController::class, 'update'])->name('update');
            Route::delete('/destroy/{class}', [SectionController::class, 'destroy'])->name('destroy');

            // Section Students
            Route::post('/sections/students/{section}', [SectionController::class, 'addStudent'])->name('students.store');
            Route::delete('/{section}/students/{student}', [SectionController::class, 'removeStudent'])->name('students.destroy');

            // Section Subjects
            Route::post('/{section}/subjects', [SectionController::class, 'addSubject'])->name('subjects.store');
            Route::post('/subjects/create', [SectionController::class, 'create'])->name('subjects.create');
            Route::delete('/{section}/subjects/{subject}', [SectionController::class, 'removeSubject'])->name('subjects.destroy');

            // Section Schedule
            Route::get('/{section}/schedule', [SectionController::class, 'schedule'])->name('schedule');
        });

        // Teachers Management
        Route::prefix('teachers')->name('teachers.')->group(function () {
            Route::get('/', [TeacherController::class, 'index'])->name('index');
            Route::get('/create', [TeacherController::class, 'create'])->name('create');
            Route::post('/', [TeacherController::class, 'store'])->name('store');
            Route::get('/{teacher}/edit', [TeacherController::class, 'edit'])->name('edit');
            Route::put('/{teacher}', [TeacherController::class, 'update'])->name('update');
            Route::delete('/{teacher}', [TeacherController::class, 'destroy'])->name('destroy');
            Route::post('/{teacher}/assign-subject', [TeacherController::class, 'assignSubject'])->name('assign_subject');
            Route::delete('/{teacher}/unassign-subject', [TeacherController::class, 'unassignSubject'])->name('unassign_subject');
        });

        // Schedules & Grade Levels
        Route::resource('schedules', ScheduleController::class);
        Route::resource('grade-levels', GradeLevelController::class);
    });

    // Admin password reset (outside of admin prefix)
    Route::post('/admin/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('admin.users.reset-password');
});
