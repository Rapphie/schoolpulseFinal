<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ScheduleHelper;
use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\ScheduleConflictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleConflictService $scheduleConflictService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeSchoolYear = SchoolYear::active()->first();
        $events = [];
        $teachers = collect();
        $gradeLevels = GradeLevel::orderBy('level')->get();
        $sections = collect();

        if ($activeSchoolYear) {
            $schedulesQuery = Schedule::whereHas('class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })->with(['class.section.gradeLevel', 'subject', 'teacher.user']);

            if ($request->filled('teacher_id')) {
                $schedulesQuery->where('teacher_id', $request->teacher_id);
            }

            if ($request->filled('grade_level_id')) {
                $schedulesQuery->whereHas('class.section.gradeLevel', function ($query) use ($request) {
                    $query->where('id', $request->grade_level_id);
                });
            }

            if ($request->filled('section_id')) {
                $schedulesQuery->whereHas('class', function ($query) use ($request) {
                    $query->where('section_id', $request->section_id);
                });
            }

            $schedules = $schedulesQuery->get();

            foreach ($schedules as $schedule) {
                $daysOfWeek = $schedule->day_of_week;
                if (! is_array($daysOfWeek)) {
                    continue;
                }

                $dayNumbers = array_map(fn ($day) => $this->dayToNumber($day), $daysOfWeek);

                $subjectName = $schedule->subject?->name ?? 'No Subject';
                $teacherName = $schedule->teacher?->user ? $schedule->teacher->user->first_name.' '.$schedule->teacher->user->last_name : 'No Teacher';
                $sectionName = $schedule->class?->section?->name ?? 'No Section';
                $gradeLevelName = $schedule->class?->section?->gradeLevel?->name ?? '';
                $displaySection = $gradeLevelName ? $gradeLevelName.' - '.$sectionName : $sectionName;

                $events[] = [
                    'title' => $subjectName,
                    'startTime' => $schedule->start_time ? $schedule->start_time->format('H:i:s') : null,
                    'endTime' => $schedule->end_time ? $schedule->end_time->format('H:i:s') : null,
                    'daysOfWeek' => $dayNumbers,
                    'allDay' => false,
                    'extendedProps' => [
                        'section' => $displaySection,
                        'subject' => $subjectName,
                        'teacher' => $teacherName,
                        'room' => $schedule->room,
                    ],
                ];
            }

            $teachers = Teacher::whereHas('schedules.class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })->with('user')->get();

            $sections = Section::whereHas('classes', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })->with('gradeLevel')->orderBy('grade_level_id')->get();
        }

        return view('admin.schedules.index', [
            'events' => json_encode($events),
            'activeSchoolYear' => $activeSchoolYear,
            'teachers' => $teachers,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
            'filters' => $request->only(['teacher_id', 'grade_level_id', 'section_id']),
        ]);
    }

    private function dayToNumber(string $day): int
    {
        return ScheduleHelper::dayToNumber($day);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $activeSchoolYear = SchoolYear::active()->first();
            $classes = collect();
            if ($activeSchoolYear) {
                $classes = Classes::where('school_year_id', $activeSchoolYear->id)->with('section.gradeLevel')->get();
            }

            $teachers = Teacher::with('user')->get();
            $subjects = Subject::with('gradeLevel')->get();
            $gradeLevels = GradeLevel::all();

            return view('admin.schedules.create', compact('classes', 'teachers', 'subjects', 'gradeLevels'));
        } catch (Throwable $e) {
            Log::error('ScheduleController@create error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to open schedule creation form: '.$e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'class_id' => 'required|exists:classes,id',
                'subject_id' => 'required|exists:subjects,id',
                'teacher_id' => 'required|exists:teachers,id',
                'day_of_week' => 'required|array|min:1',
                'day_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'room' => 'nullable|string|max:255',
            ]);

            $class = Classes::with('section.gradeLevel')->findOrFail($validated['class_id']);
            $gradeValue = optional($class->section->gradeLevel)->level;

            // Block schedule creation for Grades 1-3 (auto-managed by adviser)
            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                return redirect()->back()->withInput()
                    ->with('error', 'For Grade 1, 2, and 3, schedules are automatically managed. You cannot manually add schedules.');
            }

            $activeSchoolYear = SchoolYear::active()->first();

            // Block adviser workload check
            if ($activeSchoolYear && $this->isBlockAdviser((int) $validated['teacher_id'], $activeSchoolYear->id)) {
                return redirect()->back()->withInput()
                    ->with('error', 'This teacher is a block adviser with a full load and cannot be assigned to other subjects.');
            }

            // Check for schedule time conflicts for the assigned teacher
            $days = array_values($validated['day_of_week']);
            $start = $validated['start_time'];
            $end = $validated['end_time'];

            $teacherConflict = $this->scheduleConflictService->findTeacherScheduleConflict(
                (int) $validated['teacher_id'],
                $days,
                $start,
                $end
            );

            if ($teacherConflict) {
                return redirect()->back()->withInput()
                    ->with('error', $this->scheduleConflictService->buildTeacherConflictMessage($teacherConflict));
            }

            // Check for conflicts within the same class
            $classSchedules = Schedule::where('class_id', $validated['class_id'])->get();
            $classConflict = $this->scheduleConflictService->findClassScheduleConflict(
                $classSchedules,
                $days,
                $start,
                $end
            );

            if ($classConflict) {
                return redirect()->back()->withInput()
                    ->with('error', $this->scheduleConflictService->buildClassConflictMessage($classConflict));
            }

            Schedule::create($validated);

            return redirect()->route('admin.schedules.index')->with('success', 'Schedule created successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (Throwable $e) {
            Log::error('ScheduleController@store error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->withInput()->with('error', 'Unable to create schedule: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        try {
            $schedule->load('class.section.gradeLevel', 'class.schoolYear', 'subject', 'teacher.user');

            return view('admin.schedules.show', compact('schedule'));
        } catch (Throwable $e) {
            Log::error('ScheduleController@show error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to display schedule: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        try {
            $schedule->load('class.section.gradeLevel', 'subject', 'teacher.user');
            $schoolYearId = $schedule->class?->school_year_id ?? SchoolYear::active()->first()?->id;

            $classes = Classes::query()
                ->when($schoolYearId, fn ($query) => $query->where('school_year_id', $schoolYearId))
                ->with('section.gradeLevel')
                ->get();

            $subjects = Subject::with('gradeLevel')->get();
            $teachers = Teacher::with('user')->get();
            $gradeLevels = GradeLevel::orderBy('level')->get();

            return view('admin.schedules.edit', compact('schedule', 'classes', 'subjects', 'teachers', 'gradeLevels'));
        } catch (Throwable $e) {
            Log::error('ScheduleController@edit error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to open edit schedule form: '.$e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedule $schedule)
    {
        try {
            $validated = $request->validate([
                'class_id' => 'required|exists:classes,id',
                'subject_id' => 'required|exists:subjects,id',
                'day_of_week' => 'required|array|min:1',
                'day_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'room' => 'nullable|string|max:255',
            ]);

            $targetClass = Classes::with('section.gradeLevel')->findOrFail((int) $validated['class_id']);
            $gradeValue = optional($targetClass->section->gradeLevel)->level;

            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                $validated['teacher_id'] = $targetClass->teacher_id ?? $schedule->teacher_id;
            } else {
                $teacherData = $request->validate([
                    'teacher_id' => 'required|exists:teachers,id',
                ]);
                $validated['teacher_id'] = (int) $teacherData['teacher_id'];
            }

            $days = array_values($validated['day_of_week']);
            $start = $validated['start_time'];
            $end = $validated['end_time'];
            $assignedTeacherId = (int) $validated['teacher_id'];

            $classSchedules = Schedule::where('class_id', (int) $validated['class_id'])->get();
            $classConflict = $this->scheduleConflictService->findClassScheduleConflict(
                $classSchedules,
                $days,
                $start,
                $end,
                $schedule->id
            );

            if ($classConflict) {
                return redirect()->back()->withInput()
                    ->with('error', $this->scheduleConflictService->buildClassConflictMessage($classConflict));
            }

            $teacherConflict = $this->scheduleConflictService->findTeacherScheduleConflict(
                $assignedTeacherId,
                $days,
                $start,
                $end,
                $schedule->id
            );

            if ($teacherConflict) {
                return redirect()->back()->withInput()
                    ->with('error', $this->scheduleConflictService->buildTeacherConflictMessage($teacherConflict));
            }

            $schedule->update([
                'class_id' => (int) $validated['class_id'],
                'subject_id' => (int) $validated['subject_id'],
                'teacher_id' => $validated['teacher_id'],
                'day_of_week' => array_values($validated['day_of_week']),
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'room' => $validated['room'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Schedule updated successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (Throwable $e) {
            Log::error('ScheduleController@update error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->withInput()->with('error', 'Unable to update schedule: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        try {
            // For Grade 1, 2, 3: Deleting schedules is not allowed
            $schedule->load('class.section.gradeLevel');
            $gradeValue = optional($schedule->class->section->gradeLevel)->level;

            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                return redirect()->back()->with('error', 'For Grade 1, 2, and 3, schedules cannot be deleted. They are automatically managed based on the adviser.');
            }

            $sectionId = $schedule->class?->section_id;
            $schedule->delete();

            if (! $sectionId) {
                return redirect()->route('admin.schedules.index')->with('success', 'Assigned subject schedule deleted successfully.');
            }

            return redirect()->route('admin.sections.manage', ['section' => $sectionId])->with('success', 'Assigned subject schedule deleted successfully.');
        } catch (Throwable $e) {
            Log::error('ScheduleController@destroy error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to remove schedule: '.$e->getMessage());
        }
    }

    /**
     * Check if a teacher is a block adviser (Grades 1-3) in the given school year.
     */
    private function isBlockAdviser(int $teacherId, int $schoolYearId): bool
    {
        return Classes::where('teacher_id', $teacherId)
            ->where('school_year_id', $schoolYearId)
            ->whereHas('section.gradeLevel', function ($query) {
                $query->whereIn('level', [1, 2, 3]);
            })
            ->exists();
    }
}
