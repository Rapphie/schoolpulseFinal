<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher) {
                abort(403, 'User is not a teacher.');
            }

            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return view('teacher.dashboard')->with('error', 'Data cannot be loaded because no school year is active.');
            }

            // Get all unique Class IDs the teacher interacts with for the active school year
            $advisoryClassIds = $teacher->advisoryClasses()
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id');

            $scheduledClassIds = $teacher->schedules()
                ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                ->pluck('class_id');

            $allClassIds = $advisoryClassIds->merge($scheduledClassIds)->unique();

            // Card 1: My Classes Count
            $classCount = $allClassIds->count();

            // Card 2: Student Count (unique students across all their classes)
            $studentCount = Enrollment::whereIn('class_id', $allClassIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->distinct()
                ->count(DB::raw('COALESCE(student_profile_id, student_id)'));

            // Card 3: Today's Attendance Count (students marked by this teacher today)
            $todayAttendanceCount = Attendance::where('teacher_id', $teacher->id)
                ->where('date', today()->toDateString())
                ->distinct()
                ->count(DB::raw('COALESCE(student_profile_id, student_id)'));

            // Upcoming Schedules for Today
            $dayOfWeek = strtolower(now()->format('l'));
            $currentTime = now()->format('H:i:s');

            $upcomingSchedules = $teacher->schedules()
                ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                ->whereJsonContains('day_of_week', $dayOfWeek)
                ->whereTime('start_time', '>=', $currentTime)
                ->with(['class.section.gradeLevel', 'subject'])
                ->orderBy('start_time')
                ->get();

            // Card 4: Count of today's upcoming schedules
            $scheduleCount = $upcomingSchedules->count();

            // Subjects for the performance filter dropdown
            $subjects = Subject::whereIn('id', function ($query) use ($teacher, $activeSchoolYear) {
                $query->select('schedules.subject_id')
                    ->from('schedules')
                    ->join('classes', 'schedules.class_id', '=', 'classes.id')
                    ->where('schedules.teacher_id', $teacher->id)
                    ->where('classes.school_year_id', $activeSchoolYear->id);
            })->orderBy('name')->get();

            $selectedSubjectId = $request->integer('subject_id');
            if ($selectedSubjectId <= 0 || ! $subjects->pluck('id')->contains($selectedSubjectId)) {
                $selectedSubjectId = null;
            }

            // Student Performance Data
            $studentKeys = Enrollment::whereIn('class_id', $allClassIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->select(DB::raw('COALESCE(student_profile_id, student_id) as pid'))
                ->pluck('pid')
                ->unique()
                ->toArray();

            $grades = collect();
            if (! empty($studentKeys)) {
                $gradesQuery = DB::table('grades')
                    ->select(DB::raw('COALESCE(student_profile_id, student_id) as pid'), DB::raw('AVG(grade) as average_grade'))
                    ->whereIn(DB::raw('COALESCE(student_profile_id, student_id)'), $studentKeys)
                    ->where('school_year_id', $activeSchoolYear->id)
                    ->when($selectedSubjectId, function ($query, $subjectId) {
                        $query->where('subject_id', $subjectId);
                    });

                $grades = $gradesQuery->groupBy('pid')->get();
            }

            $highPerformers = $grades->where('average_grade', '>=', 90)->count();
            $averagePerformers = $grades->where('average_grade', '>=', 75)->where('average_grade', '<', 90)->count();
            $lowPerformers = $grades->where('average_grade', '<', 75)->count();

            $recentGradeActivities = Grade::with(['subject', 'student', 'studentProfile.student'])
                ->where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->when($selectedSubjectId, function ($query, $subjectId) {
                    $query->where('subject_id', $subjectId);
                })
                ->latest('updated_at')
                ->take(5)
                ->get()
                ->map(function (Grade $grade) {
                    $student = $grade->student ?? $grade->studentProfile?->student;
                    $studentName = $student
                        ? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
                        : 'a student';
                    $formattedGrade = number_format((float) $grade->grade, 2);

                    return (object) [
                        'type' => 'grade',
                        'title' => 'Grade updated',
                        'description' => ($grade->subject?->name ?? 'Subject').": {$studentName} received {$formattedGrade}.",
                        'created_at' => $grade->updated_at ?? $grade->created_at,
                    ];
                })
                ->values()
                ->toBase();

            $recentAttendanceActivities = Attendance::with(['subject', 'class.section'])
                ->where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->when($selectedSubjectId, function ($query, $subjectId) {
                    $query->where('subject_id', $subjectId);
                })
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Attendance $attendance) {
                    $status = ucfirst($attendance->status ?? 'recorded');
                    $sectionName = $attendance->class?->section?->name;
                    $sectionText = $sectionName ? " in {$sectionName}" : '';

                    return (object) [
                        'type' => 'attendance',
                        'title' => 'Attendance recorded',
                        'description' => ($attendance->subject?->name ?? 'Class')." attendance marked as {$status}{$sectionText}.",
                        'created_at' => $attendance->created_at,
                    ];
                })
                ->values()
                ->toBase();

            $recentEnrollmentActivities = Enrollment::with(['student', 'class.section'])
                ->where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Enrollment $enrollment) {
                    $student = $enrollment->student;
                    $studentName = $student
                        ? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
                        : 'A student';
                    $sectionName = $enrollment->class?->section?->name;
                    $sectionText = $sectionName ? " to {$sectionName}" : '';

                    return (object) [
                        'type' => 'enrollment',
                        'title' => 'Student enrolled',
                        'description' => "{$studentName} was enrolled{$sectionText}.",
                        'created_at' => $enrollment->created_at,
                    ];
                })
                ->values()
                ->toBase();

            $recentActivities = $recentGradeActivities
                ->merge($recentAttendanceActivities)
                ->merge($recentEnrollmentActivities)
                ->sortByDesc(fn ($activity) => $activity->created_at)
                ->take(10)
                ->values();

            $calendarSchedules = $teacher->schedules()
                ->whereHas('class', fn ($query) => $query->where('school_year_id', $activeSchoolYear->id))
                ->with('subject')
                ->get()
                ->map(function (Schedule $schedule) {
                    return [
                        'subject' => $schedule->subject?->name ?? 'Class',
                        'days' => collect($schedule->day_names)->map(fn ($day) => strtolower($day))->values()->all(),
                        'start_time' => $schedule->start_time?->format('h:i A'),
                    ];
                })
                ->values();

            return view('teacher.dashboard', compact(
                'classCount',
                'studentCount',
                'scheduleCount',
                'todayAttendanceCount',
                'upcomingSchedules',
                'highPerformers',
                'averagePerformers',
                'lowPerformers',
                'recentActivities',
                'subjects',
                'selectedSubjectId',
                'calendarSchedules'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@index error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load dashboard: '.$e->getMessage());
        }
    }

    public function students()
    {
        return redirect()->route('teacher.students.index');
    }
}
