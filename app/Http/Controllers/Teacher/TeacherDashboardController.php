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
use App\Models\SchoolYear;
use App\Models\Enrollment;
use App\Models\Grade;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Mail\AbsentAlertMail;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        try {
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
                ->where('date', today()->toDateString())
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
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@index error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load dashboard: ' . $e->getMessage());
        }
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
        try {
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

                $daysOfWeekRaw = $schedule->day_of_week;

                if (is_string($daysOfWeekRaw)) {
                    $decoded = json_decode($daysOfWeekRaw, true);
                    $daysOfWeek = is_array($decoded) ? $decoded : [$daysOfWeekRaw];
                } else {
                    $daysOfWeek = is_array($daysOfWeekRaw) ? $daysOfWeekRaw : [$daysOfWeekRaw];
                }
                $days = array_map([$this, 'dayToNumber'], $daysOfWeek);

                $events[] = [
                    'title' => $schedule->subject->name,
                    'startTime' => $schedule->start_time->format('H:i:s'),
                    'endTime' => $schedule->end_time->format('H:i:s'),
                    'daysOfWeek' => $days,
                    'url' => route('admin.schedules.show', $schedule),
                    'allDay' => false,
                    'extendedProps' => [
                        'section' => $schedule->class->section->name,
                        'subject' => $schedule->subject->name,
                        'room' => $schedule->room,
                    ],
                    'backgroundColor' => $subjectColors[$schedule->subject_id],
                    'borderColor' => $subjectColors[$schedule->subject_id],
                ];
            }
            return view('teacher.schedules.index', ['events' => json_encode($events)]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@loggedTeacherSchedules error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load schedules: ' . $e->getMessage());
        }
    }
    public function classes()
    {
        try {
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
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@classes error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load classes: ' . $e->getMessage());
        }
    }
    public function viewClass(Classes $class)
    {
        try {
            $class->load([
                'section.gradeLevel',
                'teacher.user',
                'schoolYear',
                'enrollments.student.guardian.user',
                'schedules.subject',
                'schedules.teacher.user'
            ]);

            $teacher = Auth::user()->teacher;
            $isAdviser = $teacher && (int) $class->teacher_id === (int) $teacher->id;

            $subjects = collect();
            $assignableTeachers = collect();

            if ($isAdviser) {
                $gradeLevelId = optional($class->section)->grade_level_id;

                if ($gradeLevelId) {
                    $subjects = Subject::where('grade_level_id', $gradeLevelId)
                        ->orderBy('name')
                        ->get();
                }

                $assignableTeachers = Teacher::with('user')
                    ->get()
                    ->sortBy(function (Teacher $candidate) {
                        $user = $candidate->user;
                        $last = $user ? strtolower($user->last_name) : '';
                        $first = $user ? strtolower($user->first_name) : '';
                        return trim($last . ' ' . $first);
                    })
                    ->values();
            }

            return view('teacher.classes.view', compact(
                'class',
                'teacher',
                'isAdviser',
                'subjects',
                'assignableTeachers'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@viewClass error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load class view: ' . $e->getMessage());
        }
    }

    public function storeSchedule(Request $request, Classes $class)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (!$teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'Only the adviser can manage schedules for this class.');
            }

            $class->loadMissing('section');

            $validated = $request->validate([
                'schedule_id' => 'nullable|exists:schedules,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
                'day_of_week' => 'required|array|min:1',
                'day_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'room' => 'nullable|string|max:255',
            ]);

            $section = $class->section;
            $gradeLevelId = $section?->grade_level_id;

            if (!$gradeLevelId) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Unable to determine the grade level for this class.');
            }

            $subject = Subject::findOrFail($validated['subject_id']);

            if ((int) $subject->grade_level_id !== (int) $gradeLevelId) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Selected subject does not belong to this class grade level.');
            }

            $payload = [
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'day_of_week' => json_encode(array_values($validated['day_of_week'])),
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'room' => ($validated['room'] ?? '') !== '' ? $validated['room'] : null,
            ];

            // Conflict checks:
            $days = array_values($validated['day_of_week']);
            $start = $validated['start_time'];
            $end = $validated['end_time'];
            $assignedTeacherId = $validated['teacher_id'];
            $scheduleIdToExclude = $validated['schedule_id'] ?? null;

            // Build day-matching query for reuse
            $dayQueryCallback = function ($q) use ($days) {
                if (empty($days)) {
                    return;
                }
                // Start with first day
                $first = array_shift($days);
                $q->whereJsonContains('day_of_week', $first);
                foreach ($days as $day) {
                    $q->orWhereJsonContains('day_of_week', $day);
                }
            };

            // Check conflicts within the same class
            $classConflicts = $class->schedules()->where(function ($q) use ($dayQueryCallback) {
                $dayQueryCallback($q);
            })->where(function ($q) use ($start, $end) {
                // overlap if existing.start < new.end AND existing.end > new.start
                $q->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start);
            });

            if ($scheduleIdToExclude) {
                $classConflicts->where('id', '!=', $scheduleIdToExclude);
            }

            $conflict = $classConflicts->with('subject', 'teacher.user')->first();
            if ($conflict) {
                $conflictDays = $conflict->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(',', $conflictDays) : $conflictDays;
                $conflictMsg = sprintf(
                    "Schedule conflicts with existing class schedule: %s (%s) %s - %s",
                    optional($conflict->subject)->name ?? 'Subject',
                    $conflictLabel,
                    optional($conflict->start_time)?->format('g:i A') ?? $conflict->start_time,
                    optional($conflict->end_time)?->format('g:i A') ?? $conflict->end_time
                );
                return redirect()->back()->withInput()->with('error', $conflictMsg);
            }

            // Check conflicts for the assigned teacher across any class
            $teacherConflicts = Schedule::where('teacher_id', $assignedTeacherId)
                ->where(function ($q) use ($dayQueryCallback) {
                    $dayQueryCallback($q);
                })->where(function ($q) use ($start, $end) {
                    $q->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start);
                });

            if ($scheduleIdToExclude) {
                $teacherConflicts->where('id', '!=', $scheduleIdToExclude);
            }

            $tconflict = $teacherConflicts->with('class.section', 'subject')->first();
            if ($tconflict) {
                $conflictDays = $tconflict->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(',', $conflictDays) : $conflictDays;
                $conflictMsg = sprintf(
                    "Assigned teacher has a conflicting schedule: %s (%s) %s - %s (Class: %s)",
                    optional($tconflict->subject)->name ?? 'Subject',
                    $conflictLabel,
                    optional($tconflict->start_time)?->format('g:i A') ?? $tconflict->start_time,
                    optional($tconflict->end_time)?->format('g:i A') ?? $tconflict->end_time,
                    optional($tconflict->class->section)->name ?? 'Class'
                );
                return redirect()->back()->withInput()->with('error', $conflictMsg);
            }

            $message = 'Schedule assigned successfully.';

            if (!empty($validated['schedule_id'])) {
                $schedule = $class->schedules()->where('id', $validated['schedule_id'])->firstOrFail();
                $schedule->update($payload);
                $message = 'Schedule updated successfully.';
            } else {
                $existingSchedule = $class->schedules()
                    ->where('subject_id', $validated['subject_id'])
                    ->first();

                if ($existingSchedule) {
                    $existingSchedule->update($payload);
                    $message = 'Schedule updated successfully.';
                } else {
                    $class->schedules()->create($payload);
                }
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@storeSchedule error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Unable to save schedule: ' . $e->getMessage());
        }
    }

    public function destroySchedule(Request $request, Classes $class, Schedule $schedule)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (!$teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'Only the adviser can remove schedules for this class.');
            }

            if ($schedule->class_id !== $class->id) {
                abort(404, 'Schedule not found for this class.');
            }

            $schedule->delete();

            return redirect()->back()->with('success', 'Schedule removed successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@destroySchedule error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to remove schedule: ' . $e->getMessage());
        }
    }
    public function getStudentsForSection(Section $section)
    {
        try {
            // Optional: Add authorization to ensure the logged-in teacher can view this section
            // For example: if ($section->teacher_id !== Auth::id()) { abort(403); }

            $students = Student::where('section_id', $section->id)->get();

            return response()->json($students);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getStudentsForSection error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Unable to retrieve students: ' . $e->getMessage()], 500);
        }
    }

    public function students()
    {
        try {
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
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@students error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load students: ' . $e->getMessage());
        }
    }

    public function grades()
    {
        try {
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();
            // Advisory classes: classes where this teacher is the adviser for the active school year
            $advisoryClasses = Classes::where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->with('section.gradeLevel')
                ->get();

            return view('teacher.grades.index', [
                'classes' => $advisoryClasses,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@grades error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load grades: ' . $e->getMessage());
        }
    }

    public function showGrades(Classes $class)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (!$teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'You are not allowed to view grades for this class.');
            }

            $class->loadMissing('section.gradeLevel');

            $students = $class->students()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            return view('teacher.grades.show', compact('class', 'students'));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@showGrades error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load class grades: ' . $e->getMessage());
        }
    }

    public function gradebookQuiz()
    {
        try {
            $sections = Section::all();
            $subjects = Section::all();

            return view('teacher.gradebook.quiz', compact('sections', 'subjects'));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@gradebookQuiz error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load gradebook (quiz): ' . $e->getMessage());
        }
    }

    public function gradebookExam()
    {
        try {
            $sections = Section::all();
            $subjects = Section::all();

            return view('teacher.gradebook.exam', compact('sections', 'subjects'));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@gradebookExam error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load gradebook (exam): ' . $e->getMessage());
        }
    }

    public function takeAttendance()
    {
        try {
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
            $sections = $schedules->pluck('class')->unique('id');

            return view('teacher.attendance.take', compact('sections', 'teacherId'));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@takeAttendance error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load attendance page: ' . $e->getMessage());
        }
    }

    public function attendanceRecords()
    {
        try {
            $userId = Auth::id();
            $teacherId = Teacher::where('user_id', $userId)->value('id');

            // Fetch individual attendance records
            $attendanceRecords = Attendance::where('attendances.teacher_id', $teacherId)
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->join('classes', 'attendances.class_id', '=', 'classes.id')
                ->join('sections', 'classes.section_id', '=', 'sections.id')
                ->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
                ->select(
                    'attendances.id',
                    'attendances.date',
                    'attendances.status',
                    'students.first_name',
                    'students.last_name',
                    'sections.name as section_name',
                    'subjects.name as subject_name',
                    'attendances.class_id'
                )
                ->orderBy('attendances.date', 'desc')
                ->orderBy('students.last_name', 'asc')
                ->get();

            // Get all subjects and sections for the filter dropdowns
            $subjects = Subject::all();
            $sections = Section::all();

            // Get the classes assigned to the logged-in teacher for the summary modal
            $teacherClasses = Classes::where('teacher_id', $teacherId)
                ->with('section') // Eager load the section for display
                ->get();

            return view('teacher.attendance.records', compact(
                'subjects',
                'sections',
                'attendanceRecords',
                'teacherClasses'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@attendanceRecords error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Unable to load attendance records: ' . $e->getMessage());
        }
    }
    /**
     * Delete attendance record
     */
    public function deleteAttendanceRecord($id)
    {
        try {
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
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@deleteAttendanceRecord error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to delete attendance record: ' . $e->getMessage());
        }
    }

    /**
     * Get students for a section
     */
    public function getStudents(Request $request)
    {
        try {
            $request->validate([
                'section_id' => 'required',
                'subject_id' => 'required|exists:subjects,id',
                'date' => 'required|date',
            ]);

            $section = Classes::with('sections')->findOrFail($request->section_id);
            $subject = Subject::findOrFail($request->subject_id);
            $date = $request->date;

            $activeSchoolYear = SchoolYear::active()->first();

            if (!$activeSchoolYear) {
                return response()->json(['message' => 'No active school year found.'], 400);
            }

            // Get the class for this section and active school year
            $class = Classes::where('section_id', $section->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->firstOrFail();

            // Get schedule for this class and subject if available
            $schedule = Schedule::where([
                'class_id' => $class->id,
                'subject_id' => $subject->id,
            ])->first();

            // Get all students enrolled in this class
            $students = Student::whereIn('id', function ($query) use ($class) {
                $query->select('student_id')
                    ->from('enrollments')
                    ->where('class_id', $class->id);
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
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getStudents error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Unable to retrieve students: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process QR code scan for attendance
     */
    public function scanAttendance(Request $request)
    {
        try {
            $request->validate([
                'bar_code' => 'required|string',
                'section_id' => 'required',
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

            $activeSchoolYear = SchoolYear::active()->first();

            if (!$activeSchoolYear) {
                return response()->json(['message' => 'No active school year found.'], 400);
            }

            // Create or update attendance record
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $request->subject_id,
                    'date' => $request->date,
                    'school_year_id' => $activeSchoolYear->id,
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
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@scanAttendance error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Unable to record attendance: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Save attendance for multiple students
     */
    public function saveAttendance(Request $request)
    {
        try {
            $request->validate([
                'section_id' => 'required',
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

                $activeSchoolYear = SchoolYear::active()->first();

                if (!$activeSchoolYear) {
                    return response()->json(['message' => 'No active school year found.'], 400);
                }

                Attendance::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $request->subject_id,
                        'class_id' => $request->section_id,
                        'date' => $request->date,
                        'quarter' => $request->quarter,
                        'school_year_id' => $activeSchoolYear->id,
                    ],
                    [
                        'status' => $status,
                        'remarks' => $remarks,
                        'teacher_id' => $teacherId,
                    ]
                );

                $this->checkAbsences($studentId, $teacherId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully'
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@saveAttendance error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Unable to save attendance: ' . $e->getMessage()], 500);
        }
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
            // Check if an email has been sent recently for this student to avoid spamming
            $lastSent = cache('absent_alert_sent_' . $studentId);
            if (!$lastSent || now()->diffInHours($lastSent) >= 24) {
                Mail::to($teacher->user->email)->send(new AbsentAlertMail($student, $teacher, $consecutiveAbsences));
                cache(['absent_alert_sent_' . $studentId => now()], now()->addHours(24));
            }
        }
    }

    /**
     * Get sections by grade level
     */
    public function getSectionsByGradeLevel(Request $request)
    {
        try {
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
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getSectionsByGradeLevel error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Unable to retrieve sections: ' . $e->getMessage()], 500);
        }
    }

    public function getGradesForSection(Section $section)
    {
        try {
            $grades = Grade::whereHas('student', function ($query) use ($section) {
                $query->where('section_id', $section->id);
            })
                ->with(['student']) // Eager load the student relationship
                ->get();

            // dd($grades);
            // This returns the simple list of grades that the new table structure needs
            return response()->json($grades);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getGradesForSection error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Unable to retrieve grades: ' . $e->getMessage()], 500);
        }
    }

    public function updateAttendance(Request $request, $id)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'status' => ['required', \Illuminate\Validation\Rule::in(['present', 'late', 'absent', 'excused'])],
            ]);

            // Find the attendance record
            $attendance = Attendance::findOrFail($id);

            // Update the status
            $attendance->status = $request->input('status');
            $attendance->save();

            // Redirect back with a success message
            return redirect()->route('teacher.attendance.records')->with('success', 'Attendance record updated successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@updateAttendance error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to update attendance: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified attendance record from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyAttendance($id)
    {
        try {
            // Find and delete the attendance record
            $attendance = Attendance::findOrFail($id);
            $attendance->delete();

            // Redirect back with a success message
            return redirect()->route('teacher.attendance.records')->with('success', 'Attendance record deleted successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@destroyAttendance error: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to delete attendance: ' . $e->getMessage());
        }
    }
    public function getStudentsBySection(Section $section)
    {
        try {
            $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();

            // Find the specific class instance associated with the section for the active school year
            $class = Classes::where('section_id', $section->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->first();

            // If no class is found for that section in the current year, return no students
            if (!$class) {
                return response()->json([]);
            }

            // Get students who are enrolled in that specific class
            $students = Student::whereHas('enrollments', function ($query) use ($class) {
                $query->where('class_id', $class->id);
            })->orderBy('last_name', 'asc')->get();

            // Format the data as expected by the DataTable in the view
            $studentData = $students->map(function ($student) {
                return [
                    'student_id'   => $student->student_id,
                    'student_name' => $student->last_name . ', ' . $student->first_name,
                    'gender'       => ucfirst($student->gender),
                ];
            });

            return response()->json($studentData);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getStudentsBySection error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Unable to retrieve students: ' . $e->getMessage()], 500);
        }
    }
}
