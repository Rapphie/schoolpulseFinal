<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Teacher;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        $schoolYears = SchoolYear::all();


        // Get the active school year
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();

        // If no active school year, use the latest one as a fallback
        if (!$activeSchoolYear) {
            $activeSchoolYear = SchoolYear::latest('end_date')->first();
        }

        $enrolledStudents = 0;
        $transferStudents = 0;
        $graduates = 0;
        $recentEnrolledStudents = 0;

        if ($activeSchoolYear) {
            $enrolledStudents = Enrollment::where('school_year_id', $activeSchoolYear->id)
                ->where('status', 'enrolled')
                ->count();

            $transferStudents = Enrollment::where('school_year_id', $activeSchoolYear->id)
                ->where('status', 'transferred')
                ->count();

            $graduates = Enrollment::where('school_year_id', $activeSchoolYear->id)
                ->where('status', 'graduated')
                ->count();

            $recentEnrolledStudents = Enrollment::where('school_year_id', $activeSchoolYear->id)
                ->whereYear('enrollment_date', now()->year)
                ->count();
        }

        $teacherCount = Teacher::where('status', 'active')->count();
        $sectionCount = $activeSchoolYear ? Classes::where('school_year_id', $activeSchoolYear->id)->count() : 0;

        $todaysAttendance = Attendance::where('date', Carbon::today()->toDateString())->get();
        $presentCount = $todaysAttendance->where('status', 'present')->count();
        $totalToday = $todaysAttendance->count();
        $attendancePercentage = ($totalToday > 0) ? ($presentCount / $totalToday) * 100 : 0;

        $totalRelevant = $enrolledStudents + $graduates + $transferStudents;
        $retentionRate = $totalRelevant > 0 ? (($enrolledStudents + $graduates) / $totalRelevant) * 100 : 0;

        // Additional metrics
        $pendingAdmissions = $activeSchoolYear ? Enrollment::where('school_year_id', $activeSchoolYear->id)->where('status', 'unenrolled')->count() : 0;
        $absentToday = $todaysAttendance->where('status', 'absent')->count();
        $averageClassSize = 0;
        if ($activeSchoolYear) {
            $averageClassSize = round(Classes::where('school_year_id', $activeSchoolYear->id)
                ->withCount(['enrollments' => function ($q) {
                    $q->where('status', 'enrolled');
                }])
                ->get()->avg('enrollments_count') ?? 0, 2);
        }
        $teacherStudentRatio = $teacherCount > 0 ? round($enrolledStudents / $teacherCount, 2) : 0;

        // Recent activity feed (limited & unified)
        $recentEnrollments = Enrollment::latest()->take(5)->get()->map(function ($e) {
            return [
                'type' => 'Enrollment',
                'created_at' => $e->created_at,
                'description' => 'Student ID ' . $e->student_id . ' ' . $e->status,
            ];
        });
        $recentAssessments = \App\Models\Assessment::latest()->take(5)->get()->map(function ($a) {
            return [
                'type' => 'Assessment',
                'created_at' => $a->created_at,
                'description' => $a->name . ' (Q' . $a->quarter . ')',
            ];
        });
        $recentAbsences = Attendance::where('status', 'absent')->latest()->take(5)->get()->map(function ($at) {
            return [
                'type' => 'Absence',
                'created_at' => $at->created_at,
                'description' => 'Student ID ' . $at->student_id . ' absent',
            ];
        });
        $recentActivities = collect($recentEnrollments)->merge($recentAssessments)->merge($recentAbsences)
            ->sortByDesc('created_at')->take(10)->values();

        // Upcoming events (if events table exists)
        $upcomingEvents = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('events')) {
            $upcomingEvents = DB::table('events')
                ->where('start_date', '>=', Carbon::today()->toDateString())
                ->orderBy('start_date')
                ->limit(5)
                ->get();
        }

        return view('admin.dashboard', [
            'enrolledStudents' => $enrolledStudents,
            'teacherCount' => $teacherCount,
            'sectionCount' => $sectionCount,
            'attendancePercentage' => round($attendancePercentage, 2),
            'recentEnrolledStudents' => $recentEnrolledStudents,
            'transferStudents' => $transferStudents,
            'graduates' => $graduates,
            'retentionRate' => round($retentionRate, 2),
            'todaysAttendance' => $presentCount,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'pendingAdmissions' => $pendingAdmissions,
            'absentToday' => $absentToday,
            'averageClassSize' => $averageClassSize,
            'teacherStudentRatio' => $teacherStudentRatio,
            'recentActivities' => $recentActivities,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    public function getChartData()
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();

        $enrollmentQuery = Enrollment::select(DB::raw('COUNT(*) as count'), 'grade_levels.name as grade_level_name')
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->join('sections', 'classes.section_id', '=', 'sections.id')
            ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id');

        if ($activeSchoolYear) {
            $enrollmentQuery->where('enrollments.school_year_id', $activeSchoolYear->id);
        }

        $enrollmentData = $enrollmentQuery->groupBy('grade_level_name')
            ->orderBy('grade_level_name')
            ->get();

        $enrollmentChart = [
            'labels' => $enrollmentData->pluck('grade_level_name')->toArray(),
            'data' => $enrollmentData->pluck('count')->toArray(),
        ];

        // Class Distribution Chart Data (Sections per Grade Level)
        $classDistributionData = Section::select(DB::raw('COUNT(*) as count'), 'grade_levels.name as grade_level_name')
            ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->groupBy('grade_level_name')
            ->orderBy('grade_level_name')
            ->get();

        $classDistributionChart = [
            'labels' => $classDistributionData->pluck('grade_level_name')->toArray(),
            'data' => $classDistributionData->pluck('count')->toArray(),
        ];

        \Illuminate\Support\Facades\Log::info('Chart data:', [
            'enrollmentChart' => $enrollmentChart,
            'classDistributionChart' => $classDistributionChart,
        ]);

        $enrollmentTrends = Enrollment::select(
            DB::raw('YEAR(enrollment_date) as enrollment_year'),
            'status',
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('enrollment_year', 'status')
            ->orderBy('enrollment_year', 'asc')
            ->orderBy('status', 'asc')
            ->get();

        // Attendance trend (last 14 days)
        $attendanceTrend = Attendance::select(
            'date',
            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
            DB::raw('COUNT(*) as total_count')
        )
            ->where('date', '>=', Carbon::today()->subDays(13))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($row) {
                $percentage = $row->total_count > 0 ? round(($row->present_count / $row->total_count) * 100, 2) : 0;
                return [
                    'date' => $row->date,
                    'present' => (int)$row->present_count,
                    'total' => (int)$row->total_count,
                    'percentage' => $percentage,
                ];
            });

        // Grade performance by quarter
        $gradePerformance = \App\Models\Grade::select('quarter', DB::raw('AVG(grade) as avg_grade'))
            ->when($activeSchoolYear, function ($q) use ($activeSchoolYear) {
                $q->where('school_year_id', $activeSchoolYear->id);
            })
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->get();

        return response()->json([
            'enrollmentChart' => $enrollmentChart,
            'classDistributionChart' => $classDistributionChart,
            'enrollmentTrends' => $enrollmentTrends,
            'attendanceTrend' => $attendanceTrend,
            'gradePerformance' => $gradePerformance,
        ]);
    }
    public function storeSchoolYear(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        // Get the currently active school year (this will be the "previous" school year)
        $previousActiveSchoolYear = SchoolYear::where('is_active', true)->first();

        // Create the new school year record. The model's event will handle the 'is_active' logic.
        $newSchoolYear = SchoolYear::create([
            'name' => $startDate->format('Y') . '-' . $endDate->format('Y'),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => $request->has('is_active'),
        ]);

        // If there was a previous active school year, duplicate its classes for the new school year
        if ($previousActiveSchoolYear) {
            $previousClasses = Classes::where('school_year_id', $previousActiveSchoolYear->id)->get();

            foreach ($previousClasses as $class) {
                Classes::create([
                    'section_id' => $class->section_id,
                    'school_year_id' => $newSchoolYear->id,
                    'teacher_id' => $class->teacher_id,
                    'capacity' => $class->capacity,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Successfully added school year!');
    }

    public function updateSchoolYear(Request $request, $id)
    {
        try {
            $schoolYear = SchoolYear::findOrFail($id);

            // Add this line to handle the unchecked checkbox
            $request->merge(['is_active' => $request->has('is_active')]);

            $data = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_active' => 'boolean',
            ]);

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            $data['name'] = $startDate->format('Y') . '-' . $endDate->format('Y');

            if ($request->input('is_active')) {
                // Set all other school years to inactive
                SchoolYear::where('id', '!=', $id)->update(['is_active' => false]);
            }

            $schoolYear->update($data);

            return redirect()->route('admin.dashboard')->with('success', 'School Year updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('admin.dashboard')->with('error', 'Error: ' . $th->getMessage());
        }
    }

    public function deleteSchoolYear($id)
    {
        try {
            $schoolYear = SchoolYear::findOrFail($id);
            $schoolYear->delete();

            return redirect()->route('admin.dashboard')->with('success', 'School Year deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('admin.dashboard')->with('error', 'Error: ' . $th->getMessage());
        }
    }
}
