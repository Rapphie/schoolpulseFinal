<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function getSubjectsForClass(Classes $class, Request $request)
    {

        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();

        $activeSchoolYear = \App\Models\SchoolYear::where('is_active', true)->first();
        $scheduleQuery = Schedule::with('subject')
            ->where('class_id', $class->id)
            ->where('teacher_id', $teacher->id);

        if ($activeSchoolYear) {
            $scheduleQuery->whereHas('class', function ($q) use ($activeSchoolYear) {
                $q->where('school_year_id', $activeSchoolYear->id);
            });
        }

        $subjects = $scheduleQuery
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code ?? null,
                ];
            });

        return response()->json($subjects);
    }

    public function attendancePattern(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeSchoolYear = \App\Models\SchoolYear::where('is_active', true)->firstOrFail();

        // --- Data for Dropdowns (More Efficiently) ---
        // Get class IDs assigned to the teacher via schedules for the active school year
        $classIds = \App\Models\Schedule::where('teacher_id', $teacher->id)
            ->whereHas('class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->pluck('class_id')->unique();

        // Get unique section IDs from those classes
        $sectionIds = Classes::whereIn('id', $classIds)->pluck('section_id')->unique();
        $sections = Section::whereIn('id', $sectionIds)->with('gradeLevel')->get();

        // Get unique grade level IDs from those sections
        $gradeLevelIds = $sections->pluck('gradeLevel.id')->unique();
        $gradeLevels = GradeLevel::whereIn('id', $gradeLevelIds)->orderBy('level')->get();

        // Get unique subject IDs from the teacher's schedules for those classes
        $subjectIds = \App\Models\Schedule::whereIn('class_id', $classIds)->pluck('subject_id')->unique();
        $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();


        // --- Filtering Logic ---
        $students = collect();
        $selectedGradeLevelId = $request->input('grade_level_id');
        $selectedSectionId = $request->input('section_id');
        $selectedSubjectId = $request->input('subject_id');

        // Only run the main query if a filter has been applied
        if ($selectedGradeLevelId || $selectedSectionId || $selectedSubjectId) {
            $studentQuery = Student::query();

            // Correctly filter students by section or grade level through their enrollment in the active year
            $studentQuery->whereHas('enrollments', function ($query) use ($selectedSectionId, $selectedGradeLevelId, $classIds, $activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id)
                    ->whereIn('class_id', $classIds); // Ensure student is in one of the teacher's classes

                if ($selectedSectionId) {
                    $query->whereHas('class', function ($q) use ($selectedSectionId) {
                        $q->where('section_id', $selectedSectionId);
                    });
                } elseif ($selectedGradeLevelId) {
                    $query->whereHas('class.section', function ($q) use ($selectedGradeLevelId) {
                        $q->where('grade_level_id', $selectedGradeLevelId);
                    });
                }
            });

            // Eager load attendance records to prevent N+1 queries.
            // This query is now less strict, relying primarily on the teacher_id.
            $studentQuery->with(['attendances' => function ($query) use ($teacher, $selectedSubjectId) {
                $query->where('teacher_id', $teacher->id); // Rely on the teacher ID

                if ($selectedSubjectId) {
                    $query->where('subject_id', $selectedSubjectId);
                }
            }]);

            $students = $studentQuery->orderBy('last_name')->get();

            // Process the already-loaded attendance records in PHP
            $students->each(function ($student) {
                $student->attendance_summary = $student->attendances
                    ->groupBy(fn($att) => \Carbon\Carbon::parse($att->date)->format('F'));
            });
        }

        return view('teacher.attendance.pattern', compact(
            'gradeLevels',
            'sections',
            'subjects',
            'students',
            'selectedGradeLevelId',
            'selectedSectionId',
            'selectedSubjectId'
        ));
    }

    public function normalizeAttendanceRecords($attendanceData)
    {
        $attendance_month = $attendanceData->format('F');;
        $max_days_per_month = [
            "June" => 11,
            "July" => 23,
            "August" => 20,
            "September" => 22,
            "October" => 23,
            "November" => 21,
            "January" => 14,
            "February" => 19,
            "March" => 23
        ];

        // Default value in case the month isn't found
        $monthly_max_school_days = 0;

        if (array_key_exists($attendance_month, $max_days_per_month)) {
            // if month key exists, so we can safely get the value
            $monthly_max_school_days = $max_days_per_month[$attendance_month];
        } else {
            return with('error', "Invalid school month days");
        }

        $max_present_days = 197;
        // $normalized_present = $monthly_present_days / $max_present_days;
        // $normalized_score = row['monthly_avg_score'] / 100.0;
        // $engagement_score = (normalized_present * ENGAGEMENT_WEIGHT_PRESENT) + (normalized_score * ENGAGEMENT_WEIGHT_SCORE);
    }
    public function exportAttendancePattern(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeSchoolYear = \App\Models\SchoolYear::where('is_active', true)->firstOrFail();

        $selectedGradeLevelId = $request->input('grade_level_id');
        $selectedSectionId = $request->input('section_id');
        $selectedSubjectId = $request->input('subject_id');

        // Redirect back if no filter is applied, as there would be nothing to export.
        if (!$selectedGradeLevelId && !$selectedSectionId && !$selectedSubjectId) {
            return redirect()->back()->with('error', 'Please apply a filter before exporting.');
        }

        $classIds = \App\Models\Schedule::where('teacher_id', $teacher->id)
            ->whereHas('class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->pluck('class_id')->unique();

        $studentQuery = Student::query();
        $studentQuery->whereHas('enrollments', function ($query) use ($selectedSectionId, $selectedGradeLevelId, $classIds, $activeSchoolYear) {
            $query->where('school_year_id', $activeSchoolYear->id)
                ->whereIn('class_id', $classIds);

            if ($selectedSectionId) {
                $query->whereHas('class', function ($q) use ($selectedSectionId) {
                    $q->where('section_id', $selectedSectionId);
                });
            } elseif ($selectedGradeLevelId) {
                $query->whereHas('class.section', function ($q) use ($selectedGradeLevelId) {
                    $q->where('grade_level_id', $selectedGradeLevelId);
                });
            }
        });

        $studentQuery->with(['attendances' => function ($query) use ($teacher, $selectedSubjectId) {
            $query->where('teacher_id', $teacher->id);
            if ($selectedSubjectId) {
                $query->where('subject_id', $selectedSubjectId);
            }
        }]);
        $students = $studentQuery->orderBy('last_name')->get();

        $fileName = "attendance_pattern_" . date('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Student Name', 'Month', 'Present', 'Absent', 'Late', 'Excused', 'Total Days'];
        $months = ['June', 'July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March', 'April', 'May'];

        $callback = function () use ($students, $columns, $months) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($students as $student) {
                $attendanceSummary = $student->attendances
                    ->groupBy(fn($att) => \Carbon\Carbon::parse($att->date)->format('F'));

                foreach ($months as $month) {
                    if (isset($attendanceSummary[$month]) && $attendanceSummary[$month]->count() > 0) {
                        $present = $attendanceSummary[$month]->where('status', 'present')->count();
                        $absent = $attendanceSummary[$month]->where('status', 'absent')->count();
                        $late = $attendanceSummary[$month]->where('status', 'late')->count();
                        $excused = $attendanceSummary[$month]->where('status', 'excused')->count();
                        $total = $present + $absent + $late + $excused;

                        fputcsv($file, [
                            $student->last_name . ', ' . $student->first_name,
                            $month,
                            $present,
                            $absent,
                            $late,
                            $excused,
                            $total
                        ]);
                    }
                }
            }
            fclose($file);
        };
        //
        return new StreamedResponse($callback, 200, $headers);
    }
}
