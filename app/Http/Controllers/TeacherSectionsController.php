<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Classes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherSectionsController extends Controller
{
    public function getSectionsByGradeLevel(Request $request)
    {
        // Active school year alignment with schema (uses is_active)
        $currentSchoolYear = SchoolYear::active()->first();
        if (!$currentSchoolYear) {
            return response()->json(['message' => 'No active school year found.'], 400);
        }

        // Logged-in teacher id
        $teacherId = optional(Auth::user()->teacher)->id;
        if (!$teacherId) {
            return response()->json(['message' => 'Current user is not a teacher.'], 403);
        }

        $gradeLevelInput = $request->input('grade_level');
        // Build classes query for the teacher's scheduled classes in the active year
        $classesQuery = Classes::with(['section.gradeLevel'])
            ->where('school_year_id', $currentSchoolYear->id)
            ->whereHas('schedules', function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            });

        // Optional grade level filter
        if (!is_null($gradeLevelInput) && $gradeLevelInput !== '') {
            $classesQuery->whereHas('section', function ($q) use ($gradeLevelInput) {
                $q->where('grade_level_id', $gradeLevelInput)
                    ->orWhereIn('grade_level_id', function ($sub) use ($gradeLevelInput) {
                        $sub->select('id')->from('grade_levels')->where('level', $gradeLevelInput);
                    });
            });
        }

        $allClasses = $classesQuery
            ->get()
            ->sortBy(function ($class) {
                return [
                    optional($class->section->gradeLevel)->level ?? 0,
                    optional($class->section)->name ?? ''
                ];
            })
            ->values();

        // Frontend expects `allClasses` array
        return response()->json(['allClasses' => $allClasses]);
    }

    public function getSubjectsBySection(Request $request, $sectionId)
    {
        $teacherId = Auth::user()->teacher->id;

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
}
