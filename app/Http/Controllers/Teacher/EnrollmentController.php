<?php

namespace App\Http\Controllers\Teacher;

use App\Exports\EnrolleesExport;
use App\Exports\TeacherEnrolleesReport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentController extends Controller
{
    public function getEnrollmentsByClass(Classes $class)
    {
        $enrollments = Enrollment::where('class_id', $class->id)->with('student')->get();

        return view('teacher.enrollment.partials.enrollment-table', compact('enrollments', 'class'));
    }

    public function export(Request $request, Classes $class)
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();
        $schoolYear = $activeSchoolYear ? $activeSchoolYear->name : 'current';

        return Excel::download(
            new EnrolleesExport($class->id, null, $class->school_year_id),
            "enrollees_SY_{$schoolYear}.xlsx"
        );
    }

    public function index(Request $request)
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->first();
        $targetSchoolYearId = $request->input('school_year_id');

        // Determine the "current" school year context for the view.
        // If the user selected a year, use it. Otherwise use the active year.
        $currentSchoolYear = $targetSchoolYearId
            ? SchoolYear::find($targetSchoolYearId)
            : $activeSchoolYear;

        $teacher = Auth::user()->teacher ?? null;
        $teacherEnrollments = collect();
        $classes = collect();
        $studentsToEnroll = collect();
        $previousSchoolYear = null;

        if ($currentSchoolYear) {
            $teacherEnrollments = $teacher ? Enrollment::where('teacher_id', $teacher->id)
                ->where('school_year_id', $currentSchoolYear->id)
                ->with('class.section.gradeLevel', 'student')
                ->get()
                ->groupBy(fn ($e) => $e->class_id) : collect();

            $classes = Classes::where('school_year_id', $currentSchoolYear->id)
                ->with('section.gradeLevel', 'enrollments')
                ->get()
                ->sortBy('section.gradeLevel.level');

            // Get the school year that ended immediately before the current school year started
            // This ensures we get the chronologically previous year, not just any inactive year
            $previousSchoolYear = SchoolYear::where('id', '!=', $currentSchoolYear->id)
                ->where('end_date', '<', $currentSchoolYear->start_date)
                ->orderBy('end_date', 'desc')
                ->first();

            // Get the highest grade level (Grade 6) - students who completed this have graduated
            $highestGradeLevel = GradeLevel::orderBy('level', 'desc')->first();

            // 1. Get students from the previous school year who are not enrolled in current year
            $previousYearStudents = collect();
            if ($previousSchoolYear) {
                $previousYearStudents = Student::whereDoesntHave('enrollments', function ($query) use ($currentSchoolYear) {
                    $query->where('school_year_id', $currentSchoolYear->id);
                })->whereHas('enrollments', function ($query) use ($previousSchoolYear) {
                    $query->where('school_year_id', $previousSchoolYear->id);
                })->with(['profiles.gradeLevel', 'profiles.schoolYear', 'guardian.user', 'enrollments.class.section.gradeLevel', 'enrollments.schoolYear'])->get();
            }

            // 2. Get students with profiles in the CURRENT school year who are NOT enrolled in the current school year
            // This includes students who have a profile for the active year but were never enrolled or dropped
            $unenrolledProfileStudents = Student::whereDoesntHave('enrollments', function ($query) use ($currentSchoolYear) {
                $query->where('school_year_id', $currentSchoolYear->id);
            })->whereHas('profiles', function ($query) use ($currentSchoolYear) {
                // Has a profile for the current/active school year
                $query->where('school_year_id', $currentSchoolYear->id);
            })->with(['profiles.gradeLevel', 'profiles.schoolYear', 'guardian.user', 'enrollments.class.section.gradeLevel', 'enrollments.schoolYear'])->get();

            // 3. Also include students who exist but have NO enrollments at all (created but never enrolled)
            // Only include those who have a profile in the current school year
            $neverEnrolledStudents = Student::whereDoesntHave('enrollments')
                ->whereHas('profiles', function ($query) use ($currentSchoolYear) {
                    $query->where('school_year_id', $currentSchoolYear->id);
                })
                ->with(['profiles.gradeLevel', 'profiles.schoolYear', 'guardian.user'])
                ->get();

            // Merge all three collections and remove duplicates
            $studentsToEnroll = $previousYearStudents
                ->merge($unenrolledProfileStudents)
                ->merge($neverEnrolledStudents)
                ->unique('id');

            // Filter out students whose last profile was at the highest grade level (graduated)
            if ($highestGradeLevel) {
                $studentsToEnroll = $studentsToEnroll->filter(function ($student) use ($highestGradeLevel) {
                    // Get the most recent profile by school year
                    $lastProfile = $student->profiles->sortByDesc('school_year_id')->first();

                    // If the student's last profile was at the highest grade level, check if they graduated
                    if ($lastProfile && $lastProfile->grade_level_id === $highestGradeLevel->id) {
                        // Students who dropped, retained, pending, or enrolled can still be re-enrolled
                        // Only exclude those who were promoted (graduated)
                        return in_array($lastProfile->status, ['dropped', 'retained', 'enrolled', 'pending']);
                    }

                    return true;
                });
            }

            // Sort students by last name, then first name
            $studentsToEnroll = $studentsToEnroll->sortBy([
                ['last_name', 'asc'],
                ['first_name', 'asc'],
            ])->values();
        }

        // Get all school years for the selector
        $allSchoolYears = SchoolYear::where('is_active', true)
            ->orWhere('is_promotion_open', true)
            ->orderBy('start_date', 'desc')->get();

        return view('teacher.enrollment.index', [
            'classes' => $classes,
            'students' => $studentsToEnroll,
            'teacherEnrollments' => $teacherEnrollments,
            'currentSchoolYear' => $currentSchoolYear,
            'previousSchoolYear' => $previousSchoolYear ?? null,
            'gradeLevels' => GradeLevel::orderBy('level')->get(),
            'error' => ! $currentSchoolYear ? 'No school year found.' : null,
            'currentTeacherId' => $teacher?->id,
            'allSchoolYears' => $allSchoolYears,
            'activeSchoolYearId' => $activeSchoolYear?->id,
        ]);
    }

    public function create()
    {
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();

        // Get the school year that ended immediately before the current school year started
        $previousSchoolYear = $currentSchoolYear
            ? SchoolYear::where('id', '!=', $currentSchoolYear->id)
                ->where('end_date', '<', $currentSchoolYear->start_date)
                ->orderBy('end_date', 'desc')
                ->first()
            : null;

        if (! $previousSchoolYear || ! $currentSchoolYear) {
            return redirect()->back()->with('error', 'Previous or current school year not found.');
        }

        $studentsToEnroll = Student::whereDoesntHave('enrollments', function ($query) use ($currentSchoolYear) {
            $query->where('school_year_id', $currentSchoolYear->id);
        })->whereHas('enrollments', function ($query) use ($previousSchoolYear) {
            $query->where('school_year_id', $previousSchoolYear->id);
        })->get();

        return view('teacher.enrollment.create', [
            'students' => $studentsToEnroll,
        ]);
    }

    public function store(Request $request, ?Classes $class = null)
    {
        // 1. Validate all the fields from the "Enroll New Student" modal
        $validated = $request->validate([
            // Class selection
            'class_id' => 'required|exists:classes,id',
            // Student fields
            'lrn' => 'nullable|string|max:12|unique:students,lrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',
            // ML Feature fields
            'distance_km' => 'nullable|numeric|min:0|max:100',
            'transportation' => 'nullable|string|max:50',
            'family_income' => 'nullable|string|in:Low,Medium,High',

            // Guardian fields
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'required|email|max:255|unique:users,email',
            'guardian_phone' => 'required|string|max:20',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',

            // Enrollment status
            'enrollment_status' => 'nullable|string|in:enrolled,transferee',
        ]);

        // Resolve the class via route-model binding or fallback to validated class_id
        $resolvedClass = $class && $class->exists ? $class : Classes::findOrFail($validated['class_id']);

        // Ensure class has a valid school year
        if (! $resolvedClass->school_year_id) {
            return redirect()->back()->with('error', 'Selected class does not represent a valid school year.');
        }

        // Determine enrollment status (default to 'enrolled')
        $enrollmentStatus = $validated['enrollment_status'] ?? 'enrolled';

        try {
            $plainPassword = '12345678';

            DB::transaction(function () use ($validated, $plainPassword, $resolvedClass, $enrollmentStatus) {
                $teacher = optional(Auth::user())->teacher;
                if (! $teacher) {
                    throw new \RuntimeException('Teacher profile missing for the current user.');
                }

                $guardianUser = User::create([
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'email' => $validated['guardian_email'],
                    'password' => Hash::make($plainPassword),
                    'role_id' => 3,
                ]);

                // 4. Create the Guardian Record, linked to the new User
                $guardian = Guardian::create([
                    'user_id' => $guardianUser->id,
                    'phone' => $validated['guardian_phone'],
                    'relationship' => $validated['guardian_relationship'],
                ]);

                // 5. Create the Student Record, linked to the new Guardian
                $student = Student::create([
                    'lrn' => $validated['lrn'],
                    'student_id' => Student::generateStudentId(),
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'],
                    'distance_km' => $validated['distance_km'] ?? null,
                    'transportation' => $validated['transportation'] ?? null,
                    'family_income' => $validated['family_income'] ?? null,
                    'guardian_id' => $guardian->id,
                ]);

                // 6. Create the final Enrollment Record, linking the Student to the Class
                $profileService = new StudentProfileService;
                $profileService->createEnrollmentWithProfile([
                    'student_id' => $student->id,
                    'class_id' => $resolvedClass->id,
                    'school_year_id' => $resolvedClass->school_year_id,
                    'teacher_id' => $teacher->id,
                    'status' => $enrollmentStatus,
                ]);
                if ($guardianUser) {
                    Mail::to($guardianUser->email)->queue(new \App\Mail\WelcomeEmail($guardianUser, $plainPassword));
                }
            });

            $statusLabel = $enrollmentStatus === 'transferee' ? ' (Transferee)' : '';

            return redirect()->back()->with('success', 'Student enrolled successfully'.$statusLabel.'.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Error student enrolment failed.'.$th->getMessage());
        }
    }

    public function storePastStudent(Request $request)
    {
        // Validate the incoming request - student_id can be single or comma-separated
        $request->validate([
            'student_id' => 'required|string',
            'class_id' => 'required|exists:classes,id',
            'student_updates' => 'nullable|string', // JSON string of student updates
            'enrollment_status' => 'nullable|string|in:enrolled,transferee',
        ]);

        try {
            $class = Classes::findOrFail($request->class_id);
            $schoolYear = $class->schoolYear;

            if (! $schoolYear || (! $schoolYear->is_active && ! $schoolYear->is_promotion_open)) {
                return redirect()->route('teacher.enrollment.index')->with('error', 'Enrollment for this school year is not open.');
            }

            // Parse student IDs (can be single ID or comma-separated list)
            $studentIds = array_filter(array_map('intval', explode(',', $request->student_id)));

            if (empty($studentIds)) {
                return redirect()->route('teacher.enrollment.index')->with('error', 'No valid students selected.');
            }

            // Parse student updates if provided
            $studentUpdates = [];
            if ($request->student_updates) {
                $studentUpdates = json_decode($request->student_updates, true) ?? [];
            }

            // Get enrollment status (default to 'enrolled' for returning students)
            $enrollmentStatus = $request->enrollment_status ?? 'enrolled';

            $class = Classes::findOrFail($request->class_id);

            // We determine the target school year from the class itself.
            $targetSchoolYearId = $class->school_year_id;

            // Check capacity
            $currentEnrolled = $class->enrollments()->count();
            $availableSlots = $class->capacity - $currentEnrolled;

            if (count($studentIds) > $availableSlots) {
                return redirect()->route('teacher.enrollment.index')
                    ->with('error', "Not enough slots available. Class has {$availableSlots} slots but trying to enroll ".count($studentIds).' students.');
            }

            $profileService = new StudentProfileService;
            $teacherId = Auth::user()->teacher->id;
            $enrolledCount = 0;
            $skippedCount = 0;
            $updatedCount = 0;
            $errors = [];

            DB::transaction(function () use ($studentIds, $studentUpdates, $class, $targetSchoolYearId, $profileService, $teacherId, $enrollmentStatus, &$enrolledCount, &$skippedCount, &$updatedCount, &$errors) {
                foreach ($studentIds as $studentId) {
                    $student = Student::find($studentId);
                    if (! $student) {
                        $errors[] = "Student ID {$studentId} not found";

                        continue;
                    }

                    // Check if the student is already enrolled in the target school year
                    $isAlreadyEnrolled = $student->enrollments()->where('school_year_id', $targetSchoolYearId)->exists();
                    if ($isAlreadyEnrolled) {
                        $skippedCount++;
                        $errors[] = "{$student->first_name} {$student->last_name} is already enrolled";

                        continue;
                    }

                    // Apply student updates if provided
                    if (isset($studentUpdates[$studentId]) && ! empty($studentUpdates[$studentId])) {
                        // ...existing code...
                        $updates = $studentUpdates[$studentId];

                        // Update student fields
                        $studentFields = ['first_name', 'last_name', 'lrn', 'gender', 'birthdate', 'address'];
                        $studentData = [];
                        foreach ($studentFields as $field) {
                            if (isset($updates[$field]) && $updates[$field] !== '') {
                                $studentData[$field] = $updates[$field];
                            }
                        }
                        if (! empty($studentData)) {
                            $student->update($studentData);
                        }

                        // Update guardian fields
                        $guardianFields = ['guardian_first_name', 'guardian_last_name', 'guardian_email', 'guardian_phone', 'guardian_relationship'];
                        $hasGuardianUpdates = false;
                        foreach ($guardianFields as $field) {
                            if (isset($updates[$field]) && $updates[$field] !== '') {
                                $hasGuardianUpdates = true;
                                break;
                            }
                        }

                        if ($hasGuardianUpdates && $student->guardian) {
                            $guardian = $student->guardian;
                            $guardianUser = $guardian->user;

                            // Update guardian user
                            if ($guardianUser) {
                                $userUpdates = [];
                                if (isset($updates['guardian_first_name']) && $updates['guardian_first_name'] !== '') {
                                    $userUpdates['first_name'] = $updates['guardian_first_name'];
                                }
                                if (isset($updates['guardian_last_name']) && $updates['guardian_last_name'] !== '') {
                                    $userUpdates['last_name'] = $updates['guardian_last_name'];
                                }
                                if (isset($updates['guardian_email']) && $updates['guardian_email'] !== '') {
                                    $userUpdates['email'] = $updates['guardian_email'];
                                }
                                if (! empty($userUpdates)) {
                                    $guardianUser->update($userUpdates);
                                }
                            }

                            // Update guardian record
                            $guardianUpdates = [];
                            if (isset($updates['guardian_phone']) && $updates['guardian_phone'] !== '') {
                                $guardianUpdates['phone'] = $updates['guardian_phone'];
                            }
                            if (isset($updates['guardian_relationship']) && $updates['guardian_relationship'] !== '') {
                                $guardianUpdates['relationship'] = $updates['guardian_relationship'];
                            }
                            if (! empty($guardianUpdates)) {
                                $guardian->update($guardianUpdates);
                            }
                        }

                        $updatedCount++;
                    }

                    // Create the new enrollment record with linked student profile
                    $profileService->createEnrollmentWithProfile([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'school_year_id' => $targetSchoolYearId,
                        'teacher_id' => $teacherId,
                        'enrollment_date' => now(),
                        'status' => $enrollmentStatus,
                    ]);

                    $enrolledCount++;
                }
            });

            // Build success message
            $message = "{$enrolledCount} student(s) enrolled successfully!";
            if ($updatedCount > 0) {
                $message .= " ({$updatedCount} student(s) updated)";
            }
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} skipped - already enrolled)";
            }

            return redirect()->route('teacher.enrollment.index')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('teacher.enrollment.index')->with('error', 'Failed to enroll students: '.$e->getMessage());
        }
    }

    /**
     * Update an existing student's details (and guardian) from class view modal.
     */
    public function updateStudent(Request $request, Student $student)
    {
        $guardian = $student->guardian; // may be null
        $guardianUser = $guardian?->user;

        $validated = $request->validate([
            // Student fields
            'lrn' => 'nullable|string|max:12|unique:students,lrn,'.$student->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',
            // ML Feature fields
            'distance_km' => 'nullable|numeric|min:0|max:100',
            'transportation' => 'nullable|string|max:50',
            'family_income' => 'nullable|string|in:Low,Medium,High',

            // Guardian fields
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'required|email|max:255'.($guardianUser ? '|unique:users,email,'.$guardianUser->id : '|unique:users,email'),
            'guardian_phone' => 'required|string|max:20',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',
        ]);

        try {
            DB::transaction(function () use ($validated, $student, $guardian, $guardianUser) {
                // Update guardian user (create if missing)
                if ($guardianUser) {
                    $guardianUser->update([
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'email' => $validated['guardian_email'],
                    ]);
                } else {
                    $guardianUser = User::create([
                        'first_name' => $validated['guardian_first_name'],
                        'last_name' => $validated['guardian_last_name'],
                        'email' => $validated['guardian_email'],
                        'password' => Hash::make(12345678),
                        'role_id' => 3,
                    ]);
                }

                if ($guardian) {
                    $guardian->update([
                        'phone' => $validated['guardian_phone'],
                        'relationship' => $validated['guardian_relationship'],
                    ]);
                } else {
                    $guardian = Guardian::create([
                        'user_id' => $guardianUser->id,
                        'phone' => $validated['guardian_phone'],
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
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to update student: '.$e->getMessage());
        }

        return back()->with('success', 'Student updated successfully.');
    }

    public function storeStudentByAdviser(Request $request, Classes $class)
    {
        // 1. Validate all the fields from the "Enroll New Student" modal
        // dd($request->all(), $class->section->name);
        $validated = $request->validate([
            // Student fields
            'lrn' => 'nullable|string|max:12|unique:students,lrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',
            // ML Feature fields
            'distance_km' => 'nullable|numeric|min:0|max:100',
            'transportation' => 'nullable|string|max:50',
            'family_income' => 'nullable|string|in:Low,Medium,High',

            // Guardian fields
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'required|email|max:255|unique:users,email',
            'guardian_phone' => 'required|string|max:20',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',
        ]);

        try {
            // Optional: Check class capacity before proceeding
            if ($class->enrollments()->count() >= $class->capacity) {
                return back()->with('error', 'This class has reached its full capacity.');
            }

            // Ensure the adviser is enrolling into a class for the active school year
            $currentSchoolYear = SchoolYear::where('is_active', true)->first();
            if (! $currentSchoolYear) {
                return back()->with('error', 'No active school year found.');
            }

            if ($class->school_year_id !== $currentSchoolYear->id) {
                Log::warning('Adviser attempted to enroll student into class not in active school year', [
                    'class_id' => $class->id,
                    'class_school_year_id' => $class->school_year_id,
                    'active_school_year_id' => $currentSchoolYear->id,
                    'validated' => $validated,
                ]);

                return back()->with('error', 'Selected class is not in the active school year.');
            }

            // 2. Use a database transaction for safety.
            // This ensures all records are created successfully, or none are.
            DB::transaction(function () use ($validated, $class) {

                // 3. Create the Guardian's User Account
                $guardianUser = User::create([
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'email' => $validated['guardian_email'],
                    'password' => Hash::make(12345678),
                    'role_id' => 3,
                ]);

                // 4. Create the Guardian Record, linked to the new User
                $guardian = Guardian::create([
                    'user_id' => $guardianUser->id,
                    'phone' => $validated['guardian_phone'],
                    'relationship' => $validated['guardian_relationship'],
                ]);

                // 5. Create the Student Record, linked to the new Guardian
                $student = Student::create([
                    'student_id' => Student::generateStudentId(),
                    'lrn' => $validated['lrn'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'],
                    'distance_km' => $validated['distance_km'] ?? null,
                    'transportation' => $validated['transportation'] ?? null,
                    'family_income' => $validated['family_income'] ?? null,
                    'guardian_id' => $guardian->id,
                ]);

                // 6. Create the final Enrollment Record, linking the Student to the Class
                $profileService = new StudentProfileService;
                $profileService->createEnrollmentWithProfile([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                    'teacher_id' => Auth::user()->teacher->id,
                    'status' => 'enrolled',
                ]);
            });

            return redirect()->back()->with('success', 'Student enrolled successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Error student enrolment failed.'.$th->getMessage());
        }
    }

    public function enrollment()
    {

        return view('teacher.enrollment.index');
    }

    public function exportAll()
    {
        $teacher = Auth::user()->teacher;
        if (! $teacher) {
            return redirect()->back()->with('error', 'Teacher profile not found.');
        }

        $activeSchoolYear = \App\Models\SchoolYear::where('is_active', true)->first();
        $schoolYear = $activeSchoolYear ? $activeSchoolYear->name : 'current';
        $fileName = "{$teacher->user->last_name}_All_Enrollees_SY_{$schoolYear}.xlsx";

        return Excel::download(new TeacherEnrolleesReport($teacher->id), $fileName);
    }

    /**
     * Show a student's profile page for teachers (similar to admin view with Grade Level History).
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

        $activeSchoolYear = SchoolYear::where('is_active', true)->first();
        $activeEnrollment = null;
        $activeProfile = null;
        $attendanceSummary = null;
        $gradeSummary = null;

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
        }

        // Grade level history from profiles (distinct by academic year)
        $gradeHistory = $student->profiles
            ->sortByDesc('school_year_id')
            ->values();

        return view('teacher.students.show', compact(
            'student',
            'activeSchoolYear',
            'activeEnrollment',
            'activeProfile',
            'attendanceSummary',
            'gradeSummary',
            'gradeHistory'
        ));
    }

    /**
     * Show form to edit a student's details for teachers.
     */
    public function editStudent(Student $student)
    {
        $student->load(['guardian.user']);

        return view('teacher.students.edit', compact('student'));
    }
}
