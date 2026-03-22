<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ClassroomSectionController extends Controller
{
    /**
     * Display a listing of the classes for the active school year.
     */
    public function index()
    {
        $activeSchoolYear = $this->getActiveSchoolYear();

        $classes = Classes::where('school_year_id', $activeSchoolYear->id)
            ->with(['section.gradeLevel', 'teacher.user', 'enrollments'])
            ->get()
            ->sortBy(fn ($class) => optional($class->section->gradeLevel)->level);

        $teachers = Teacher::with('user')->orderBy('id')->get();
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return view('admin.sections.index', compact('classes', 'teachers', 'gradeLevels', 'activeSchoolYear'));
    }

    /**
     * Show the form for creating a new section.
     */
    public function create()
    {
        $teachers = Teacher::with('user')->orderBy('id')->get();
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return view('admin.sections.create', compact('teachers', 'gradeLevels'));
    }

    /**
     * Store a newly created section and its class for the active year.
     */
    public function store(Request $request)
    {
        $gradeLevelId = $this->resolveGradeLevelId($request);
        if (! $gradeLevelId) {
            return back()->withInput()->with('error', 'Invalid grade level selected.');
        }

        $request->merge(['grade_level_id' => $gradeLevelId]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')->where(function (QueryBuilder $query) use ($gradeLevelId) {
                    $query->where('grade_level_id', $gradeLevelId);
                }),
            ],
            'grade_level_id' => 'required|integer|exists:grade_levels,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'capacity' => 'nullable|integer|min:1|max:60',
            'description' => 'nullable|string',
        ]);

        $activeSchoolYear = $this->getActiveSchoolYear();

        // Teacher assignment rule: A teacher can only be an adviser to one section regardless of grade level
        if (! empty($validated['teacher_id'])) {
            $teacherId = $validated['teacher_id'];
            $gradeLevel = GradeLevel::find($validated['grade_level_id']);

            // Workload check: if a teacher is a block adviser, they have a full load.
            if ($this->isBlockAdviser($teacherId, $activeSchoolYear->id)) {
                return back()->with('error', 'This teacher is already a block adviser and has a full load.');
            }

            // Check if this teacher is already assigned as adviser to any section
            $existingAdvisory = Classes::where('teacher_id', $teacherId)
                ->where('school_year_id', $activeSchoolYear->id)
                ->with('section.gradeLevel')
                ->first();

            if ($existingAdvisory) {
                $existingSectionName = optional($existingAdvisory->section)->name ?? 'another section';
                $existingGradeLabel = optional($existingAdvisory->section->gradeLevel)->name ?? 'Unknown Grade';

                return back()->withInput()->with(
                    'error',
                    "This teacher is already assigned as adviser to {$existingGradeLabel} - {$existingSectionName}. ".
                        'Teachers can only be an adviser to one section.'
                );
            }

            // If assigning to a block section (Grades 1-3), check for existing subject loads
            if ($gradeLevel && in_array($gradeLevel->level, [1, 2, 3])) {
                $hasSubjects = Schedule::where('teacher_id', $teacherId)
                    ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                    ->exists();

                if ($hasSubjects) {
                    return back()->with('error', 'This teacher has existing subject loads and cannot be a block adviser.');
                }
            }
        }

        DB::beginTransaction();
        try {
            $section = Section::firstOrCreate(
                [
                    'name' => $validated['name'],
                    'grade_level_id' => $validated['grade_level_id'],
                ],
                [
                    'description' => $validated['description'] ?? null,
                ]
            );

            Classes::create([
                'section_id' => $section->id,
                'school_year_id' => $activeSchoolYear->id,
                'teacher_id' => $validated['teacher_id'] ?? null,
                'capacity' => $validated['capacity'] ?? 40,
            ]);

            DB::commit();

            return redirect()->route('admin.sections.index')->with('success', 'Section created successfully.');
        } catch (QueryException $exception) {
            DB::rollBack();
            if (str_contains($exception->getMessage(), 'sections_name_grade_level_id_unique')) {
                return back()->withInput()->with('error', 'A section with this name already exists for the selected grade level.');
            }
            if (str_contains($exception->getMessage(), 'classes_section_id_school_year_id_unique')) {
                return back()->withInput()->with('error', 'This section already exists for the current school year.');
            }

            return back()->withInput()->with('error', 'Failed to create section. '.$exception->getMessage());
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Failed to create section. '.$throwable->getMessage());
        }
    }

    /**
     * Display the specified section overview page.
     */
    public function show(Section $section)
    {
        $section->load('gradeLevel');
        $class = $this->findActiveClass($section, [
            'teacher.user',
            'schoolYear',
            'enrollments.student.guardian.user',
            'schedules.subject',
            'schedules.teacher.user',
        ]);

        if (! $class) {
            return redirect()->route('admin.sections.index')
                ->with('error', 'No active class found for this section in the current school year.');
        }

        $section->setAttribute('grade_level', $section->gradeLevel?->level);

        return view('admin.sections.show', compact('section', 'class'));
    }

    /**
     * Show the edit form for a given section.
     */
    public function edit(Section $section)
    {
        $section->load('gradeLevel');
        $section->setAttribute('grade_level', $section->gradeLevel?->level);

        $teachers = Teacher::with('user')->orderBy('id')->get();
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return view('admin.sections.edit', compact('section', 'teachers', 'gradeLevels'));
    }

    /**
     * Update the specified section and its active class.
     */
    public function update(Request $request, Section $section)
    {
        $gradeLevelId = $this->resolveGradeLevelId($request) ?? $section->grade_level_id;
        $request->merge(['grade_level_id' => $gradeLevelId]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sections', 'name')->where(function (QueryBuilder $query) use ($gradeLevelId) {
                    $query->where('grade_level_id', $gradeLevelId);
                })->ignore($section->id),
            ],
            'grade_level_id' => 'required|integer|exists:grade_levels,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1|max:60',
        ]);

        $section->update([
            'name' => $validated['name'],
            'grade_level_id' => $validated['grade_level_id'],
            'description' => $validated['description'] ?? null,
        ]);

        $class = $this->findActiveClass($section);
        if ($class) {
            // Teacher assignment rule: Check if teacher is being changed
            $newTeacherId = $validated['teacher_id'] ?? $class->teacher_id;

            if ($newTeacherId && $newTeacherId != $class->teacher_id) {
                $activeSchoolYear = SchoolYear::getActive();
                $gradeLevel = $class->section->gradeLevel;

                // Workload check: if a teacher is a block adviser, they have a full load.
                if ($this->isBlockAdviser($newTeacherId, $activeSchoolYear->id, $class->id)) {
                    return back()->with('error', 'This teacher is already a block adviser and has a full load.');
                }

                // Check if this teacher is already assigned as adviser to any other section
                $existingAdvisory = Classes::where('teacher_id', $newTeacherId)
                    ->where('school_year_id', $activeSchoolYear->id)
                    ->where('id', '!=', $class->id)
                    ->with('section.gradeLevel')
                    ->first();

                if ($existingAdvisory) {
                    $existingSectionName = optional($existingAdvisory->section)->name ?? 'another section';
                    $existingGradeLabel = optional($existingAdvisory->section->gradeLevel)->name ?? 'Unknown Grade';

                    return back()->withInput()->with(
                        'error',
                        "This teacher is already assigned as adviser to {$existingGradeLabel} - {$existingSectionName}. ".
                            'Teachers can only be an adviser to one section.'
                    );
                }

                // If assigning to a block section (Grades 1-3), check for existing subject loads
                if ($gradeLevel && in_array($gradeLevel->level, [1, 2, 3])) {
                    $hasSubjects = Schedule::where('teacher_id', $newTeacherId)
                        ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                        ->where('class_id', '!=', $class->id)
                        ->exists();

                    if ($hasSubjects) {
                        return back()->with('error', 'This teacher has existing subject loads and cannot be a block adviser.');
                    }
                }
            }

            $class->update([
                'teacher_id' => $newTeacherId,
                'capacity' => $validated['capacity'] ?? $class->capacity,
            ]);
        }

        return redirect()->route('admin.sections.edit', $section)->with('success', 'Section updated successfully.');
    }

    /**
     * Return key data for the active class of a section (AJAX helper).
     */
    public function getSectionData(Section $section)
    {
        $section->load('gradeLevel');
        $class = $this->findActiveClass($section, [
            'teacher.user',
            'enrollments.student.guardian.user',
            'schedules.subject',
            'schedules.teacher.user',
        ]);

        if (! $class) {
            return response()->json([
                'message' => 'No active class found for this section.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'section' => $section,
            'class' => $class,
            'enrollments' => $class->enrollments,
            'schedules' => $class->schedules,
        ]);
    }

    /**
     * Manage page for a specific section.
     */
    public function manageClass(Section $section, Request $request)
    {
        $section->load('gradeLevel');

        $relations = [
            'teacher.user',
            'schoolYear',
            'enrollments.student.guardian.user',
            'enrollments.teacher.user',
            'enrollments.enrolledByUser',
            'schedules.subject',
            'schedules.teacher.user',
        ];

        $classId = $request->query('class_id');
        $schoolYearId = $request->query('school_year_id');

        if ($classId) {
            $class = Classes::where('section_id', $section->id)
                ->with($relations)
                ->findOrFail($classId);
        } elseif ($schoolYearId) {
            $class = Classes::where('section_id', $section->id)
                ->where('school_year_id', $schoolYearId)
                ->with($relations)
                ->first();
        } else {
            $class = $this->findActiveClass($section, $relations);
        }

        if (! $class) {
            $errorMessage = $schoolYearId
                ? 'No class found for this section in the selected school year.'
                : 'No active class found for this section in the current school year.';

            return redirect()->route('admin.sections.index')
                ->with('error', $errorMessage);
        }

        $subjects = Subject::query()
            ->where('is_active', true)
            ->where(function ($query) use ($section) {
                $query->whereHas('gradeLevelSubjects', function ($gradeLevelSubjectQuery) use ($section) {
                    $gradeLevelSubjectQuery
                        ->where('grade_level_id', $section->grade_level_id)
                        ->where('is_active', true);
                })->orWhere('grade_level_id', $section->grade_level_id);
            })
            ->select('subjects.*')
            ->distinct()
            ->orderBy('name')
            ->get();
        $teachers = Teacher::with('user')->orderBy('id')->get();

        // Section/Class History: get all classes for this section, sorted by school year (desc)
        $allClasses = Classes::where('section_id', $section->id)
            ->with(['schoolYear', 'teacher.user', 'schedules', 'enrollments'])
            ->orderByDesc('school_year_id')
            ->get();

        $sectionHistory = $allClasses->map(function ($c) {
            $adviser = $c->teacher && $c->teacher->user ? ($c->teacher->user->first_name.' '.$c->teacher->user->last_name) : 'N/A';
            $rooms = $c->schedules->pluck('room')->filter()->unique()->implode(', ');

            return [
                'class_id' => $c->id,
                'school_year' => $c->schoolYear ? $c->schoolYear->name : 'N/A',
                'adviser' => $adviser,
                'capacity' => $c->capacity,
                'enrolled' => $c->enrollments->count(),
                'rooms' => $rooms ?: '—',
            ];
        });

        $activeSchoolYear = SchoolYear::getRealActive();
        $isEditable = $activeSchoolYear && (int) $class->school_year_id === (int) $activeSchoolYear->id;

        return view('admin.sections.manage', compact(
            'section',
            'class',
            'subjects',
            'teachers',
            'sectionHistory',
            'isEditable'
        ));
    }

    /**
     * Assign an adviser to a class.
     */
    public function assignClassAdviser(Request $request, Classes $class)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => 'required|exists:teachers,id',
            ]);

            // Load the section and grade level for restriction check and auto-schedule creation
            $class->load('section.gradeLevel');
            $gradeLevel = $class->section->gradeLevel ?? null;
            $gradeValue = optional($gradeLevel)->level;

            $activeSchoolYear = SchoolYear::getActive();
            $teacherId = $validated['teacher_id'];

            // Teacher availability and workload checks
            if ($this->isBlockAdviser($teacherId, $activeSchoolYear->id, $class->id)) {
                return back()->with('error', 'This teacher is already a block adviser and cannot be assigned elsewhere.');
            }

            // Check if this teacher is already assigned as adviser to any other section
            $existingAdvisory = Classes::where('teacher_id', $teacherId)
                ->where('school_year_id', $activeSchoolYear->id)
                ->where('id', '!=', $class->id)
                ->with('section.gradeLevel')
                ->first();

            if ($existingAdvisory) {
                $existingSectionName = optional($existingAdvisory->section)->name ?? 'another section';
                $existingGradeLabel = optional($existingAdvisory->section->gradeLevel)->name ?? 'Unknown Grade';

                return back()->withInput()->with(
                    'error',
                    "This teacher is already assigned as adviser to {$existingGradeLabel} - {$existingSectionName}. ".
                        'Teachers can only be an adviser to one section.'
                );
            }

            // If assigning to a block section, ensure teacher has no other subject loads
            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                $hasSubjects = Schedule::where('teacher_id', $teacherId)
                    ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
                    ->where('class_id', '!=', $class->id)
                    ->exists();

                if ($hasSubjects) {
                    return back()->with('error', 'This teacher has existing subject loads and cannot be a block adviser.');
                }
            }

            $class->update(['teacher_id' => $validated['teacher_id']]);

            // For Grade 1, 2, 3: Auto-create schedules for all subjects assigned to the adviser
            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                $subjects = Subject::query()
                    ->where('is_active', true)
                    ->where(function ($query) use ($class) {
                        $query->whereHas('gradeLevelSubjects', function ($gradeLevelSubjectQuery) use ($class) {
                            $gradeLevelSubjectQuery
                                ->where('grade_level_id', $class->section->grade_level_id)
                                ->where('is_active', true);
                        })->orWhere('grade_level_id', $class->section->grade_level_id);
                    })
                    ->select('subjects.*')
                    ->distinct()
                    ->get();

                foreach ($subjects as $subject) {
                    // Check if schedule already exists for this subject in this class
                    $existingSchedule = Schedule::where('class_id', $class->id)
                        ->where('subject_id', $subject->id)
                        ->first();

                    if (! $existingSchedule) {
                        Schedule::create([
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacherId,
                            'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                            'start_time' => '00:00',
                            'end_time' => '00:00',
                            'room' => null,
                        ]);
                    } else {
                        $existingSchedule->update([
                            'teacher_id' => $teacherId,
                            'start_time' => '00:00',
                            'end_time' => '00:00',
                        ]);
                    }
                }
            }

            return back()->with('success', 'Adviser assigned successfully.');
        } catch (\Throwable $throwable) {
            return back()->withInput()->with('error', 'Failed to assign adviser: '.$throwable->getMessage());
        }
    }

    /**
     * Remove the adviser from a class.
     */
    public function removeClassAdviser(Classes $class)
    {
        try {
            // For Grade 1, 2, 3: Remove all schedules when adviser is unassigned
            $class->load('section.gradeLevel');
            $gradeValue = optional($class->section->gradeLevel)->level;

            if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
                // Delete all schedules for this class
                Schedule::where('class_id', $class->id)->delete();
            }

            $class->update(['teacher_id' => null]);

            return back()->with('success', 'Adviser removed successfully.');
        } catch (\Throwable $throwable) {
            return back()->with('error', 'Failed to remove adviser: '.$throwable->getMessage());
        }
    }

    /**
     * Update the capacity of a class.
     */
    public function updateCapacity(Request $request, Classes $class)
    {
        try {
            $validated = $request->validate([
                'capacity' => 'required|integer|min:1',
            ]);

            // Ensure capacity is not less than current enrollment
            $currentEnrollment = $class->enrollments()->count();
            if ($validated['capacity'] < $currentEnrollment) {
                return back()->withInput()->with(
                    'error',
                    "Capacity cannot be less than current enrollment ({$currentEnrollment} students)."
                );
            }

            $class->update(['capacity' => $validated['capacity']]);

            return back()->with('success', 'Capacity updated successfully.');
        } catch (\Throwable $throwable) {
            return back()->withInput()->with('error', 'Failed to update capacity: '.$throwable->getMessage());
        }
    }

    /**
     * Store a schedule entry for a class.
     */
    public function storeSchedule(Request $request, Classes $class)
    {
        // For Grade 1, 2, 3: Adding schedules is not allowed (auto-created when adviser is assigned)
        $class->load('section.gradeLevel');
        $gradeValue = optional($class->section->gradeLevel)->level;

        if (! is_null($gradeValue) && in_array($gradeValue, [1, 2, 3])) {
            return back()->with('error', 'For Grade 1, 2, and 3, schedules are automatically managed. You cannot manually add schedules.');
        }

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day_of_week' => 'required|array|min:1',
            'day_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
        ]);
        try {
            $validated['class_id'] = $class->id;
            $activeSchoolYear = $this->getActiveSchoolYear();

            // Workload check: prevent block advisers from being assigned to other subject schedules
            if ($this->isBlockAdviser($validated['teacher_id'], $activeSchoolYear->id)) {
                return back()->withInput()->with('error', 'This teacher is a block adviser with a full load and cannot be assigned to other subjects.');
            }

            // Conflict checks: ensure no overlapping schedule for this class or for the assigned teacher
            $days = array_values($validated['day_of_week']);
            $start = $validated['start_time'];
            $end = $validated['end_time'];
            $assignedTeacherId = $validated['teacher_id'];

            // Check for conflicts within the same class
            $classConflicts = $class->schedules()->where(function ($q) use ($days) {
                foreach ($days as $i => $day) {
                    if ($i === 0) {
                        $q->whereJsonContains('day_of_week', $day);
                    } else {
                        $q->orWhereJsonContains('day_of_week', $day);
                    }
                }
            })->where(function ($q) use ($start, $end) {
                // overlap if existing.start < new.end AND existing.end > new.start
                $q->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start);
            })->first();

            if ($classConflicts) {
                $conflictDays = $classConflicts->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(',', $conflictDays) : $conflictDays;
                $conflictMsg = sprintf(
                    'Schedule conflicts with existing class schedule: %s (%s) %s - %s',
                    optional($classConflicts->subject)->name ?? 'Subject',
                    $conflictLabel,
                    optional($classConflicts->start_time)?->format('g:i A') ?? $classConflicts->start_time,
                    optional($classConflicts->end_time)?->format('g:i A') ?? $classConflicts->end_time
                );

                return back()->withInput()->with('error', $conflictMsg);
            }

            // Check conflicts for the assigned teacher across any class
            $teacherConflicts = Schedule::where('teacher_id', $assignedTeacherId)
                ->where(function ($q) use ($days) {
                    foreach ($days as $i => $day) {
                        if ($i === 0) {
                            $q->whereJsonContains('day_of_week', $day);
                        } else {
                            $q->orWhereJsonContains('day_of_week', $day);
                        }
                    }
                })->where(function ($q) use ($start, $end) {
                    $q->whereTime('start_time', '<', $end)->whereTime('end_time', '>', $start);
                })->with('class.section', 'subject')->first();

            if ($teacherConflicts) {
                $conflictDays = $teacherConflicts->day_of_week;
                $conflictLabel = is_array($conflictDays) ? implode(',', $conflictDays) : $conflictDays;
                $conflictMsg = sprintf(
                    'Assigned teacher has a conflicting schedule: %s (%s) %s - %s (Class: %s)',
                    optional($teacherConflicts->subject)->name ?? 'Subject',
                    $conflictLabel,
                    optional($teacherConflicts->start_time)?->format('g:i A') ?? $teacherConflicts->start_time,
                    optional($teacherConflicts->end_time)?->format('g:i A') ?? $teacherConflicts->end_time,
                    optional($teacherConflicts->class->section)->name ?? 'Class'
                );

                return back()->withInput()->with('error', $conflictMsg);
            }

            Schedule::create($validated);

            return back()->with('success', 'Schedule entry saved.');
        } catch (\Throwable $throwable) {
            return back()->withInput()->with('error', 'Failed to save schedule: '.$throwable->getMessage());
        }
    }

    /**
     * Show the full-page enrollment form for a specific class.
     */
    public function createEnrollment(Classes $class)
    {
        $class->load(['section.gradeLevel', 'schoolYear', 'enrollments']);

        $section = $class->section;
        $activeSchoolYear = SchoolYear::getRealActive();
        $enrolledCount = $class->enrollments->count();

        return view('admin.enrollment.create', compact(
            'class',
            'section',
            'activeSchoolYear',
            'enrolledCount'
        ));
    }

    /**
     * Enroll a brand-new student directly from the admin class view (uses Classes model).
     */
    public function enrollStudent(Request $request, Classes $class)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|string|max:50|unique:students,student_id',
            'lrn' => 'nullable|string|max:12|unique:students,lrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            // ML Feature fields
            'distance_km' => 'nullable|numeric|min:0|max:100',
            'transportation' => 'nullable|string|max:50',
            'family_income' => 'nullable|string|in:Low,Medium,High',
            'use_existing_guardian' => 'nullable|boolean',
            'guardian_id' => 'nullable|exists:guardians,id',
            // Guardian fields
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'nullable|email|max:255',
            'guardian_phone' => 'nullable|string|max:25',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',
        ]);
        $useExistingGuardian = (bool) ($validated['use_existing_guardian'] ?? false);
        try {
            // Optional: prevent over-capacity
            if ($class->enrollments()->count() >= $class->capacity) {
                return back()->with('error', 'This class has reached its full capacity.')->with('error_form', 'enroll');
            }

            // Ensure the class belongs to the active school year
            $activeSchoolYear = SchoolYear::getActive();
            if (! $activeSchoolYear) {
                return back()->with('error', 'No active school year found.')->with('error_form', 'enroll');
            }

            if ($class->school_year_id !== $activeSchoolYear->id) {
                Log::warning('Admin attempted to enroll student into class not in active school year', [
                    'class_id' => $class->id,
                    'class_school_year_id' => $class->school_year_id,
                    'active_school_year_id' => $activeSchoolYear->id,
                    'validated' => $validated,
                ]);

                return back()->with('error', 'Selected class is not in the active school year.')->with('error_form', 'enroll');
            }

            $plainPassword = '12345678';
            $guardianUser = null;
            $guardianUserWasCreated = false;
            $connectedStudentName = null;
            $guardianEmail = $validated['guardian_email'] ?? null;

            if ($useExistingGuardian && empty($validated['guardian_id'])) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'guardian_email' => 'Select an existing guardian from the dropdown first.',
                    ]);
            }

            if (! $useExistingGuardian && ! empty($guardianEmail)) {
                $existingGuardianUser = User::query()
                    ->where('email', $guardianEmail)
                    ->with('guardian')
                    ->first();

                if ($existingGuardianUser) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'guardian_email' => 'Guardian email already exists. Enable "Use Existing Guardian", select from dropdown, then continue.',
                        ]);
                }
            }

            DB::transaction(function () use (
                $validated,
                $class,
                $plainPassword,
                $useExistingGuardian,
                &$guardianUser,
                &$guardianUserWasCreated,
                &$connectedStudentName
            ) {
                if ($useExistingGuardian) {
                    $guardian = Guardian::query()
                        ->with([
                            'user',
                            'students:id,guardian_id,first_name,last_name',
                        ])
                        ->findOrFail((int) $validated['guardian_id']);

                    $guardianUser = $guardian->user;
                    if (! $guardianUser) {
                        throw new \RuntimeException('Guardian account is incomplete. Missing user profile.');
                    }

                    $connectedStudent = $guardian->students->first();
                    if ($connectedStudent) {
                        $connectedStudentName = trim($connectedStudent->first_name.' '.$connectedStudent->last_name);
                    }

                    $guardianUser->update([
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                    ]);

                    $guardian->update([
                        'phone' => $validated['guardian_phone'] ?? $guardian->phone,
                        'relationship' => $validated['guardian_relationship'],
                    ]);
                } else {
                    $guardianUser = User::create([
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'email' => $validated['guardian_email'] ?? null,
                        'password' => Hash::make($plainPassword),
                        'role_id' => Role::GUARDIAN_ID,
                    ]);
                    $guardianUserWasCreated = true;

                    $guardian = Guardian::create([
                        'user_id' => $guardianUser->id,
                        'phone' => $validated['guardian_phone'] ?? null,
                        'relationship' => $validated['guardian_relationship'],
                    ]);
                }

                $student = Student::create([
                    'student_id' => Student::generateStudentId(),
                    'lrn' => $validated['lrn'] ?? null,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'] ?? null,
                    'distance_km' => $validated['distance_km'] ?? null,
                    'transportation' => $validated['transportation'] ?? null,
                    'family_income' => $validated['family_income'] ?? null,
                    'guardian_id' => $guardian->id,
                    'enrollment_date' => now(),
                ]);

                // Create enrollment with linked student profile
                $profileService = new StudentProfileService;
                $profileService->createEnrollmentWithProfile([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                    'teacher_id' => $class->teacher_id,
                    'enrolled_by_user_id' => Auth::id(),
                    'status' => $validated['status'] ?? 'enrolled',
                ]);
            });

            // Send email AFTER the DB transaction to avoid sending within a transaction
            if ($guardianUser && $guardianUserWasCreated && ! empty($guardianUser->email)) {
                Mail::to($guardianUser->email)->queue(new \App\Mail\WelcomeEmail($guardianUser, $plainPassword));
            }
        } catch (\Throwable $throwable) {
            return back()->withInput()->with('error', 'Failed to add student: '.$throwable->getMessage());
        }

        $successMessage = 'Student enrolled successfully.';
        if ($useExistingGuardian) {
            if ($connectedStudentName) {
                $successMessage .= " Connected to student {$connectedStudentName}; existing credentials were used.";
            } else {
                $successMessage .= ' Existing guardian credentials were used.';
            }
        }

        return redirect()->route('admin.sections.manage', $class->section)->with('success', $successMessage);
    }

    /**
     * Show a student's profile page for admins.
     */
    public function showStudent(Student $student)
    {
        $student->load([
            'guardian.user',
            'enrollments.schoolYear',
            'enrollments.class.section.gradeLevel',
            'enrollments.class.teacher.user',
            'profiles.schoolYear',
            'profiles.gradeLevel',
            'profiles.enrollments.class.section',
        ]);

        $activeSchoolYear = SchoolYear::getActive();
        $activeEnrollment = null;
        $activeProfile = null;
        $attendanceSummary = null;
        $gradeSummary = null;
        $gradesByQuarter = collect();
        $gradesBySubject = collect();
        $quarters = collect();

        if ($activeSchoolYear) {
            $activeEnrollment = $student->enrollments
                ->firstWhere('school_year_id', $activeSchoolYear->id);

            $activeProfile = $student->profiles
                ->firstWhere('school_year_id', $activeSchoolYear->id);

            $attendanceSummary = Attendance::query()
                ->where('student_id', $student->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $gradeSummary = Grade::query()
                ->where('student_id', $student->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->selectRaw('AVG(grade) as avg_grade, COUNT(*) as grade_count')
                ->first();

            $gradesByQuarter = Grade::query()
                ->where('student_id', $student->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->select('quarter as quarter_name', DB::raw('AVG(grade) as average_grade'))
                ->groupBy('quarter')
                ->orderBy('quarter')
                ->get()
                ->keyBy('quarter_name');

            $gradesBySubject = Grade::query()
                ->where('student_id', $student->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
                ->select('subjects.name as subject_name', DB::raw('AVG(grade) as average_grade'))
                ->groupBy('subjects.name')
                ->orderBy('subjects.name')
                ->get();

            $quarters = $activeSchoolYear->quarters()->orderBy('start_date')->get();
        }

        // Grade level history from profiles (distinct by academic year)
        $gradeHistory = $student->profiles
            ->sortByDesc('school_year_id')
            ->values();

        return view('admin.students.show', compact(
            'student',
            'activeSchoolYear',
            'activeEnrollment',
            'activeProfile',
            'attendanceSummary',
            'gradeSummary',
            'gradeHistory',
            'gradesByQuarter',
            'gradesBySubject',
            'quarters'
        ));
    }

    /**
     * Show form to edit a student's details for admins.
     */
    public function editStudent(Student $student)
    {
        $student->load(['guardian.user']);

        return view('admin.students.edit', compact('student'));
    }

    /**
     * Update an existing student's details (and guardian) from the admin manage view.
     */
    public function updateStudent(Request $request, Student $student)
    {
        $guardian = $student->guardian;
        $guardianUser = $guardian?->user;

        $validated = $request->validate([
            'lrn' => 'nullable|string|max:12|unique:students,lrn,'.$student->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',
            'distance_km' => 'nullable|numeric|min:0|max:100',
            'transportation' => 'nullable|string|max:50',
            'family_income' => 'nullable|string|in:Low,Medium,High',
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'nullable|email|max:255'.($guardianUser ? '|unique:users,email,'.$guardianUser->id : '|unique:users,email'),
            'guardian_phone' => 'nullable|string|max:25',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',
        ]);

        try {
            DB::transaction(function () use ($validated, $student, $guardian, $guardianUser) {
                if ($guardianUser) {
                    $guardianUser->update([
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'email' => array_key_exists('guardian_email', $validated) ? $validated['guardian_email'] : $guardianUser->email,
                    ]);
                } else {
                    $guardianUser = User::create([
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'email' => $validated['guardian_email'] ?? null,
                        'password' => Hash::make(12345678),
                        'role_id' => Role::GUARDIAN_ID,
                    ]);
                }

                if ($guardian) {
                    $guardian->update([
                        'phone' => array_key_exists('guardian_phone', $validated) ? $validated['guardian_phone'] : $guardian->phone,
                        'relationship' => $validated['guardian_relationship'],
                    ]);
                } else {
                    $guardian = Guardian::create([
                        'user_id' => $guardianUser->id,
                        'phone' => $validated['guardian_phone'] ?? null,
                        'relationship' => $validated['guardian_relationship'],
                    ]);
                }

                $student->update([
                    'lrn' => $validated['lrn'] ?? $student->lrn,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'] ?? $student->address,
                    'distance_km' => $validated['distance_km'] ?? $student->distance_km,
                    'transportation' => $validated['transportation'] ?? $student->transportation,
                    'family_income' => $validated['family_income'] ?? $student->family_income,
                    'guardian_id' => $guardian->id,
                ]);
            });
        } catch (\Throwable $throwable) {
            return back()->withInput()->with('edit_student_id', $student->id)->with('error', 'Failed to update student: '.$throwable->getMessage());
        }

        return back()->with('success', 'Student updated successfully.');
    }

    /**
     * Delete a class (and cascading relationships).
     */
    public function destroyClass(Classes $class)
    {
        try {
            $class->delete();

            return redirect()->route('admin.sections.index')->with('success', 'Class deleted successfully.');
        } catch (\Throwable $throwable) {
            return back()->with('error', 'Failed to delete class: '.$throwable->getMessage());
        }
    }

    /**
     * Remove a student from the active class of a section.
     */
    public function removeStudent(Section $section, Student $student)
    {
        $class = $this->findActiveClass($section);
        if (! $class) {
            return redirect()->route('admin.sections.index')
                ->with('error', 'No active class found for this section in the current school year.');
        }
        try {
            $enrollment = Enrollment::where('class_id', $class->id)
                ->where('student_id', $student->id)
                ->where('school_year_id', $class->school_year_id)
                ->first();

            if ($enrollment) {
                $enrollment->delete();

                return back()->with('success', 'Student removed from section.');
            }

            return back()->with('error', 'Student is not enrolled in this section.');
        } catch (\Throwable $throwable) {
            return back()->with('error', 'Failed to remove student: '.$throwable->getMessage());
        }
    }

    /**
     * Placeholder for adding a subject to a section.
     */
    public function addSubject(Request $request, Section $section)
    {
        return back()->with('error', 'Subject assignment per section is not yet implemented.');
    }

    /**
     * Placeholder for removing a subject from a section.
     */
    public function removeSubject(Section $section, Subject $subject)
    {
        return back()->with('error', 'Subject assignment per section is not yet implemented.');
    }

    /**
     * Rename the section.
     */
    public function renameSection(Request $request, Section $section)
    {
        try {
            $gradeLevelId = $section->grade_level_id;

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('sections', 'name')->where(function (QueryBuilder $query) use ($gradeLevelId) {
                        $query->where('grade_level_id', $gradeLevelId);
                    })->ignore($section->id),
                ],
            ]);

            $section->update(['name' => $validated['name']]);

            return redirect()->back()->with('success', 'Section renamed successfully.');
        } catch (\Throwable $throwable) {
            return back()->withInput()->with('error', 'Failed to rename section: '.$throwable->getMessage());
        }
    }

    /**
     * Return schedule data for a section (JSON helper used by dashboards/widgets).
     */
    public function schedule(Section $section)
    {
        $class = $this->findActiveClass($section, [
            'schedules.subject',
            'schedules.teacher.user',
        ]);

        if (! $class) {
            return response()->json([
                'message' => 'No active class found for this section.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'section' => $section->load('gradeLevel'),
            'schedules' => $class->schedules,
        ]);
    }

    /**
     * Check if a teacher is an adviser of a block section (Grades 1-3).
     */
    private function isBlockAdviser(int $teacherId, int $schoolYearId, ?int $ignoreClassId = null): bool
    {
        return Classes::where('teacher_id', $teacherId)
            ->where('school_year_id', $schoolYearId)
            ->when($ignoreClassId, function ($query) use ($ignoreClassId) {
                $query->where('id', '!=', $ignoreClassId);
            })
            ->whereHas('section.gradeLevel', function ($query) {
                $query->whereIn('level', [1, 2, 3]);
            })
            ->exists();
    }

    /**
     * Retrieve the active school year or throw a friendly error.
     */
    private function getActiveSchoolYear(): SchoolYear
    {
        $active = SchoolYear::getActive();

        if (! $active) {
            $message = 'No school year found. Please create one first in the dashboard.';

            if (request()->expectsJson()) {
                abort(404, $message);
            }

            throw new HttpResponseException(
                redirect()->route('admin.dashboard')->with('error', $message)
            );
        }

        return $active;
    }

    /**
     * Find the active class for a given section.
     */
    private function findActiveClass(Section $section, array $relations = [])
    {
        $activeSchoolYear = $this->getActiveSchoolYear();

        $query = Classes::where('section_id', $section->id)
            ->where('school_year_id', $activeSchoolYear->id);

        if ($relations) {
            $query->with($relations);
        }

        return $query->first();
    }

    /**
     * Resolve the grade level id whether the request sends an id or numeric level.
     */
    private function resolveGradeLevelId(Request $request): ?int
    {
        if ($request->filled('grade_level_id')) {
            return (int) $request->input('grade_level_id');
        }

        if ($request->filled('grade_level')) {
            $gradeLevel = GradeLevel::where('level', $request->input('grade_level'))->first();

            return $gradeLevel?->id;
        }

        return null;
    }
}
