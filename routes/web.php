<?php

use App\Http\Controllers\ClassRecordController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\GradeLevelController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\EnrollmentController;
use App\Http\Controllers\Teacher\AssessmentController;
use App\Http\Controllers\Teacher\ScheduleController as TeacherScheduleController;
use App\Http\Controllers\TeacherSectionsController;
use App\Http\Controllers\Guardian\GuardianController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\ReportCardController;
use App\Imports\ClassRecordImport;

// Authentication Routes
Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('login', [LoginController::class, 'authenticate'])->name('authenticate');
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

// Admin password reset
Route::post('/admin/users/{user}/reset-password', [AdminController::class, 'resetUserPassword'])->name('admin.users.reset-password');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/teacher/welcome-email', [EmailController::class, 'sendTeacherWelcomeEmail'])->name('teacher.send.welcome');


// Authenticated Routes


Route::middleware(['auth', 'password.force-change'])->group(function () {
    Route::get('/change-password', [ChangePasswordController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
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
        return redirect()->route('login'); // Or a default landing page
    })->name('dashboard');

    // Admin Routes
    Route::group(['middleware' => 'role:admin'], function () {

        Route::prefix('admin')->name('admin.')->group(function () {

            // Dashboard
            Route::get('/dashboard/chart-data/', [AdminDashboardController::class, 'getChartData'])->name('chart-data');
            Route::get('/attendance/records', [AdminDashboardController::class, 'attendanceReport'])->name('records');
            Route::post('/school-year/store', [AdminDashboardController::class, 'storeSchoolYear'])->name('school-year.store');
            Route::put('/school-year/{id}', [AdminDashboardController::class, 'updateSchoolYear'])->name('school-year.update');
            Route::delete('/school-year/{id}', [AdminDashboardController::class, 'deleteSchoolYear'])->name('school-year.delete');

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

            Route::get('analytics', [AdminController::class, 'dashboard'])->name('analytics.show');

            // Sections
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
                // Route::post('/{section}/schedule', [SectionController::class, 'storeSchedule'])->name('schedule.store');
            });
            // Teachers
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

            // Schedules
            Route::resource('schedules', ScheduleController::class);
            Route::resource('grade-levels', GradeLevelController::class);
        });
    });


    // Teacher routes
    Route::group(['middleware' => 'role:teacher'], function () {

        Route::prefix('teacher')->name('teacher.')->group(function () {
            Route::get('/enrollment/export-all', [EnrollmentController::class, 'exportAll'])
                ->name('enrollment.exportAll');
            Route::post('enrollment/store-past-student', [App\Http\Controllers\Teacher\EnrollmentController::class, 'storePastStudent'])->name('enrollment.storePastStudent');
            Route::prefix('classes/assessments')->name('assessments.')->group(function () {
                Route::get('/list', [AssessmentController::class, 'list'])->name('list');
                Route::get('/{class}', [AssessmentController::class, 'index'])->name('index');
                // Form to create a new assessment
                Route::get('/create/{class}', [AssessmentController::class, 'create'])->name('create');

                // Store the new assessment
                Route::post('/create/{class}/store', [AssessmentController::class, 'store'])->name('store');

                // Page to input/edit scores for all students for a specific assessment
                Route::get('/{assessment}/scores', [AssessmentController::class, 'editScores'])->name('scores.edit');

                // Save the scores
                Route::post('/{assessment}/scores', [AssessmentController::class, 'updateScores'])->name('scores.update');

                // Delete an assessment
                Route::delete('/{assessment}', [AssessmentController::class, 'destroy'])->name('destroy');
            });

            Route::post('/classes/{class}/enroll', [EnrollmentController::class, 'store'])->name('enrollment.store');
            Route::get('/enrollment', [EnrollmentController::class, 'index'])->name('enrollment.index');
            Route::get('enrollment/export', [EnrollmentController::class, 'export'])->name('enrollment.export');
            Route::get('enrollment/class/{class}', [EnrollmentController::class, 'getEnrollmentsByClass'])->name('enrollment.class');
            // School-Wide Enrollment Routes for Teachers
            Route::post('/enrollment', [EnrollmentController::class, 'store'])->name('enrollment.store');

            Route::get('/classes/{section?}', [TeacherDashboardController::class, 'classes'])->name('classes');
            Route::get('/classes/view/{class}', [TeacherDashboardController::class, 'viewClass'])->name('classes.view');
            Route::get('/sections/{section}/students', [TeacherDashboardController::class, 'getStudentsForSection'])->name('sections.students');
            Route::get('/schedules', [TeacherDashboardController::class, 'loggedTeacherSchedules'])->name('schedules.index');
            Route::get('/students', [TeacherDashboardController::class, 'students'])->name('students');
            Route::get('/grades', [TeacherDashboardController::class, 'grades'])->name('grades');
            Route::get('/sections-by-grade-level', [TeacherSectionsController::class, 'getSectionsByGradeLevel'])->name('sections.by-grade-level');
            Route::get('/sections/{section}/subjects', [TeacherSectionsController::class, 'getSubjectsBySection'])->name('subjects.by-section');
            // Route::get('/sections/{sectionId}/manage', [TeacherSectionsController::class, 'manageSection'])->name('sections.manage');
            Route::post('/upload-class-record', [ClassRecordController::class, 'upload'])->name('class-record.upload');
            Route::post('/save-class-record', [ClassRecordController::class, 'saveClassRecord'])->name('class-record.save');
            Route::post('/upload-report-card', [ReportCardController::class, 'upload'])->name('report-card.upload');

            Route::get('/sections/{section}/grades', [ReportCardController::class, 'getGradesForSection'])->name('sections.grades');
            Route::get('/report-card/{student}', [ReportCardController::class, 'showReportCard'])
                ->name('report-card.show');


            // CORRECTED: Removed the redundant ->name('teacher.least-learned.')
            Route::prefix('least-learned')->name('least-learned.')->group(function () {
                Route::get('/', [TeacherDashboardController::class, 'leastLearnedCompetencies'])->name('index');
                Route::get('/subjects', [TeacherDashboardController::class, 'leastLearnedSubjects'])->name('subjects');
            });

            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::get('/take', [TeacherDashboardController::class, 'takeAttendance'])->name('take');
                Route::get('/records', [TeacherDashboardController::class, 'attendanceRecords'])->name('records');
                Route::post('/scan', [TeacherDashboardController::class, 'scanAttendance'])->name('scan');
                Route::get('/get-students', [TeacherDashboardController::class, 'getStudents'])->name('get-students');
                Route::post('/save', [TeacherDashboardController::class, 'saveAttendance'])->name('save');
                Route::put('/{id}/delete', [TeacherDashboardController::class, 'updateAttendance'])->name('update');
                Route::delete('/{id}/delete', [TeacherDashboardController::class, 'destroyAttendance'])->name('delete');
                Route::delete('/', [TeacherDashboardController::class, 'getAttendanceSummary'])->name('summary');
                Route::get('/pattern', [\App\Http\Controllers\Teacher\AttendanceController::class, 'attendancePattern'])->name('pattern');
                Route::get('/export', [\App\Http\Controllers\Teacher\AttendanceController::class, 'exportAttendancePattern'])->name('pattern.export');
            });

            Route::get('/report-cards', [ReportCardController::class, 'index'])->name('report-cards');
        });
    });


    // Guardian routes
    Route::group(['middleware' => 'role:guardian'], function () {
        Route::prefix('guardian')->name('guardian.')->group(function () {
            Route::get('/dashboard', [GuardianController::class, 'viewStudentGrades'])->name('dashboard');
            // Route::get('/profile', [GuardianProfileController::class, 'index'])->name('profile');
            // Route::put('/profile/update', [GuardianProfileController::class, 'update'])->name('profile.update');
            // Route::put('/profile/password', [GuardianProfileController::class, 'updatePassword'])->name('profile.password');
        });
    });

    // Profile
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

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('enrollees', [ReportController::class, 'enrollees'])->name('enrollees');
        Route::get('attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('grades', [ReportController::class, 'grades'])->name('grades');
        Route::get('least-learned', [ReportController::class, 'leastLearned'])->name('least-learned');
        Route::get('cumulative', [ReportController::class, 'cumulative'])->name('cumulative');

        // Export Reports
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('enrollees/export', [ReportController::class, 'exportEnrollees'])->name('enrollees');
            Route::get('attendance', [ReportController::class, 'exportAttendance'])->name('attendance');
            Route::get('grades', [ReportController::class, 'exportGrades'])->name('grades');
        });
    });
});
