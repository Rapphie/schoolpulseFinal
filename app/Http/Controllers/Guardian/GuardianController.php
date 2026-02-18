<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class GuardianController extends Controller
{
    /**
     * Display all linked students with their live grades and attendance snapshots.
     */
    public function viewStudentGrades()
    {
        $activeSchoolYear = SchoolYear::active()->first();

        $guardian = Auth::user()
            ->guardian()
            ->with(['students' => function ($query) {
                $query->with([
                    'enrollments.class.section.gradeLevel',
                    'enrollments.schoolYear',
                    'grades.subject',
                    'grades.teacher.user',
                    'attendances.subject',
                ]);
            }])
            ->first();

        if (!$guardian) {
            abort(403, 'Guardian profile not found.');
        }

        $students = $guardian->students ?? collect();

        $studentsData = $students->map(function (Student $student) use ($activeSchoolYear) {
            $gradesByQuarter = collect($this->quarterLabels())->mapWithKeys(fn($label) => [$label => collect()]);
            $currentEnrollment = $this->resolveCurrentEnrollment($student, $activeSchoolYear);

            $grades = $student->grades;
            if ($activeSchoolYear) {
                $grades = $grades->where('school_year_id', $activeSchoolYear->id);
            }

            foreach ($grades as $grade) {
                $quarterLabel = $this->normalizeQuarterLabel($grade->quarter);
                if (!isset($gradesByQuarter[$quarterLabel])) {
                    $gradesByQuarter[$quarterLabel] = collect();
                }

                $gradesByQuarter[$quarterLabel]->push([
                    'subject' => $grade->subject?->name ?? 'Subject',
                    'teacher' => $grade->teacher?->user?->full_name ?? $grade->teacher?->user?->name ?? '—',
                    'grade' => $grade->grade,
                    'remarks' => $this->gradeRemark($grade->grade),
                ]);
            }

            $gradesByQuarter = $gradesByQuarter->map(fn($entries) => $entries->sortBy('subject')->values());

            $attendanceRecords = $student->attendances;
            if ($activeSchoolYear) {
                $attendanceRecords = $attendanceRecords->where('school_year_id', $activeSchoolYear->id);
            }
            $attendanceRecords = $attendanceRecords->sortByDesc('date')->values();

            $attendanceSummary = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'excused' => 0,
            ];

            foreach ($attendanceRecords as $attendance) {
                $status = strtolower($attendance->status ?? 'present');
                $attendanceSummary[$status] = ($attendanceSummary[$status] ?? 0) + 1;
            }

            $formattedAttendanceRecords = $attendanceRecords
                ->take(25)
                ->map(function ($attendance) {
                    return [
                        'formatted_date' => optional($attendance->date)->format('M d, Y') ?? '—',
                        'subject' => $attendance->subject?->name ?? 'Homeroom',
                        'status' => strtolower($attendance->status ?? 'present'),
                        'quarter' => $this->normalizeQuarterLabel($attendance->quarter),
                        'time_in' => optional($attendance->time_in)->format('h:i A') ?? '—',
                    ];
                });

            return [
                'student' => $student,
                'student_identifier' => $student->student_id ?? $student->lrn ?? 'N/A',
                'lrn' => $student->lrn ?? 'N/A',
                'class_section' => $currentEnrollment?->class?->section?->name ?? 'Unassigned',
                'grade_level' => $currentEnrollment?->class?->section?->gradeLevel?->name ?? 'Unassigned',
                'school_year' => $currentEnrollment?->schoolYear?->name ?? $activeSchoolYear?->name ?? '—',
                'grades_by_quarter' => $gradesByQuarter,
                'attendance_records' => $formattedAttendanceRecords,
                'attendance_summary' => $attendanceSummary,
            ];
        });

        return view('guardian.index', [
            'guardian' => $guardian,
            'studentsData' => $studentsData,
            'activeSchoolYear' => $activeSchoolYear,
            'quarterLabels' => $this->quarterLabels(),
        ]);
    }

    private function quarterLabels(): array
    {
        return [
            1 => '1st Quarter',
            2 => '2nd Quarter',
            3 => '3rd Quarter',
            4 => '4th Quarter',
        ];
    }

    private function normalizeQuarterLabel(?string $value): string
    {
        $labels = $this->quarterLabels();

        if ($value === null) {
            return $labels[1];
        }

        $numeric = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        if ($numeric && isset($labels[$numeric])) {
            return $labels[$numeric];
        }

        $normalized = strtoupper(trim($value));
        $map = [
            'FIRST QUARTER' => 1,
            'SECOND QUARTER' => 2,
            'THIRD QUARTER' => 3,
            'FOURTH QUARTER' => 4,
        ];

        return isset($map[$normalized]) ? $labels[$map[$normalized]] : ($value ?: 'Unspecified Quarter');
    }

    private function gradeRemark(?float $grade): string
    {
        if ($grade === null) {
            return 'No Grade';
        }

        return $grade >= 75 ? 'Passed' : 'Failed';
    }

    private function resolveCurrentEnrollment(Student $student, ?SchoolYear $activeSchoolYear)
    {
        if ($activeSchoolYear) {
            $match = $student->enrollments->firstWhere('school_year_id', $activeSchoolYear->id);
            if ($match) {
                return $match;
            }
        }

        return $student->enrollments->sortByDesc('enrollment_date')->first();
    }
}
