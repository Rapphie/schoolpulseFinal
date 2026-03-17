<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ExportAttendancePatternSf2Request;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Attendance\Sf2WorkbookExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function getSubjectsForClass(Classes $class, Request $request)
    {

        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();

        $activeSchoolYear = SchoolYear::getRealActive();
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
        $activeSchoolYear = SchoolYear::getRealActive();

        if (! $activeSchoolYear) {
            return redirect()->back()->with('error', 'No active school year found.');
        }

        // --- Data for Dropdowns (More Efficiently) ---
        // Get class IDs assigned to the teacher via schedules for the active school year
        $classIds = Schedule::where('teacher_id', $teacher->id)
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
        $subjectIds = Schedule::whereIn('class_id', $classIds)->pluck('subject_id')->unique();
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
                    ->groupBy(fn ($att) => \Carbon\Carbon::parse($att->date)->format('F'));
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

    public function exportAttendancePattern(ExportAttendancePatternSf2Request $request): BinaryFileResponse|RedirectResponse
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeSchoolYear = SchoolYear::getRealActive();

        if (! $activeSchoolYear) {
            return redirect()->back()->with('error', 'No active school year found.');
        }

        $sectionId = $request->input('section_id');
        $month = $request->input('month');
        $schoolId = $request->input('school_id');
        $schoolName = $request->input('school_name');

        $section = Section::findOrFail($sectionId);

        $class = Classes::where('section_id', $sectionId)
            ->where('school_year_id', $activeSchoolYear->id)
            ->first();

        if (! $class) {
            return redirect()->back()->with('error', 'No class found for the selected section in the current school year.');
        }

        if ($class->teacher_id !== $teacher->id) {
            return redirect()->back()->with('error', 'You are not the adviser of this section. Only the class adviser can export SF2.');
        }

        $sectionSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $section->name));
        $monthSlug = str_replace('-', '', $month);
        $fileName = "sf2_{$sectionSlug}_{$monthSlug}.xlsx";

        try {
            $exportService = new Sf2WorkbookExportService(
                $section,
                $month,
                $schoolId,
                $schoolName
            );

            $spreadsheet = $exportService->build();
            $tempFile = tempnam(sys_get_temp_dir(), 'sf2_');

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tempFile);

            return response()->download(
                $tempFile,
                $fileName,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('AttendanceController@exportAttendancePattern SF2 export failed', [
                'exception' => $e,
                'section_id' => $sectionId,
                'month' => $month,
                'teacher_id' => $teacher->id,
            ]);

            return redirect()->back()->with('error', 'Failed to generate SF2 export: '.$e->getMessage());
        }
    }
}
