<?php

use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\ClassRecordController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
// ...existing code...
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Teacher\EnrollmentController;
use App\Http\Controllers\Teacher\ScheduleController as TeacherScheduleController;
use App\Http\Controllers\TeacherSectionsController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Imports\ClassRecordImport;
use Laravel\Prompts\Clear;

// testing routes
Route::get('/student/qr-code', function () {
    return view('student.qr-code');
})->name('student.qr-code');
Route::get('/least-learn-competency', function () {
    return view('llc');
})->name('llc');
// Route::get('/test', function () {
//     return view('welcome');
// });
Route::get('/test', [AdminController::class, 'test']);


Route::get('/base', function () {
    return view('welcome');
})->name('base');
Route::get('/send-email', function () {
    return view('send-email');
});

Route::post('/resend-email', [EmailController::class, 'send'])->name('resend.email');



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

    // Dashboard
    Route::get('/', function () {
        if (Auth::check()) {
            if (Auth::user()->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            } elseif (Auth::user()->hasRole('teacher')) {
                return redirect()->route('teacher.dashboard');
            }
        }
        return redirect()->route('login'); // Or a default landing page
    })->name('dashboard');

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


    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/dashboard/chart-data', [AdminController::class, 'getChartData'])->name('chart-data');
        Route::get('/attendance/records', [AdminController::class, 'attendanceReport'])->name('records');
        // Email

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
            Route::delete('/{section}', [SectionController::class, 'destroy'])->name('destroy');

            // Section Students
            Route::post('/sections/students/{section}', [SectionController::class, 'addStudent'])->name('students.store');
            Route::delete('/{section}/students/{student}', [SectionController::class, 'removeStudent'])->name('students.destroy');

            // Section Subjects
            Route::post('/{section}/subjects', [SectionController::class, 'addSubject'])->name('subjects.store');
            Route::post('/subjects/create', [SectionController::class, 'create'])->name('subjects.create');
            Route::delete('/{section}/subjects/{subject}', [SectionController::class, 'removeSubject'])->name('subjects.destroy');

            // Section Schedule
            Route::get('/{section}/schedule', [SectionController::class, 'schedule'])->name('schedule');
            Route::post('/{section}/schedule', [SectionController::class, 'storeSchedule'])->name('schedule.store');
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
    });


    // Teacher Routes

    // Dashboard
    Route::prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        Route::get('/classes', [TeacherDashboardController::class, 'classes'])->name('classes');
        Route::get('/sections/{section}/students', [TeacherDashboardController::class, 'getStudentsForSection'])->name('sections.students');
        Route::get('/schedules', [TeacherDashboardController::class, 'loggedTeacherSchedules'])->name('schedules.index');
        Route::get('/students', [TeacherDashboardController::class, 'students'])->name('students');
        Route::get('/grades', [TeacherDashboardController::class, 'grades'])->name('grades');
        Route::get('/sections-by-grade-level', [TeacherSectionsController::class, 'getSectionsByGradeLevel'])->name('sections.by-grade-level');
        Route::get('/sections/{section}/subjects', [TeacherSectionsController::class, 'getSubjectsBySection'])->name('subjects.by-section');
        Route::get('/enrollment', [EnrollmentController::class, 'index'])->name('enrollment.index');
        Route::post('/upload-class-record', [ClassRecordController::class, 'upload'])->name('class-record.upload');
        Route::post('/save-class-record', [ClassRecordController::class, 'saveClassRecord'])->name('class-record.save');
    });


    // Gradebook
    Route::prefix('gradebook')->name('teacher.gradebook.')->group(function () {
        Route::get('/quiz', [TeacherDashboardController::class, 'gradebookQuiz'])->name('quiz');
        Route::get('/exam', [TeacherDashboardController::class, 'gradebookExam'])->name('exam');
    });

    // Least Learned
    Route::prefix('least-learned')->name('teacher.least-learned.')->group(function () {
        Route::get('/subjects', [TeacherDashboardController::class, 'leastLearnedSubjects'])->name('subjects');
    });

    // Attendance
    Route::prefix('attendance')->name('teacher.attendance.')->group(function () {
        Route::get('/take', [TeacherDashboardController::class, 'takeAttendance'])->name('take');
        Route::get('/records', [TeacherDashboardController::class, 'attendanceRecords'])->name('records');
        Route::post('/scan', [TeacherDashboardController::class, 'scanAttendance'])->name('scan');
        Route::get('/get-students', [TeacherDashboardController::class, 'getStudents'])->name('get-students');
        Route::post('/save', [TeacherDashboardController::class, 'saveAttendance'])->name('save');
        Route::delete('/{id}/delete', [TeacherDashboardController::class, 'deleteAttendanceRecord'])->name('delete');
    });




    // Reports
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
