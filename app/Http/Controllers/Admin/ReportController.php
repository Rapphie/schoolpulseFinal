<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EnrolleesExport;
use App\Exports\AttendanceExport;
use App\Exports\GradesExport;

class ReportController extends Controller
{
    public function enrollees(Request $request)
    {
        $sections = Section::withCount('students')
            ->with('adviser')
            ->get();

        $grades = range(1, 12);
        $selectedGrade = $request->input('grade');

        if ($selectedGrade) {
            $sections = $sections->where('grade_level', $selectedGrade);
        }

        return view('admin.reports.enrollees', compact('sections', 'grades', 'selectedGrade'));
    }


    public function exportEnrollees(Request $request)
    {
        $grade = $request->input('grade');
        $filename = 'enrollees_report' . ($grade ? '_grade_' . $grade : '') . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new EnrolleesExport($grade), $filename);
    }


    public function attendance(Request $request)
    {
        $sections = Section::with('students')->get();
        $selectedSection = $request->input('section');
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        $attendanceData = collect();

        if ($selectedSection) {
            $startDate = Carbon::parse($selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($selectedMonth)->endOfMonth();

            $section = Section::with(['students' => function ($query) use ($startDate, $endDate) {
                $query->with(['attendances' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }]);
            }])->find($selectedSection);

            if ($section) {
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
            $section = Section::with(['students' => function ($query) use ($selectedSubject, $gradingPeriod) {
                $query->with(['grades' => function ($q) use ($selectedSubject, $gradingPeriod) {
                    $q->where('subject_id', $selectedSubject)
                        ->where('grading_period', $gradingPeriod);
                }]);
            }])->find($selectedSection);

            if ($section) {
                $gradesData = $this->processGradesData($section, $selectedSubject, $gradingPeriod);

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
                ->where('grading_period', $gradingPeriod)
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
            'gradingPeriod' => ucfirst($gradingPeriod) . ' Grading',
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


    public function leastLearned()
    {
        // Implementation for least learned competencies report
        return view('admin.reports.least_learned');
    }


    public function cumulative()
    {
        // Implementation for cumulative report
        return view('admin.reports.cumulative');
    }
}
