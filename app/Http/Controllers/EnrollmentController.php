<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Classes;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index()
    {
        $students = Student::all();
        $schoolYears = SchoolYear::all();
        $gradeLevels = GradeLevel::all();
        return view('teacher.enrollment.index', compact('students', 'schoolYears', 'gradeLevels'));
    }

    public function getSectionsByGradeLevel(Request $request)
    {
        $currentSchoolYear = SchoolYear::where('is_current', true)->first(); // adjust the condition as needed
        $sections = Section::with(['classes' => function ($query) use ($currentSchoolYear) {
            $query->where('school_year_id', $currentSchoolYear->id);
        }])->get();
        $allClasses = collect();
        foreach ($sections as $section) {
            $allClasses = $allClasses->merge($section->classes);
        }
        return response()->json($allClasses);
    }

    public function enroll(Request $request)
    {
        $student = Student::find($request->student_id);
        $schoolYear = SchoolYear::find($request->school_year_id);
        $section = Section::find($request->section_id);

        // Basic validation
        if (!$student || !$schoolYear || !$section) {
            return redirect()->back()->with('error', 'Invalid data provided.');
        }

        // Check for existing enrollment
        $existingEnrollment = Enrollment::where('student_id', $student->id)
            ->where('school_year_id', $schoolYear->id)
            ->first();

        if ($existingEnrollment) {
            return redirect()->back()->with('error', 'Student is already enrolled for this school year.');
        }

        // Create new enrollment
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $section->classes()->where('school_year_id', $schoolYear->id)->first()->id, // This might need adjustment
            'school_year_id' => $schoolYear->id,
            'status' => 'enrolled',
        ]);

        return redirect()->back()->with('success', 'Student enrolled successfully.');
    }
}
