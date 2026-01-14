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
use App\Models\Schedule;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Cache dashboard metrics for 2 minutes to reduce database load
        $cacheKey = 'admin_dashboard_metrics_' . ($activeSchoolYear?->id ?? 'none');
        $metrics = Cache::remember($cacheKey, 120, function () use ($activeSchoolYear) {
            $enrolledStudents = 0;
            $transferStudents = 0;
            $graduates = 0;
            $recentEnrolledStudents = 0;
            $pendingAdmissions = 0;
            $sectionCount = 0;
            $averageClassSize = 0;

            if ($activeSchoolYear) {
                // Combine enrollment counts into a single query
                $enrollmentCounts = Enrollment::where('school_year_id', $activeSchoolYear->id)
                    ->selectRaw("
                        SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as enrolled,
                        SUM(CASE WHEN status = 'transferred' THEN 1 ELSE 0 END) as transferred,
                        SUM(CASE WHEN status = 'graduated' THEN 1 ELSE 0 END) as graduated,
                        SUM(CASE WHEN status = 'unenrolled' THEN 1 ELSE 0 END) as unenrolled
                    ")
                    ->first();

                $enrolledStudents = (int) ($enrollmentCounts->enrolled ?? 0);
                $transferStudents = (int) ($enrollmentCounts->transferred ?? 0);
                $graduates = (int) ($enrollmentCounts->graduated ?? 0);
                $pendingAdmissions = (int) ($enrollmentCounts->unenrolled ?? 0);

                $recentEnrolledStudents = Enrollment::where('school_year_id', $activeSchoolYear->id)
                    ->whereYear('enrollment_date', now()->year)
                    ->count();

                $sectionCount = Classes::where('school_year_id', $activeSchoolYear->id)->count();

                // Calculate average class size more efficiently
                $classStats = DB::table(DB::raw('(SELECT classes.id, (SELECT COUNT(*) FROM enrollments WHERE enrollments.class_id = classes.id AND enrollments.status = "enrolled") as enrollment_count FROM classes WHERE school_year_id = ?) as class_enrollments'))
                    ->setBindings([$activeSchoolYear->id])
                    ->selectRaw('AVG(enrollment_count) as avg_size')
                    ->first();
                $averageClassSize = round($classStats->avg_size ?? 0, 2);
            }

            $teacherCount = Teacher::where('status', 'active')->count();

            return compact(
                'enrolledStudents',
                'transferStudents',
                'graduates',
                'recentEnrolledStudents',
                'pendingAdmissions',
                'teacherCount',
                'sectionCount',
                'averageClassSize'
            );
        });

        // Today's attendance - use aggregate query instead of fetching all records
        $todayDate = Carbon::today()->toDateString();
        $attendanceStats = Cache::remember('admin_attendance_today_' . $todayDate, 60, function () use ($todayDate) {
            return Attendance::where('date', $todayDate)
                ->selectRaw("
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    COUNT(*) as total_count
                ")
                ->first();
        });

        $presentCount = (int) ($attendanceStats->present_count ?? 0);
        $totalToday = (int) ($attendanceStats->total_count ?? 0);
        $absentToday = (int) ($attendanceStats->absent_count ?? 0);
        $attendancePercentage = ($totalToday > 0) ? ($presentCount / $totalToday) * 100 : 0;

        $enrolledStudents = $metrics['enrolledStudents'];
        $transferStudents = $metrics['transferStudents'];
        $graduates = $metrics['graduates'];

        $totalRelevant = $enrolledStudents + $graduates + $transferStudents;
        $retentionRate = $totalRelevant > 0 ? (($enrolledStudents + $graduates) / $totalRelevant) * 100 : 0;
        $teacherStudentRatio = $metrics['teacherCount'] > 0 ? round($enrolledStudents / $metrics['teacherCount'], 2) : 0;

        // Cache recent activities for 1 minute
        $recentActivities = Cache::remember('admin_recent_activities', 60, function () {
            $recentEnrollments = Enrollment::latest()->take(5)->get()->map(function ($e) {
                return [
                    'type' => 'Enrollment',
                    'created_at' => $e->created_at,
                    'description' => 'New student ' . $e->status,
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
                    'description' => 'Student marked absent',
                ];
            });
            return collect($recentEnrollments)->merge($recentAssessments)->merge($recentAbsences)
                ->sortByDesc('created_at')->take(10)->values();
        });

        // Cache events table check - this only needs to happen once
        $upcomingEvents = [];
        $hasEventsTable = Cache::remember('has_events_table', 3600, function () {
            return \Illuminate\Support\Facades\Schema::hasTable('events');
        });

        if ($hasEventsTable) {
            $upcomingEvents = Cache::remember('upcoming_events', 300, function () {
                return DB::table('events')
                    ->where('start_date', '>=', Carbon::today()->toDateString())
                    ->orderBy('start_date')
                    ->limit(5)
                    ->get();
            });
        }

        return view('admin.dashboard', [
            'enrolledStudents' => $enrolledStudents,
            'teacherCount' => $metrics['teacherCount'],
            'sectionCount' => $metrics['sectionCount'],
            'attendancePercentage' => round($attendancePercentage, 2),
            'recentEnrolledStudents' => $metrics['recentEnrolledStudents'],
            'transferStudents' => $transferStudents,
            'graduates' => $graduates,
            'retentionRate' => round($retentionRate, 2),
            'todaysAttendance' => $presentCount,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'pendingAdmissions' => $metrics['pendingAdmissions'],
            'absentToday' => $absentToday,
            'averageClassSize' => $metrics['averageClassSize'],
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
            'school_years.name as school_year_name',
            'school_years.id as school_year_id',
            'enrollments.status',
            DB::raw('COUNT(*) as count')
        )
            ->join('school_years', 'enrollments.school_year_id', '=', 'school_years.id')
            ->groupBy('school_years.id', 'school_years.name', 'enrollments.status')
            ->orderBy('school_years.id', 'asc')
            ->orderBy('enrollments.status', 'asc')
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
        try {
            // Handle the checkbox - convert to boolean
            $request->merge(['is_active' => $request->has('is_active')]);

            $data = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'is_active' => 'boolean',
            ]);

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            // Check for overlapping school years
            $overlapping = SchoolYear::findOverlapping($startDate, $endDate);
            if ($overlapping) {
                $overlapStart = Carbon::parse($overlapping->start_date)->format('M d, Y');
                $overlapEnd = Carbon::parse($overlapping->end_date)->format('M d, Y');
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Date range overlaps with existing school year: {$overlapping->name} ({$overlapStart} - {$overlapEnd})");
            }

            // Get the currently active school year (this will be the "previous" school year)
            $previousActiveSchoolYear = SchoolYear::where('is_active', true)->first();

            // Create the new school year record. The model's event will handle the 'is_active' logic.
            $newSchoolYear = SchoolYear::create([
                'name' => $startDate->format('Y') . '-' . $endDate->format('Y'),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => $data['is_active'],
            ]);

            // If there was a previous active school year, duplicate its classes for the new school year
            if ($previousActiveSchoolYear) {
                $previousClasses = Classes::where('school_year_id', $previousActiveSchoolYear->id)
                    ->with('section.gradeLevel')
                    ->get();

                foreach ($previousClasses as $class) {
                    $newClass = Classes::create([
                        'section_id' => $class->section_id,
                        'school_year_id' => $newSchoolYear->id,
                        'teacher_id' => $class->teacher_id,
                        'capacity' => $class->capacity,
                    ]);

                    // For Grade 1, 2, 3: Auto-create schedules with the adviser as teacher for all subjects
                    $gradeLevel = optional($class->section)->gradeLevel;
                    $gradeValue = optional($gradeLevel)->level;

                    if (!is_null($gradeValue) && in_array($gradeValue, [1, 2, 3]) && $class->teacher_id) {
                        $subjects = Subject::where('grade_level_id', $class->section->grade_level_id)->get();

                        foreach ($subjects as $subject) {
                            Schedule::create([
                                'class_id' => $newClass->id,
                                'subject_id' => $subject->id,
                                'teacher_id' => $class->teacher_id,
                                'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                                'start_time' => '00:00',
                                'end_time' => '00:00',
                                'room' => null,
                            ]);
                        }
                    }
                }
            }

            return redirect()->back()->with('success', 'Successfully added school year!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // Re-throw validation exceptions to let Laravel handle them
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create school year: ' . $th->getMessage());
        }
    }

    public function updateSchoolYear(Request $request, $id)
    {
        try {
            $schoolYear = SchoolYear::findOrFail($id);

            // Add this line to handle the unchecked checkbox
            $request->merge(['is_active' => $request->has('is_active')]);

            $data = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'is_active' => 'boolean',
            ]);

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);

            // Check for overlapping school years (excluding current one)
            $overlapping = SchoolYear::findOverlapping($startDate, $endDate, $id);
            if ($overlapping) {
                $overlapStart = Carbon::parse($overlapping->start_date)->format('M d, Y');
                $overlapEnd = Carbon::parse($overlapping->end_date)->format('M d, Y');
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Date range overlaps with existing school year: {$overlapping->name} ({$overlapStart} - {$overlapEnd})");
            }

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

            // Prevent deletion of active school year
            if ($schoolYear->is_active) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'Cannot delete the active school year. Please set another school year as active first.');
            }

            // Prevent deletion if school year has related data
            if ($schoolYear->hasRelatedData()) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'Cannot delete school year with existing enrollments, grades, attendance records, or classes. Please remove related data first.');
            }

            $schoolYear->delete();

            return redirect()->route('admin.dashboard')->with('success', 'School Year deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('admin.dashboard')->with('error', 'Error: ' . $th->getMessage());
        }
    }
}
