<?php

namespace App\Http\Controllers;

use App\Imports\ReportCardImport;
use App\Models\Classes;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ReportCardController extends Controller
{
    public function index()
    {
        return view('report-cards');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'report_card_file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $import = new ReportCardImport;
        Excel::import($import, $request->file('report_card_file'));
        $extractedData = $import->getExtractedData();
        // dd($extractedData);
        DB::beginTransaction();
        try {
            $headers = $extractedData['headers'];

            // --- HEADER & RELATIONSHIP EXTRACTION ---
            $schoolYear = $headers['school_year'] ?? null;
            if (! $schoolYear) {
                throw new \Exception('School Year not found.');
            }

            $gradeSection = $headers['grade_section'] ?? null;
            if (! $gradeSection) {
                throw new \Exception('Grade & Section not found.');
            }

            preg_match('/(\d+)\s*-\s*(.*)/', $gradeSection, $matches);
            if (count($matches) < 3) {
                throw new \Exception('Could not parse Grade & Section: '.$gradeSection);
            }

            $gradeLevelNumber = (int) $matches[1];
            $sectionName = trim($matches[2]);

            $gradeLevel = GradeLevel::where('level', $gradeLevelNumber)->firstOrFail();
            $section = Section::where('name', $sectionName)->where('grade_level_id', $gradeLevel->id)->firstOrFail();

            $subjectName = $headers['subject'] ?? null;

            if (! $subjectName) {
                throw new \Exception('Subject not found.');
            }
            $subject = Subject::query()
                ->where('name', $subjectName)
                ->where(function ($query) use ($gradeLevel) {
                    $query->whereHas('gradeLevelSubjects', function ($gradeLevelSubjectQuery) use ($gradeLevel) {
                        $gradeLevelSubjectQuery
                            ->where('grade_level_id', $gradeLevel->id)
                            ->where('is_active', true);
                    })->orWhere('grade_level_id', $gradeLevel->id);
                })
                ->firstOrFail();

            $teacherName = $headers['teacher'] ?? null;
            if (! $teacherName) {
                throw new \Exception('Teacher not found.');
            }

            // --- Robust Teacher Name Parsing ---

            $nameParts = explode(' ', trim($teacherName));

            // The first name is the first element of the array
            $firstName = $nameParts[0];

            $lastName = count($nameParts) > 1 ? array_pop($nameParts) : '';
            $teacherQuery = Teacher::query();
            if ($firstName) {
                $teacherQuery->whereHas('user', function ($query) use ($firstName, $lastName) {
                    $query->where('last_name', $lastName)->where('first_name', 'LIKE', $firstName.'%');
                });
            } else {
                $teacherQuery->whereHas('user', function ($query) use ($lastName) {
                    $query->where('first_name', $lastName)->orWhere('last_name', $lastName);
                });
            }
            $teacher = $teacherQuery->firstOrFail();

            // --- HELPER FUNCTION TO SAVE GRADES ---
            $saveStudentGrades = function (string $studentName, array $gradesData) use ($subject, $teacher, $schoolYear) {
                // --- Robust Student Name Parsing ---
                $studentNameParts = explode(',', $studentName, 2);
                if (count($studentNameParts) < 2) {
                    return;
                } // Skip if name format is invalid

                [$lastName, $firstNameRaw] = array_map('trim', $studentNameParts);

                // Remove any middle initials (single capital letter with optional period)
                $firstNameParts = explode(' ', $firstNameRaw);
                $filteredParts = array_filter($firstNameParts, function ($part) {
                    return ! preg_match('/^[A-Z]\.?$/', $part);
                });

                $firstName = implode(' ', $filteredParts);
                // dd($lastName, $firstName);
                // Find student, but continue if not found
                $student = Student::where('last_name', $lastName)
                    ->where('first_name', 'LIKE', $firstName.'%')
                    ->first();

                if (! $student) {
                    return;
                } // Skip this student if not found in DB

                foreach ($gradesData as $quarter => $gradeValue) {
                    if (is_numeric($gradeValue)) {
                        Grade::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'subject_id' => $subject->id,
                                'teacher_id' => $teacher->id,
                                'quarter' => $quarter,
                                'school_year' => $schoolYear,
                            ],
                            [
                                'grade' => (float) $gradeValue,
                            ]
                        );
                    }
                }
            };

            // --- PROCESS STUDENTS ---
            foreach ($extractedData['male_students'] as $index => $studentName) {
                $saveStudentGrades($studentName, $extractedData['male_grades'][$index]);
            }

            foreach ($extractedData['female_students'] as $index => $studentName) {
                $saveStudentGrades($studentName, $extractedData['female_grades'][$index]);
            }

            DB::commit();

            return back()->with('success', 'Report card data imported and saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error saving data: '.$e->getMessage());
        }
    }

    public function getStudentsBySection(Section $section)
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();
        // Find the specific class instance associated with the section for the active school year
        $class = Classes::where('id', $section->id)
            ->where('school_year_id', $activeSchoolYear->id)
            ->first();

        // If no class is found for that section in the current year, return no students
        if (! $class) {
            return response()->json([]);
        }

        // Get students who are enrolled in that specific class
        $students = Student::whereHas('enrollments', function ($query) use ($class) {
            $query->where('class_id', $class->id);
        })->orderBy('last_name', 'asc')->get();

        // Format the data as expected by the DataTable in the view
        $studentData = $students->map(function ($student) {
            return [
                'student_id' => $student->student_id,
                'student_name' => $student->last_name.', '.$student->first_name,
                'gender' => ucfirst($student->gender),
            ];
        });

        return response()->json($studentData);
    }

    public function getGradesForSection(Section $section)
    {
        // Fetch all grade records for students in the given section
        $grades = \App\Models\Grade::whereHas('student', function ($query) use ($section) {
            $query->where('section_id', $section->id);
        })
            ->with('student') // Eager load student details
            ->get();

        // Group the flat collection by student_id
        $groupedByStudent = $grades->groupBy('student_id');

        $formattedData = [];

        // Process each student's group of grades
        foreach ($groupedByStudent as $studentId => $studentGrades) {
            $student = $studentGrades->first()->student;
            if (! $student) {
                continue;
            }

            // Create a map of this student's grades ('1' => 85, '2' => 88, etc.)
            $quarterlyGrades = $studentGrades->keyBy('quarter')->map(fn ($g) => $g->grade);

            $formattedData[] = [
                'gender' => $student->gender,
                'student_name' => $student->last_name.', '.$student->first_name,
                'grades' => [
                    '1' => $quarterlyGrades->get('1'),
                    '2' => $quarterlyGrades->get('2'),
                    '3' => $quarterlyGrades->get('3'),
                    '4' => $quarterlyGrades->get('4'),
                ],
                'student_id' => $studentId,
            ];
        }

        // Sort the final results alphabetically by student name
        usort($formattedData, fn ($a, $b) => strcmp($a['student_name'], $b['student_name']));

        return response()->json($formattedData);
    }

    public function showReportCard(): \Illuminate\View\View
    {

        // Return the view, passing the student data to it
        return view('template.report-card');
    }
}
