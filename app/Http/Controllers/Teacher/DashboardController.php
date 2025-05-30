<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $teacherId = Auth::id();

        // Get today's date
        $today = now()->toDateString();
        $dayOfWeek = now()->format('l'); // e.g., "Monday"

        // Count classes (schedules) assigned to this teacher
        $classCount = \App\Models\Schedule::where('teacher_id', $teacherId)->distinct('subject_id', 'section_id')->count();

        // Get subjects taught by this teacher
        $subjects = \App\Models\Subject::whereHas('schedules', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->get();

        // Get sections taught by this teacher
        $sections = \App\Models\Section::whereHas('schedules', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->get();

        // Count students in those sections
        $studentCount = \App\Models\Student::whereIn('section_id', $sections->pluck('id'))->count();

        // Count attendance records created by this teacher today
        $todayAttendanceCount = \App\Models\Attendance::where('teacher_id', $teacherId)
            ->where('date', $today)
            ->count();

        // Get upcoming classes for today (schedules)
        $currentTime = now()->format('H:i:s');
        $upcomingClasses = \App\Models\Schedule::where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
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

    public function classes()
    {
        $teacherId = Auth::id();

        // Get sections where the teacher is an adviser (advisory_id)
        // or teaches a subject (through section_subject pivot)
        $sectionIds = Section::where('adviser_id', $teacherId)
            ->orWhereHas('subjects', function ($query) use ($teacherId) {
                $query->where('section_subject.teacher_id', $teacherId);
            })
            ->pluck('id')
            ->toArray();

        // Get classes (sections) and students for those sections
        $classes = Section::whereIn('id', $sectionIds)->get();
        $students = Student::whereIn('section_id', $sectionIds)->get();

        return view('teacher.classes.view', compact('classes', 'students'));
    }

    public function students()
    {
        $teacherId = Auth::id();

        // Get sections where the teacher is an adviser (advisory_id)
        // or teaches a subject (through section_subject pivot)
        $sectionIds = Section::where('adviser_id', $teacherId)
            ->orWhereHas('subjects', function ($query) use ($teacherId) {
                $query->where('section_subject.teacher_id', $teacherId);
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
        return view('teacher.grades.edit');
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
        $subjects = Subject::all();
        $sections = Section::all();
        $gradeLevels = GradeLevel::orderBy('level')->get();
        return view('teacher.attendance.take', compact('subjects', 'sections', 'gradeLevels'));
    }

    public function attendanceRecords()
    {
        $subjects = Subject::all();

        $sections = Section::all();
        return view('teacher.attendance.records', compact('subjects', 'sections'));
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
        $schedule = \App\Models\Schedule::where([
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
                'qr_code' => $student->qr_code ?? $student->lrn ?? 'N/A',
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
            'status' => 'required|array',
            'remarks' => 'nullable|array',
        ]);

        $teacherId = Auth::id();
        $now = now();

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
                    'date' => $request->date
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
