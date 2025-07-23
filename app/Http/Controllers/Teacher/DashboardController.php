<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');

        // Get today's date
        $today = now()->toDateString();
        $dayOfWeek = strtolower(now()->format('l')); // e.g., "monday"

        // Count classes (schedules) assigned to this teacher
        $classCount = Schedule::where('teacher_id', $teacherId)->distinct('subject_id', 'section_id')->count();

        // Get subjects taught by this teacher
        $subjects = Subject::whereHas('schedules', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->get();

        // Get sections taught by this teacher
        $sections = Section::whereHas('schedules', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->get();

        // Count students in those sections
        $studentCount = Student::whereIn('section_id', $sections->pluck('id'))->count();

        // Count attendance records created by this teacher today
        $todayAttendanceCount = Attendance::where('teacher_id', $teacherId)
            ->where('date', $today)
            ->count();

        // Get upcoming classes for today (schedules)
        $currentTime = now()->format('H:i:s');
        $upcomingClasses = Schedule::where('teacher_id', $teacherId)
            ->whereJsonContains('day_of_week', $dayOfWeek)
            ->whereTime('start_time', '>=', $currentTime)
            ->with(['section', 'subject'])
            ->orderBy('start_time')
            ->get();


        // Count total upcoming schedules
        $scheduleCount = $upcomingClasses->count();

        // Get students performance data
        // High performers (above 90%)
        $highPerformers = 0;
        $averagePerformers = 0;
        $lowPerformers = 0;

        // In a real application, this would be calculated from actual grade data
        // For demo purposes, we'll use placeholder values
        $highPerformers = rand(3, 8);
        $averagePerformers = rand(10, 20);
        $lowPerformers = rand(1, 5);

        // Recent activities - for demo, we'll create some placeholder activities
        $recentActivities = collect([
            (object)[
                'title' => 'Attendance Recorded',
                'description' => 'You recorded attendance for Science class',
                'created_at' => now()->subHours(2),
            ],
            (object)[
                'title' => 'Quiz Created',
                'description' => 'You created a new quiz for Mathematics class',
                'created_at' => now()->subDays(1),
            ],
            (object)[
                'title' => 'Exam Graded',
                'description' => 'You finished grading midterm exams for English class',
                'created_at' => now()->subDays(3),
            ],
        ]);

        return view('teacher.dashboard', compact(
            'classCount',
            'studentCount',
            'scheduleCount',
            'todayAttendanceCount',
            'upcomingClasses',
            'highPerformers',
            'averagePerformers',
            'lowPerformers',
            'recentActivities',
            'subjects'
        ));
    }
    private function dayToNumber($day)
    {
        switch (strtolower($day)) {
            case 'monday':
                return 1;
            case 'tuesday':
                return 2;
            case 'wednesday':
                return 3;
            case 'thursday':
                return 4;
            case 'friday':
                return 5;
            default:
                return 0; // Should not happen
        }
    }

    public function loggedTeacherSchedules()
    {
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');

        $schedules = Schedule::where('teacher_id', $teacherId)->get();
        $events = [];
        $subjectColors = [];
        $colorPalette = ['#4C51BF', '#6B46C1', '#9F7AEA', '#ED64A6', '#F56565', '#ED8936', '#ECC94B', '#48BB78', '#38B2AC', '#4299E1'];
        $colorIndex = 0;

        foreach ($schedules as $schedule) {
            if (!isset($subjectColors[$schedule->subject_id])) {
                $subjectColors[$schedule->subject_id] = $colorPalette[$colorIndex % count($colorPalette)];
                $colorIndex++;
            }

            $daysOfWeek = is_array($schedule->day_of_week) ? $schedule->day_of_week : [$schedule->day_of_week];
            $days = array_map([$this, 'dayToNumber'], $daysOfWeek);

            $events[] = [
                'title' => $schedule->subject->name,
                'startTime' => $schedule->start_time,
                'endTime' => $schedule->end_time,
                'daysOfWeek' => $days,
                'url' => route('admin.schedules.show', $schedule),
                'extendedProps' => [
                    'section' => $schedule->section->name,
                    'subject' => $schedule->subject->name,
                    'room' => $schedule->room,
                ],
                'backgroundColor' => $subjectColors[$schedule->subject_id],
                'borderColor' => $subjectColors[$schedule->subject_id],
            ];
        }
        return view('teacher.schedules.index', ['events' => json_encode($events)]);
    }
    public function classes()
    {
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id',  $userId)->value('id');
        $gradeLevels = GradeLevel::all();
        $subjects = Subject::whereHas('schedules', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->get();
        $sections = Section::where('teacher_id', $teacherId)->get();

        return view('teacher.classes', compact('sections', 'subjects'));
    }

    public function getStudentsForSection(Section $section)
    {
        // Optional: Add authorization to ensure the logged-in teacher can view this section
        // For example: if ($section->teacher_id !== Auth::id()) { abort(403); }

        $students = Student::where('section_id', $section->id)->get();

        return response()->json($students);
    }

    public function students()
    {
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');
        // Get sections where the teacher is an adviser (advisory_id)
        // or teaches a subject (through section_subject pivot)
        $sectionIds = Section::where('teacher_id', $teacherId)
            ->orWhereHas('subjects', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->pluck('id')
            ->toArray();

        // Get sections and students for those sections
        $sections = Section::whereIn('id', $sectionIds)->get();
        $students = Student::whereIn('section_id', $sectionIds)->get();

        return view('teacher.students', compact('sections', 'students'));
    }

    public function grades()
    {
        return view('teacher.grades.index');
    }

    public function gradebookQuiz()
    {
        $sections = Section::all();
        $subjects = Section::all();

        return view('teacher.gradebook.quiz', compact('sections', 'subjects'));
    }

    public function gradebookExam()
    {
        $sections = Section::all();
        $subjects = Section::all();

        return view('teacher.gradebook.exam', compact('sections', 'subjects'));
    }

    public function leastLearnedSubjects()
    {

        $subjects = Subject::all();
        $sections = Section::all();


        return view('teacher.least-learned.subjects', compact('subjects', 'sections'));
    }


    public function takeAttendance()
    {
        $userId = Auth::user()->id;
        $teacherId = Teacher::where('user_id', $userId)->value('id');
        // Get all schedules for this teacher
        $schedules = Schedule::where('teacher_id', $teacherId)->get();
        $gradeLevels = GradeLevel::all();
        // Only pass sections and not subjects
        return view('teacher.attendance.take', compact('schedules', 'teacherId', 'gradeLevels'));
    }

    public function attendanceRecords()
    {
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');

        // Get grouped attendance records by date, section, and subject
        $attendanceRecords = Attendance::where('attendances.teacher_id', $teacherId)

            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
            ->select(
                'attendances.date',
                'sections.name as section_name',
                'subjects.name as subject_name',
                'subjects.id as subject_id',
                'sections.id as section_id',
                DB::raw('COUNT(CASE WHEN attendances.status = "present" THEN 1 END) as present_count'),
                DB::raw('COUNT(CASE WHEN attendances.status = "late" THEN 1 END) as late_count'),
                DB::raw('COUNT(CASE WHEN attendances.status = "absent" THEN 1 END) as absent_count'),
                DB::raw('COUNT(CASE WHEN attendances.status = "excused" THEN 1 END) as excused_count'),
                DB::raw('MIN(attendances.id) as id')
            )
            ->groupBy('attendances.date', 'sections.name', 'subjects.name', 'subjects.id', 'sections.id')
            ->orderBy('attendances.date', 'desc')
            ->get();

        $subjects = Subject::all();
        $sections = Section::all();

        return view('teacher.attendance.records', compact('subjects', 'sections', 'attendanceRecords'));
    }

    /**
     * Delete attendance record
     */
    public function deleteAttendanceRecord($id)
    {
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');

        // Find all attendance records with the same date, subject, and section as the record with $id
        $recordToDelete = Attendance::findOrFail($id);

        $date = $recordToDelete->date;
        $subjectId = $recordToDelete->subject_id;
        $studentIds = Student::where('section_id', $recordToDelete->student->section_id)->pluck('id')->toArray();

        // Delete all attendance records for this date, subject, and section
        $deletedCount = Attendance::where('attendances.date', $date)
            ->where('attendances.subject_id', $subjectId)
            ->where('attendances.teacher_id', $teacherId)
            ->whereIn('attendances.student_id', $studentIds)
            ->delete();

        return redirect()->route('teacher.attendance.records')
            ->with('success', "Attendance record deleted successfully. {$deletedCount} entries were removed.");
    }

    /**
     * Get students for a section
     */
    public function getStudents(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
        ]);

        $section = Section::with('gradeLevel')->findOrFail($request->section_id);
        $subject = Subject::findOrFail($request->subject_id);
        $date = $request->date;

        // Get schedule for this section and subject if available
        $schedule = Schedule::where([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ])->first();

        // Get all students in this section
        $students = Student::where('section_id', $section->id)->get();

        // Get existing attendance records for this section, subject and date
        $existingAttendance = Attendance::where([
            'subject_id' => $subject->id,
            'date' => $date,
        ])->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');

        // Format attendance data
        $attendance = [];
        foreach ($existingAttendance as $item) {
            $attendance[$item->student_id] = [
                'status' => $item->status,
                'remarks' => $item->remarks
            ];
        }

        // Format student data with attendance information
        $formattedStudents = [];
        foreach ($students as $student) {
            $formattedStudents[] = [
                'id' => $student->id,
                'student_id' => $student->student_id ?? $student->lrn ?? 'N/A',
                'name' => $student->full_name ?? $student->name,
                'attendance' => $attendance[$student->id] ?? null
            ];
        }

        return response()->json([
            'section' => $section,
            'subject' => $subject,
            'schedule' => $schedule,
            'students' => $formattedStudents
        ]);
    }

    /**
     * Process QR code scan for attendance
     */
    public function scanAttendance(Request $request)
    {
        $request->validate([
            'bar_code' => 'required|string',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
        ]);

        // Find student by bar code
        $student = Student::where('bar_code', $request->bar_code)
            ->orWhere('lrn', $request->bar_code)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with this QR code'
            ], 404);
        }

        // Verify student belongs to the selected section
        if ($student->section_id != $request->section_id) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not in the selected section'
            ], 400);
        }

        // Create or update attendance record
        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $student->id,
                'subject_id' => $request->subject_id,
                'date' => $request->date
            ],
            [
                'status' => 'present',
                'teacher_id' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'student_name' => $student->full_name,
            'student_id' => $student->id
        ]);
    }

    /**
     * Save attendance for multiple students
     */
    public function saveAttendance(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'date' => 'required|date',
            'quarter' => 'required|string', // Accept string (e.g., '1st Quarter')
            'status' => 'required|array',
            'remarks' => 'nullable|array',
        ]);

        $teacherId = Auth::id();

        // Process each student's attendance
        foreach ($request->status as $studentId => $status) {
            // Validate student ID
            if (!is_numeric($studentId)) {
                continue; // Skip if not a valid student ID
            }

            $remarks = $request->remarks[$studentId] ?? null;

            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'subject_id' => $request->subject_id,
                    'date' => $request->date,
                    'quarter' => $request->quarter
                ],
                [
                    'status' => $status,
                    'remarks' => $remarks,
                    'teacher_id' => $teacherId,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved successfully'
        ]);
    }

    /**
     * Get sections by grade level
     */
    public function getSectionsByGradeLevel(Request $request)
    {
        $request->validate([
            'grade_level' => 'required',
        ]);

        // Filter sections by the grade_level value (not ID)
        $sections = Section::where('grade_level', $request->grade_level)
            ->orderBy('name')
            ->get();

        return response()->json([
            'sections' => $sections
        ]);
    }
}
