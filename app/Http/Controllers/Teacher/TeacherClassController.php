<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\GradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class TeacherClassController extends Controller
{
    /**
     * Display teacher's classes.
     */
    public function classes()
    {
        try {
            $teacher = Auth::user()->teacher;
            $activeSchoolYear = SchoolYear::active()->first();

            if (! $activeSchoolYear) {
                return view('teacher.classes')->with('error', 'No active school year has been set.');
            }

            $advisoryClassIds = $teacher->advisoryClasses()
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id');

            $scheduledClassIds = $teacher->schedules()
                ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                ->pluck('class_id');

            $allClassIds = $advisoryClassIds->merge($scheduledClassIds)->unique();

            $classes = Classes::whereIn('id', $allClassIds)
                ->with(['section.gradeLevel', 'teacher.user', 'enrollments'])
                ->get()
                ->sortBy('section.gradeLevel.level');

            $teacher->load('schedules.subject');

            return view('teacher.classes', compact('classes', 'teacher'));
        } catch (Throwable $e) {
            Log::error('TeacherClassController@classes error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load classes: '.$e->getMessage());
        }
    }

    /**
     * Display a specific class.
     */
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
            Log::error('TeacherClassController@viewClass error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load class view: '.$e->getMessage());
        }
    }

    /**
     * Store a new schedule for a class.
     */
    public function storeSchedule(Request $request, Classes $class)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'Only the adviser can manage schedules for this class.');
            }

            $class->loadMissing('section.gradeLevel');

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

            $days = array_values($validated['day_of_week']);
            $start = $validated['start_time'];
            $end = $validated['end_time'];
            $assignedTeacherId = $teacherIdToUse;
            $scheduleIdToExclude = $validated['schedule_id'] ?? null;

            $dayQueryCallback = function ($q) use ($days) {
                if (empty($days)) {
                    return;
                }
                $first = array_shift($days);
                $q->whereJsonContains('day_of_week', $first);
                foreach ($days as $day) {
                    $q->orWhereJsonContains('day_of_week', $day);
                }
            };

            $classConflicts = $class->schedules()->where(function ($q) use ($dayQueryCallback) {
                $dayQueryCallback($q);
            })->where(function ($q) use ($start, $end) {
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
            Log::error('TeacherClassController@storeSchedule error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->withInput()->with('error', 'Unable to save schedule: '.$e->getMessage());
        }
    }

    /**
     * Delete a schedule.
     */
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

            $class->loadMissing('section.gradeLevel');
            $gradeValue = optional($class->section->gradeLevel)->level;

            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                return redirect()->back()->with('error', 'For Grade 1, 2, and 3, schedules cannot be deleted. They are automatically managed based on the adviser.');
            }

            $schedule->delete();

            return redirect()->back()->with('success', 'Schedule removed successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherClassController@destroySchedule error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to remove schedule: '.$e->getMessage());
        }
    }

    /**
     * Rename the section.
     */
    public function renameSection(Request $request, Classes $class)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'Only the adviser can rename this section.');
            }

            $section = $class->section;
            $gradeLevelId = $section->grade_level_id;

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('sections', 'name')->where(function ($query) use ($gradeLevelId) {
                        $query->where('grade_level_id', $gradeLevelId);
                    })->ignore($section->id),
                ],
            ]);

            $section->update(['name' => $validated['name']]);

            return redirect()->back()->with('success', 'Section renamed successfully.');
        } catch (Throwable $e) {
            Log::error('TeacherClassController@renameSection error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to rename section: '.$e->getMessage());
        }
    }

    /**
     * Get students for a section.
     */
    public function getStudentsForSection(Section $section)
    {
        try {
            $students = Student::where('section_id', $section->id)->get();

            return response()->json($students);
        } catch (Throwable $e) {
            Log::error('TeacherClassController@getStudentsForSection error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve students: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get sections by grade level.
     */
    public function getSectionsByGradeLevel(Request $request)
    {
        try {
            $request->validate([
                'grade_level' => 'required',
            ]);

            $sections = Section::where('grade_level', $request->input('grade_level'))
                ->orderBy('name')
                ->get();

            return response()->json([
                'sections' => $sections,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherClassController@getSectionsByGradeLevel error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve sections: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get grades for a section.
     */
    public function getGradesForSection(Section $section)
    {
        try {
            $grades = Grade::whereHas('student', function ($query) use ($section) {
                $query->where('section_id', $section->id);
            })
                ->with(['student'])
                ->get();

            return response()->json($grades);
        } catch (Throwable $e) {
            Log::error('TeacherClassController@getGradesForSection error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve grades: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get students by section.
     */
    public function getStudentsBySection(Section $section)
    {
        try {
            $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();

            $class = Classes::where('section_id', $section->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->first();

            if (! $class) {
                return response()->json([]);
            }

            $students = Student::whereHas('enrollments', function ($query) use ($class) {
                $query->where('class_id', $class->id);
            })->orderBy('last_name', 'asc')->get();

            $studentData = $students->map(function ($student) {
                return [
                    'student_id' => $student->student_id,
                    'student_name' => $student->last_name.', '.$student->first_name,
                    'gender' => ucfirst($student->gender),
                ];
            });

            return response()->json($studentData);
        } catch (Throwable $e) {
            Log::error('TeacherClassController@getStudentsBySection error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Unable to retrieve students: '.$e->getMessage()], 500);
        }
    }

    /**
     * Display teacher's grades page.
     */
    public function grades()
    {
        try {
            $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
            $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();

            $advisoryClasses = Classes::where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->with('section.gradeLevel')
                ->get();

            return view('teacher.grades.index', [
                'classes' => $advisoryClasses,
            ]);
        } catch (Throwable $e) {
            Log::error('TeacherClassController@grades error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back();
        }
    }

    /**
     * Display grades for a specific class.
     */
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
            Log::error('TeacherClassController@showGrades error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load class grades: '.$e->getMessage());
        }
    }

    /**
     * Display student grades for a specific class.
     */
    public function studentGrades(Classes $class, Student $student)
    {
        try {
            $teacher = Auth::user()->teacher;

            if (! $teacher || (int) $class->teacher_id !== (int) $teacher->id) {
                abort(403, 'You are not allowed to view grades for this class.');
            }

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

            $rawGrades = Grade::where('student_id', $student->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->with('subject')
                ->get()
                ->groupBy('subject_id');

            $processedGrades = GradeService::processGradesForReportCard($rawGrades, $requiredSubjectIds);
            $gradesData = $processedGrades['gradesData'];
            $generalAverage = $processedGrades['generalAverage'];

            $maxDaysPerMonth = [
                'jun' => 11, 'jul' => 23, 'aug' => 20, 'sep' => 22, 'oct' => 23,
                'nov' => 21, 'dec' => 14, 'jan' => 21, 'feb' => 19, 'mar' => 23, 'apr' => 0,
            ];

            $monthMapping = [
                6 => 'jun', 7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'oct',
                11 => 'nov', 12 => 'dec', 1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
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

            $student->load(['profiles.schoolYear', 'profiles.gradeLevel', 'enrollments.class.section']);
            $gradeHistory = $student->profiles
                ->sortByDesc('school_year_id')
                ->values();

            return view('teacher.grades.student', compact(
                'class', 'student', 'gradesData', 'generalAverage', 'activeSchoolYear',
                'attendanceData', 'totalSchoolDays', 'totalDaysPresent', 'totalDaysAbsent', 'gradeHistory'
            ));
        } catch (Throwable $e) {
            Log::error('TeacherClassController@studentGrades error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load student grades: '.$e->getMessage());
        }
    }
}
