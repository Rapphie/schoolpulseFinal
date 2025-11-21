<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;

class ReportCardOutputController extends Controller
{
    /**
     * Generate and download a report card for a specific student.
     */
    public function generateReportCard($studentId)
    {
        try {
            // Load active school year
            $schoolYear = SchoolYear::active()->first() ?? SchoolYear::orderByDesc('start_date')->first();
            if (!$schoolYear) {
                return response()->json(['error' => 'No active school year found.'], 404);
            }

            // Load student with related grades (subjects) and enrollments for context
            $student = Student::with([
                'grades.subject',
                'enrollments.class.section.gradeLevel'
            ])->findOrFail($studentId);

            // Derive section and grade level via enrollment in active school year
            $activeEnrollment = $student->enrollments->firstWhere('school_year_id', $schoolYear->id);
            $section = $activeEnrollment?->class?->section;
            $gradeLevel = $section?->gradeLevel;

            // Load the .docx template
            $templatePath = storage_path('app/templates/report-card-template.docx');
            if (!file_exists($templatePath)) {
                return response()->json(['error' => 'Template file not found.'], 404);
            }

            $templateProcessor = new TemplateProcessor($templatePath);
            // Basic student identity values
            $templateProcessor->setValue('student_name', $student->full_name ?? ($student->first_name . ' ' . $student->last_name));
            $templateProcessor->setValue('student_lrn', $student->lrn ?? '');
            $templateProcessor->setValue('student_age', $student->age ?? '');
            $templateProcessor->setValue('student_sex', ucfirst($student->gender ?? ''));
            $templateProcessor->setValue('grade_level', $gradeLevel?->name ?? '');
            $templateProcessor->setValue('section_name', $section?->name ?? '');
            $templateProcessor->setValue('school_year', $schoolYear->name);
            // Adviser/teacher placeholder (if a teacher is tied to the class)
            $templateProcessor->setValue('teacher_name', $activeEnrollment?->class?->teacher?->user?->first_name ?? '');

            // Attendance Pattern based on predefined school days
            $max_days_per_month = [
                "June" => 11,
                "July" => 23,
                "August" => 20,
                "September" => 22,
                "October" => 23,
                "November" => 21,
                "December" => 14, // Assuming 0 for Dec
                "January" => 21,
                "February" => 19,
                "March" => 23,
                "April" => 0 // Assuming 0 for Apr
            ];

            $attendanceByMonth = Attendance::selectRaw('
                MONTH(date) as month_num,
                DATE_FORMAT(date, "%M") as month_name,
                SUM(CASE WHEN status IN ("present", "late", "excused") THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days
            ')
                ->where('student_id', $student->id)
                ->where('school_year_id', $schoolYear->id)
                ->groupBy('month_num', 'month_name')
                ->get()
                ->keyBy('month_name');

            $month_mapping = [
                'June' => 'jun',
                'July' => 'jul',
                'August' => 'aug',
                'September' => 'sep',
                'October' => 'oct',
                'November' => 'nov',
                'December' => 'dec',
                'January' => 'jan',
                'February' => 'feb',
                'March' => 'mar',
                'April' => 'apr'
            ];

            $totalSchoolDays = 0;
            $totalPresentDays = 0;
            $totalAbsentDays = 0;

            foreach ($max_days_per_month as $monthName => $schoolDays) {
                $monthAbbr = $month_mapping[$monthName] ?? '';
                if (!$monthAbbr) continue;

                $monthlyData = $attendanceByMonth->get($monthName);
                $presentDays = $monthlyData ? $monthlyData->present_days : 0;
                $absentDays = $monthlyData ? $monthlyData->absent_days : 0;

                // Ensure present days doesn't exceed school days for the month
                $presentDays = min($presentDays, $schoolDays);

                // Logic to handle absent days based on school days and present days
                // This assumes that if a student is marked present, they can't be absent on the same day.
                // The number of absent days is what's recorded, but shouldn't exceed school_days - present_days
                $absentDays = min($absentDays, $schoolDays - $presentDays);


                $templateProcessor->setValue("sd_{$monthAbbr}", $schoolDays);
                $templateProcessor->setValue("dp_{$monthAbbr}", $presentDays);
                $templateProcessor->setValue("da_{$monthAbbr}", $absentDays);

                $totalSchoolDays += $schoolDays;
                $totalPresentDays += $presentDays;
                $totalAbsentDays += $absentDays;
            }

            $templateProcessor->setValue('total_school_days', $totalSchoolDays);
            $templateProcessor->setValue('total_days_present', $totalPresentDays);
            $templateProcessor->setValue('total_days_absent', $totalAbsentDays);


            // Grades aggregation per subject (quarters Q1..Q4)
            $rawGrades = Grade::where('student_id', $student->id)
                ->where('school_year_id', $schoolYear->id)
                ->with('subject')
                ->get()
                ->groupBy('subject_id');

            $gradesData = [];
            $finalGrades = [];
            foreach ($rawGrades as $subjectId => $collection) {
                $subjectName = optional($collection->first()->subject)->name ?? 'Subject';
                // Map quarters (normalize quarter field like 'Q1', '1', 'Quarter 1')
                $quarters = [1 => null, 2 => null, 3 => null, 4 => null];
                foreach ($collection as $g) {
                    // Prefer quarter_int if present, else parse digits
                    $qi = $g->quarter_int ?? (int)preg_replace('/[^0-9]/', '', $g->quarter) ?: null;
                    if ($qi && $qi >= 1 && $qi <= 4) {
                        $quarters[$qi] = $g->grade;
                    }
                }
                $existingGrades = array_filter($quarters, fn($val) => $val !== null);
                $final = count($existingGrades) ? round(array_sum($existingGrades) / count($existingGrades), 2) : null;
                $finalGrades[] = $final ?? 0;
                $gradesData[] = [
                    'subject_name' => $subjectName,
                    'q1' => $quarters[1] ?? '',
                    'q2' => $quarters[2] ?? '',
                    'q3' => $quarters[3] ?? '',
                    'q4' => $quarters[4] ?? '',
                    'final_grade' => $final ?? '',
                    'remarks' => ($final !== null && $final >= 75) ? 'Passed' : (($final !== null) ? 'Failed' : ''),
                ];
            }

            // Clone subject rows
            if (!empty($gradesData)) {
                $templateProcessor->cloneRowAndSetValues('subject_name', $gradesData);
            }

            $generalAverage = count($finalGrades) ? round(array_sum($finalGrades) / count($finalGrades), 2) : '';
            $templateProcessor->setValue('general_average', $generalAverage);
            $templateProcessor->setValue('final_remarks', ($generalAverage !== '' && $generalAverage >= 75) ? 'Passed' : (($generalAverage !== '') ? 'Failed' : ''));


            // Save and download the file
            $fileName = 'Report_Card_' . str_replace(' ', '_', $student->full_name ?? $student->id) . '.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'PHPWord');
            $templateProcessor->saveAs($tempFile);

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Handle any errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
