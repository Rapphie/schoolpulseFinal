<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $activeSchoolYear = SchoolYear::active()->first();
            $events = [];
            $teachers = collect();
            $gradeLevels = GradeLevel::orderBy('level')->get();
            $sections = collect();
            $teacherCards = collect();
            $selectedTeacher = null;

            if ($activeSchoolYear) {
                $teachers = Teacher::whereHas('schedules.class', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })->with('user')->get();

                $sections = Section::whereHas('classes', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })->with('gradeLevel')->orderBy('grade_level_id')->get();

                if ($request->filled('teacher_id')) {
                    $selectedTeacher = $teachers->firstWhere('id', (int) $request->teacher_id);

                    if ($selectedTeacher) {
                        $schedulesQuery = Schedule::where('teacher_id', (int) $request->teacher_id)
                            ->whereHas('class', function ($query) use ($activeSchoolYear) {
                                $query->where('school_year_id', $activeSchoolYear->id);
                            })
                            ->with(['class.section.gradeLevel', 'subject', 'teacher.user']);

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

                        $subjectColors = [];
                        $colorPalette = ['#4C51BF', '#6B46C1', '#9F7AEA', '#ED64A6', '#F56565', '#ED8936', '#ECC94B', '#48BB78', '#38B2AC', '#4299E1', '#F6AD55', '#FC8181'];
                        $colorIndex = 0;

                        foreach ($schedules as $schedule) {
                            $daysOfWeek = $schedule->day_of_week;
                            if (! is_array($daysOfWeek)) {
                                continue;
                            }

                            if (! isset($subjectColors[$schedule->subject_id])) {
                                $subjectColors[$schedule->subject_id] = $colorPalette[$colorIndex % count($colorPalette)];
                                $colorIndex++;
                            }

                            $dayNumbers = array_map(fn ($day) => $this->dayToNumber($day), $daysOfWeek);

                            $subjectName = $schedule->subject?->name ?? 'No Subject';
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
                                    'room' => $schedule->room,
                                ],
                                'backgroundColor' => $subjectColors[$schedule->subject_id],
                                'borderColor' => $subjectColors[$schedule->subject_id],
                            ];
                        }
                    }
                }

                if (! $request->filled('teacher_id') || ! $selectedTeacher) {
                    $allSchedules = Schedule::whereHas('class', function ($query) use ($activeSchoolYear) {
                        $query->where('school_year_id', $activeSchoolYear->id);
                    })->with('subject')->get()->groupBy('teacher_id');

                    $teacherCards = $teachers->map(function ($teacher) use ($allSchedules) {
                        $teacherSchedules = $allSchedules->get($teacher->id, collect());
                        $scheduleCount = $teacherSchedules->count();
                        $subjects = $teacherSchedules
                            ->pluck('subject.name')
                            ->filter()
                            ->unique()
                            ->take(3)
                            ->values();

                        return [
                            'id' => $teacher->id,
                            'first_name' => $teacher->user->first_name,
                            'last_name' => $teacher->user->last_name,
                            'name' => $teacher->user->first_name.' '.$teacher->user->last_name,
                            'initials' => strtoupper(substr($teacher->user->first_name, 0, 1).substr($teacher->user->last_name, 0, 1)),
                            'schedule_count' => $scheduleCount,
                            'subjects' => $subjects,
                        ];
                    });
                }
            }

            return view('admin.schedules.index', [
                'events' => json_encode($events),
                'activeSchoolYear' => $activeSchoolYear,
                'teachers' => $teachers,
                'gradeLevels' => $gradeLevels,
                'sections' => $sections,
                'filters' => $request->only(['teacher_id', 'grade_level_id', 'section_id']),
                'teacherCards' => $teacherCards,
                'selectedTeacher' => $selectedTeacher,
            ]);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (Throwable $e) {
            Log::error('ScheduleController@index error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load schedules: '.$e->getMessage());
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

            $teacherConflict = Schedule::where('teacher_id', $validated['teacher_id'])
                ->where(function ($q) use ($days) {
                    foreach ($days as $i => $day) {
                        if ($i === 0) {
                            $q->whereJsonContains('day_of_week', $day);
                        } else {
                            $q->orWhereJsonContains('day_of_week', $day);
                        }
                    }
                })
                ->where(function ($q) use ($start, $end) {
                    $q->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start);
                })
                ->with('class.section', 'subject')
                ->first();

            if ($teacherConflict) {
                $conflictDays = $teacherConflict->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(', ', $conflictDays) : $conflictDays;

                return redirect()->back()->withInput()
                    ->with('error', sprintf(
                        'Teacher has a conflicting schedule: %s (%s) %s - %s (Section: %s)',
                        optional($teacherConflict->subject)->name ?? 'Subject',
                        $conflictLabel,
                        optional($teacherConflict->start_time)?->format('g:i A') ?? $teacherConflict->start_time,
                        optional($teacherConflict->end_time)?->format('g:i A') ?? $teacherConflict->end_time,
                        optional($teacherConflict->class->section)->name ?? 'Section'
                    ));
            }

            // Check for conflicts within the same class
            $classConflict = Schedule::where('class_id', $validated['class_id'])
                ->where(function ($q) use ($days) {
                    foreach ($days as $i => $day) {
                        if ($i === 0) {
                            $q->whereJsonContains('day_of_week', $day);
                        } else {
                            $q->orWhereJsonContains('day_of_week', $day);
                        }
                    }
                })
                ->where(function ($q) use ($start, $end) {
                    $q->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start);
                })
                ->with('subject')
                ->first();

            if ($classConflict) {
                $conflictDays = $classConflict->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(', ', $conflictDays) : $conflictDays;

                return redirect()->back()->withInput()
                    ->with('error', sprintf(
                        'Class has a conflicting schedule: %s (%s) %s - %s',
                        optional($classConflict->subject)->name ?? 'Subject',
                        $conflictLabel,
                        optional($classConflict->start_time)?->format('g:i A') ?? $classConflict->start_time,
                        optional($classConflict->end_time)?->format('g:i A') ?? $classConflict->end_time
                    ));
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
            $isLowerGrade = ! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3]);

            if ($isLowerGrade) {
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

            $classConflict = Schedule::query()
                ->where('class_id', (int) $validated['class_id'])
                ->where('id', '!=', $schedule->id)
                ->where(function ($query) use ($days) {
                    foreach ($days as $index => $day) {
                        if ($index === 0) {
                            $query->whereJsonContains('day_of_week', $day);
                        } else {
                            $query->orWhereJsonContains('day_of_week', $day);
                        }
                    }
                })
                ->where(function ($query) use ($start, $end) {
                    $query->whereTime('start_time', '<', $end)
                        ->whereTime('end_time', '>', $start);
                })
                ->with('subject')
                ->first();

            if ($classConflict) {
                $conflictDays = $classConflict->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(',', $conflictDays) : (string) $conflictDays;
                $message = sprintf(
                    'Schedule conflicts with existing class schedule: %s (%s) %s - %s',
                    optional($classConflict->subject)->name ?? 'Subject',
                    $conflictLabel,
                    optional($classConflict->start_time)?->format('g:i A') ?? $classConflict->start_time,
                    optional($classConflict->end_time)?->format('g:i A') ?? $classConflict->end_time
                );

                return redirect()->back()->withInput()->with('error', $message);
            }

            $teacherConflict = Schedule::query()
                ->where('teacher_id', $assignedTeacherId)
                ->where('id', '!=', $schedule->id)
                ->where(function ($query) use ($days) {
                    foreach ($days as $index => $day) {
                        if ($index === 0) {
                            $query->whereJsonContains('day_of_week', $day);
                        } else {
                            $query->orWhereJsonContains('day_of_week', $day);
                        }
                    }
                })
                ->where(function ($query) use ($start, $end) {
                    $query->whereTime('start_time', '<', $end)
                        ->whereTime('end_time', '>', $start);
                })
                ->with('subject', 'class.section')
                ->first();

            if ($teacherConflict) {
                $conflictDays = $teacherConflict->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(',', $conflictDays) : (string) $conflictDays;
                $message = sprintf(
                    'Assigned teacher has a conflicting schedule: %s (%s) %s - %s (Class: %s)',
                    optional($teacherConflict->subject)->name ?? 'Subject',
                    $conflictLabel,
                    optional($teacherConflict->start_time)?->format('g:i A') ?? $teacherConflict->start_time,
                    optional($teacherConflict->end_time)?->format('g:i A') ?? $teacherConflict->end_time,
                    optional($teacherConflict->class->section)->name ?? 'Class'
                );

                return redirect()->back()->withInput()->with('error', $message);
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
