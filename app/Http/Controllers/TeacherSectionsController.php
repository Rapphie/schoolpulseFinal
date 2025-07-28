<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\Classes;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherSectionsController extends Controller
{
    public function getSectionsByGradeLevel(Request $request)
    {
        $currentSchoolYear = SchoolYear::where('is_current', true)->first();
        $gradeLevel = $request->input('grade_level');
        $sections = Section::with(['classes' => function ($query) use ($currentSchoolYear, $gradeLevel) {
            $query->where('school_year_id', $currentSchoolYear->id)->where('grade_level_id', $gradeLevel);
        }])->get();
        $allClasses = collect();
        foreach ($sections as $section) {
            $allClasses = $allClasses->merge($section->classes);
        }
        return response()->json($allClasses);
    }

    public function getSubjectsBySection(Request $request, $sectionId)
    {
        $userId = Auth::user()->id;
        $teacherId = Teacher::where('user_id', $userId)->value('id');

        // Find schedules for this teacher and section
        $schedules = Schedule::with('subject')
            ->where('teacher_id', $teacherId)
            ->whereHas('class', function ($query) use ($sectionId) {
                $query->where('section_id', $sectionId);
            })
            ->get();

        // Return a unique list of subjects
        $subjects = $schedules->pluck('subject')->unique('id')->values();

        return response()->json($subjects);
    }
    public function getStudentsBySection($section)
    {
        $students = Student::where('section_id', $section)->get();
        return response()->json(['students' => $students]);
    }

    public function manageSection($sectionId)
    {
        $class = Section::where('id', $sectionId)->get();
        $schedules = Schedule::where('section_id', $sectionId)->get();
        $students = Student::where('section_id', $sectionId)->get();

        return view('teacher.classes.view', compact('classes', 'students'));
    }
}
