<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Grade;
use App\Models\GradeLevelSubject;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\Attendance\ReportCardAttendanceService;
use App\Services\GradeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;

class ReportCardOutputController extends Controller
{
    /**
     * Generate and download a report card (.docx) for a specific student.
     */
    public function generateReportCard(Classes $class, Student $student): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $teacher = Auth::user()?->teacher;

            // Security check: Verify teacher owns the class
            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'You are not allowed to download report cards for this class.');
            }

            // Security check: Verify student is enrolled in this class
            $isEnrolled = $class->students()->where('students.id', $student->id)->exists();
            if (! $isEnrolled) {
                abort(404, 'Student is not enrolled in this class.');
            }

            $class->loadMissing('section.gradeLevel');
            $gradeLevelId = $class->section?->grade_level_id;
            $requiredSubjectIds = GradeLevelSubject::where('grade_level_id', $gradeLevelId)
                ->where('is_active', true)
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            // Load active school year
            $schoolYear = SchoolYear::active()->first();
            if (! $schoolYear) {
                return response()->json(['error' => 'No active school year found.'], 404);
            }

            // Get grades for the student in the current school year (exactly like preview)
            $rawGrades = Grade::where('student_id', $student->id)
                ->where('school_year_id', $schoolYear->id)
                ->with('subject')
                ->get()
                ->groupBy('subject_id');

            // Use GradeService to process grades with proper DepEd transmutation and calculations
            $processedGrades = GradeService::processGradesForReportCard($rawGrades, $requiredSubjectIds);
            $gradesData = $processedGrades['gradesData'];
            $generalAverage = $processedGrades['generalAverage'];

            $attendanceByMonth = Attendance::where('student_id', $student->id)
                ->where('school_year_id', $schoolYear->id)
                ->get()
                ->groupBy(fn ($attendance) => (int) date('n', strtotime((string) $attendance->date)))
                ->map(function ($rows) {
                    return (object) [
                        'present_days' => $rows->whereIn('status', ['present', 'late', 'excused'])->count(),
                        'absent_days' => $rows->where('status', 'absent')->count(),
                    ];
                });

            $attendanceSummary = ReportCardAttendanceService::summarizeAttendanceByReportCardMonth(
                $attendanceByMonth,
                $schoolYear
            );

            $attendanceData = $attendanceSummary['attendanceData'];
            $totalSchoolDays = $attendanceSummary['totalSchoolDays'];
            $totalDaysPresent = $attendanceSummary['totalDaysPresent'];
            $totalDaysAbsent = $attendanceSummary['totalDaysAbsent'];

            // Pre-computed values for the template
            $studentName = $student->full_name;
            $studentAge = $student->birthdate ? $student->birthdate->age : '';
            $studentGender = ucfirst($student->gender ?? '');
            $studentLrn = $student->lrn ?? '';
            $gradeLevelName = $class->section->gradeLevel->name ?? '';
            $sectionName = $class->section->name ?? '';
            $schoolYearName = $schoolYear->name;
            $adviserName = $class->teacher?->user?->full_name ?? '';

            // Load the .docx template
            $templatePath = storage_path('templates/REPORT CARD.docx');
            if (! file_exists($templatePath)) {
                return response()->json(['error' => 'Template file not found.'], 404);
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            // Set student info placeholders
            $templateProcessor->setValue('student_name', $studentName);
            $templateProcessor->setValue('student_age', $studentAge);
            $templateProcessor->setValue('student_gender', $studentGender);
            $templateProcessor->setValue('student_lrn', $studentLrn);
            $templateProcessor->setValue('grade_level_name', $gradeLevelName);
            $templateProcessor->setValue('section_name', $sectionName);
            $templateProcessor->setValue('school_year_name', $schoolYearName);
            $templateProcessor->setValue('adviser_name', $adviserName);

            // Set fixed grade rows (1-11) - if no subject, leave blank
            $sortedGrades = collect($gradesData)->sortBy('subject_name', SORT_NATURAL | SORT_FLAG_CASE)->values();
            $maxGradeRows = 11;

            for ($i = 1; $i <= $maxGradeRows; $i++) {
                $row = $sortedGrades->get($i - 1);

                if ($row === null) {
                    $templateProcessor->setValue("subj_{$i}_name", '');
                    $templateProcessor->setValue("subj_{$i}_q1", '');
                    $templateProcessor->setValue("subj_{$i}_q2", '');
                    $templateProcessor->setValue("subj_{$i}_q3", '');
                    $templateProcessor->setValue("subj_{$i}_q4", '');
                    $templateProcessor->setValue("subj_{$i}_final", '');
                    $templateProcessor->setValue("subj_{$i}_remarks", '');

                    continue;
                }

                $templateProcessor->setValue("subj_{$i}_name", $row['subject_name'] ?? '');
                $templateProcessor->setValue("subj_{$i}_q1", $row['q1'] !== null && $row['q1'] !== '' ? $row['q1'] : '');
                $templateProcessor->setValue("subj_{$i}_q2", $row['q2'] !== null && $row['q2'] !== '' ? $row['q2'] : '');
                $templateProcessor->setValue("subj_{$i}_q3", $row['q3'] !== null && $row['q3'] !== '' ? $row['q3'] : '');
                $templateProcessor->setValue("subj_{$i}_q4", $row['q4'] !== null && $row['q4'] !== '' ? $row['q4'] : '');
                $templateProcessor->setValue("subj_{$i}_final", GradeService::formatFinalGradeForExport($row['final_grade'] ?? null));
                $templateProcessor->setValue("subj_{$i}_remarks", $row['remarks'] ?? '');
            }

            // Set general average and final remarks
            $finalRemarks = '';
            if ($generalAverage !== null) {
                $finalRemarks = $generalAverage >= 75 ? 'Passed' : 'Failed';
            }
            $templateProcessor->setValue('general_average', $generalAverage ?? '');
            $templateProcessor->setValue('final_remarks', $finalRemarks);

            // Set attendance placeholders
            $months = array_values(ReportCardAttendanceService::reportCardMonthMapping());
            foreach ($months as $month) {
                $data = $attendanceData[$month] ?? ['school_days' => 0, 'present' => 0, 'absent' => 0];
                $templateProcessor->setValue("sd_{$month}", $data['school_days']);
                $templateProcessor->setValue("dp_{$month}", $data['present']);
                $templateProcessor->setValue("da_{$month}", $data['absent']);
            }

            // Set attendance totals
            $templateProcessor->setValue('total_school_days', $totalSchoolDays);
            $templateProcessor->setValue('total_days_present', $totalDaysPresent);
            $templateProcessor->setValue('total_days_absent', $totalDaysAbsent);

            // Save and download the file
            $fileName = 'Report_Card_'.str_replace(' ', '_', $studentName).'.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'PHPWord');
            $templateProcessor->saveAs($tempFile);

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('ReportCardOutputController@generateReportCard error: '.$e->getMessage(), [
                'exception' => $e,
                'class_id' => $class->id ?? null,
                'student_id' => $student->id ?? null,
            ]);

            return response()->json(['error' => 'Unable to generate report card. Please try again.'], 500);
        }
    }
}
