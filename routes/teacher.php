<?php

use App\Http\Controllers\ClassRecordController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportCardOutputController;
use App\Http\Controllers\Teacher\AnalyticsController;
use App\Http\Controllers\Teacher\AssessmentController;
use App\Http\Controllers\Teacher\EnrollmentController;
use App\Http\Controllers\Teacher\LeastLearnedController;
use App\Http\Controllers\Teacher\OralParticipationController;
use App\Http\Controllers\Teacher\StudentController;
use App\Http\Controllers\Teacher\TeacherAttendanceController;
use App\Http\Controllers\Teacher\TeacherClassController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherScheduleController;
use App\Http\Controllers\TeacherSectionsController;
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

        // Student Profile Management (separate from enrollment)
        Route::prefix('students')->name('students.')->group(function () {
            Route::get('/', [StudentController::class, 'index'])->name('index');
            Route::get('/create', [StudentController::class, 'create'])->name('create');
            Route::post('/', [StudentController::class, 'store'])->name('store');
            Route::get('/{student}', [StudentController::class, 'show'])->name('show');
            Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit');
            Route::put('/{student}', [StudentController::class, 'update'])->name('update');
            Route::get('/{student}/grades/{sy}', [StudentController::class, 'grades'])->name('grades');
        });

        // Enrollment Management (protected by enrollment.enabled middleware)
        Route::middleware(['enrollment.enabled'])->group(function () {
            Route::get('/enrollment', [EnrollmentController::class, 'index'])->name('enrollment.index');
            Route::get('/enrollment/export-all', [EnrollmentController::class, 'exportAll'])->name('enrollment.exportAll');
            Route::get('/enrollment/guardian-search', [EnrollmentController::class, 'searchGuardians'])->name('enrollment.guardian.search');
            Route::get('enrollment/export', [EnrollmentController::class, 'export'])->name('enrollment.export');
            Route::get('enrollment/class/{class}', [EnrollmentController::class, 'getEnrollmentsByClass'])->name('enrollment.class');
            Route::post('enrollment/store-past-student', [EnrollmentController::class, 'storePastStudent'])->name('enrollment.storePastStudent');
            Route::post('/enrollment', [EnrollmentController::class, 'store'])->name('enrollment.store');
        });

        // Assessment Management
        Route::prefix('classes/assessments')->name('assessments.')->group(function () {
            Route::get('/list', [AssessmentController::class, 'list'])->name('list');
            Route::get('/{class}', [AssessmentController::class, 'index'])->name('index');
            Route::get('/create/{class}', [AssessmentController::class, 'create'])->name('create');
            Route::post('/create/{class}/store', [AssessmentController::class, 'store'])->name('store');
            Route::post('/{class}/quick-add', [AssessmentController::class, 'quickAddAssessment'])->name('quickAdd');
            Route::post('/{class}/update-max-score', [AssessmentController::class, 'updateMaxScore'])->name('updateMaxScore');
            Route::post('/{class}/save-grades', [AssessmentController::class, 'saveGrades'])->name('saveGrades');

            Route::get('/{class}/{assessment}/scores', [AssessmentController::class, 'editScores'])->name('scores.edit');
            Route::put('/{class}/{assessment}/scores', [AssessmentController::class, 'updateScores'])->name('scores.update');
            Route::delete('/{class}/{assessment}', [AssessmentController::class, 'destroy'])->name('destroy');
        });

        // Oral Participation Management
        Route::prefix('oral-participation')->name('oral-participation.')->group(function () {
            Route::get('/selector', [OralParticipationController::class, 'selector'])->name('selector');
            Route::get('/sections', [OralParticipationController::class, 'getSectionsByGradeLevel'])->name('sections');
            Route::get('/{class}', [OralParticipationController::class, 'index'])->name('index');
            Route::get('/{class}/students', [OralParticipationController::class, 'getStudentsWithScores'])->name('students');
            Route::post('/{class}/save-scores', [OralParticipationController::class, 'saveScores'])->name('saveScores');
            Route::post('/{class}/quick-save', [OralParticipationController::class, 'quickSave'])->name('quickSave');
            Route::post('/{class}/append-scores', [OralParticipationController::class, 'appendScores'])->name('appendScores');
            Route::post('/{class}/update-max-score', [OralParticipationController::class, 'updateMaxScore'])->name('updateMaxScore');
            Route::get('/{class}/{assessment}/scores-json', [OralParticipationController::class, 'getSessionScores'])->name('session-scores');
        });

        // Class Management
        Route::get('/classes/{section?}', [TeacherClassController::class, 'classes'])->name('classes');
        Route::get('/classes/view/{class}', [TeacherClassController::class, 'viewClass'])->name('classes.view');
        Route::get('/classes/{class}/subjects', [AssessmentController::class, 'getSubjectsForClass'])->name('classes.subjects');
        // Adviser schedule creation
        Route::post('/classes/{class}/store-schedule', [TeacherClassController::class, 'storeSchedule'])->name('classes.schedule.store');
        Route::delete('/classes/{class}/schedules/{schedule}', [TeacherClassController::class, 'destroySchedule'])->name('classes.schedule.destroy');
        Route::put('/classes/{class}/rename-section', [TeacherClassController::class, 'renameSection'])->name('classes.section.rename');
        Route::get('/sections/{section}/students', [TeacherClassController::class, 'getStudentsForSection'])->name('sections.students');

        // Schedules & Students
        Route::get('/schedules', [TeacherScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/students-overview', [TeacherDashboardController::class, 'students'])->name('students-overview');
        Route::get('/grades', [TeacherClassController::class, 'grades'])->name('grades');
        Route::get('/grades/{class}', [TeacherClassController::class, 'showGrades'])->name('grades.show');
        Route::get('/grades/{class}/student/{student}', [TeacherClassController::class, 'studentGrades'])->name('grades.student');
        Route::get('/grades/{class}/student/{student}/download-report-card', [ReportCardOutputController::class, 'generateReportCard'])
            ->name('grades.student.download');

        // Section & Subject Queries
        Route::get('/sections-by-grade-level', [TeacherClassController::class, 'getSectionsByGradeLevel'])->name('sections.by-grade-level');
        Route::get('/sections/{section}/subjects', [TeacherSectionsController::class, 'getSubjectsBySection'])->name('subjects.by-section');
        Route::get('/sections/{section}/grades', [TeacherClassController::class, 'getGradesForSection'])->name('grades.by-section');
        Route::get('/sections/{section}/students-list', [TeacherClassController::class, 'getStudentsBySection'])->name('students.by-section');

        // File Uploads
        Route::post('/upload-class-record', [ClassRecordController::class, 'upload'])->name('class-record.upload');
        Route::post('/save-class-record', [ClassRecordController::class, 'saveClassRecord'])->name('class-record.save');
        Route::post('/upload-report-card', [ReportCardController::class, 'upload'])->name('report-card.upload');

        // Report Cards
        Route::get('/report-cards', [ReportCardController::class, 'index'])->name('report-cards');
        Route::get('/report-card/show/{studentId}', [ReportCardOutputController::class, 'generateReportCard'])
            ->name('report-card.show');

        // Least Learned Competencies
        Route::prefix('least-learned')->name('least-learned.')->group(function () {
            Route::get('/', [LeastLearnedController::class, 'index'])->name('index');
            Route::post('/', [LeastLearnedController::class, 'store'])->name('store');
            Route::get('/{llc}', [LeastLearnedController::class, 'show'])->name('show');
        });

        // Attendance Management
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/take', [TeacherAttendanceController::class, 'takeAttendance'])->name('take');
            Route::get('/records', [TeacherAttendanceController::class, 'attendanceRecords'])->name('records');
            Route::post('/scan', [TeacherAttendanceController::class, 'scanAttendance'])->name('scan');
            Route::get('/get-students', [TeacherAttendanceController::class, 'getStudents'])->name('get-students');
            Route::post('/save', [TeacherAttendanceController::class, 'saveAttendance'])->name('save');
            Route::put('/{id}/delete', [TeacherAttendanceController::class, 'updateAttendance'])->name('update');
            Route::delete('/{id}/delete', [TeacherAttendanceController::class, 'destroyAttendance'])->name('delete');
            Route::get('/summary', [TeacherAttendanceController::class, 'getAttendanceSummary'])->name('summary');
            Route::get('/pattern', [\App\Http\Controllers\Teacher\AttendanceController::class, 'attendancePattern'])->name('pattern');
            Route::get('/export', [\App\Http\Controllers\Teacher\AttendanceController::class, 'exportAttendancePattern'])->name('pattern.export');
        });

        // Analytics
        Route::get('/analytics/absenteeism', [AnalyticsController::class, 'absenteeismAnalytics'])->name('analytics.absenteeism');
        Route::get('/analytics/classes-by-grade', [AnalyticsController::class, 'classesByGrade'])->name('analytics.classes-by-grade');
    });
});
