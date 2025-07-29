<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display absenteeism analytics for the teacher.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function absenteeismAnalytics(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();

        // Get all class IDs taught by this teacher in the active school year
        $classIds = \App\Models\Schedule::where('teacher_id', $teacher->id)
            ->whereHas('class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->pluck('class_id')->unique();

        // --- 1. Monthly Attendance Percentage Trend ---
        $monthlyTrend = Attendance::whereIn('class_id', $classIds)
            ->where('teacher_id', $teacher->id)
            ->select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("COUNT(*) as total_count")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->mapWithKeys(function ($item) {
                $percentage = ($item->total_count > 0) ? ($item->present_count / $item->total_count) * 100 : 0;
                return [\Carbon\Carbon::parse($item->month)->format('M Y') => round($percentage, 2)];
            });

        // --- 2. Absences by Subject ---
        $absencesBySubject = Subject::whereHas('schedules', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })
            ->withCount(['attendances' => function ($query) use ($classIds, $teacher) {
                $query->where('status', 'absent')
                    ->where('teacher_id', $teacher->id)
                    ->whereIn('class_id', $classIds);
            }])
            ->get()
            ->mapWithKeys(function ($subject) {
                return [$subject->name => $subject->attendances_count];
            });


        // --- 3. Students with Highest Absence Rates ---
        $topAbsentees = Student::whereHas('enrollments', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })
            ->withCount(['attendances as absent_count' => function ($query) use ($teacher) {
                $query->where('status', 'absent')->where('teacher_id', $teacher->id);
            }])
            ->orderBy('absent_count', 'desc')
            ->take(10) // Get the top 10
            ->get();

        return view('teacher.analytics.absenteeism', compact(
            'monthlyTrend',
            'absencesBySubject',
            'topAbsentees'
        ));
    }
}
