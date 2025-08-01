<?php

use App\Http\Controllers\ClassRecordController;
use App\Http\Controllers\Teacher\AnalyticsController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\EnrollmentController;
use App\Http\Controllers\Teacher\AssessmentController;
use App\Http\Controllers\TeacherSectionsController;
use App\Http\Controllers\ReportCardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
|
| Here are all routes for teacher users. These routes are protected by
| the 'role:teacher' middleware and are prefixed with 'teacher'.
|
*/

Route::group(['middleware' => ['auth', 'password.force-change', 'role:teacher']], function () {
    Route::prefix('teacher')->name('teacher.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

        // Enrollment Management
        Route::get('/enrollment', [EnrollmentController::class, 'index'])->name('enrollment.index');
        Route::get('/enrollment/export-all', [EnrollmentController::class, 'exportAll'])->name('enrollment.exportAll');
        Route::get('enrollment/export', [EnrollmentController::class, 'export'])->name('enrollment.export');
        Route::get('enrollment/class/{class}', [EnrollmentController::class, 'getEnrollmentsByClass'])->name('enrollment.class');
        Route::post('enrollment/store-past-student', [EnrollmentController::class, 'storePastStudent'])->name('enrollment.storePastStudent');
        Route::post('/classes/{class}/enroll', [EnrollmentController::class, 'store'])->name('enrollment.store');
        Route::post('/enrollment', [EnrollmentController::class, 'store'])->name('enrollment.store');

        // Assessment Management
        Route::prefix('classes/assessments')->name('assessments.')->group(function () {
            Route::get('/list', [AssessmentController::class, 'list'])->name('list');
            Route::get('/{class}', [AssessmentController::class, 'index'])->name('index');
            Route::get('/create/{class}', [AssessmentController::class, 'create'])->name('create');
            Route::post('/create/{class}/store', [AssessmentController::class, 'store'])->name('store');
            Route::get('/{class}/{assessment}/scores', [AssessmentController::class, 'editScores'])->name('scores.edit');
            Route::post('/{class}/{assessment}/scores', [AssessmentController::class, 'updateScores'])->name('scores.update');
            Route::delete('/{class}/{assessment}', [AssessmentController::class, 'destroy'])->name('destroy');
        });

        // Class Management
        Route::get('/classes/{section?}', [TeacherDashboardController::class, 'classes'])->name('classes');
        Route::get('/classes/view/{class}', [TeacherDashboardController::class, 'viewClass'])->name('classes.view');
        Route::get('/sections/{section}/students', [TeacherDashboardController::class, 'getStudentsForSection'])->name('sections.students');
        Route::get('/sections/{section}/grades', [ReportCardController::class, 'getStudentsBySection'])->name('sections.grades');

        // Schedules & Students
        Route::get('/schedules', [TeacherDashboardController::class, 'loggedTeacherSchedules'])->name('schedules.index');
        Route::get('/students', [TeacherDashboardController::class, 'students'])->name('students');
        Route::get('/grades', [TeacherDashboardController::class, 'grades'])->name('grades');

        // Section & Subject Queries
        Route::get('/sections-by-grade-level', [TeacherSectionsController::class, 'getSectionsByGradeLevel'])->name('sections.by-grade-level');
        Route::get('/sections/{section}/subjects', [TeacherSectionsController::class, 'getSubjectsBySection'])->name('subjects.by-section');

        // File Uploads
        Route::post('/upload-class-record', [ClassRecordController::class, 'upload'])->name('class-record.upload');
        Route::post('/save-class-record', [ClassRecordController::class, 'saveClassRecord'])->name('class-record.save');
        Route::post('/upload-report-card', [ReportCardController::class, 'upload'])->name('report-card.upload');

        // Report Cards
        Route::get('/report-cards', [ReportCardController::class, 'index'])->name('report-cards');
        Route::get('/report-card/show', [ReportCardController::class, 'showReportCard'])->name('report-card.show');

        // Least Learned Competencies
        Route::prefix('least-learned')->name('least-learned.')->group(function () {
            Route::get('/', [TeacherDashboardController::class, 'leastLearnedCompetencies'])->name('index');
            Route::get('/subjects', [TeacherDashboardController::class, 'leastLearnedSubjects'])->name('subjects');
        });

        // Attendance Management
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

        // Analytics
        Route::get('/analytics/absenteeism', [AnalyticsController::class, 'absenteeismAnalytics'])->name('analytics.absenteeism');
    });
});
