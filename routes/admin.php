<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\ClassroomSectionController;
use App\Http\Controllers\Admin\GradeLevelController;
use App\Http\Controllers\Admin\GradeLevelSubjectController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SchoolYearQuarterController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClassRecordController;
use App\Http\Controllers\Teacher\EnrollmentController as TeacherEnrollmentController;
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
        // Route::get('/attendance/records', [AdminReportController::class, 'attendance'])->name('records');
        Route::post('/school-year/store', [AdminDashboardController::class, 'storeSchoolYear'])->name('school-year.store');
        Route::put('/school-year/{id}', [AdminDashboardController::class, 'updateSchoolYear'])->name('school-year.update');
        Route::delete('/school-year/{id}', [AdminDashboardController::class, 'deleteSchoolYear'])->name('school-year.delete');
        Route::post('/school-year/{id}/toggle-promotion', [AdminDashboardController::class, 'togglePromotion'])->name('school-year.toggle-promotion');
        Route::post('/school-year/{id}/view', [AdminDashboardController::class, 'viewSchoolYear'])->name('school-year.view');
        Route::post('/school-year/view/reset', [AdminDashboardController::class, 'clearViewedSchoolYear'])->name('school-year.view.reset');
        Route::post('/school-year/{id}/set-active', [AdminDashboardController::class, 'setSchoolYearActive'])->name('school-year.set-active');

        // School Year Quarters
        Route::prefix('school-year/{schoolYear}/quarters')->name('school-year.quarters.')->group(function () {
            Route::get('/', [SchoolYearQuarterController::class, 'index'])->name('index');
            Route::post('/', [SchoolYearQuarterController::class, 'store'])->name('store');
            Route::put('/{quarter}', [SchoolYearQuarterController::class, 'update'])->name('update');
            Route::delete('/{quarter}', [SchoolYearQuarterController::class, 'destroy'])->name('destroy');
            Route::post('/{quarter}/toggle-lock', [SchoolYearQuarterController::class, 'toggleLock'])->name('toggle-lock');
            Route::post('/{quarter}/set-active', [SchoolYearQuarterController::class, 'setActive'])->name('set-active');
            Route::post('/{quarter}/unset-active', [SchoolYearQuarterController::class, 'unsetActive'])->name('unset-active');
            Route::post('/auto-generate', [SchoolYearQuarterController::class, 'autoGenerate'])->name('auto-generate');
        });

        // Class Management
        Route::prefix('enrollment')->name('enrollment.')->group(function () {
            Route::get('/', [TeacherEnrollmentController::class, 'index'])->name('index');
            Route::get('/export-all', [TeacherEnrollmentController::class, 'exportAll'])->name('exportAll');
            Route::get('/export-mine', [TeacherEnrollmentController::class, 'exportMine'])->name('exportMine');
            Route::get('/guardian-search', [TeacherEnrollmentController::class, 'searchGuardians'])->name('guardian.search');
            Route::post('/', [TeacherEnrollmentController::class, 'store'])->name('page.store');
            Route::post('/store-past-student', [TeacherEnrollmentController::class, 'storePastStudent'])->name('page.storePastStudent');
        });

        Route::post('/classes/{class}/assign-adviser', [ClassroomSectionController::class, 'assignClassAdviser'])->name('sections.adviser.assign');
        Route::delete('/classes/{class}/remove-adviser', [ClassroomSectionController::class, 'removeClassAdviser'])->name('sections.adviser.remove');
        Route::put('/classes/{class}/update-capacity', [ClassroomSectionController::class, 'updateCapacity'])->name('sections.capacity.update');
        Route::post('/classes/{class}/store-schedule', [ClassroomSectionController::class, 'storeSchedule'])->name('sections.schedule.store');
        // Use the admin ClassroomSectionController to handle enrollments created from the admin class view
        Route::get('/classes/{class}/enroll', [ClassroomSectionController::class, 'createEnrollment'])->name('enrollment.create');
        Route::post('/classes/{class}/enroll', [ClassroomSectionController::class, 'enrollStudent'])->name('enrollment.store');
        Route::post('/class-record/upload', [ClassRecordController::class, 'upload'])->name('class-record.upload');
        Route::post('/class-record/save', [ClassRecordController::class, 'saveClassRecord'])->name('class-record.save');
        Route::get('/students/{student}', [ClassroomSectionController::class, 'showStudent'])->name('students.show');
        Route::get('/students/{student}/edit', [ClassroomSectionController::class, 'editStudent'])->name('students.edit');
        Route::put('/students/{student}', [ClassroomSectionController::class, 'updateStudent'])->name('students.update');

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

        // Subjects
        Route::resource('subjects', SubjectController::class);
        Route::get('subjects/grade/{gradeLevel}', [SubjectController::class, 'getSubjectsByGradeLevel'])->name('subjects.by_grade_level');

        // Subject Assignments
        Route::post('subject-assignments', [GradeLevelSubjectController::class, 'store'])->name('subject-assignments.store');
        Route::patch('subject-assignments/{gradeLevelSubject}', [GradeLevelSubjectController::class, 'update'])->name('subject-assignments.update');
        Route::delete('subject-assignments/{gradeLevelSubject}', [GradeLevelSubjectController::class, 'destroy'])->name('subject-assignments.destroy');

        // Analytics
        Route::get('analytics', [AdminDashboardController::class, 'dashboard'])->name('analytics.show');

        // Sections Management
        Route::prefix('sections')->name('sections.')->group(function () {
            Route::get('/', [ClassroomSectionController::class, 'index'])->name('index');
            Route::get('/create', [ClassroomSectionController::class, 'create'])->name('create');
            Route::post('/', [ClassroomSectionController::class, 'store'])->name('store');
            Route::get('students/{section}', [ClassroomSectionController::class, 'show'])->name('show');
            Route::get('/{section}/edit', [ClassroomSectionController::class, 'edit'])->name('edit');
            Route::get('/{section}/data', [ClassroomSectionController::class, 'getSectionData'])->name('data');
            Route::get('/{section}/manage', [ClassroomSectionController::class, 'manageClass'])->name('manage');
            Route::put('/{section}', [ClassroomSectionController::class, 'update'])->name('update');
            Route::delete('/destroy/{class}', [ClassroomSectionController::class, 'destroyClass'])->name('destroy');

            // Section Students
            Route::post('/sections/students/{section}', [ClassroomSectionController::class, 'addStudent'])->name('students.store');
            Route::delete('/{section}/students/{student}', [ClassroomSectionController::class, 'removeStudent'])->name('students.destroy');

            // Section Subjects
            Route::post('/{section}/subjects', [ClassroomSectionController::class, 'addSubject'])->name('subjects.store');
            Route::post('/subjects/create', [ClassroomSectionController::class, 'create'])->name('subjects.create');
            Route::delete('/{section}/subjects/{subject}', [ClassroomSectionController::class, 'removeSubject'])->name('subjects.destroy');

            // Section Schedule
            Route::get('/{section}/schedule', [ClassroomSectionController::class, 'schedule'])->name('schedule');
        });

        // Teachers Management
        Route::prefix('teachers')->name('teachers.')->group(function () {
            Route::get('/', [TeacherController::class, 'index'])->name('index');
            Route::get('/create', [TeacherController::class, 'create'])->name('create');
            Route::post('/', [TeacherController::class, 'store'])->name('store');
            Route::get('/{teacher}', [TeacherController::class, 'show'])->name('show');
            Route::get('/{teacher}/edit', [TeacherController::class, 'edit'])->name('edit');
            Route::put('/{teacher}', [TeacherController::class, 'update'])->name('update');
            Route::delete('/{teacher}', [TeacherController::class, 'destroy'])->name('destroy');
            Route::post('/{teacher}/assign-subject', [TeacherController::class, 'assignSubject'])->name('assign_subject');
            Route::delete('/{teacher}/unassign-subject', [TeacherController::class, 'unassignSubject'])->name('unassign_subject');
        });

        // Schedules & Grade Levels
        Route::resource('schedules', ScheduleController::class);
        Route::resource('grade-levels', GradeLevelController::class);

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('enrollees', [AdminReportController::class, 'enrollees'])->name('enrollees');
            Route::get('enrollees/detail/{type}', [AdminReportController::class, 'enrolleesDetail'])
                ->name('enrollees.detail')
                ->where('type', 'students|sections|average|largest');
            // Route::get('attendance', [AdminReportController::class, 'attendance'])->name('attendance');
            Route::get('attendance', [AdminReportController::class, 'attendanceReport'])->name('attendance');
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
        // Note: settings routes handled by Admin\SettingController above
    });

    // Admin password reset (outside of admin prefix)
    Route::post('/admin/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('admin.users.reset-password');
});
