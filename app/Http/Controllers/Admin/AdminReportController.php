<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\LLC;
use App\Models\SchoolYear;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EnrolleesExport;
use App\Exports\AttendanceExport;
use App\Exports\GradesExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;

class AdminReportController extends Controller
{
    public function enrollees(Request $request)
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $selectedGrade = $request->input('grade');

        $schoolYears = SchoolYear::query()
            ->orderByDesc('start_date')
            ->get();

        $activeSchoolYear = $schoolYears->firstWhere('is_active', true)
            ?? $schoolYears->first();

        $selectedSchoolYearId = (int) $request->input('school_year_id', $activeSchoolYear?->id);
        $currentSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId)
            ?? $activeSchoolYear;

        $selectedSchoolYearId = $currentSchoolYear?->id;

        $analyticsPayload = $this->buildEnrollmentAnalyticsPayload($selectedSchoolYearId, $selectedGrade);

        if ($request->expectsJson()) {
            return response()->json(array_merge($analyticsPayload, [
                'selectedGrade' => $selectedGrade,
                'schoolYearId' => $selectedSchoolYearId,
                'schoolYearLabel' => $currentSchoolYear?->name ?? 'N/A',
            ]));
        }

        return view('admin.reports.enrollees', array_merge($analyticsPayload, [
            'gradeLevels' => $gradeLevels,
            'selectedGrade' => $selectedGrade,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'currentSchoolYear' => $currentSchoolYear,
        ]));
    }

    private function buildEnrollmentAnalyticsPayload(?int $schoolYearId, $selectedGrade): array
    {
        $enrollmentRows = Enrollment::query()
            ->select(
                'classes.id as class_id',
                'sections.id as section_id',
                'sections.name as section_name',
                'grade_levels.id as grade_id',
                'grade_levels.name as grade_name',
                'grade_levels.level as grade_level',
                DB::raw('COUNT(enrollments.id) as students_count')
            )
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->join('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($schoolYearId, function ($query) use ($schoolYearId) {
                $query->where('enrollments.school_year_id', $schoolYearId);
            })
            ->when($selectedGrade, function ($query) use ($selectedGrade) {
                $query->where('grade_levels.level', $selectedGrade);
            })
            ->groupBy(
                'classes.id',
                'sections.id',
                'sections.name',
                'grade_levels.id',
                'grade_levels.name',
                'grade_levels.level'
            )
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->get();

        $classBreakdown = $enrollmentRows->map(function ($row) {
            $sectionName = $row->section_name ?? 'Section';
            $gradeLabel = $row->grade_name ?: ($row->grade_level ? 'Grade ' . $row->grade_level : 'Unassigned');

            return [
                'class_id' => $row->class_id,
                'section_id' => $row->section_id,
                'section_name' => $sectionName,
                'grade_id' => $row->grade_id,
                'grade_level' => $row->grade_level,
                'grade_name' => $gradeLabel,
                'label' => trim($sectionName . ' (' . $gradeLabel . ')'),
                'students_count' => (int) $row->students_count,
            ];
        });

        $colorPalette = [
            'rgba(13,110,253,0.7)',
            'rgba(25,135,84,0.7)',
            'rgba(255,193,7,0.7)',
            'rgba(220,53,69,0.7)',
            'rgba(111,66,193,0.7)',
            'rgba(13,202,240,0.7)',
            'rgba(32,201,151,0.7)',
            'rgba(73,80,87,0.7)',
        ];

        $colorIndex = 0;

        $sectionsByGrade = $classBreakdown
            ->groupBy(function ($section) {
                return $section['grade_id'] ?? 'unassigned';
            })
            ->map(function ($gradeSections, $gradeId) use (&$colorIndex, $colorPalette) {
                $color = $colorPalette[$colorIndex % count($colorPalette)];
                $colorIndex++;

                $totalStudents = (int) $gradeSections->sum('students_count');
                $sectionCount = $gradeSections->count();
                $firstSection = $gradeSections->first();
                $label = $firstSection['grade_name'] ?? 'Unassigned';
                $gradeLevelValue = $firstSection['grade_level'] ?? null;

                return [
                    'grade' => $gradeId ?? 'unassigned',
                    'label' => $label,
                    'grade_level' => $gradeLevelValue,
                    'total_students' => $totalStudents,
                    'section_count' => $sectionCount,
                    'average_per_section' => $sectionCount > 0 ? round($totalStudents / $sectionCount, 1) : 0,
                    'color' => $color,
                    'sections' => $gradeSections->sortBy('section_name')->values()->map(function ($section) use ($totalStudents) {
                        $students = (int) $section['students_count'];
                        $percentageOfGrade = $totalStudents > 0 ? round(($students / $totalStudents) * 100, 1) : 0;

                        return [
                            'id' => $section['section_id'],
                            'class_id' => $section['class_id'],
                            'name' => $section['section_name'],
                            'students' => $students,
                            'percentage' => $percentageOfGrade,
                        ];
                    })->toArray(),
                ];
            })
            ->sortBy(function ($gradeEntry) {
                if (isset($gradeEntry['grade_level'])) {
                    return $gradeEntry['grade_level'];
                }

                return PHP_INT_MAX;
            })
            ->values();

        $gradeColorMap = $sectionsByGrade->pluck('color', 'grade');

        $classChartData = [
            'labels' => $classBreakdown->pluck('label')->values()->toArray(),
            'totals' => $classBreakdown->pluck('students_count')->values()->toArray(),
            'colors' => $classBreakdown->map(function ($section) use ($gradeColorMap) {
                $gradeKey = $section['grade_id'] ?? 'unassigned';
                return $gradeColorMap->get($gradeKey, 'rgba(13,110,253,0.7)');
            })->values()->toArray(),
        ];

        $gradeChartData = [
            'labels' => $sectionsByGrade->pluck('label')->values()->toArray(),
            'totals' => $sectionsByGrade->pluck('total_students')->values()->toArray(),
            'colors' => $sectionsByGrade->pluck('color')->values()->toArray(),
        ];

        $monthlyTrendRows = Enrollment::query()
            ->select(
                DB::raw("DATE_FORMAT(COALESCE(enrollment_date, enrollments.created_at), '%Y-%m') as ym"),
                DB::raw('COUNT(*) as total')
            )
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->join('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($schoolYearId, function ($query) use ($schoolYearId) {
                $query->where('enrollments.school_year_id', $schoolYearId);
            })
            ->when($selectedGrade, function ($query) use ($selectedGrade) {
                $query->where('grade_levels.level', $selectedGrade);
            })
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $monthlyTrend = [
            'labels' => $monthlyTrendRows->map(function ($row) {
                try {
                    return Carbon::createFromFormat('Y-m', $row->ym)->format('M Y');
                } catch (\Exception $e) {
                    return $row->ym;
                }
            })->toArray(),
            'totals' => $monthlyTrendRows->pluck('total')->map(fn($value) => (int) $value)->toArray(),
        ];

        $totalStudents = (int) $classBreakdown->sum('students_count');
        $totalSections = $classBreakdown->count();
        $averagePerSection = $totalSections > 0 ? round($totalStudents / $totalSections, 1) : 0;
        $largestSection = $classBreakdown->max('students_count') ?? 0;

        return [
            'sectionsByGrade' => $sectionsByGrade,
            'classChartData' => $classChartData,
            'gradeChartData' => $gradeChartData,
            'monthlyTrend' => $monthlyTrend,
            'totalStudents' => $totalStudents,
            'totalSections' => $totalSections,
            'averagePerSection' => $averagePerSection,
            'largestSection' => $largestSection,
        ];
    }

    /**
     * Show enrollees detail view based on card type.
     * Types: students, sections, average, largest
     */
    public function enrolleesDetail(Request $request, string $type)
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $selectedGrade = $request->input('grade');

        $schoolYears = SchoolYear::query()
            ->orderByDesc('start_date')
            ->get();

        $activeSchoolYear = $schoolYears->firstWhere('is_active', true)
            ?? $schoolYears->first();

        $selectedSchoolYearId = (int) $request->input('school_year_id', $activeSchoolYear?->id);
        $currentSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId)
            ?? $activeSchoolYear;

        $selectedSchoolYearId = $currentSchoolYear?->id;

        $data = [];
        $title = '';
        $description = '';

        switch ($type) {
            case 'students':
                $title = 'All Enrolled Students';
                $description = 'Complete list of all enrolled students for the selected school year.';
                $data = $this->getEnrolledStudentsData($selectedSchoolYearId, $selectedGrade);
                break;

            case 'sections':
                $title = 'All Sections';
                $description = 'List of all active sections with enrollment counts.';
                $data = $this->getSectionsData($selectedSchoolYearId, $selectedGrade);
                break;

            case 'average':
                $title = 'Section Enrollment Analysis';
                $description = 'Sections sorted by student count to analyze enrollment distribution.';
                $data = $this->getSectionsData($selectedSchoolYearId, $selectedGrade, 'average');
                break;

            case 'largest':
                $title = 'Largest Section(s)';
                $description = 'The section(s) with the highest student enrollment.';
                $data = $this->getSectionsData($selectedSchoolYearId, $selectedGrade, 'largest');
                break;

            default:
                abort(404, 'Invalid detail type.');
        }

        return view('admin.reports.enrollees-detail', [
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'gradeLevels' => $gradeLevels,
            'selectedGrade' => $selectedGrade,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'currentSchoolYear' => $currentSchoolYear,
        ]);
    }

    /**
     * Get all enrolled students for the detail view.
     */
    private function getEnrolledStudentsData(?int $schoolYearId, $selectedGrade): array
    {
        $query = Enrollment::query()
            ->select(
                'enrollments.id as enrollment_id',
                'students.id as student_id',
                'students.lrn',
                'students.first_name',
                'students.last_name',
                'students.gender',
                'enrollments.enrollment_date',
                'sections.name as section_name',
                'grade_levels.name as grade_name',
                'grade_levels.level as grade_level'
            )
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->join('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($schoolYearId, function ($query) use ($schoolYearId) {
                $query->where('enrollments.school_year_id', $schoolYearId);
            })
            ->when($selectedGrade, function ($query) use ($selectedGrade) {
                $query->where('grade_levels.level', $selectedGrade);
            })
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->get();

        return [
            'students' => $query->map(function ($row) {
                return [
                    'student_id' => $row->student_id,
                    'lrn' => $row->lrn ?? 'N/A',
                    'full_name' => $row->last_name . ', ' . $row->first_name,
                    'gender' => ucfirst($row->gender ?? 'N/A'),
                    'section' => $row->section_name ?? 'Unassigned',
                    'grade' => $row->grade_name ?? ('Grade ' . ($row->grade_level ?? 'N/A')),
                    'enrollment_date' => $row->enrollment_date ? Carbon::parse($row->enrollment_date)->format('M d, Y') : 'N/A',
                ];
            })->toArray(),
            'total' => $query->count(),
        ];
    }

    /**
     * Get sections data for the detail view.
     */
    private function getSectionsData(?int $schoolYearId, $selectedGrade, string $mode = 'all'): array
    {
        $query = Enrollment::query()
            ->select(
                'classes.id as class_id',
                'sections.id as section_id',
                'sections.name as section_name',
                'grade_levels.id as grade_id',
                'grade_levels.name as grade_name',
                'grade_levels.level as grade_level',
                'teachers.id as teacher_id',
                'users.first_name as adviser_first_name',
                'users.last_name as adviser_last_name',
                'classes.capacity',
                DB::raw('COUNT(enrollments.id) as students_count')
            )
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->join('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->leftJoin('teachers', 'classes.teacher_id', '=', 'teachers.id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.id')
            ->when($schoolYearId, function ($query) use ($schoolYearId) {
                $query->where('enrollments.school_year_id', $schoolYearId);
            })
            ->when($selectedGrade, function ($query) use ($selectedGrade) {
                $query->where('grade_levels.level', $selectedGrade);
            })
            ->groupBy(
                'classes.id',
                'sections.id',
                'sections.name',
                'grade_levels.id',
                'grade_levels.name',
                'grade_levels.level',
                'teachers.id',
                'users.first_name',
                'users.last_name',
                'classes.capacity'
            )
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->get();

        $sections = $query->map(function ($row) {
            return [
                'class_id' => $row->class_id,
                'section_id' => $row->section_id,
                'section_name' => $row->section_name ?? 'Section',
                'grade' => $row->grade_name ?? ('Grade ' . ($row->grade_level ?? 'N/A')),
                'grade_level' => $row->grade_level,
                'adviser' => $row->adviser_first_name
                    ? $row->adviser_first_name . ' ' . $row->adviser_last_name
                    : 'Not Assigned',
                'students_count' => (int) $row->students_count,
                'capacity' => $row->capacity ?? 'N/A',
            ];
        });

        $totalStudents = $sections->sum('students_count');
        $totalSections = $sections->count();
        $averagePerSection = $totalSections > 0 ? round($totalStudents / $totalSections, 1) : 0;
        $maxStudents = $sections->max('students_count') ?? 0;

        if ($mode === 'largest') {
            // Filter only the section(s) with the maximum student count
            $sections = $sections->filter(fn($s) => $s['students_count'] === $maxStudents)->values();
        } elseif ($mode === 'average') {
            // Sort by student count descending to show distribution
            $sections = $sections->sortByDesc('students_count')->values();
        }

        return [
            'sections' => $sections->toArray(),
            'total_sections' => $totalSections,
            'total_students' => $totalStudents,
            'average_per_section' => $averagePerSection,
            'largest_count' => $maxStudents,
        ];
    }


    public function exportEnrollees(Request $request)
    {
        $grade = $request->input('grade');
        $format = $request->input('format', 'xlsx');
        $schoolYearId = $request->input('school_year_id');

        if (!$schoolYearId) {
            $fallbackYear = SchoolYear::query()->where('is_active', true)->first()
                ?? SchoolYear::query()->orderByDesc('end_date')->first();
            $schoolYearId = $fallbackYear?->id;
        }

        $schoolYearLabel = $schoolYearId ? optional(SchoolYear::find($schoolYearId))->name : null;

        $filenameParts = ['enrollees_report'];
        if ($grade) {
            $filenameParts[] = 'grade_' . $grade;
        }
        if ($schoolYearLabel) {
            $filenameParts[] = str_replace(' ', '_', strtolower($schoolYearLabel));
        }
        $filenameParts[] = now()->format('Y-m-d');

        $filename = implode('_', array_filter($filenameParts));
        $export = new EnrolleesExport(null, $grade ? (int) $grade : null, $schoolYearId ? (int) $schoolYearId : null);

        switch ($format) {
            case 'csv':
                return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
            case 'xlsx':
            default:
                return Excel::download($export, $filename . '.xlsx');
        }
    }


    public function attendanceReport(Request $request)
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $schoolYears = SchoolYear::orderByDesc('start_date')->get();

        $activeSchoolYear = $schoolYears->firstWhere('is_active', true)
            ?? $schoolYears->first();

        $selectedSchoolYearId = (int) $request->input('school_year_id', $activeSchoolYear?->id);
        $currentSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId)
            ?? $activeSchoolYear;

        $selectedSchoolYearId = $currentSchoolYear?->id;
        $selectedGradeLevelId = $request->filled('grade_level_id') ? (int) $request->input('grade_level_id') : null;
        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;

        // Enforce grade selection before narrowing down to a specific class
        if ($selectedClassId && !$selectedGradeLevelId) {
            $selectedClassId = null;
        }

        $classes = $this->fetchClassesForSchoolYear($selectedSchoolYearId);
        $classOptionsMap = $classes
            ->groupBy(fn($class) => $class['grade_level_id'] ?? 'unassigned')
            ->map(fn($group) => $group->map(fn($class) => [
                'id' => $class['id'],
                'label' => $class['label'],
            ])->values())
            ->toArray();

        $analyticsPayload = $this->buildAttendanceAnalyticsPayload(
            $selectedSchoolYearId,
            $selectedGradeLevelId,
            $selectedClassId
        );

        if ($request->expectsJson()) {
            return response()->json(array_merge($analyticsPayload, [
                'schoolYearId' => $selectedSchoolYearId,
                'schoolYearLabel' => $currentSchoolYear?->name ?? 'N/A',
                'selectedGradeLevelId' => $selectedGradeLevelId,
                'selectedClassId' => $selectedClassId,
                'classOptionsMap' => $classOptionsMap,
            ]));
        }

        return view('admin.reports.attendance', array_merge($analyticsPayload, [
            'gradeLevels' => $gradeLevels,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'currentSchoolYear' => $currentSchoolYear,
            'selectedGradeLevelId' => $selectedGradeLevelId,
            'selectedClassId' => $selectedClassId,
            'classOptionsMap' => $classOptionsMap,
        ]));
    }

    /**
     * Show attendance detail view based on card type.
     * Types: records, present, absent, late
     */
    public function attendanceDetail(Request $request, string $type)
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $schoolYears = SchoolYear::orderByDesc('start_date')->get();

        $activeSchoolYear = $schoolYears->firstWhere('is_active', true)
            ?? $schoolYears->first();

        $selectedSchoolYearId = (int) $request->input('school_year_id', $activeSchoolYear?->id);
        $currentSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId)
            ?? $activeSchoolYear;

        $selectedSchoolYearId = $currentSchoolYear?->id;
        $selectedGradeLevelId = $request->filled('grade_level_id') ? (int) $request->input('grade_level_id') : null;
        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;

        if ($selectedClassId && !$selectedGradeLevelId) {
            $selectedClassId = null;
        }

        $classes = $this->fetchClassesForSchoolYear($selectedSchoolYearId);
        $classOptionsMap = $classes
            ->groupBy(fn($class) => $class['grade_level_id'] ?? 'unassigned')
            ->map(fn($group) => $group->map(fn($class) => [
                'id' => $class['id'],
                'label' => $class['label'],
            ])->values())
            ->toArray();

        $data = [];
        $title = '';
        $description = '';
        $statusFilter = null;

        switch ($type) {
            case 'records':
                $title = 'All Attendance Records';
                $description = 'Complete attendance log for the selected school year.';
                break;

            case 'present':
                $title = 'Present Records';
                $description = 'All attendance records marked as present.';
                $statusFilter = 'present';
                break;

            case 'absent':
                $title = 'Absence Records';
                $description = 'All attendance records marked as absent.';
                $statusFilter = 'absent';
                break;

            case 'late':
                $title = 'Late Arrival Records';
                $description = 'All attendance records marked as late.';
                $statusFilter = 'late';
                break;

            default:
                abort(404, 'Invalid detail type.');
        }

        $data = $this->getAttendanceRecordsData($selectedSchoolYearId, $selectedGradeLevelId, $selectedClassId, $statusFilter);

        return view('admin.reports.attendance-detail', [
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevelId' => $selectedGradeLevelId,
            'selectedClassId' => $selectedClassId,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'currentSchoolYear' => $currentSchoolYear,
            'classOptionsMap' => $classOptionsMap,
        ]);
    }

    /**
     * Get attendance records for the detail view.
     */
    private function getAttendanceRecordsData(?int $schoolYearId, ?int $gradeLevelId, ?int $classId, ?string $statusFilter): array
    {
        $query = Attendance::query()
            ->select(
                'attendances.id',
                'attendances.date',
                'attendances.status',
                'attendances.time_in',
                'attendances.quarter',
                'students.id as student_id',
                'students.lrn',
                'students.first_name',
                'students.last_name',
                'sections.name as section_name',
                'grade_levels.name as grade_name',
                'grade_levels.level as grade_level',
                'subjects.name as subject_name'
            )
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->leftJoin('classes', 'attendances.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->leftJoin('subjects', 'attendances.subject_id', '=', 'subjects.id')
            ->when($schoolYearId, function ($query) use ($schoolYearId) {
                $query->where('attendances.school_year_id', $schoolYearId);
            })
            ->when($gradeLevelId, function ($query) use ($gradeLevelId) {
                $query->where('grade_levels.id', $gradeLevelId);
            })
            ->when($classId, function ($query) use ($classId) {
                $query->where('attendances.class_id', $classId);
            })
            ->when($statusFilter, function ($query) use ($statusFilter) {
                $query->where('attendances.status', $statusFilter);
            })
            ->orderByDesc('attendances.date')
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->orderBy('students.last_name')
            ->limit(500)
            ->get();

        $statusCounts = Attendance::query()
            ->leftJoin('classes', 'attendances.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($schoolYearId, fn($q) => $q->where('attendances.school_year_id', $schoolYearId))
            ->when($gradeLevelId, fn($q) => $q->where('grade_levels.id', $gradeLevelId))
            ->when($classId, fn($q) => $q->where('attendances.class_id', $classId))
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late
            ")
            ->first();

        return [
            'records' => $query->map(function ($row) {
                return [
                    'id' => $row->id,
                    'student_id' => $row->student_id,
                    'lrn' => $row->lrn ?? 'N/A',
                    'full_name' => $row->last_name . ', ' . $row->first_name,
                    'grade' => $row->grade_name ?? ('Grade ' . ($row->grade_level ?? 'N/A')),
                    'section' => $row->section_name ?? 'Unassigned',
                    'subject' => $row->subject_name ?? 'N/A',
                    'date' => $row->date ? Carbon::parse($row->date)->format('M d, Y') : 'N/A',
                    'status' => ucfirst($row->status ?? 'N/A'),
                    'status_raw' => $row->status,
                    'time_in' => $row->time_in ? Carbon::parse($row->time_in)->format('g:i A') : 'N/A',
                    'quarter' => $row->quarter ?? 'N/A',
                ];
            })->toArray(),
            'total' => (int) ($statusCounts->total ?? 0),
            'present' => (int) ($statusCounts->present ?? 0),
            'absent' => (int) ($statusCounts->absent ?? 0),
            'late' => (int) ($statusCounts->late ?? 0),
            'filtered_count' => $query->count(),
        ];
    }

    /**
     * Export attendance records to Excel/CSV.
     */
    public function exportAttendance(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $schoolYearId = $request->input('school_year_id');
        $gradeLevelId = $request->input('grade_level_id');
        $classId = $request->input('class_id');
        $status = $request->input('status');

        if (!$schoolYearId) {
            $fallbackYear = SchoolYear::query()->where('is_active', true)->first()
                ?? SchoolYear::query()->orderByDesc('end_date')->first();
            $schoolYearId = $fallbackYear?->id;
        }

        $schoolYearLabel = $schoolYearId ? optional(SchoolYear::find($schoolYearId))->name : null;

        $filenameParts = ['attendance_report'];
        if ($status) {
            $filenameParts[] = strtolower($status);
        }
        if ($schoolYearLabel) {
            $filenameParts[] = str_replace(' ', '_', strtolower($schoolYearLabel));
        }
        $filenameParts[] = now()->format('Y-m-d');

        $filename = implode('_', array_filter($filenameParts));
        $export = new \App\Exports\AttendanceExport(
            $schoolYearId ? (int) $schoolYearId : null,
            $gradeLevelId ? (int) $gradeLevelId : null,
            $classId ? (int) $classId : null,
            $status
        );

        switch ($format) {
            case 'csv':
                return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
            case 'xlsx':
            default:
                return Excel::download($export, $filename . '.xlsx');
        }
    }


    public function grades(Request $request)
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $schoolYears = SchoolYear::orderByDesc('start_date')->get();

        $activeSchoolYear = $schoolYears->firstWhere('is_active', true)
            ?? $schoolYears->first();

        $selectedSchoolYearId = (int) $request->input('school_year_id', $activeSchoolYear?->id);
        $currentSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId)
            ?? $activeSchoolYear;

        $selectedSchoolYearId = $currentSchoolYear?->id;
        $selectedGradeLevelId = $request->filled('grade_level_id') ? (int) $request->input('grade_level_id') : null;
        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;

        if ($selectedClassId && !$selectedGradeLevelId) {
            $selectedClassId = null;
        }

        $classes = $this->fetchClassesForSchoolYear($selectedSchoolYearId);
        $classOptionsMap = $classes
            ->groupBy(fn($class) => $class['grade_level_id'] ?? 'unassigned')
            ->map(fn($group) => $group->map(fn($class) => [
                'id' => $class['id'],
                'label' => $class['label'],
            ])->values())
            ->toArray();

        $analyticsPayload = $this->buildGradesAnalyticsPayload(
            $selectedSchoolYearId,
            $selectedGradeLevelId,
            $selectedClassId
        );

        if ($request->expectsJson()) {
            return response()->json(array_merge($analyticsPayload, [
                'schoolYearId' => $selectedSchoolYearId,
                'schoolYearLabel' => $currentSchoolYear?->name ?? 'N/A',
                'selectedGradeLevelId' => $selectedGradeLevelId,
                'selectedClassId' => $selectedClassId,
                'classOptionsMap' => $classOptionsMap,
            ]));
        }

        return view('admin.reports.grades', array_merge($analyticsPayload, [
            'gradeLevels' => $gradeLevels,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'currentSchoolYear' => $currentSchoolYear,
            'selectedGradeLevelId' => $selectedGradeLevelId,
            'selectedClassId' => $selectedClassId,
            'classOptionsMap' => $classOptionsMap,
        ]));
    }

    /**
     * Show grades detail view based on card type.
     * Types: records, passing, highest, average
     */
    public function gradesDetail(Request $request, string $type)
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $schoolYears = SchoolYear::orderByDesc('start_date')->get();

        $activeSchoolYear = $schoolYears->firstWhere('is_active', true)
            ?? $schoolYears->first();

        $selectedSchoolYearId = (int) $request->input('school_year_id', $activeSchoolYear?->id);
        $currentSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId)
            ?? $activeSchoolYear;

        $selectedSchoolYearId = $currentSchoolYear?->id;
        $selectedGradeLevelId = $request->filled('grade_level_id') ? (int) $request->input('grade_level_id') : null;
        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;

        if ($selectedClassId && !$selectedGradeLevelId) {
            $selectedClassId = null;
        }

        $classes = $this->fetchClassesForSchoolYear($selectedSchoolYearId);
        $classOptionsMap = $classes
            ->groupBy(fn($class) => $class['grade_level_id'] ?? 'unassigned')
            ->map(fn($group) => $group->map(fn($class) => [
                'id' => $class['id'],
                'label' => $class['label'],
            ])->values())
            ->toArray();

        $data = [];
        $title = '';
        $description = '';
        $filterType = null;

        switch ($type) {
            case 'records':
                $title = 'All Grade Records';
                $description = 'Complete list of all grade entries for the selected school year.';
                break;

            case 'passing':
                $title = 'Passing Grades';
                $description = 'All grade records with scores of 75 and above.';
                $filterType = 'passing';
                break;

            case 'highest':
                $title = 'Highest Grades';
                $description = 'Records with the highest grade scores.';
                $filterType = 'highest';
                break;

            case 'average':
                $title = 'Grade Analysis by Subject';
                $description = 'Average grades breakdown by subject.';
                $filterType = 'average';
                break;

            default:
                abort(404, 'Invalid detail type.');
        }

        $data = $this->getGradeRecordsData($selectedSchoolYearId, $selectedGradeLevelId, $selectedClassId, $filterType);

        return view('admin.reports.grades-detail', [
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevelId' => $selectedGradeLevelId,
            'selectedClassId' => $selectedClassId,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'currentSchoolYear' => $currentSchoolYear,
            'classOptionsMap' => $classOptionsMap,
        ]);
    }

    /**
     * Get grade records for the detail view.
     */
    private function getGradeRecordsData(?int $schoolYearId, ?int $gradeLevelId, ?int $classId, ?string $filterType): array
    {
        $baseQuery = Grade::query()
            ->select(
                'grades.id',
                'grades.grade',
                'grades.quarter',
                'students.id as student_id',
                'students.lrn',
                'students.first_name',
                'students.last_name',
                'subjects.name as subject_name',
                'sections.name as section_name',
                'grade_levels.name as grade_level_name',
                'grade_levels.level as grade_level'
            )
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->leftJoin('enrollments', function ($join) {
                $join->on('enrollments.school_year_id', '=', 'grades.school_year_id')
                    ->whereRaw('(enrollments.student_profile_id = grades.student_profile_id OR enrollments.student_id = grades.student_id)');
            })
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($schoolYearId, fn($q) => $q->where('grades.school_year_id', $schoolYearId))
            ->when($gradeLevelId, fn($q) => $q->where('grade_levels.id', $gradeLevelId))
            ->when($classId, fn($q) => $q->where('classes.id', $classId));

        // Get summary stats
        $summaryQuery = Grade::query()
            ->leftJoin('enrollments', function ($join) {
                $join->on('enrollments.school_year_id', '=', 'grades.school_year_id')
                    ->whereRaw('(enrollments.student_profile_id = grades.student_profile_id OR enrollments.student_id = grades.student_id)');
            })
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($schoolYearId, fn($q) => $q->where('grades.school_year_id', $schoolYearId))
            ->when($gradeLevelId, fn($q) => $q->where('grade_levels.id', $gradeLevelId))
            ->when($classId, fn($q) => $q->where('classes.id', $classId))
            ->selectRaw("
                COUNT(*) as total,
                AVG(grades.grade) as average,
                MAX(grades.grade) as highest,
                MIN(grades.grade) as lowest,
                SUM(CASE WHEN grades.grade >= 75 THEN 1 ELSE 0 END) as passing,
                SUM(CASE WHEN grades.grade < 75 THEN 1 ELSE 0 END) as failing
            ")
            ->first();

        $total = (int) ($summaryQuery->total ?? 0);
        $passingCount = (int) ($summaryQuery->passing ?? 0);
        $passingRate = $total > 0 ? round(($passingCount / $total) * 100, 1) : 0;

        // Apply filter based on type
        if ($filterType === 'passing') {
            $baseQuery->where('grades.grade', '>=', 75);
        } elseif ($filterType === 'highest') {
            $maxGrade = $summaryQuery->highest;
            if ($maxGrade) {
                $baseQuery->where('grades.grade', '=', $maxGrade);
            }
        }

        // For average type, get subject-level aggregation
        if ($filterType === 'average') {
            $subjectStats = Grade::query()
                ->select(
                    'subjects.id as subject_id',
                    'subjects.name as subject_name',
                    DB::raw('COUNT(*) as records'),
                    DB::raw('AVG(grades.grade) as average'),
                    DB::raw('MAX(grades.grade) as highest'),
                    DB::raw('MIN(grades.grade) as lowest'),
                    DB::raw("SUM(CASE WHEN grades.grade >= 75 THEN 1 ELSE 0 END) as passing")
                )
                ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
                ->leftJoin('enrollments', function ($join) {
                    $join->on('enrollments.school_year_id', '=', 'grades.school_year_id')
                        ->whereRaw('(enrollments.student_profile_id = grades.student_profile_id OR enrollments.student_id = grades.student_id)');
                })
                ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
                ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
                ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
                ->when($schoolYearId, fn($q) => $q->where('grades.school_year_id', $schoolYearId))
                ->when($gradeLevelId, fn($q) => $q->where('grade_levels.id', $gradeLevelId))
                ->when($classId, fn($q) => $q->where('classes.id', $classId))
                ->groupBy('subjects.id', 'subjects.name')
                ->orderByDesc('average')
                ->get();

            return [
                'subjects' => $subjectStats->map(function ($row) {
                    $passingRate = $row->records > 0 ? round(($row->passing / $row->records) * 100, 1) : 0;
                    return [
                        'subject_id' => $row->subject_id,
                        'subject_name' => $row->subject_name,
                        'records' => (int) $row->records,
                        'average' => round($row->average, 1),
                        'highest' => round($row->highest, 1),
                        'lowest' => round($row->lowest, 1),
                        'passing_rate' => $passingRate,
                    ];
                })->toArray(),
                'total' => $total,
                'average' => round($summaryQuery->average ?? 0, 1),
                'highest' => round($summaryQuery->highest ?? 0, 1),
                'lowest' => round($summaryQuery->lowest ?? 0, 1),
                'passing' => $passingCount,
                'passing_rate' => $passingRate,
                'is_subject_view' => true,
            ];
        }

        $records = $baseQuery
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->orderBy('students.last_name')
            ->orderByDesc('grades.grade')
            ->limit(500)
            ->get();

        return [
            'records' => $records->map(function ($row) {
                $status = $row->grade >= 75 ? 'Passing' : 'Failing';
                $statusClass = $row->grade >= 75 ? 'success' : 'danger';
                return [
                    'id' => $row->id,
                    'student_id' => $row->student_id,
                    'lrn' => $row->lrn ?? 'N/A',
                    'full_name' => $row->last_name . ', ' . $row->first_name,
                    'grade_level' => $row->grade_level_name ?? ('Grade ' . ($row->grade_level ?? 'N/A')),
                    'section' => $row->section_name ?? 'Unassigned',
                    'subject' => $row->subject_name ?? 'N/A',
                    'quarter' => $row->quarter ?? 'N/A',
                    'grade' => round($row->grade, 1),
                    'status' => $status,
                    'status_class' => $statusClass,
                ];
            })->toArray(),
            'total' => $total,
            'average' => round($summaryQuery->average ?? 0, 1),
            'highest' => round($summaryQuery->highest ?? 0, 1),
            'lowest' => round($summaryQuery->lowest ?? 0, 1),
            'passing' => $passingCount,
            'passing_rate' => $passingRate,
            'filtered_count' => $records->count(),
            'is_subject_view' => false,
        ];
    }

    /**
     * Export grade records to Excel/CSV.
     */
    public function exportGrades(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $schoolYearId = $request->input('school_year_id');
        $gradeLevelId = $request->input('grade_level_id');
        $classId = $request->input('class_id');
        $filterType = $request->input('filter');

        if (!$schoolYearId) {
            $fallbackYear = SchoolYear::query()->where('is_active', true)->first()
                ?? SchoolYear::query()->orderByDesc('end_date')->first();
            $schoolYearId = $fallbackYear?->id;
        }

        $schoolYearLabel = $schoolYearId ? optional(SchoolYear::find($schoolYearId))->name : null;

        $filenameParts = ['grades_report'];
        if ($filterType) {
            $filenameParts[] = strtolower($filterType);
        }
        if ($schoolYearLabel) {
            $filenameParts[] = str_replace(' ', '_', strtolower($schoolYearLabel));
        }
        $filenameParts[] = now()->format('Y-m-d');

        $filename = implode('_', array_filter($filenameParts));
        $export = new \App\Exports\GradesExport(
            $schoolYearId ? (int) $schoolYearId : null,
            $gradeLevelId ? (int) $gradeLevelId : null,
            $classId ? (int) $classId : null,
            $filterType
        );

        switch ($format) {
            case 'csv':
                return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
            case 'xlsx':
            default:
                return Excel::download($export, $filename . '.xlsx');
        }
    }


    public function cumulative(Request $request)
    {
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $schoolYears = SchoolYear::orderByDesc('start_date')->get();

        $activeSchoolYear = $schoolYears->firstWhere('is_active', true)
            ?? $schoolYears->first();

        $selectedSchoolYearId = (int) $request->input('school_year_id', $activeSchoolYear?->id);
        $currentSchoolYear = $schoolYears->firstWhere('id', $selectedSchoolYearId)
            ?? $activeSchoolYear;

        $selectedSchoolYearId = $currentSchoolYear?->id;
        $selectedGradeLevelId = $request->filled('grade_level_id') ? (int) $request->input('grade_level_id') : null;
        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;

        if ($selectedClassId && !$selectedGradeLevelId) {
            $selectedClassId = null;
        }

        $classes = $this->fetchClassesForSchoolYear($selectedSchoolYearId);
        $classOptionsMap = $classes
            ->groupBy(fn($class) => $class['grade_level_id'] ?? 'unassigned')
            ->map(fn($group) => $group->map(fn($class) => [
                'id' => $class['id'],
                'label' => $class['label'],
            ])->values())
            ->toArray();

        $analyticsPayload = $this->buildCumulativeAnalyticsPayload(
            $selectedSchoolYearId,
            $selectedGradeLevelId,
            $selectedClassId
        );

        $responsePayload = array_merge($analyticsPayload, [
            'schoolYearId' => $selectedSchoolYearId,
            'schoolYearLabel' => $currentSchoolYear?->name ?? 'N/A',
            'selectedGradeLevelId' => $selectedGradeLevelId,
            'selectedClassId' => $selectedClassId,
            'classOptionsMap' => $classOptionsMap,
        ]);

        if ($request->expectsJson()) {
            return response()->json($responsePayload);
        }

        return view('admin.reports.cumulative', array_merge($responsePayload, [
            'gradeLevels' => $gradeLevels,
            'schoolYears' => $schoolYears,
            'activeSchoolYear' => $activeSchoolYear,
            'currentSchoolYear' => $currentSchoolYear,
        ]));
    }

    /**
     * Export cumulative report to Excel (multi-sheet).
     */
    public function exportCumulative(Request $request)
    {
        $format = $request->input('format', 'xlsx');
        $schoolYearId = $request->input('school_year_id');
        $gradeLevelId = $request->input('grade_level_id');
        $classId = $request->input('class_id');

        if (!$schoolYearId) {
            $fallbackYear = SchoolYear::query()->where('is_active', true)->first()
                ?? SchoolYear::query()->orderByDesc('end_date')->first();
            $schoolYearId = $fallbackYear?->id;
        }

        $schoolYearLabel = $schoolYearId ? optional(SchoolYear::find($schoolYearId))->name : null;

        $filenameParts = ['cumulative_report'];
        if ($schoolYearLabel) {
            $filenameParts[] = str_replace(' ', '_', strtolower($schoolYearLabel));
        }
        $filenameParts[] = now()->format('Y-m-d');

        $filename = implode('_', array_filter($filenameParts));
        $export = new \App\Exports\CumulativeExport(
            $schoolYearId ? (int) $schoolYearId : null,
            $gradeLevelId ? (int) $gradeLevelId : null,
            $classId ? (int) $classId : null
        );

        switch ($format) {
            case 'csv':
                // CSV doesn't support multiple sheets, export first sheet only
                return Excel::download(new \App\Exports\EnrolleesExport(
                    $schoolYearId ? (int) $schoolYearId : null,
                    $gradeLevelId ? (int) $gradeLevelId : null
                ), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
            case 'xlsx':
            default:
                return Excel::download($export, $filename . '.xlsx');
        }
    }


    private function buildAttendanceAnalyticsPayload(?int $schoolYearId, ?int $gradeLevelId, ?int $classId): array
    {
        $baseQuery = Attendance::query()
            ->leftJoin('classes', 'attendances.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id');

        if ($schoolYearId) {
            $baseQuery->where('attendances.school_year_id', $schoolYearId);
        }

        if ($gradeLevelId) {
            $baseQuery->where('grade_levels.id', $gradeLevelId);
        }

        if ($classId) {
            $baseQuery->where('classes.id', $classId);
        }

        $statusCounts = (clone $baseQuery)
            ->select('attendances.status as status_label', DB::raw('COUNT(*) as total'))
            ->groupBy('attendances.status')
            ->pluck('total', 'status_label');

        $statusCounts = collect($statusCounts)->mapWithKeys(function ($total, $status) {
            $key = $status ? strtolower($status) : 'unknown';
            return [$key => (int) $total];
        });

        $totalRecords = (int) $statusCounts->sum();
        $presentCount = $statusCounts->get('present', 0);
        $absentCount = $statusCounts->get('absent', 0);
        $lateCount = $statusCounts->get('late', 0);

        $attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;
        $absenceRate = $totalRecords > 0 ? round(($absentCount / $totalRecords) * 100, 1) : 0;
        $lateRate = $totalRecords > 0 ? round(($lateCount / $totalRecords) * 100, 1) : 0;

        $statusDistribution = $statusCounts->map(function ($total, $status) use ($totalRecords) {
            return [
                'status' => $status,
                'label' => ucfirst(str_replace('_', ' ', $status)),
                'total' => (int) $total,
                'percentage' => $totalRecords > 0 ? round(($total / $totalRecords) * 100, 1) : 0,
            ];
        })->values()->toArray();

        $monthlyRows = (clone $baseQuery)
            ->select(
                DB::raw("DATE_FORMAT(COALESCE(attendances.date, attendances.created_at), '%Y-%m') as ym"),
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_total"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_total"),
                DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late_total"),
                DB::raw('COUNT(*) as total_records')
            )
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $monthlyTrend = [
            'labels' => $monthlyRows->map(function ($row) {
                if (!$row->ym) {
                    return 'N/A';
                }

                try {
                    return Carbon::createFromFormat('Y-m', $row->ym)->format('M Y');
                } catch (\Exception $e) {
                    return $row->ym;
                }
            })->toArray(),
            'present' => $monthlyRows->map(fn($row) => (int) $row->present_total)->toArray(),
            'absent' => $monthlyRows->map(fn($row) => (int) $row->absent_total)->toArray(),
            'late' => $monthlyRows->map(fn($row) => (int) $row->late_total)->toArray(),
        ];

        $recentStartDate = Carbon::now()->subDays(13)->toDateString();

        $recentRows = (clone $baseQuery)
            ->select(
                DB::raw("DATE(COALESCE(attendances.date, attendances.created_at)) as day"),
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_total"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_total"),
                DB::raw('COUNT(*) as total_records')
            )
            ->whereRaw("DATE(COALESCE(attendances.date, attendances.created_at)) >= ?", [$recentStartDate])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $dailySparkline = [
            'labels' => $recentRows->map(function ($row) {
                if (!$row->day) {
                    return 'N/A';
                }

                try {
                    return Carbon::parse($row->day)->format('M d');
                } catch (\Exception $e) {
                    return $row->day;
                }
            })->toArray(),
            'present' => $recentRows->map(fn($row) => (int) $row->present_total)->toArray(),
            'absent' => $recentRows->map(fn($row) => (int) $row->absent_total)->toArray(),
            'attendance_rate' => $recentRows->map(function ($row) {
                return $row->total_records > 0
                    ? round(($row->present_total / $row->total_records) * 100, 1)
                    : 0;
            })->toArray(),
        ];

        $classLeaderboard = (clone $baseQuery)
            ->select(
                'classes.id as class_id',
                'sections.name as section_name',
                'grade_levels.name as grade_label',
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_total"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_total"),
                DB::raw('COUNT(*) as total_records')
            )
            ->whereNotNull('classes.id')
            ->groupBy('classes.id', 'sections.name', 'grade_levels.name')
            ->havingRaw('COUNT(*) > 0')
            ->orderByDesc(DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)"))
            ->limit(6)
            ->get()
            ->map(function ($row) {
                $rate = $row->total_records > 0 ? round(($row->present_total / $row->total_records) * 100, 1) : 0;
                $labelPieces = array_filter([
                    $row->section_name,
                    $row->grade_label,
                ]);

                return [
                    'class_id' => (int) $row->class_id,
                    'label' => implode(' • ', $labelPieces) ?: 'Class',
                    'attendance_rate' => $rate,
                    'present' => (int) $row->present_total,
                    'absent' => (int) $row->absent_total,
                    'total' => (int) $row->total_records,
                ];
            })
            ->values()
            ->toArray();

        $todayDate = Carbon::now()->toDateString();
        $todaySnapshotRow = (clone $baseQuery)
            ->select(
                DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_total"),
                DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_total"),
                DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late_total"),
                DB::raw('COUNT(*) as total_records')
            )
            ->whereRaw("DATE(COALESCE(attendances.date, attendances.created_at)) = ?", [$todayDate])
            ->first();

        $todaySnapshot = [
            'date' => Carbon::now()->format('M d, Y'),
            'present' => (int) ($todaySnapshotRow->present_total ?? 0),
            'absent' => (int) ($todaySnapshotRow->absent_total ?? 0),
            'late' => (int) ($todaySnapshotRow->late_total ?? 0),
            'total' => (int) ($todaySnapshotRow->total_records ?? 0),
        ];

        return [
            'attendanceSummary' => [
                'total_records' => $totalRecords,
                'present' => $presentCount,
                'absent' => $absentCount,
                'late' => $lateCount,
                'present_rate' => $attendanceRate,
                'absence_rate' => $absenceRate,
                'late_rate' => $lateRate,
            ],
            'statusDistribution' => $statusDistribution,
            'monthlyTrend' => $monthlyTrend,
            'dailySparkline' => $dailySparkline,
            'classLeaderboard' => $classLeaderboard,
            'todaySnapshot' => $todaySnapshot,
        ];
    }


    private function buildCumulativeAnalyticsPayload(?int $schoolYearId, ?int $gradeLevelId, ?int $classId): array
    {
        $enrollmentSnapshot = $this->buildEnrollmentSnapshot($schoolYearId, $gradeLevelId, $classId);
        $attendancePayload = $this->buildAttendanceAnalyticsPayload($schoolYearId, $gradeLevelId, $classId);
        $gradesPayload = $this->buildGradesAnalyticsPayload($schoolYearId, $gradeLevelId, $classId);

        $attendanceSummary = $attendancePayload['attendanceSummary'] ?? [
            'present_rate' => 0,
            'total_records' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'absence_rate' => 0,
            'late_rate' => 0,
        ];

        $gradesSummary = $gradesPayload['summary'] ?? [
            'average' => 0,
            'highest' => 0,
            'lowest' => 0,
            'records' => 0,
            'passing_rate' => 0,
        ];

        $summaryCards = [
            'students' => [
                'label' => 'Enrolled Students',
                'value' => $enrollmentSnapshot['totals']['students'] ?? 0,
                'suffix' => '',
                'precision' => 0,
            ],
            'classes' => [
                'label' => 'Active Classes',
                'value' => $enrollmentSnapshot['totals']['classes'] ?? 0,
                'suffix' => '',
                'precision' => 0,
            ],
            'attendance_rate' => [
                'label' => 'Attendance Rate',
                'value' => $attendanceSummary['present_rate'] ?? 0,
                'suffix' => '%',
                'precision' => 1,
            ],
            'average_grade' => [
                'label' => 'Average Grade',
                'value' => $gradesSummary['average'] ?? 0,
                'suffix' => '',
                'precision' => 1,
            ],
        ];

        return [
            'summaryCards' => $summaryCards,
            'enrollmentSnapshot' => $enrollmentSnapshot,
            'attendanceTrend' => $attendancePayload['monthlyTrend'] ?? [
                'labels' => [],
                'present' => [],
                'absent' => [],
                'late' => [],
            ],
            'attendanceSummary' => $attendanceSummary,
            'gradesSummary' => $gradesSummary,
            'gradeDistribution' => $gradesPayload['gradeDistribution'] ?? [],
            'gradeLevelBreakdown' => $gradesPayload['gradeLevelBreakdown'] ?? [],
            'classLeaderboard' => $attendancePayload['classLeaderboard'] ?? [],
        ];
    }


    private function buildEnrollmentSnapshot(?int $schoolYearId, ?int $gradeLevelId, ?int $classId): array
    {
        $baseQuery = Enrollment::query()
            ->leftJoin('students', 'enrollments.student_id', '=', 'students.id')
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id');

        if ($schoolYearId) {
            $baseQuery->where('enrollments.school_year_id', $schoolYearId);
        }

        if ($gradeLevelId) {
            $baseQuery->where('grade_levels.id', $gradeLevelId);
        }

        if ($classId) {
            $baseQuery->where('classes.id', $classId);
        }

        $totalStudents = (clone $baseQuery)->count();
        $uniqueStudents = (clone $baseQuery)->distinct('enrollments.student_id')->count('enrollments.student_id');
        $classCount = (clone $baseQuery)
            ->whereNotNull('classes.id')
            ->distinct('classes.id')
            ->count('classes.id');

        $averagePerClass = $classCount > 0 ? round($totalStudents / $classCount, 1) : 0;

        $gradeBreakdownRows = (clone $baseQuery)
            ->selectRaw('COALESCE(grade_levels.id, 0) as grade_level_id')
            ->selectRaw('grade_levels.name as grade_name')
            ->selectRaw('grade_levels.level as grade_value')
            ->selectRaw('COUNT(enrollments.id) as total_students')
            ->groupByRaw('COALESCE(grade_levels.id, 0), grade_levels.name, grade_levels.level')
            ->orderByRaw('grade_levels.level IS NULL, grade_levels.level')
            ->get();

        $gradeBreakdown = $gradeBreakdownRows->map(function ($row) use ($totalStudents) {
            $label = $row->grade_name ?: ($row->grade_value ? 'Grade ' . $row->grade_value : 'Unassigned');

            return [
                'grade_level_id' => (int) ($row->grade_level_id ?? 0),
                'label' => $label,
                'total' => (int) ($row->total_students ?? 0),
                'percentage' => $totalStudents > 0
                    ? round(($row->total_students / $totalStudents) * 100, 1)
                    : 0,
            ];
        })->values()->toArray();

        $classDistributionRows = (clone $baseQuery)
            ->select('classes.id as class_id', 'sections.name as section_name', 'grade_levels.name as grade_label')
            ->selectRaw('COUNT(enrollments.id) as total_students')
            ->whereNotNull('classes.id')
            ->groupBy('classes.id', 'sections.name', 'grade_levels.name')
            ->orderByDesc('total_students')
            ->limit(6)
            ->get();

        $classDistribution = $classDistributionRows->map(function ($row) {
            $labelPieces = array_filter([
                $row->section_name,
                $row->grade_label,
            ]);

            return [
                'class_id' => (int) $row->class_id,
                'label' => implode(' • ', $labelPieces) ?: 'Class',
                'students' => (int) ($row->total_students ?? 0),
            ];
        })->values()->toArray();

        $genderRows = (clone $baseQuery)
            ->selectRaw("LOWER(COALESCE(students.gender, 'unspecified')) as gender_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('gender_key')
            ->get();

        $genderBreakdown = $genderRows->map(function ($row) use ($uniqueStudents) {
            $key = $row->gender_key ?? 'unspecified';
            $label = $key === 'unspecified'
                ? 'Unspecified'
                : ucfirst($key);

            return [
                'label' => $label,
                'total' => (int) ($row->total ?? 0),
                'percentage' => $uniqueStudents > 0
                    ? round(($row->total / $uniqueStudents) * 100, 1)
                    : 0,
            ];
        })->values()->toArray();

        $monthlyRows = (clone $baseQuery)
            ->select(DB::raw("DATE_FORMAT(COALESCE(enrollments.enrollment_date, enrollments.created_at), '%Y-%m') as ym"))
            ->selectRaw('COUNT(*) as total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $monthlyTrend = [
            'labels' => $monthlyRows->map(function ($row) {
                if (!$row->ym) {
                    return 'N/A';
                }

                try {
                    return Carbon::createFromFormat('Y-m', $row->ym)->format('M Y');
                } catch (\Exception $e) {
                    return $row->ym;
                }
            })->toArray(),
            'totals' => $monthlyRows->map(fn($row) => (int) ($row->total ?? 0))->toArray(),
        ];

        return [
            'totals' => [
                'students' => $totalStudents,
                'unique_students' => $uniqueStudents,
                'classes' => $classCount,
                'average_per_class' => $averagePerClass,
            ],
            'gradeBreakdown' => $gradeBreakdown,
            'classDistribution' => $classDistribution,
            'genderBreakdown' => $genderBreakdown,
            'monthlyTrend' => $monthlyTrend,
        ];
    }


    private function fetchClassesForSchoolYear(?int $schoolYearId): Collection
    {
        if (!$schoolYearId) {
            return collect();
        }

        return Classes::query()
            ->select(
                'classes.id',
                'classes.section_id',
                'sections.name as section_name',
                'grade_levels.id as grade_level_id',
                'grade_levels.name as grade_label',
                'grade_levels.level as grade_level'
            )
            ->join('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->where('classes.school_year_id', $schoolYearId)
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->get()
            ->map(function ($row) {
                $gradeLabel = $row->grade_label ?? ($row->grade_level ? 'Grade ' . $row->grade_level : 'Unassigned');

                return [
                    'id' => (int) $row->id,
                    'section_id' => (int) $row->section_id,
                    'section_name' => $row->section_name,
                    'grade_level_id' => $row->grade_level_id ? (int) $row->grade_level_id : null,
                    'grade_label' => $gradeLabel,
                    'label' => trim(($row->section_name ?? 'Class') . ' (' . $gradeLabel . ')'),
                ];
            });
    }


    private function buildGradesAnalyticsPayload(?int $schoolYearId, ?int $gradeLevelId, ?int $classId): array
    {
        $baseQuery = Grade::query()
            ->leftJoin('students', 'grades.student_id', '=', 'students.id')
            ->leftJoin('enrollments', function ($join) use ($schoolYearId) {
                $join->on('enrollments.school_year_id', '=', 'grades.school_year_id')
                    ->whereRaw('(enrollments.student_profile_id = grades.student_profile_id OR enrollments.student_id = grades.student_id)');

                if ($schoolYearId) {
                    $join->where('enrollments.school_year_id', $schoolYearId);
                }
            })
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->leftJoin('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->whereNotNull('grades.grade');

        if ($schoolYearId) {
            $baseQuery->where('grades.school_year_id', $schoolYearId);
        }

        if ($gradeLevelId) {
            $baseQuery->where('grade_levels.id', $gradeLevelId);
        }

        if ($classId) {
            $baseQuery->where('classes.id', $classId);
        }

        $aggregates = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw('AVG(grades.grade) as avg_grade')
            ->selectRaw('MAX(grades.grade) as max_grade')
            ->selectRaw('MIN(grades.grade) as min_grade')
            ->first();

        $passingCount = (clone $baseQuery)
            ->where('grades.grade', '>=', 75)
            ->count();

        $totalRecords = (int) ($aggregates->total_records ?? 0);

        $summary = [
            'average' => $totalRecords > 0 ? round((float) ($aggregates->avg_grade ?? 0), 1) : 0,
            'highest' => $totalRecords > 0 ? round((float) ($aggregates->max_grade ?? 0), 1) : 0,
            'lowest' => $totalRecords > 0 ? round((float) ($aggregates->min_grade ?? 0), 1) : 0,
            'records' => $totalRecords,
            'passing_rate' => $totalRecords > 0 ? round(($passingCount / $totalRecords) * 100, 1) : 0,
        ];

        $distributionRow = (clone $baseQuery)
            ->selectRaw("SUM(CASE WHEN grades.grade >= 90 THEN 1 ELSE 0 END) as g90")
            ->selectRaw("SUM(CASE WHEN grades.grade >= 85 AND grades.grade < 90 THEN 1 ELSE 0 END) as g85")
            ->selectRaw("SUM(CASE WHEN grades.grade >= 75 AND grades.grade < 85 THEN 1 ELSE 0 END) as g75")
            ->selectRaw("SUM(CASE WHEN grades.grade >= 70 AND grades.grade < 75 THEN 1 ELSE 0 END) as g70")
            ->selectRaw("SUM(CASE WHEN grades.grade < 70 THEN 1 ELSE 0 END) as gBelow70")
            ->first();

        $gradeDistribution = collect([
            ['label' => '90-100', 'key' => 'g90'],
            ['label' => '85-89', 'key' => 'g85'],
            ['label' => '75-84', 'key' => 'g75'],
            ['label' => '70-74', 'key' => 'g70'],
            ['label' => 'Below 70', 'key' => 'gBelow70'],
        ])->map(function ($bucket) use ($distributionRow, $totalRecords) {
            $total = (int) ($distributionRow->{$bucket['key']} ?? 0);

            return [
                'label' => $bucket['label'],
                'total' => $total,
                'percentage' => $totalRecords > 0 ? round(($total / $totalRecords) * 100, 1) : 0,
            ];
        })->toArray();

        $quarterOrder = ['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'];
        $quarterTrendRows = (clone $baseQuery)
            ->select('grades.quarter')
            ->selectRaw('AVG(grades.grade) as avg_grade')
            ->groupBy('grades.quarter')
            ->orderByRaw("FIELD(grades.quarter, '1st Quarter','2nd Quarter','3rd Quarter','4th Quarter')")
            ->get();

        $quarterTrend = [
            'labels' => $quarterTrendRows->map(fn($row) => $row->quarter ?? 'N/A')->toArray(),
            'averages' => $quarterTrendRows->map(fn($row) => round((float) ($row->avg_grade ?? 0), 1))->toArray(),
        ];

        $subjectLeaderboard = (clone $baseQuery)
            ->select('subjects.id as subject_id', 'subjects.name as subject_name')
            ->selectRaw('AVG(grades.grade) as avg_grade')
            ->whereNotNull('subjects.id')
            ->groupBy('subjects.id', 'subjects.name')
            ->orderByDesc('avg_grade')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                return [
                    'subject_id' => (int) $row->subject_id,
                    'label' => $row->subject_name ?? 'Subject',
                    'average' => round((float) ($row->avg_grade ?? 0), 1),
                ];
            })->toArray();

        $studentLeaderboard = (clone $baseQuery)
            ->select('students.id as student_id', 'students.first_name', 'students.last_name')
            ->selectRaw('AVG(grades.grade) as avg_grade')
            ->whereNotNull('students.id')
            ->groupBy('students.id', 'students.first_name', 'students.last_name')
            ->orderByDesc('avg_grade')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));

                return [
                    'student_id' => (int) $row->student_id,
                    'label' => $name !== '' ? $name : 'Student',
                    'average' => round((float) ($row->avg_grade ?? 0), 1),
                ];
            })->toArray();

        $classLeaderboard = (clone $baseQuery)
            ->select('classes.id as class_id', 'sections.name as section_name', 'grade_levels.name as grade_label')
            ->selectRaw('AVG(grades.grade) as avg_grade')
            ->whereNotNull('classes.id')
            ->groupBy('classes.id', 'sections.name', 'grade_levels.name')
            ->orderByDesc('avg_grade')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                $labelPieces = array_filter([
                    $row->section_name,
                    $row->grade_label,
                ]);

                return [
                    'class_id' => (int) $row->class_id,
                    'label' => implode(' • ', $labelPieces) ?: 'Class',
                    'average' => round((float) ($row->avg_grade ?? 0), 1),
                ];
            })->toArray();

        $gradeLevelBreakdown = (clone $baseQuery)
            ->select('grade_levels.id as grade_level_id', 'grade_levels.name as grade_name', 'grade_levels.level as grade_value')
            ->selectRaw('AVG(grades.grade) as avg_grade')
            ->selectRaw('COUNT(*) as total_records')
            ->whereNotNull('grade_levels.id')
            ->groupBy('grade_levels.id', 'grade_levels.name', 'grade_levels.level')
            ->orderBy('grade_levels.level')
            ->get()
            ->map(function ($row) {
                $label = $row->grade_name ?: ($row->grade_value ? 'Grade ' . $row->grade_value : 'Grade Level');

                return [
                    'grade_level_id' => (int) $row->grade_level_id,
                    'label' => $label,
                    'average' => round((float) ($row->avg_grade ?? 0), 1),
                    'records' => (int) ($row->total_records ?? 0),
                ];
            })->values()->toArray();

        return [
            'summary' => $summary,
            'gradeDistribution' => $gradeDistribution,
            'quarterTrend' => $quarterTrend,
            'subjectLeaderboard' => $subjectLeaderboard,
            'studentLeaderboard' => $studentLeaderboard,
            'classLeaderboard' => $classLeaderboard,
            'gradeLevelBreakdown' => $gradeLevelBreakdown,
        ];
    }






    public function leastLearned(Request $request)
    {
        // For admin, we'll show all LLC data across all teachers
        $sections = Section::with('gradeLevel')->get();
        $subjects = Subject::all();
        $quarters = ['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'];

        $selectedSection = $request->input('section');
        $selectedSubject = $request->input('subject');
        $selectedQuarter = $request->input('quarter', '1st Quarter');

        $llcData = null;
        $llcItems = collect();

        if ($selectedSection && $selectedSubject) {
            // Find the LLC record for the selected filters
            $llcData = LLC::with(['subject', 'teacher.user', 'schoolYear'])
                ->where('section_id', $selectedSection)
                ->where('subject_id', $selectedSubject)
                ->where('quarter', $this->mapQuarterToNumber($selectedQuarter))
                ->first();

            if ($llcData) {
                $llcItems = $llcData->llcItems()
                    ->orderByDesc('students_wrong')
                    ->get();
            }
        }

        return view('admin.reports.least_learned', compact(
            'sections',
            'subjects',
            'quarters',
            'selectedSection',
            'selectedSubject',
            'selectedQuarter',
            'llcData',
            'llcItems'
        ));
    }

    /**
     * Convert quarter name to number
     */
    private function mapQuarterToNumber($quarter)
    {
        $map = [
            '1st Quarter' => 1,
            '2nd Quarter' => 2,
            '3rd Quarter' => 3,
            '4th Quarter' => 4,
        ];

        return $map[$quarter] ?? 1;
    }
    /**
     * Map grading period inputs to the stored quarter format.
     */
    private function mapGradingPeriod($value)
    {
        $map = [
            'first' => '1st Quarter',
            'second' => '2nd Quarter',
            'third' => '3rd Quarter',
            'fourth' => '4th Quarter',
            '1' => '1st Quarter',
            '2' => '2nd Quarter',
            '3' => '3rd Quarter',
            '4' => '4th Quarter',
        ];

        if (is_string($value) && stripos($value, 'quarter') !== false) {
            return $value; // already in desired format
        }

        return $map[strtolower((string)$value)] ?? $value;
    }
}
