<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
            ->sortBy(fn($class) => optional($class->section->gradeLevel)->level);

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
        if (!$gradeLevelId) {
            return back()->withInput()->with('error', 'Invalid grade level selected.');
        }

        $request->merge(['grade_level_id' => $gradeLevelId]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level_id' => 'required|integer|exists:grade_levels,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'capacity' => 'nullable|integer|min:1|max:60',
            'description' => 'nullable|string',
        ]);

        $activeSchoolYear = $this->getActiveSchoolYear();

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
            if (str_contains($exception->getMessage(), 'classes_section_id_school_year_id_unique')) {
                return back()->withInput()->with('error', 'This section already exists for the current school year.');
            }

            return back()->withInput()->with('error', 'Failed to create section. ' . $exception->getMessage());
        } catch (\Throwable $throwable) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create section. ' . $throwable->getMessage());
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

        if (!$class) {
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
            'name' => 'required|string|max:255',
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
            $class->update([
                'teacher_id' => $validated['teacher_id'] ?? $class->teacher_id,
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

        if (!$class) {
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
    public function manageClass(Section $section)
    {
        $section->load('gradeLevel');
        $class = $this->findActiveClass($section, [
            'teacher.user',
            'schoolYear',
            'enrollments.student.guardian.user',
            'schedules.subject',
            'schedules.teacher.user',
        ]);

        if (!$class) {
            return redirect()->route('admin.sections.index')
                ->with('error', 'No active class found for this section in the current school year.');
        }

        $subjects = Subject::where('grade_level_id', $section->grade_level_id)->orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('id')->get();

        return view('admin.sections.manage', compact('section', 'class', 'subjects', 'teachers'));
    }

    /**
     * Assign an adviser to a class.
     */
    public function assignClassAdviser(Request $request, Classes $class)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        $class->update(['teacher_id' => $validated['teacher_id']]);

        return back()->with('success', 'Adviser assigned successfully.');
    }

    /**
     * Store a schedule entry for a class.
     */
    public function storeSchedule(Request $request, Classes $class)
    {

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day_of_week' => 'required|array|min:1',
            'day_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
        ]);

        $validated['class_id'] = $class->id;

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
                "Schedule conflicts with existing class schedule: %s (%s) %s - %s",
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
                "Assigned teacher has a conflicting schedule: %s (%s) %s - %s (Class: %s)",
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
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'required|email|max:255|unique:users,email',
            'guardian_phone' => 'required|string|max:25',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',
        ]);

        try {
            // Optional: prevent over-capacity
            if ($class->enrollments()->count() >= $class->capacity) {
                return back()->with('error', 'This class has reached its full capacity.');
            }

            DB::transaction(function () use ($validated, $class) {
                $guardianUser = User::create([
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'email' => $validated['guardian_email'],
                    'password' => Hash::make(Str::random(12)),
                    'role_id' => 3,
                ]);

                $guardian = Guardian::create([
                    'user_id' => $guardianUser->id,
                    'phone' => $validated['guardian_phone'],
                    'relationship' => $validated['guardian_relationship'],
                ]);

                $student = Student::create([
                    'student_id' => $validated['student_id'] ?? null,
                    'lrn' => $validated['lrn'] ?? null,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'] ?? null,
                    'guardian_id' => $guardian->id,
                    'enrollment_date' => now(),
                ]);

                Enrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                    'teacher_id' => $class->teacher_id,
                    'status' => $validated['status'] ?? 'enrolled',
                ]);

                Mail::to($guardianUser->email)->send(new \App\Mail\WelcomeEmail($guardianUser, $guardianUser->password));
            });
        } catch (\Throwable $throwable) {
            return back()->with('error', 'Failed to add student: ' . $throwable->getMessage());
        }

        return back()->with('success', 'Student enrolled successfully.');
    }

    /**
     * Delete a class (and cascading relationships).
     */
    public function destroyClass(Classes $class)
    {
        $class->delete();
        return redirect()->route('admin.sections.index')->with('success', 'Class deleted successfully.');
    }

    /**
     * Enroll a brand-new student directly from the admin section UI.
     */
    public function addStudent(Request $request, Section $section)
    {
        $class = $this->findActiveClass($section);
        if (!$class) {
            return redirect()->route('admin.sections.index')
                ->with('error', 'No active class found for this section in the current school year.');
        }

        $validated = $request->validate([
            'student_id' => 'nullable|string|max:50|unique:students,student_id',
            'lrn' => 'nullable|string|max:12|unique:students,lrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'required|email|max:255|unique:users,email',
            'guardian_phone' => 'required|string|max:25',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',
        ]);

        try {
            DB::transaction(function () use ($validated, $class, $section) {
                $guardianUser = User::create([
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'email' => $validated['guardian_email'],
                    'password' => Hash::make(Str::random(12)),
                    'role_id' => 3,
                ]);

                $guardian = Guardian::create([
                    'user_id' => $guardianUser->id,
                    'phone' => $validated['guardian_phone'],
                    'relationship' => $validated['guardian_relationship'],
                ]);

                $student = Student::create([
                    'student_id' => $validated['student_id'] ?? null,
                    'lrn' => $validated['lrn'] ?? null,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'] ?? null,
                    'guardian_id' => $guardian->id,
                    'enrollment_date' => now(),
                ]);

                Enrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                    'teacher_id' => $class->teacher_id,
                    'status' => $validated['status'] ?? 'enrolled',
                ]);
            });
        } catch (\Throwable $throwable) {
            return back()->with('error', 'Failed to add student: ' . $throwable->getMessage());
        }

        return back()->with('success', 'Student enrolled successfully.');
    }

    /**
     * Remove a student from the active class of a section.
     */
    public function removeStudent(Section $section, Student $student)
    {
        $class = $this->findActiveClass($section);
        if (!$class) {
            return redirect()->route('admin.sections.index')
                ->with('error', 'No active class found for this section in the current school year.');
        }

        $enrollment = Enrollment::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->where('school_year_id', $class->school_year_id)
            ->first();

        if ($enrollment) {
            $enrollment->delete();
            return back()->with('success', 'Student removed from section.');
        }

        return back()->with('error', 'Student is not enrolled in this section.');
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
     * Return schedule data for a section (JSON helper used by dashboards/widgets).
     */
    public function schedule(Section $section)
    {
        $class = $this->findActiveClass($section, [
            'schedules.subject',
            'schedules.teacher.user',
        ]);

        if (!$class) {
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
     * Retrieve the active school year or throw a friendly error.
     */
    private function getActiveSchoolYear(): SchoolYear
    {
        return SchoolYear::where('is_active', true)->firstOrFail();
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
