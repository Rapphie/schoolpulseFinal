<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;

class TeacherSectionsController extends Controller
{
    public function getSectionsByGradeLevel(Request $request)
    {
        $validated = $request->validate([
            'grade_level' => 'required|integer|exists:grade_levels,id'
        ]);

        $sections = Section::where('grade_level_id', $validated['grade_level'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['sections' => $sections]);
    }

    public function getSubjectsBySection($section)
    {
        // Get subjects from the schedule table where section_id matches
        $schedules = Schedule::where('section_id', $section)
            ->with('subject')  // Load the subject relationship
            ->get();

        // Map the schedules to get unique subjects
        $subjects = $schedules->map(function ($schedule) {
            return $schedule->subject;
        })->unique('id')->values();

        return response()->json(['subjects' => $subjects]);
    }
    public function getStudentsBySection($section)
    {
        $students = Student::where('section_id', $section)->get();
        return response()->json(['students' => $students]);
    }
}
