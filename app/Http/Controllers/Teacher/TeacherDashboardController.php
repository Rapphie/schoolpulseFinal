<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Mail\AbsentAlertMail;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GradeService;
use App\Services\QuarterLockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class TeacherDashboardController extends Controller
{
    public function __construct(private QuarterLockService $quarterLockService) {}

    public function index(Request $request)
    {
        try {
            // Get the authenticated user and their teacher profile
            $teacher = Auth::user()->teacher;

            if (! $teacher) {
                // Handle cases where the user is not a teacher
                abort(403, 'User is not a teacher.');
            }

            $activeSchoolYear = SchoolYear::active()->first();

            // If no active school year, return the view with a message and empty data
            if (! $activeSchoolYear) {
                return view('teacher.dashboard')->with('error', 'Data cannot be loaded because no school year is active.');
            }

            // --- DATA FOR CARDS ---

            // 1. Get all unique Class IDs the teacher interacts with for the active school year
            $advisoryClassIds = $teacher->advisoryClasses()
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id');

            $scheduledClassIds = $teacher->schedules()
                ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                ->pluck('class_id');

            $allClassIds = $advisoryClassIds->merge($scheduledClassIds)->unique();

            // Card 1: My Classes Count
            $classCount = $allClassIds->count();

            // Card 2: Student Count (unique students across all their classes)
            $studentCount = Enrollment::whereIn('class_id', $allClassIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->distinct()
                ->count(DB::raw('COALESCE(student_profile_id, student_id)'));

            // Card 3: Today's Attendance Count (students marked by this teacher today)
            $todayAttendanceCount = Attendance::where('teacher_id', $teacher->id)
                ->where('date', today()->toDateString())
                ->distinct()
                ->count(DB::raw('COALESCE(student_profile_id, student_id)'));

            // --- DATA FOR TABLES AND LISTS ---

            // Upcoming Schedules for Today
            $dayOfWeek = strtolower(now()->format('l'));
            $currentTime = now()->format('H:i:s');

            $upcomingSchedules = $teacher->schedules()
                ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                ->whereJsonContains('day_of_week', $dayOfWeek)
                ->whereTime('start_time', '>=', $currentTime)
                ->with(['class.section.gradeLevel', 'subject'])
                ->orderBy('start_time')
                ->get();

            // Card 4: Count of today's upcoming schedules
            $scheduleCount = $upcomingSchedules->count();

            // Subjects for the performance filter dropdown
            $subjects = Subject::whereIn('id', function ($query) use ($teacher, $activeSchoolYear) {
                $query->select('schedules.subject_id')
                    ->from('schedules')
                    ->join('classes', 'schedules.class_id', '=', 'classes.id')
                    ->where('schedules.teacher_id', $teacher->id)
                    ->where('classes.school_year_id', $activeSchoolYear->id);
            })->orderBy('name')->get();

            $selectedSubjectId = $request->integer('subject_id');
            if ($selectedSubjectId <= 0 || ! $subjects->pluck('id')->contains($selectedSubjectId)) {
                $selectedSubjectId = null;
            }

            // Student Performance Data
            // Get unique student profile keys (prefer student_profile_id) for classes, then compute averages grouped by that key.
            $studentKeys = Enrollment::whereIn('class_id', $allClassIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->select(DB::raw('COALESCE(student_profile_id, student_id) as pid'))
                ->pluck('pid')
                ->unique()
                ->toArray();

            $grades = collect();
            if (! empty($studentKeys)) {
                $gradesQuery = DB::table('grades')
                    ->select(DB::raw('COALESCE(student_profile_id, student_id) as pid'), DB::raw('AVG(grade) as average_grade'))
                    ->whereIn(DB::raw('COALESCE(student_profile_id, student_id)'), $studentKeys)
                    ->where('school_year_id', $activeSchoolYear->id)
                    ->when($selectedSubjectId, function ($query, $subjectId) {
                        $query->where('subject_id', $subjectId);
                    });

                $grades = $gradesQuery->groupBy('pid')->get();
            }

            $highPerformers = $grades->where('average_grade', '>=', 90)->count();
            $averagePerformers = $grades->where('average_grade', '>=', 75)->where('average_grade', '<', 90)->count();
            $lowPerformers = $grades->where('average_grade', '<', 75)->count();

            $recentGradeActivities = Grade::with(['subject', 'student', 'studentProfile.student'])
                ->where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->when($selectedSubjectId, function ($query, $subjectId) {
                    $query->where('subject_id', $subjectId);
                })
                ->latest('updated_at')
                ->take(5)
                ->get()
                ->map(function (Grade $grade) {
                    $student = $grade->student ?? $grade->studentProfile?->student;
                    $studentName = $student
                        ? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
                        : 'a student';
                    $formattedGrade = number_format((float) $grade->grade, 2);

                    return (object) [
                        'type' => 'grade',
                        'title' => 'Grade updated',
                        'description' => ($grade->subject?->name ?? 'Subject').": {$studentName} received {$formattedGrade}.",
                        'created_at' => $grade->updated_at ?? $grade->created_at,
                    ];
                })
                ->values()
                ->toBase();

            $recentAttendanceActivities = Attendance::with(['subject', 'class.section'])
                ->where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->when($selectedSubjectId, function ($query, $subjectId) {
                    $query->where('subject_id', $subjectId);
                })
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Attendance $attendance) {
                    $status = ucfirst($attendance->status ?? 'recorded');
                    $sectionName = $attendance->class?->section?->name;
                    $sectionText = $sectionName ? " in {$sectionName}" : '';

                    return (object) [
                        'type' => 'attendance',
                        'title' => 'Attendance recorded',
                        'description' => ($attendance->subject?->name ?? 'Class')." attendance marked as {$status}{$sectionText}.",
                        'created_at' => $attendance->created_at,
                    ];
                })
                ->values()
                ->toBase();

            $recentEnrollmentActivities = Enrollment::with(['student', 'class.section'])
                ->where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(function (Enrollment $enrollment) {
                    $student = $enrollment->student;
                    $studentName = $student
                        ? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
                        : 'A student';
                    $sectionName = $enrollment->class?->section?->name;
                    $sectionText = $sectionName ? " to {$sectionName}" : '';

                    return (object) [
                        'type' => 'enrollment',
                        'title' => 'Student enrolled',
                        'description' => "{$studentName} was enrolled{$sectionText}.",
                        'created_at' => $enrollment->created_at,
                    ];
                })
                ->values()
                ->toBase();

            $recentActivities = $recentGradeActivities
                ->merge($recentAttendanceActivities)
                ->merge($recentEnrollmentActivities)
                ->sortByDesc(function ($activity) {
                    return $activity->created_at;
                })
                ->take(10)
                ->values();

            $calendarSchedules = $teacher->schedules()
                ->whereHas('class', fn ($query) => $query->where('school_year_id', $activeSchoolYear->id))
                ->with('subject')
                ->get()
                ->map(function (Schedule $schedule) {
                    return [
                        'subject' => $schedule->subject?->name ?? 'Class',
                        'days' => collect($schedule->day_names)->map(fn ($day) => strtolower($day))->values()->all(),
                        'start_time' => $schedule->start_time?->format('h:i A'),
                    ];
                })
                ->values();

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
                'subjects',
                'selectedSubjectId',
                'calendarSchedules'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@index error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load dashboard: '.$e->getMessage());
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
            $activeSchoolYear = SchoolYear::active()->first();

            $schedules = Schedule::query()
                ->with(['subject', 'class.section'])
                ->where('teacher_id', $teacherId)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->whereTime('start_time', '!=', '00:00:00')
                ->whereTime('end_time', '!=', '00:00:00')
                ->when($activeSchoolYear, function ($query) use ($activeSchoolYear) {
                    $query->whereHas('class', function ($classQuery) use ($activeSchoolYear) {
                        $classQuery->where('school_year_id', $activeSchoolYear->id);
                    });
                })
                ->get();
            $events = [];
            $subjectColors = [];
            $colorPalette = ['#4C51BF', '#6B46C1', '#9F7AEA', '#ED64A6', '#F56565', '#ED8936', '#ECC94B', '#48BB78', '#38B2AC', '#4299E1'];
            $colorIndex = 0;

            foreach ($schedules as $schedule) {
                if (! isset($subjectColors[$schedule->subject_id])) {
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
                    // Teachers do not have a dedicated schedule show page.
                    'url' => null,
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
            Log::error('TeacherDashboardController@loggedTeacherSchedules error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load schedules: '.$e->getMessage());
        }
    }

    public function classes()
    {
        try {
            $teacher = Auth::user()->teacher;
            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return view('teacher.classes')->with('error', 'No active school year has been set.');
            }

            // 1. Get IDs of classes where the teacher is the adviser
            $advisoryClassIds = $teacher->advisoryClasses()
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id');

            // 2. Get IDs of classes where the teacher has a schedule
            $scheduledClassIds = $teacher->schedules()
                ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                ->pluck('class_id');

            // 3. Merge and get unique IDs, then fetch the full Class models
            $allClassIds = $advisoryClassIds->merge($scheduledClassIds)->unique();

            $classes = Classes::whereIn('id', $allClassIds)
                ->with(['section.gradeLevel', 'teacher.user', 'enrollments']) // Eager load needed data
                ->get()
                ->sortBy('section.gradeLevel.level');

            // Eager-load teacher's schedules with subjects to avoid N+1 in the view
            $teacher->load('schedules.subject');

            return view('teacher.classes', compact('classes', 'teacher'));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@classes error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load classes: '.$e->getMessage());
        }
    }

    public function viewClass(Classes $class, Request $request)
    {
        try {
            $classId = $request->query('class_id');
            if ($classId && (int) $classId !== (int) $class->id) {
                $class = Classes::findOrFail($classId);
            }

            $class->load([
                'section.gradeLevel',
                'teacher.user',
                'schoolYear',
                'enrollments.student.guardian.user',
                'schedules.subject',
                'schedules.teacher.user',
            ]);

            $teacher = Auth::user()->teacher;
            $isAdviser = $teacher && (int) $class->teacher_id === (int) $teacher->id;

            $subjects = collect();
            $assignableTeachers = collect();
            $sectionHistory = collect();

            if ($isAdviser) {
                // Section History for advisers
                $allClasses = Classes::where('section_id', $class->section_id)
                    ->with(['schoolYear', 'teacher.user', 'enrollments'])
                    ->orderByDesc('school_year_id')
                    ->get();

                $sectionHistory = $allClasses->map(function ($c) {
                    return [
                        'class_id' => $c->id,
                        'school_year' => $c->schoolYear ? $c->schoolYear->name : 'N/A',
                        'adviser' => $c->teacher && $c->teacher->user ? ($c->teacher->user->first_name.' '.$c->teacher->user->last_name) : 'N/A',
                        'capacity' => $c->capacity,
                        'enrolled' => $c->enrollments->count(),
                    ];
                });

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

                        return trim($last.' '.$first);
                    })
                    ->values();
            }

            return view('teacher.classes.view', [
                'class' => $class,
                'section' => $class->section,
                'teacher' => $teacher,
                'isAdviser' => $isAdviser,
                'subjects' => $subjects,
                'assignableTeachers' => $assignableTeachers,
                'sectionHistory' => $sectionHistory,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@viewClass error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load class view: '.$e->getMessage());
        }
    }

    public function storeSchedule(Request $request, Classes $class)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'Only the adviser can manage schedules for this class.');
            }

            $class->loadMissing('section.gradeLevel');

            // For Grade 1, 2, 3: Only allow editing existing schedules (no new ones), and teacher cannot be changed
            $gradeValue = optional($class->section->gradeLevel)->level;
            $isLowerGrade = ! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3]);

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

            // For lower grades: Prevent adding new schedules (only editing allowed)
            if ($isLowerGrade && empty($validated['schedule_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'For Grade 1, 2, and 3, schedules are automatically managed. You cannot manually add new schedules.');
            }

            $section = $class->section;
            $gradeLevelId = $section?->grade_level_id;

            if (! $gradeLevelId) {
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

            // For lower grades: Teacher must remain the class adviser (ignore teacher_id from request)
            $teacherIdToUse = $validated['teacher_id'];
            if ($isLowerGrade) {
                $teacherIdToUse = $class->teacher_id;
            }

            $payload = [
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $teacherIdToUse,
                'day_of_week' => json_encode(array_values($validated['day_of_week'])),
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'room' => ($validated['room'] ?? '') !== '' ? $validated['room'] : null,
            ];

            // Conflict checks:
            $days = array_values($validated['day_of_week']);
            $start = $validated['start_time'];
            $end = $validated['end_time'];
            $assignedTeacherId = $teacherIdToUse;
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
                    'Schedule conflicts with existing class schedule: %s (%s) %s - %s',
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
                    'Assigned teacher has a conflicting schedule: %s (%s) %s - %s (Class: %s)',
                    optional($tconflict->subject)->name ?? 'Subject',
                    $conflictLabel,
                    optional($tconflict->start_time)?->format('g:i A') ?? $tconflict->start_time,
                    optional($tconflict->end_time)?->format('g:i A') ?? $tconflict->end_time,
                    optional($tconflict->class->section)->name ?? 'Class'
                );

                return redirect()->back()->withInput()->with('error', $conflictMsg);
            }

            $message = 'Schedule assigned successfully.';

            if (! empty($validated['schedule_id'])) {
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
            Log::error('TeacherDashboardController@storeSchedule error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->withInput()->with('error', 'Unable to save schedule: '.$e->getMessage());
        }
    }

    public function destroySchedule(Request $request, Classes $class, Schedule $schedule)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'Only the adviser can remove schedules for this class.');
            }

            if ($schedule->class_id !== $class->id) {
                abort(404, 'Schedule not found for this class.');
            }

            // For Grade 1, 2, 3: Deleting schedules is not allowed
            $class->loadMissing('section.gradeLevel');
            $gradeValue = optional($class->section->gradeLevel)->level;

            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                return redirect()->back()->with('error', 'For Grade 1, 2, and 3, schedules cannot be deleted. They are automatically managed based on the adviser.');
            }

            $schedule->delete();

            return redirect()->back()->with('success', 'Schedule removed successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@destroySchedule error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to remove schedule: '.$e->getMessage());
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
            Log::error('TeacherDashboardController@getStudentsForSection error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve students: '.$e->getMessage()], 500);
        }
    }

    public function students()
    {
        return redirect()->route('teacher.students.index');
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
            Log::error('TeacherDashboardController@grades error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back();
        }
    }

    public function showGrades(Classes $class)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'You are not allowed to view grades for this class.');
            }

            $class->loadMissing('section.gradeLevel');

            $students = $class->students()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            return view('teacher.grades.show', compact('class', 'students'));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@showGrades error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load class grades: '.$e->getMessage());
        }
    }

    public function studentGrades(Classes $class, Student $student)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'You are not allowed to view grades for this class.');
            }

            // Verify the student is enrolled in this class
            $isEnrolled = $class->students()->where('students.id', $student->id)->exists();
            if (! $isEnrolled) {
                abort(404, 'Student is not enrolled in this class.');
            }

            $class->loadMissing('section.gradeLevel');

            $activeSchoolYear = SchoolYear::active()->first();
            $gradeLevelId = $class->section?->grade_level_id;
            $requiredSubjectIds = Subject::query()
                ->where('grade_level_id', $gradeLevelId)
                ->active()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            // Get grades for the student in the current school year
            $rawGrades = Grade::where('student_id', $student->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->with('subject')
                ->get()
                ->groupBy('subject_id');

            // Use GradeService to process grades with proper DepEd transmutation and calculations
            $processedGrades = GradeService::processGradesForReportCard($rawGrades, $requiredSubjectIds);
            $gradesData = $processedGrades['gradesData'];
            $generalAverage = $processedGrades['generalAverage'];

            // Attendance data
            $maxDaysPerMonth = [
                'jun' => 11,
                'jul' => 23,
                'aug' => 20,
                'sep' => 22,
                'oct' => 23,
                'nov' => 21,
                'dec' => 14,
                'jan' => 21,
                'feb' => 19,
                'mar' => 23,
                'apr' => 0,
            ];

            $monthMapping = [
                6 => 'jun',
                7 => 'jul',
                8 => 'aug',
                9 => 'sep',
                10 => 'oct',
                11 => 'nov',
                12 => 'dec',
                1 => 'jan',
                2 => 'feb',
                3 => 'mar',
                4 => 'apr',
            ];

            $attendanceByMonth = Attendance::selectRaw('
                MONTH(date) as month_num,
                SUM(CASE WHEN status IN ("present", "late", "excused") THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days
            ')
                ->where('student_id', $student->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->groupBy('month_num')
                ->get()
                ->keyBy('month_num');

            $attendanceData = [];
            $totalSchoolDays = 0;
            $totalDaysPresent = 0;
            $totalDaysAbsent = 0;

            foreach ($maxDaysPerMonth as $monthAbbr => $schoolDays) {
                $monthNum = array_search($monthAbbr, $monthMapping);
                $monthlyData = $attendanceByMonth->get($monthNum);

                $presentDays = $monthlyData ? min($monthlyData->present_days, $schoolDays) : 0;
                $absentDays = $monthlyData ? min($monthlyData->absent_days, $schoolDays - $presentDays) : 0;

                $attendanceData[$monthAbbr] = [
                    'school_days' => $schoolDays,
                    'present' => $presentDays,
                    'absent' => $absentDays,
                ];

                $totalSchoolDays += $schoolDays;
                $totalDaysPresent += $presentDays;
                $totalDaysAbsent += $absentDays;
            }

            // Grade level history from profiles (distinct by academic year)
            $student->load(['profiles.schoolYear', 'profiles.gradeLevel', 'enrollments.class.section']);
            $gradeHistory = $student->profiles
                ->sortByDesc('school_year_id')
                ->values();

            return view('teacher.grades.student', compact(
                'class',
                'student',
                'gradesData',
                'generalAverage',
                'activeSchoolYear',
                'attendanceData',
                'totalSchoolDays',
                'totalDaysPresent',
                'totalDaysAbsent',
                'gradeHistory'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@studentGrades error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load student grades: '.$e->getMessage());
        }
    }

    public function gradebookQuiz()
    {
        return redirect()->route('teacher.assessments.list');
    }

    public function gradebookExam()
    {
        return redirect()->route('teacher.assessments.list');
    }

    public function takeAttendance()
    {
        try {
            $userId = Auth::user()->id;
            $teacher = Teacher::where('user_id', $userId)->firstOrFail();
            $teacherId = $teacher->id;

            // Get the active school year
            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return redirect()->back()->with('error', 'Unable to load attendance page because no active school year is set.');
            }

            // Get the active quarter for the active school year
            $activeQuarter = null;
            $activeQuarter = \App\Models\SchoolYearQuarter::where('school_year_id', $activeSchoolYear->id)
                ->current()
                ->first();

            // Get all schedules for this teacher in the active school year
            $schedules = Schedule::with('class.section.gradeLevel', 'subject')
                ->where('teacher_id', $teacherId)
                ->whereHas('class', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })
                ->get();

            // Get unique classes from teacher schedules for the active school year.
            $sections = $schedules->pluck('class')
                ->filter()
                ->unique('id')
                ->sortBy(function ($class) {
                    return [
                        (int) ($class->section?->gradeLevel?->level ?? 0),
                        (string) ($class->section?->name ?? ''),
                    ];
                })
                ->values();

            $sectionOptions = $sections->map(function ($class) use ($teacher) {
                $gradeLevel = (int) ($class->section?->gradeLevel?->level ?? 0);

                return [
                    'class_id' => (int) $class->id,
                    'section_id' => (int) ($class->section?->id ?? 0),
                    'section_name' => (string) ($class->section?->name ?? 'Unknown Section'),
                    'grade_level' => $gradeLevel,
                    'is_adviser' => (int) $class->teacher_id === (int) $teacher->id,
                ];
            })->values();

            return view('teacher.attendance.take', compact('sections', 'sectionOptions', 'teacherId', 'activeQuarter'));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@takeAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load attendance page right now. Please try again.');
        }
    }

    public function attendanceRecords()
    {
        try {
            $userId = Auth::id();
            $teacher = Teacher::where('user_id', $userId)->firstOrFail();
            $teacherId = $teacher->id;
            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return redirect()->back()->with('error', 'No active school year found.');
            }

            // Fetch individual attendance records
            // Join student_profiles so we can resolve student identity when attendance rows reference a profile
            $attendanceRecords = Attendance::where('attendances.teacher_id', $teacherId)
                ->where('attendances.school_year_id', $activeSchoolYear->id)
                ->leftJoin('student_profiles', 'attendances.student_profile_id', '=', 'student_profiles.id')
                ->join('students', DB::raw('COALESCE(attendances.student_id, student_profiles.student_id)'), '=', 'students.id')
                ->join('classes', 'attendances.class_id', '=', 'classes.id')
                ->join('sections', 'classes.section_id', '=', 'sections.id')
                ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
                ->join('subjects', 'attendances.subject_id', '=', 'subjects.id')
                ->select(
                    'attendances.id',
                    'attendances.date',
                    'attendances.status',
                    'students.first_name',
                    'students.last_name',
                    'sections.name as section_name',
                    'grade_levels.name as grade_level_name',
                    'subjects.name as subject_name',
                    'attendances.class_id'
                )
                ->orderBy('attendances.date', 'desc')
                ->orderBy('students.last_name', 'asc')
                ->limit(500)
                ->get();

            // Get relevant Grade Levels, Sections, and Subjects for the teacher
            $teacherSchedules = Schedule::where('teacher_id', $teacherId)
                ->whereHas('class', function ($q) use ($activeSchoolYear) {
                    $q->where('school_year_id', $activeSchoolYear->id);
                })
                ->with(['class.section.gradeLevel', 'subject'])
                ->get();

            $gradeLevels = $teacherSchedules->pluck('class.section.gradeLevel')->filter()->unique('id')->values();
            $sections = $teacherSchedules->pluck('class.section')->filter()->unique('id')->values();
            $subjects = $teacherSchedules->pluck('subject')->filter()->unique('id')->values();

            // Get the classes assigned to the logged-in teacher for the summary modal
            $teacherClasses = Classes::where('teacher_id', $teacherId)
                ->with('section') // Eager load the section for display
                ->get();

            $defaultSummaryDateTo = Carbon::today()->toDateString();
            $defaultSummaryDateFrom = Carbon::today()->subDays(13)->toDateString();

            return view('teacher.attendance.records', compact(
                'gradeLevels',
                'subjects',
                'sections',
                'attendanceRecords',
                'teacherClasses',
                'defaultSummaryDateFrom',
                'defaultSummaryDateTo'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@attendanceRecords error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load attendance records: '.$e->getMessage());
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
            // Also consider attendance rows linked by student_profile_id for these students
            $profileIds = \App\Models\StudentProfile::whereIn('student_id', $studentIds)
                ->where('school_year_id', $recordToDelete->school_year_id)
                ->pluck('id')
                ->toArray();

            $deletedCount = Attendance::where('attendances.date', $date)
                ->where('attendances.subject_id', $subjectId)
                ->where('attendances.teacher_id', $teacherId)
                ->where('attendances.school_year_id', $recordToDelete->school_year_id)
                ->where(function ($q) use ($studentIds, $profileIds) {
                    $q->whereIn('attendances.student_id', $studentIds);
                    if (! empty($profileIds)) {
                        $q->orWhereIn('attendances.student_profile_id', $profileIds);
                    }
                })
                ->delete();

            return redirect()->route('teacher.attendance.records')
                ->with('success', "Attendance record deleted successfully. {$deletedCount} entries were removed.");
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@deleteAttendanceRecord error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to delete attendance record: '.$e->getMessage());
        }
    }

    /**
     * Get students for a section
     */
    public function getStudents(Request $request)
    {
        try {
            $request->validate([
                'section_id' => 'required|integer',
                'subject_id' => 'required',
                'date' => 'required|date',
            ]);

            $activeSchoolYear = SchoolYear::active()->first();
            if (! $activeSchoolYear) {
                return response()->json(['message' => 'No active school year found.'], 400);
            }

            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            $class = $this->resolveClassForSection((int) $request->input('section_id'), (int) $activeSchoolYear->id);

            if (! $class) {
                return $this->validationErrorResponse(
                    'The selected section is not available for the active school year.',
                    'section_id'
                );
            }

            $isAllDay = $request->input('subject_id') === 'all';
            $subject = null;
            $schedule = null;

            if ($isAllDay) {
                if (! $this->isAdviserForClass($teacher, $class)) {
                    return $this->validationErrorResponse(
                        'All-subject attendance is only allowed for your advisory class.',
                        'subject_id'
                    );
                }
            } else {
                $subjectId = (int) $request->input('subject_id');
                $subject = Subject::find($subjectId);

                if (! $subject) {
                    return $this->validationErrorResponse('The selected subject is invalid.', 'subject_id');
                }

                $schedule = Schedule::where([
                    'class_id' => $class->id,
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacher->id,
                ])->first();

                if (! $schedule) {
                    return $this->validationErrorResponse(
                        'You are not scheduled to handle this subject for the selected section.',
                        'subject_id'
                    );
                }
            }

            $date = $request->input('date');

            $students = Student::whereIn('id', function ($query) use ($class, $activeSchoolYear) {
                $query->select('student_id')
                    ->from('enrollments')
                    ->where('class_id', $class->id)
                    ->where('school_year_id', $activeSchoolYear->id);
            })->orderBy('last_name')->orderBy('first_name')->get();

            // Map student -> profile IDs for active year to resolve attendance rows linked to profiles
            $studentIds = $students->pluck('id')->toArray();
            $profileMap = \App\Models\StudentProfile::whereIn('student_id', $studentIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id', 'student_id')
                ->toArray();

            $profileIds = array_values($profileMap);

            // Get existing attendance records for this class, subject and date (profile-aware)
            $existingAttendanceQuery = Attendance::where([
                'date' => $date,
                'class_id' => $class->id,
                'school_year_id' => $activeSchoolYear->id,
            ]);

            if (! $isAllDay && $subject) {
                $existingAttendanceQuery->where('subject_id', $subject->id);
            }

            $existingAttendanceQuery->where(function ($q) use ($studentIds, $profileIds) {
                $q->whereIn('student_id', $studentIds);
                if (! empty($profileIds)) {
                    $q->orWhereIn('student_profile_id', $profileIds);
                }
            });

            $existingAttendanceRows = $existingAttendanceQuery->get();

            // Key attendance by resolved student id (values as arrays with status and remarks)
            $attendance = [];
            foreach ($existingAttendanceRows as $att) {
                $resolvedStudentId = $att->student_id;
                if (! $resolvedStudentId && $att->student_profile_id) {
                    $resolvedStudentId = \App\Models\StudentProfile::find($att->student_profile_id)?->student_id;
                }
                if ($resolvedStudentId) {
                    // If all day, we might have multiple subjects. Just take the first one found or handle differently.
                    // For now, if all day, we just want to know if they were already marked at least once.
                    if (! isset($attendance[$resolvedStudentId])) {
                        $attendance[$resolvedStudentId] = [
                            'status' => $att->status,
                            'remarks' => $att->remarks,
                        ];
                    }
                }
            }

            // Format student data with attendance information
            $formattedStudents = [];
            foreach ($students as $student) {
                $formattedStudents[] = [
                    'id' => $student->id,
                    'student_id' => $student->student_id ?? $student->lrn ?? 'N/A',
                    'name' => $student->full_name ?? $student->name,
                    'gender' => $student->gender,
                    'attendance' => $attendance[$student->id] ?? null,
                ];
            }

            $warning = null;
            if ($isAllDay && $this->isGradeFourToSixClass($class)) {
                $warning = 'All-subject attendance marks all scheduled class subjects, including those not handled by you.';
            }

            return response()->json([
                'section' => $class->section,
                'class_id' => $class->id,
                'subject' => $isAllDay ? ['name' => 'All Scheduled Subjects', 'code' => 'MULTIPLE'] : $subject,
                'schedule' => $schedule,
                'students' => $formattedStudents,
                'warning' => $warning,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getStudents error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve students right now. Please try again.'], 500);
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
            $barCode = $request->input('bar_code');
            $student = Student::where('bar_code', $barCode)
                ->orWhere('lrn', $barCode)
                ->first();

            if (! $student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with this QR code',
                ], 404);
            }

            // Verify student belongs to the selected section
            if ($student->section_id != $request->input('section_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not in the selected section',
                ], 400);
            }

            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
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
                'student_id' => $student->id,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@scanAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to record attendance: '.$e->getMessage()], 500);
        }
    }

    /**
     * Save attendance for multiple students
     */
    public function saveAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'section_id' => 'required|integer',
                'subject_id' => 'required',
                'date' => 'required|date',
                'quarter' => 'required|string',
                'status' => 'required|array',
                'remarks' => 'nullable|array',
            ]);

            $userId = Auth::id();
            $teacher = Teacher::where('user_id', $userId)->firstOrFail();
            $teacherId = $teacher->id;
            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return response()->json(['message' => 'No active school year found.'], 400);
            }

            $class = $this->resolveClassForSection((int) $validated['section_id'], (int) $activeSchoolYear->id);
            if (! $class) {
                return $this->validationErrorResponse(
                    'The selected section is not available for the active school year.',
                    'section_id'
                );
            }

            $isAllDay = $validated['subject_id'] === 'all';
            $isAdviser = $this->isAdviserForClass($teacher, $class);

            if ($isAllDay && ! $isAdviser) {
                return $this->validationErrorResponse(
                    'All-subject attendance is only allowed for your advisory class.',
                    'subject_id'
                );
            }

            $quarterNumber = (int) filter_var($request->input('quarter'), FILTER_SANITIZE_NUMBER_INT);

            if ($this->quarterLockService->isLocked((int) $activeSchoolYear->id, $quarterNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This quarter is locked. Attendance changes are disabled.',
                ], 423);
            }

            $subjectIds = [];
            if ($isAllDay) {
                $subjectIds = $this->resolveAllDaySubjectIdsForClass($class, $validated['date']);
            } else {
                $subjectId = (int) $validated['subject_id'];
                $subjectExists = Subject::where('id', $subjectId)->exists();

                if (! $subjectExists) {
                    return $this->validationErrorResponse('The selected subject is invalid.', 'subject_id');
                }

                $isTeacherScheduledForSubject = Schedule::where('class_id', $class->id)
                    ->where('subject_id', $subjectId)
                    ->where('teacher_id', $teacherId)
                    ->exists();

                if (! $isTeacherScheduledForSubject) {
                    return $this->validationErrorResponse(
                        'You are not scheduled to handle this subject for the selected section.',
                        'subject_id'
                    );
                }

                $subjectIds = [$subjectId];
            }

            if (empty($subjectIds)) {
                return response()->json(['message' => 'No scheduled subjects found for the selected class.'], 400);
            }

            $statusArray = $request->input('status', []);
            $remarksArray = $request->input('remarks', []);
            $enrolledStudentIds = Enrollment::query()
                ->where('class_id', $class->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('student_id')
                ->map(fn ($studentId) => (int) $studentId)
                ->all();

            $validStudentLookup = array_fill_keys($enrolledStudentIds, true);
            $studentProfileIdByStudentId = \App\Models\StudentProfile::query()
                ->whereIn('student_id', $enrolledStudentIds)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id', 'student_id')
                ->map(fn ($profileId) => (int) $profileId)
                ->all();

            foreach ($statusArray as $studentId => $status) {
                if (! is_numeric($studentId)) {
                    continue;
                }

                $studentId = (int) $studentId;
                if (! isset($validStudentLookup[$studentId])) {
                    continue;
                }

                $remarks = $remarksArray[$studentId] ?? null;
                $studentProfileId = $studentProfileIdByStudentId[$studentId] ?? null;

                foreach ($subjectIds as $subjectId) {
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'subject_id' => $subjectId,
                            'class_id' => $class->id,
                            'date' => $validated['date'],
                            'quarter' => $validated['quarter'],
                            'school_year_id' => $activeSchoolYear->id,
                        ],
                        [
                            'status' => $status,
                            'remarks' => $remarks,
                            'teacher_id' => $teacherId,
                            'student_profile_id' => $studentProfileId,
                        ]
                    );
                }

                $this->checkAbsences($studentId, $teacherId);
            }

            $responsePayload = [
                'success' => true,
                'message' => 'Attendance saved successfully for '.(count($subjectIds)).' subjects.',
            ];

            if ($isAllDay && $isAdviser && $this->isGradeFourToSixClass($class)) {
                $responsePayload['warning'] = 'All-subject attendance marks all scheduled class subjects, including those not handled by you.';
            }

            return response()->json($responsePayload);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@saveAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to save attendance right now. Please try again.'], 500);
        }
    }

    private function resolveClassForSection(int $sectionId, int $schoolYearId): ?Classes
    {
        if ($sectionId <= 0 || $schoolYearId <= 0) {
            return null;
        }

        return Classes::query()
            ->with('section.gradeLevel')
            ->where('section_id', $sectionId)
            ->where('school_year_id', $schoolYearId)
            ->first();
    }

    private function isAdviserForClass(Teacher $teacher, Classes $class): bool
    {
        return (int) $class->teacher_id === (int) $teacher->id;
    }

    private function isGradeFourToSixClass(Classes $class): bool
    {
        $gradeLevel = (int) ($class->section?->gradeLevel?->level ?? 0);

        return $gradeLevel >= 4 && $gradeLevel <= 6;
    }

    private function resolveAllDaySubjectIdsForClass(Classes $class, string $date): array
    {
        return Schedule::query()
            ->where('class_id', $class->id)
            ->pluck('subject_id')
            ->map(fn ($subjectId) => (int) $subjectId)
            ->unique()
            ->values()
            ->all();
    }

    private function validationErrorResponse(string $message, string $field): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [
                $field => [$message],
            ],
        ], 422);
    }

    private function checkAbsences($studentId, $teacherId)
    {
        // Check for 3 consecutive absences using a single query
        $threeDaysAgo = now()->subDays(2)->toDateString();
        $today = now()->toDateString();

        $absentDays = Attendance::where('student_id', $studentId)
            ->whereBetween('date', [$threeDaysAgo, $today])
            ->where('status', 'absent')
            ->distinct('date')
            ->count('date');

        // Only proceed if absent all 3 days
        $consecutiveAbsences = $absentDays;

        if ($consecutiveAbsences >= 3) {
            // Check if an email has been sent recently for this student to avoid spamming
            $cacheKey = 'absent_alert_sent_'.$studentId;
            $lastSent = cache($cacheKey);
            if (! $lastSent || now()->diffInHours($lastSent) >= 24) {
                try {
                    $student = Student::find($studentId);
                    $teacher = Teacher::with('user')->find($teacherId);

                    // Send to teacher first (if available)
                    if ($teacher && $teacher->user && ! empty($teacher->user->email)) {
                        Mail::to($teacher->user->email)->queue(new AbsentAlertMail($student, $teacher, $consecutiveAbsences));
                    }

                    // Also send a copy to the guardian if present and has an email
                    $guardian = $student->guardian ?? null;
                    $guardianUser = $guardian?->user;
                    if ($guardianUser && ! empty($guardianUser->email)) {
                        $guardianEmail = $guardianUser->email;
                        $teacherEmail = $teacher->user->email ?? null;
                        // avoid duplicate send if guardian and teacher share the same email
                        if ($guardianEmail !== $teacherEmail) {
                            Mail::to($guardianEmail)->queue(new AbsentAlertMail($student, $teacher, $consecutiveAbsences));
                        }
                    }
                } catch (Throwable $e) {
                    Log::error('Error sending absent alert: '.$e->getMessage(), ['student_id' => $studentId, 'exception' => $e]);
                }

                // Set cache to avoid re-sending within 24 hours
                cache([$cacheKey => now()], now()->addHours(24));
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
            $sections = Section::where('grade_level', $request->input('grade_level'))
                ->orderBy('name')
                ->get();

            return response()->json([
                'sections' => $sections,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getSectionsByGradeLevel error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve sections: '.$e->getMessage()], 500);
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
            Log::error('TeacherDashboardController@getGradesForSection error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve grades: '.$e->getMessage()], 500);
        }
    }

    public function updateAttendance(Request $request, $id)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'status' => ['required', Rule::in(['present', 'late', 'absent', 'excused'])],
            ]);

            // Find the attendance record
            $attendance = Attendance::findOrFail($id);

            if ($this->quarterLockService->isLocked((int) $attendance->school_year_id, (int) $attendance->quarter)) {
                return redirect()->route('teacher.attendance.records')->with('error', 'This quarter is locked. Attendance changes are disabled.');
            }

            // Update the status
            $attendance->status = $request->input('status');
            $attendance->save();

            // Redirect back with a success message
            return redirect()->route('teacher.attendance.records')->with('success', 'Attendance record updated successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@updateAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to update attendance: '.$e->getMessage());
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

            if ($this->quarterLockService->isLocked((int) $attendance->school_year_id, (int) $attendance->quarter)) {
                return redirect()->route('teacher.attendance.records')->with('error', 'This quarter is locked. Attendance changes are disabled.');
            }

            $attendance->delete();

            // Redirect back with a success message
            return redirect()->route('teacher.attendance.records')->with('success', 'Attendance record deleted successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@destroyAttendance error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->route('teacher.attendance.records')->with('error', 'Unable to delete attendance: '.$e->getMessage());
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
            if (! $class) {
                return response()->json([]);
            }

            // Get students who are enrolled in that specific class
            $students = Student::whereHas('enrollments', function ($query) use ($class) {
                $query->where('class_id', $class->id);
            })->orderBy('last_name', 'asc')->get();

            // Format the data as expected by the DataTable in the view
            $studentData = $students->map(function ($student) {
                return [
                    'student_id' => $student->student_id,
                    'student_name' => $student->last_name.', '.$student->first_name,
                    'gender' => ucfirst($student->gender),
                ];
            });

            return response()->json($studentData);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getStudentsBySection error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve students: '.$e->getMessage()], 500);
        }
    }

    public function getAttendanceSummary(Request $request)
    {
        try {
            $activeSchoolYear = SchoolYear::active()->first();
            if (! $activeSchoolYear) {
                return response()->json(['success' => false, 'message' => 'No active school year found.'], 400);
            }

            $classId = $request->input('class_id');
            $subjectId = $request->input('subject_id');
            $dateToInput = $request->input('date_to');
            $dateFromInput = $request->input('date_from');

            $dateTo = $dateToInput ? Carbon::parse($dateToInput)->toDateString() : Carbon::today()->toDateString();
            $dateFrom = $dateFromInput ? Carbon::parse($dateFromInput)->toDateString() : Carbon::parse($dateTo)->subDays(13)->toDateString();

            if ($dateFrom > $dateTo) {
                $dateFrom = Carbon::parse($dateTo)->subDays(13)->toDateString();
            }

            $attendanceQuery = Attendance::where('class_id', $classId)
                ->where('school_year_id', $activeSchoolYear->id)
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if ($subjectId !== 'all') {
                $attendanceQuery->where('subject_id', $subjectId);
            }

            $stats = [
                'present_count' => (clone $attendanceQuery)->where('status', 'present')->count(),
                'late_count' => (clone $attendanceQuery)->where('status', 'late')->count(),
                'absent_count' => (clone $attendanceQuery)->where('status', 'absent')->count(),
                'excused_count' => (clone $attendanceQuery)->where('status', 'excused')->count(),
            ];

            // Get student details with attendance counts in a single aggregate query
            $studentAttendanceQuery = Attendance::where('attendances.class_id', $classId)
                ->where('attendances.school_year_id', $activeSchoolYear->id)
                ->whereBetween('attendances.date', [$dateFrom, $dateTo]);

            if ($subjectId !== 'all') {
                $studentAttendanceQuery->where('attendances.subject_id', $subjectId);
            }

            $studentDetails = $studentAttendanceQuery
                ->join('students', 'attendances.student_id', '=', 'students.id')
                ->select(
                    'students.first_name',
                    'students.last_name',
                    DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                    DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('attendances.student_id', 'students.first_name', 'students.last_name')
                ->get()
                ->map(fn ($row) => [
                    'first_name' => $row->first_name,
                    'last_name' => $row->last_name,
                    'present_count' => (int) $row->present_count,
                    'late_count' => (int) $row->late_count,
                    'absent_count' => (int) $row->absent_count,
                    'is_at_risk' => (int) $row->absent_count >= 3,
                ])
                ->values()
                ->toArray();

            // Get trend data (attendance counts per day)
            $trendDataQuery = Attendance::where('class_id', $classId)
                ->where('school_year_id', $activeSchoolYear->id)
                ->whereBetween('date', [$dateFrom, $dateTo]);

            if ($subjectId !== 'all') {
                $trendDataQuery->where('subject_id', $subjectId);
            }

            $trendData = $trendDataQuery->select(
                'date',
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent")
            )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'stats' => $stats,
                'student_details' => $studentDetails,
                'trend_data' => $trendData,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherDashboardController@getAttendanceSummary error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve summary: '.$e->getMessage()], 500);
        }
    }
}
