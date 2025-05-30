<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Authentication Routes
Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('login', [LoginController::class, 'authenticate'])->name('authenticate');


// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', fn() => match (true) {
        Auth::check() && Auth::user()->hasRole('admin')   => view('admin.dashboard'),
        Auth::check() && Auth::user()->hasRole('teacher') => view('teacher.dashboard'),
        // Auth::user()->hasRole('parent')  => view('parent.dashboard'),
        default                            => abort(403),
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

    // Logout
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/attendance/records', [AdminController::class, 'attendanceReport'])->name('records');

        // Subjects
        Route::resource('subjects', AdminController::class)->only([
            'index',
            'store',
            'edit',
            'update',
            'destroy'
        ]);

        // Sections
        Route::prefix('sections')->name('sections.')->group(function () {
            Route::get('/', [SectionController::class, 'index'])->name('index');
            Route::get('/create', [SectionController::class, 'create'])->name('create');
            Route::post('/', [SectionController::class, 'store'])->name('store');
            Route::get('students/{section}', [SectionController::class, 'show'])->name('show');
            Route::get('/{section}/edit', [SectionController::class, 'edit'])->name('edit');
            Route::get('/{section}/data', [SectionController::class, 'getSectionData'])->name('data');
            Route::put('/{section}', [SectionController::class, 'update'])->name('update');
            Route::delete('/{section}', [SectionController::class, 'destroy'])->name('destroy');

            // Section Students
            Route::post('/{section}/students', [SectionController::class, 'addStudent'])->name('students.store');
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
            Route::get('/{teacher}', [TeacherController::class, 'show'])->name('show');
            Route::get('/{teacher}/edit', [TeacherController::class, 'edit'])->name('edit');
            Route::put('/{teacher}', [TeacherController::class, 'update'])->name('update');

            Route::delete('/delete={teacher}', [TeacherController::class, 'destroy'])->name('admin.destroy');


            // Teacher Documents
            Route::post('/{teacher}/documents', [TeacherController::class, 'uploadDocument'])->name('documents.store');
            Route::delete('/documents/{document}', [TeacherController::class, 'deleteDocument'])->name('documents.destroy');

            // Teacher Status
            Route::post('/{teacher}/status', [TeacherController::class, 'updateStatus'])->name('status.update');
        });
    });


    // Teacher Routes

    // Dashboard
    Route::prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        Route::get('/classes', [TeacherDashboardController::class, 'classes'])->name('classes');
        Route::get('/students', [TeacherDashboardController::class, 'students'])->name('students');
        Route::get('/grades', [TeacherDashboardController::class, 'grades'])->name('grades');
        Route::get('/sections-by-grade-level', [TeacherDashboardController::class, 'getSectionsByGradeLevel'])->name('sections.by-grade-level');
    });

    // Student QR Code (for testing)
    Route::get('/student/qr-code', function () {
        return view('student.qr-code');
    })->name('student.qr-code');
    Route::get('/least-learn-competency', function () {
        return view('llc');
    })->name('llc');
    Route::get('/test', function () {
        return view('test');
    })->name('test');

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
    });

    // Get sections by grade level



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
