<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Llc;
use App\Models\SchoolYear;
use App\Models\Enrollment;
use App\Models\Grade;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AbsentAlertMail;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        // Get the authenticated user and their teacher profile
        $teacher = Auth::user()->teacher;

        if (!$teacher) {
            // Handle cases where the user is not a teacher
            abort(403, 'User is not a teacher.');
        }

        $activeSchoolYear = SchoolYear::active()->first();

        // If no active school year, return the view with a message and empty data
        if (!$activeSchoolYear) {
            return view('teacher.dashboard')->with('error', 'Data cannot be loaded because no school year is active.');
        }

        // --- DATA FOR CARDS ---

        // 1. Get all unique Class IDs the teacher interacts with for the active school year
        $advisoryClassIds = $teacher->advisoryClasses()
            ->where('school_year_id', $activeSchoolYear->id)
            ->pluck('id');

        $scheduledClassIds = $teacher->schedules()
            ->whereHas('class', fn($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->pluck('class_id');

        $allClassIds = $advisoryClassIds->merge($scheduledClassIds)->unique();

        // Card 1: My Classes Count
        $classCount = $allClassIds->count();

        // Card 2: Student Count (unique students across all their classes)
        $studentCount = Enrollment::whereIn('class_id', $allClassIds)->distinct('student_id')->count();

        // Card 3: Today's Attendance Count (students marked by this teacher today)
        $todayAttendanceCount = Attendance::where('teacher_id', $teacher->id)
            ->whereDate('date', today())
            ->distinct('student_id')
            ->count();

        // --- DATA FOR TABLES AND LISTS ---

        // Upcoming Schedules for Today
        $dayOfWeek = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i:s');

        $upcomingSchedules = $teacher->schedules()
            ->whereHas('class', fn($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->whereJsonContains('day_of_week', $dayOfWeek)
            ->whereTime('start_time', '>=', $currentTime)
            ->with(['class.section.gradeLevel', 'subject'])
            ->orderBy('start_time')
            ->get();

        // Card 4: Count of today's upcoming schedules
        $scheduleCount = $upcomingSchedules->count();

        // Student Performance Data
        $studentIds = Enrollment::whereIn('class_id', $allClassIds)->pluck('student_id')->unique();
        $grades = Grade::whereIn('student_id', $studentIds)
            ->where('school_year_id', $activeSchoolYear->id)
            ->select('student_id', DB::raw('AVG(grade) as average_grade'))
            ->groupBy('student_id')
            ->get();

        $highPerformers = $grades->where('average_grade', '>=', 90)->count();
        $averagePerformers = $grades->where('average_grade', '>=', 75)->where('average_grade', '<', 90)->count();
        $lowPerformers = $grades->where('average_grade', '<', 75)->count();

        // Subjects for the performance filter dropdown
        $subjects = Subject::whereIn('id', function ($query) use ($teacher, $activeSchoolYear) {
            $query->select('schedules.subject_id')
                ->from('schedules')
                ->join('classes', 'schedules.class_id', '=', 'classes.id')
                ->where('schedules.teacher_id', $teacher->id)
                ->where('classes.school_year_id', $activeSchoolYear->id);
        })->get();

        // Placeholder for recent activities
        $recentActivities = collect([]);

        return view('teacher.dashboard', compact(
            'classCount',
            'studentCount',
            'scheduleCount',
            'todayAttendanceCount',
            'upcomingSchedules',
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
        $teacher = Auth::user()->teacher;
        $activeSchoolYear = SchoolYear::active()->first();

        if (!$activeSchoolYear) {
            return view('teacher.classes')->with('error', 'No active school year has been set.');
        }

        // 1. Get IDs of classes where the teacher is the adviser
        $advisoryClassIds = $teacher->advisoryClasses()
            ->where('school_year_id', $activeSchoolYear->id)
            ->pluck('id');

        // 2. Get IDs of classes where the teacher has a schedule
        $scheduledClassIds = $teacher->schedules()
            ->whereHas('class', fn($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->pluck('class_id');

        // 3. Merge and get unique IDs, then fetch the full Class models
        $allClassIds = $advisoryClassIds->merge($scheduledClassIds)->unique();

        $classes = Classes::whereIn('id', $allClassIds)
            ->with(['section.gradeLevel', 'teacher.user', 'enrollments']) // Eager load needed data
            ->get()
            ->sortBy('section.gradeLevel.level');

        return view('teacher.classes', compact('classes', 'teacher'));
    }
    public function viewClass(Classes $class)
    {
        $class->load([
            'section.gradeLevel',
            'teacher.user',
            'schoolYear',
            'enrollments.student.guardian.user',
            'schedules.subject',
            'schedules.teacher.user'
        ]);

        // Get the currently logged-in teacher to pass to the view
        $teacher = Auth::user()->teacher;

        return view('teacher.classes.view', compact('class', 'teacher'));
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
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');
        $sections = Section::where('teacher_id', $teacherId)->get();
        $subjects = Subject::where('teacher_id', $teacherId)->get();

        return view('teacher.grades', compact('sections', 'subjects'));
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

    public function leastLearnedCompetencies()
    {
        // Data for dropdown filters
        $subjects = Subject::all();
        $sections = Section::all();

        // **FIX: Query the LLC data**
        // This query fetches all LLCs, counts their competencies,
        // and joins the subject and section tables to get their names.
        $llcs = Llc::withCount('llcItems') // This adds an `llc_items_count` attribute
            ->join('subjects', 'llc.subject_id', '=', 'subjects.id')
            ->join('sections', 'llc.section_id', '=', 'sections.id')
            ->select(
                'llc.*', // Selects all columns from the `llc` table
                'subjects.name as subject_name',
                'sections.name as section_name'
            )
            ->orderBy('llc.created_at', 'desc')
            ->get();
        $gradeLevels = GradeLevel::all();
        return view('llc', compact('llcs', 'subjects', 'sections', 'gradeLevels'));
    }
    // **FIX: Pass the new $ll
    public function leastLearnedSubjects()
    {
        // Data for dropdown filters
        $subjects = Subject::all();
        $sections = Section::all();

        // **FIX: Query the LLC data**
        // This query fetches all LLCs, counts their competencies,
        // and joins the subject and section tables to get their names.
        $llcs = Llc::withCount('llcItems') // This adds an `llc_items_count` attribute
            ->join('subjects', 'llc.subject_id', '=', 'subjects.id')
            ->join('sections', 'llc.section_id', '=', 'sections.id')
            ->select(
                'llc.*', // Selects all columns from the `llc` table
                'subjects.name as subject_name',
                'sections.name as section_name'
            )
            ->orderBy('llc.created_at', 'desc')
            ->get();

        // **FIX: Pass the new $llcs variable to the view**
        return view('teacher.least-learned.subjects', compact('llcs', 'subjects', 'sections'));
    }



    public function takeAttendance()
    {
        $userId = Auth::user()->id;
        $teacher = Teacher::where('user_id', $userId)->firstOrFail();
        $teacherId = $teacher->id;

        // Get the active school year
        $activeSchoolYear = SchoolYear::active()->first();

        // Get all schedules for this teacher in the active school year
        $schedules = Schedule::with('class.section', 'subject')
            ->where('teacher_id', $teacherId)
            ->whereHas('class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->get();

        // Get unique sections (classes) from the schedules
        $sections = $schedules->pluck('class.section')->unique('id');

        return view('teacher.attendance.take', compact('sections', 'teacherId'));
    }

    public function attendanceRecords()
    {
        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');

        // Get grouped attendance records by date, section, and subject
        $attendanceRecords = Attendance::where('attendances.teacher_id', $teacherId)

            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
            ->select(
                'attendances.date',
                'classes.name as class_name',
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

        $section = Classes::with('sections')->findOrFail($request->section_id);
        $subject = Subject::findOrFail($request->subject_id);
        $date = $request->date;

        $class =
            // Get schedule for this section and subject if available
            $schedule = Schedule::where([
                'class_id' => $section->id,
                'subject_id' => $subject->id,
            ])->first();

        // Get all students enrolled in this section (via enrollments and classes)
        $students = Student::whereIn('id', function ($query) use ($section) {
            $query->select('student_id')
                ->from('enrollments')
                ->where('class_id', function ($subQuery) use ($section) {
                    $subQuery->select('id')
                        ->from('classes')
                        ->where('section_id', $section->id);
                });
        })->get();

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

        $userId = Auth::id();
        $teacherId = Teacher::where('user_id', $userId)->value('id');

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

            // Check for absences
            $this->checkAbsences($studentId, $teacherId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance saved successfully'
        ]);
    }

    private function checkAbsences($studentId, $teacherId)
    {
        $student = Student::find($studentId);
        $teacher = Teacher::find($teacherId);

        // Check for 3 consecutive absences
        $consecutiveAbsences = 0;
        for ($i = 0; $i < 3; $i++) {
            $date = now()->subDays($i)->toDateString();
            $attendance = Attendance::where('student_id', $studentId)
                ->where('date', $date)
                ->where('status', 'absent')
                ->first();

            if ($attendance) {
                $consecutiveAbsences++;
            } else {
                break;
            }
        }

        if ($consecutiveAbsences >= 3) {
            Mail::to($teacher->user->email)->send(new AbsentAlertMail($student, $teacher, $consecutiveAbsences));
        }

        // Check for 5 total absences in the current month
        $totalAbsences = Attendance::where('student_id', $studentId)
            ->where('status', 'absent')
            ->whereMonth('date', now()->month)
            ->count();

        if ($totalAbsences >= 5) {
            Mail::to($teacher->user->email)->send(new AbsentAlertMail($student, $teacher, $totalAbsences));
        }
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

    public function getGradesForSection(Section $section)
    {
        $grades = Grade::whereHas('student', function ($query) use ($section) {
            $query->where('section_id', $section->id);
        })
            ->with(['student']) // Eager load the student relationship
            ->get();

        dd($grades);
        // This returns the simple list of grades that the new table structure needs
        return response()->json($grades);
    }
}
