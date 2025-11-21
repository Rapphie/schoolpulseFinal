<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\Teacher;
use App\Models\Attendance;
use App\Models\LLC;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EnrolleesExport;
use App\Exports\AttendanceExport;
use App\Exports\GradesExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;

class AdminReportController extends Controller
{
    public function enrollees(Request $request)
    {
        $grades = range(1, 12); // UI helper; levels come from grade_levels
        $selectedGrade = $request->input('grade');

        $activeSchoolYear = SchoolYear::query()->where('is_active', true)->first();

        if (!$activeSchoolYear) {
            $activeSchoolYear = SchoolYear::query()->orderByDesc('end_date')->first();
        }

        $activeSchoolYearId = $activeSchoolYear?->id;

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
            ->when($activeSchoolYearId, function ($query) use ($activeSchoolYearId) {
                $query->where('enrollments.school_year_id', $activeSchoolYearId);
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
                'students_count' => (int)$row->students_count,
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

                $totalStudents = (int)$gradeSections->sum('students_count');
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
                        $students = (int)$section['students_count'];
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

        $totalStudents = (int)$classBreakdown->sum('students_count');
        $totalSections = $classBreakdown->count();
        $averagePerSection = $totalSections > 0 ? round($totalStudents / $totalSections, 1) : 0;
        $largestSection = $classBreakdown->max('students_count') ?? 0;
        // dd(
        //     $grades,
        //     $selectedGrade,
        //     $sectionsByGrade,
        //     $classChartData,
        //     $gradeChartData,
        //     $totalStudents,
        //     $totalSections,
        //     $averagePerSection,
        //     $largestSection
        // );
        return view('admin.reports.enrollees', compact(
            'grades',
            'selectedGrade',
            'sectionsByGrade',
            'classChartData',
            'gradeChartData',
            'totalStudents',
            'totalSections',
            'averagePerSection',
            'largestSection'
        ));
    }


    public function exportEnrollees(Request $request)
    {
        $grade = $request->input('grade');
        $format = $request->input('format', 'xlsx'); // Default to xlsx

        $filename = 'enrollees_report' . ($grade ? '_grade_' . $grade : '') . '_' . now()->format('Y-m-d');

        switch ($format) {
            case 'csv':
                return Excel::download(new EnrolleesExport($grade), $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
            case 'xlsx':
            default:
                return Excel::download(new EnrolleesExport($grade), $filename . '.xlsx');
        }
    }

    public function attendanceReport(Request $request)
    {
        // Filter by month (YYYY-MM)
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        // Paginated detailed attendance for the selected month
        $attendanceRecords = Attendance::with(['subject', 'student.enrollments.class.section'])
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'desc')
            ->paginate(10);

        // KPI metrics
        $todayPresentCount = Attendance::where('date', Carbon::today()->toDateString())
            ->where('status', 'present')
            ->count();

        $counts = Attendance::select('status', DB::raw('COUNT(*) as cnt'))
            ->whereBetween('date', [$start, $end])
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $presentCount = (int)($counts['present'] ?? 0);
        $absentCount = (int)($counts['absent'] ?? 0);
        $lateCount = (int)($counts['late'] ?? 0);
        $totalAbsences = $absentCount; // monthly absences
        $lateArrivalsCount = $lateCount; // monthly late arrivals

        $totalAttendance = max(1, $presentCount + $absentCount + $lateCount);
        $monthlyAttendanceRate = number_format(($presentCount / $totalAttendance) * 100, 2);

        // Monthly trend data: counts per day by status
        $rawDaily = Attendance::select(
            DB::raw('DATE(date) as d'),
            'status',
            DB::raw('COUNT(*) as cnt')
        )
            ->whereBetween('date', [$start, $end])
            ->groupBy(DB::raw('DATE(date)'), 'status')
            ->orderBy('d')
            ->get();

        $monthlyData = [];
        foreach ($rawDaily as $row) {
            $day = Carbon::parse($row->d)->format('Y-m-d');
            if (!isset($monthlyData[$day])) {
                $monthlyData[$day] = [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                ];
            }
            $status = strtolower($row->status);
            if (isset($monthlyData[$day][$status])) {
                $monthlyData[$day][$status] = (int)$row->cnt;
            }
        }

        return view('admin.reports.attendance', compact(
            'attendanceRecords',
            'todayPresentCount',
            'monthlyAttendanceRate',
            'totalAbsences',
            'lateArrivalsCount',
            'presentCount',
            'absentCount',
            'lateCount',
            'monthlyData'
        ));
    }
    public function attendance(Request $request)
    {
        $sections = Section::all();
        $selectedSection = $request->input('section');
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        $attendanceData = collect();

        if ($selectedSection) {
            $startDate = Carbon::parse($selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($selectedMonth)->endOfMonth();

            $section = Section::find($selectedSection);

            if ($section) {
                // Fetch students enrolled in any class belonging to this section
                $students = Student::whereHas('enrollments', function ($q) use ($selectedSection) {
                    $q->whereHas('class', function ($cq) use ($selectedSection) {
                        $cq->where('section_id', $selectedSection);
                    });
                })->with(['attendances' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }])->get();

                // Attach as a relation for downstream processing
                $section->setRelation('students', $students);

                $attendanceData = $this->processAttendanceData($section, $startDate, $endDate);
            }
        }

        return view('admin.reports.attendance', compact(
            'sections',
            'selectedSection',
            'selectedMonth',
            'attendanceData'
        ));
    }


    private function processAttendanceData($section, $startDate, $endDate)
    {
        $data = collect();
        $dates = [];
        $currentDate = $startDate->copy();

        // Generate all dates in the range
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday()) { // Only include weekdays
                $dates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        foreach ($section->students as $student) {
            $studentData = [
                'id' => $student->id,
                'name' => $student->full_name,
                'lrn' => $student->lrn,
                'attendance' => [],
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0
            ];

            // Initialize attendance for each date
            foreach ($dates as $date) {
                $studentData['attendance'][$date] = [
                    'status' => null,
                    'time_in' => null,
                    // 'time_out' => null,
                    'remarks' => null
                ];
            }

            // Fill in actual attendance data
            foreach ($student->attendances as $attendance) {
                $date = $attendance->date->format('Y-m-d');
                if (array_key_exists($date, $studentData['attendance'])) {
                    $studentData['attendance'][$date] = [
                        'status' => $attendance->status,
                        'time_in' => $attendance->time_in,
                        // 'time_out' => $attendance->time_out,
                        'remarks' => $attendance->remarks
                    ];

                    // Count statuses
                    if (isset($studentData[$attendance->status])) {
                        $studentData[$attendance->status]++;
                    }
                }
            }

            $data->push((object)$studentData);
        }

        return (object)[
            'section' => $section,
            'dates' => $dates,
            'students' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }


    public function exportAttendance(Request $request)
    {
        $sectionId = $request->input('section');
        $month = $request->input('month', now()->format('Y-m'));

        return Excel::download(
            new AttendanceExport($sectionId, $month),
            'attendance_report_' . now()->format('Y-m-d') . '.xlsx'
        );
    }


    public function grades(Request $request)
    {
        $sections = Section::all();
        $subjects = Subject::all();

        $selectedSection = $request->input('section');
        $selectedSubject = $request->input('subject');
        $gradingPeriod = $request->input('grading_period', 'first');
        $quarter = $this->mapGradingPeriod($gradingPeriod);

        $gradesData = collect();
        $averageGrade = 0;
        $highestGrade = 0;
        $lowestGrade = 0;
        $passingRate = 0;
        $grades = collect();
        $gradeDistribution = [
            '90-100' => 0,
            '80-89' => 0,
            '70-79' => 0,
            '60-69' => 0,
            'Below 60' => 0
        ];
        $performanceSummary = [
            'excellent' => 0,
            'good' => 0,
            'average' => 0,
            'needs_improvement' => 0,
            'failing' => 0
        ];

        if ($selectedSection && $selectedSubject) {
            $section = Section::find($selectedSection);

            if ($section) {
                // Fetch students for the section via enrollments and load their grades for the subject/quarter
                $students = Student::whereHas('enrollments', function ($q) use ($selectedSection) {
                    $q->whereHas('class', function ($cq) use ($selectedSection) {
                        $cq->where('section_id', $selectedSection);
                    });
                })->with(['grades' => function ($q) use ($selectedSubject, $quarter) {
                    $q->where('subject_id', $selectedSubject)
                        ->where('quarter', $quarter);
                }])->get();

                $section->setRelation('students', $students);

                $gradesData = $this->processGradesData($section, $selectedSubject, $quarter);

                // Extract data from gradesData for direct use in blade template
                $averageGrade = $gradesData->average ?? 0;
                $highestGrade = $gradesData->highest ?? 0;
                $lowestGrade = $gradesData->lowest ?? 0;

                // Calculate passing rate
                if ($gradesData->students->count() > 0) {
                    $passingCount = $gradesData->students->filter(function ($student) {
                        return $student->grade >= 75;
                    })->count();

                    $passingRate = round(($passingCount / $gradesData->students->count()) * 100);
                }

                // Calculate grade distribution for chart
                $grades = $gradesData->students;
                foreach ($grades as $student) {
                    if ($student->grade >= 90) {
                        $gradeDistribution['90-100']++;
                        $performanceSummary['excellent']++;
                    } elseif ($student->grade >= 80) {
                        $gradeDistribution['80-89']++;
                        $performanceSummary['good']++;
                    } elseif ($student->grade >= 70) {
                        $gradeDistribution['70-79']++;
                        $performanceSummary['average']++;
                    } elseif ($student->grade >= 60) {
                        $gradeDistribution['60-69']++;
                        $performanceSummary['needs_improvement']++;
                    } elseif ($student->grade !== null) {
                        $gradeDistribution['Below 60']++;
                        $performanceSummary['failing']++;
                    }
                }
            }
        }

        return view('admin.reports.grades', compact(
            'sections',
            'subjects',
            'selectedSection',
            'selectedSubject',
            'gradingPeriod',
            'gradesData',
            'averageGrade',
            'highestGrade',
            'lowestGrade',
            'passingRate',
            'grades',
            'gradeDistribution',
            'performanceSummary'
        ));
    }


    private function processGradesData($section, $subjectId, $gradingPeriod)
    {
        $subject = Subject::find($subjectId);

        $students = $section->students->map(function ($student) use ($subjectId, $gradingPeriod) {
            $grade = $student->grades
                ->where('subject_id', $subjectId)
                ->where('quarter', $gradingPeriod)
                ->first();

            return (object)[
                'id' => $student->id,
                'name' => $student->full_name,
                'lrn' => $student->lrn,
                'grade' => $grade ? $grade->grade : null,
                'remarks' => $grade ? $this->getGradeRemarks($grade->grade) : 'No Grade'
            ];
        })->sortBy('name');

        return (object)[
            'section' => $section,
            'subject' => $subject,
            'gradingPeriod' => $gradingPeriod,
            'students' => $students,
            'average' => $students->avg('grade'),
            'highest' => $students->max('grade'),
            'lowest' => $students->min('grade')
        ];
    }


    private function getGradeRemarks($grade)
    {
        if ($grade === null) return 'No Grade';

        if ($grade >= 90) return 'Outstanding';
        if ($grade >= 85) return 'Very Satisfactory';
        if ($grade >= 80) return 'Satisfactory';
        if ($grade >= 75) return 'Fairly Satisfactory';
        return 'Did Not Meet Expectations';
    }


    public function exportGrades(Request $request)
    {
        $sectionId = $request->input('section');
        $subjectId = $request->input('subject');
        $gradingPeriod = $request->input('grading_period', 'first');

        return Excel::download(
            new GradesExport($sectionId, $subjectId, $gradingPeriod),
            'grades_report_' . now()->format('Y-m-d') . '.xlsx'
        );
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


    public function cumulative(Request $request)
    {
        $sections = Section::with('gradeLevel')->get();
        $subjects = Subject::all();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();

        $selectedSection = $request->input('section');
        $selectedSubject = $request->input('subject');
        $selectedSchoolYear = $request->input('school_year');

        $cumulativeData = collect();
        $studentPerformance = collect();

        if ($selectedSection && $selectedSubject && $selectedSchoolYear) {
            // Get all students in the selected section
            $students = Student::whereHas('enrollments', function ($q) use ($selectedSection, $selectedSchoolYear) {
                $q->whereHas('class', function ($cq) use ($selectedSection) {
                    $cq->where('section_id', $selectedSection);
                })
                    ->where('school_year_id', $selectedSchoolYear);
            })->with(['grades' => function ($q) use ($selectedSubject, $selectedSchoolYear) {
                $q->where('subject_id', $selectedSubject)
                    ->where('school_year_id', $selectedSchoolYear)
                    ->orderBy('quarter');
            }])->get();

            // Process cumulative data for each student
            $studentPerformance = $students->map(function ($student) {
                $grades = $student->grades;
                $quarters = [
                    '1st Quarter' => $grades->where('quarter', '1st Quarter')->first()?->grade,
                    '2nd Quarter' => $grades->where('quarter', '2nd Quarter')->first()?->grade,
                    '3rd Quarter' => $grades->where('quarter', '3rd Quarter')->first()?->grade,
                    '4th Quarter' => $grades->where('quarter', '4th Quarter')->first()?->grade,
                ];

                $validGrades = array_filter($quarters, fn($g) => $g !== null);
                $average = count($validGrades) > 0 ? array_sum($validGrades) / count($validGrades) : null;

                return (object)[
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'lrn' => $student->lrn,
                    'quarters' => $quarters,
                    'average' => $average,
                    'status' => $average !== null ? ($average >= 75 ? 'Passing' : 'Failing') : 'No Data',
                ];
            })->sortBy('name');

            // Calculate summary statistics
            $validAverages = $studentPerformance->pluck('average')->filter(fn($a) => $a !== null);
            $cumulativeData = (object)[
                'totalStudents' => $studentPerformance->count(),
                'averageGrade' => $validAverages->count() > 0 ? $validAverages->avg() : 0,
                'highestGrade' => $validAverages->count() > 0 ? $validAverages->max() : 0,
                'lowestGrade' => $validAverages->count() > 0 ? $validAverages->min() : 0,
                'passingCount' => $studentPerformance->where('status', 'Passing')->count(),
                'failingCount' => $studentPerformance->where('status', 'Failing')->count(),
            ];
        }

        return view('admin.reports.cumulative', compact(
            'sections',
            'subjects',
            'schoolYears',
            'selectedSection',
            'selectedSubject',
            'selectedSchoolYear',
            'cumulativeData',
            'studentPerformance'
        ));
    }
}
