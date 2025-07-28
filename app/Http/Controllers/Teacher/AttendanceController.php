<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function attendancePattern(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();

        // Data for Dropdowns
        $teacherSchedules = Schedule::where('teacher_id', $teacher->id)->get();
        $subjectIds = $teacherSchedules->pluck('subject_id')->unique();
        $sectionIds = $teacherSchedules->pluck('section_id')->unique();

        $subjects = Subject::whereIn('id', $subjectIds)->get();
        $sections = Section::whereIn('id', $sectionIds)->with('gradeLevel')->get();
        $gradeLevelIds = $sections->pluck('gradeLevel.id')->unique();
        $gradeLevels = GradeLevel::whereIn('id', $gradeLevelIds)->get();

        // Filtering logic
        $students = collect();
        $selectedGradeLevelId = $request->input('grade_level_id');
        $selectedSectionId = $request->input('section_id');
        $selectedSubjectId = $request->input('subject_id');

        if ($selectedGradeLevelId || $selectedSectionId) {
            $studentQuery = Student::query();

            if ($selectedSectionId) {
                $studentQuery->where('section_id', $selectedSectionId);
            } else {
                $sectionsInGradeForTeacher = Section::where('grade_level_id', $selectedGradeLevelId)
                                                  ->whereIn('id', $sectionIds)
                                                  ->pluck('id');
                $studentQuery->whereIn('section_id', $sectionsInGradeForTeacher);
            }

            $students = $studentQuery->get();

            $students->each(function ($student) use ($teacher, $selectedSubjectId) {
                $attendanceQuery = Attendance::where('student_id', $student->id)
                                             ->where('teacher_id', $teacher->id);

                if ($selectedSubjectId) {
                    $attendanceQuery->where('subject_id', $selectedSubjectId);
                }

                $student->attendance = $attendanceQuery->get()
                    ->groupBy(fn($att) => \Carbon\Carbon::parse($att->date)->format('F'));
            });
        }

        return view('teacher.attendance.pattern', compact(
            'gradeLevels', 'sections', 'subjects', 'students',
            'selectedGradeLevelId', 'selectedSectionId', 'selectedSubjectId'
        ));
    }
}