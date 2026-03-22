<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\SchoolYear;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StudentController extends Controller
{
    /**
     * Display a listing of all student profiles.
     */
    public function index(Request $request)
    {
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        $gradeLevels = GradeLevel::orderBy('level')->get();

        // Get the current teacher
        $teacher = Auth::user()->teacher;

        // Get class IDs where the teacher has schedules or is the advisory teacher
        $teacherClassIds = collect();
        if ($teacher && $currentSchoolYear) {
            // Classes from schedules (subjects the teacher teaches)
            $scheduleClassIds = $teacher->schedules()
                ->whereHas('class', function ($q) use ($currentSchoolYear) {
                    $q->where('school_year_id', $currentSchoolYear->id);
                })
                ->pluck('class_id');

            // Advisory classes
            $advisoryClassIds = $teacher->classes()
                ->where('school_year_id', $currentSchoolYear->id)
                ->pluck('id');

            $teacherClassIds = $scheduleClassIds->merge($advisoryClassIds)->unique();
        }

        // Build query - only students enrolled in classes where teacher has schedules
        $query = Student::with([
            'guardian.user',
            'profiles' => function ($q) use ($currentSchoolYear) {
                $q->with(['gradeLevel', 'schoolYear']);
                if ($currentSchoolYear) {
                    $q->where('school_year_id', $currentSchoolYear->id);
                }
            },
            'enrollments' => function ($q) use ($currentSchoolYear) {
                if ($currentSchoolYear) {
                    $q->where('school_year_id', $currentSchoolYear->id);
                }
            },
            'enrollments.class.section.gradeLevel',
        ]);

        // Filter to only students in teacher's classes OR students with pending profiles created by this teacher
        if ($teacherClassIds->isNotEmpty()) {
            $query->where(function ($q) use ($teacherClassIds, $currentSchoolYear, $teacher) {
                // Students enrolled in teacher's classes
                $q->whereHas('enrollments', function ($subQ) use ($teacherClassIds, $currentSchoolYear) {
                    $subQ->whereIn('class_id', $teacherClassIds);
                    if ($currentSchoolYear) {
                        $subQ->where('school_year_id', $currentSchoolYear->id);
                    }
                });
                // OR students with pending profiles created by this teacher
                if ($teacher && $currentSchoolYear) {
                    $q->orWhereHas('profiles', function ($subQ) use ($teacher, $currentSchoolYear) {
                        $subQ->where('status', 'pending')
                            ->where('created_by_teacher_id', $teacher->id)
                            ->where('school_year_id', $currentSchoolYear->id);
                    });
                }
            });
        } elseif ($teacher && $currentSchoolYear) {
            // Teacher has no classes, but may have created pending profiles
            $query->whereHas('profiles', function ($q) use ($teacher, $currentSchoolYear) {
                $q->where('status', 'pending')
                    ->where('created_by_teacher_id', $teacher->id)
                    ->where('school_year_id', $currentSchoolYear->id);
            });
        } else {
            // No teacher or no school year, return empty result
            $query->whereRaw('1 = 0');
        }

        // Check if teacher enrollment is enabled
        $teacherEnrollmentEnabled = filter_var(
            Setting::where('key', 'teacher_enrollment')->value('value'),
            FILTER_VALIDATE_BOOLEAN
        );

        // Filter by enrollment status - default to 'all'
        $enrollmentFilter = $request->get('enrollment_status', 'all');
        if ($enrollmentFilter === 'pending' && $currentSchoolYear && $teacher) {
            // Show students with pending profiles created by this teacher
            $query->whereHas('profiles', function ($q) use ($teacher, $currentSchoolYear) {
                $q->where('status', 'pending')
                    ->where('created_by_teacher_id', $teacher->id)
                    ->where('school_year_id', $currentSchoolYear->id);
            })->whereDoesntHave('enrollments', function ($q) use ($currentSchoolYear) {
                $q->where('school_year_id', $currentSchoolYear->id);
            });
        } elseif ($enrollmentFilter === 'not_enrolled' && $currentSchoolYear) {
            $query->whereDoesntHave('enrollments', function ($q) use ($currentSchoolYear) {
                $q->where('school_year_id', $currentSchoolYear->id);
            });
        } elseif ($enrollmentFilter === 'enrolled' && $currentSchoolYear) {
            $query->whereHas('enrollments', function ($q) use ($currentSchoolYear) {
                $q->where('school_year_id', $currentSchoolYear->id);
            });
        }

        // Filter by grade level (last known grade)
        $gradeFilter = $request->get('grade_level');
        if ($gradeFilter) {
            $query->whereHas('profiles', function ($q) use ($gradeFilter, $currentSchoolYear) {
                $q->where('grade_level_id', $gradeFilter);
                if ($currentSchoolYear) {
                    $q->where('school_year_id', $currentSchoolYear->id);
                }
            });
        }

        // Search
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('lrn', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->paginate(20);

        // Stats - scoped to teacher's students (enrolled + pending profiles created by teacher)
        $enrolledCount = 0;
        $pendingCount = 0;

        if ($teacherClassIds->isNotEmpty() && $currentSchoolYear) {
            $enrolledCount = Student::whereHas('enrollments', function ($q) use ($teacherClassIds, $currentSchoolYear) {
                $q->whereIn('class_id', $teacherClassIds)
                    ->where('school_year_id', $currentSchoolYear->id);
            })->count();
        }

        if ($teacher && $currentSchoolYear) {
            $pendingCount = Student::whereHas('profiles', function ($q) use ($teacher, $currentSchoolYear) {
                $q->where('status', 'pending')
                    ->where('created_by_teacher_id', $teacher->id)
                    ->where('school_year_id', $currentSchoolYear->id);
            })->whereDoesntHave('enrollments', function ($q) use ($currentSchoolYear) {
                $q->where('school_year_id', $currentSchoolYear->id);
            })->count();
        }

        $totalStudents = $enrolledCount + $pendingCount;
        $enrolledThisYear = $enrolledCount;
        $notEnrolledThisYear = $pendingCount;

        return view('teacher.students.index', [
            'students' => $students,
            'currentSchoolYear' => $currentSchoolYear,
            'gradeLevels' => $gradeLevels,
            'enrollmentFilter' => $enrollmentFilter,
            'gradeFilter' => $gradeFilter,
            'search' => $search,
            'totalStudents' => $totalStudents,
            'enrolledThisYear' => $enrolledThisYear,
            'notEnrolledThisYear' => $notEnrolledThisYear,
            'teacherEnrollmentEnabled' => $teacherEnrollmentEnabled,
        ]);
    }

    /**
     * Show the form for creating a new student profile.
     */
    public function create()
    {
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return view('teacher.students.create', [
            'currentSchoolYear' => $currentSchoolYear,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    /**
     * Store a newly created student profile.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Student fields
            'lrn' => 'nullable|string|max:12|unique:students,lrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',
            'distance_km' => 'nullable|numeric|min:0|max:100',
            'transportation' => 'nullable|string|max:50',
            'family_income' => 'nullable|string|in:Low,Medium,High',

            // Guardian fields
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'nullable|email|max:255|unique:users,email',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',

            // Initial grade level (for creating StudentProfile)
            'grade_level_id' => 'required|exists:grade_levels,id',
        ]);

        $currentSchoolYear = SchoolYear::where('is_active', true)->first();

        if (! $currentSchoolYear) {
            return redirect()->back()->with('error', 'No active school year found.');
        }

        try {
            $plainPassword = '12345678';
            $teacher = Auth::user()->teacher;

            DB::transaction(function () use ($validated, $plainPassword, $currentSchoolYear, $teacher) {
                // Create guardian user
                $guardianUser = User::create([
                    'first_name' => $validated['guardian_first_name'],
                    'last_name' => $validated['guardian_last_name'],
                    'email' => $validated['guardian_email'] ?? null,
                    'password' => Hash::make($plainPassword),
                    'role_id' => Role::GUARDIAN_ID,
                ]);

                // Create guardian record
                $guardian = Guardian::create([
                    'user_id' => $guardianUser->id,
                    'phone' => $validated['guardian_phone'] ?? null,
                    'relationship' => $validated['guardian_relationship'],
                ]);

                // Create student
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

                // Create initial student profile for current school year
                StudentProfile::create([
                    'student_id' => $student->id,
                    'school_year_id' => $currentSchoolYear->id,
                    'grade_level_id' => $validated['grade_level_id'],
                    'status' => 'pending',
                    'created_by_teacher_id' => $teacher?->id,
                ]);

                // Send welcome email to guardian only if email is provided
                if (! empty($guardianUser->email)) {
                    Mail::to($guardianUser->email)->queue(new \App\Mail\WelcomeEmail($guardianUser, $plainPassword));
                }
            });

            return redirect()->route('teacher.students.index')
                ->with('success', 'Student profile created successfully. The student is now ready to be enrolled.');
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create student profile: '.$th->getMessage());
        }
    }

    /**
     * Display the specified student profile with grade history.
     */
    public function show(Student $student)
    {
        $student->load([
            'guardian.user',
            'profiles' => function ($q) {
                $q->with(['gradeLevel', 'schoolYear'])->orderBy('school_year_id', 'desc');
            },
            'enrollments' => function ($q) {
                $q->with(['class.section.gradeLevel', 'schoolYear'])->orderBy('school_year_id', 'desc');
            },
            'grades.subject',
            'attendances',
        ]);

        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        $teacher = Auth::user()->teacher;
        $isAdviser = false;
        $sectionHistory = collect();
        $studentClass = null;

        if ($currentSchoolYear && $teacher) {
            $currentEnrollment = $student->enrollments->where('school_year_id', $currentSchoolYear->id)->first();
            if ($currentEnrollment && $currentEnrollment->class && (int) $currentEnrollment->class->teacher_id === (int) $teacher->id) {
                $isAdviser = true;
                $studentClass = $currentEnrollment->class;

                // Section History for advisers
                $allClasses = Classes::where('section_id', $studentClass->section_id)
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
            }
        }

        // Organize grades by school year
        $gradesByYear = $student->grades
            ->groupBy(function ($grade) {
                return $grade->school_year_id ?? 'unknown';
            })
            ->map(function ($grades) {
                return $grades->groupBy('subject_id');
            });

        // Get attendance summary by school year
        $attendanceByYear = $student->attendances
            ->groupBy('school_year_id')
            ->map(function ($attendances) {
                return [
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                    'late' => $attendances->where('status', 'late')->count(),
                    'total' => $attendances->count(),
                ];
            });

        // Build daily attendance trend for the current school year (last 30 days max)
        $attendanceTrend = [];
        if ($currentSchoolYear) {
            $attendanceTrend = $student->attendances
                ->where('school_year_id', $currentSchoolYear->id)
                ->whereNotNull('date')
                ->groupBy(fn ($a) => $a->date->format('Y-m-d'))
                ->sortKeys()
                ->map(function ($dayRecords, $date) {
                    return [
                        'date' => $date,
                        'present' => $dayRecords->where('status', 'present')->count(),
                        'absent' => $dayRecords->where('status', 'absent')->count(),
                        'late' => $dayRecords->where('status', 'late')->count(),
                        'total' => $dayRecords->count(),
                    ];
                })
                ->values()
                ->toArray();
        }

        return view('teacher.students.show', [
            'student' => $student,
            'currentSchoolYear' => $currentSchoolYear,
            'gradesByYear' => $gradesByYear,
            'attendanceByYear' => $attendanceByYear,
            'attendanceTrend' => $attendanceTrend,
            'isAdviser' => $isAdviser,
            'sectionHistory' => $sectionHistory,
            'studentClass' => $studentClass,
        ]);
    }

    /**
     * Display the grades of a student for a specific school year.
     */
    public function grades(Student $student, SchoolYear $sy)
    {
        // Eager load necessary relationships
        $student->load([
            'grades' => function ($query) use ($sy) {
                $query->where('school_year_id', $sy->id)->with('subject')->orderBy('subject_id')->orderBy('quarter');
            },
            'enrollments' => function ($query) use ($sy) {
                $query->where('school_year_id', $sy->id)->with('class.section.gradeLevel');
            },
        ]);

        $profile = StudentProfile::where('student_id', $student->id)
            ->where('school_year_id', $sy->id)
            ->first();

        $grades = $student->grades;
        $enrollment = $student->enrollments->first();

        // Group grades by subject for easy display
        $gradesBySubject = $grades->groupBy('subject.name');

        return view('teacher.students.grades', [
            'student' => $student,
            'schoolYear' => $sy,
            'gradesBySubject' => $gradesBySubject,
            'enrollment' => $enrollment,
            'profile' => $profile,
        ]);
    }

    /**
     * Show the form for editing the specified student profile.
     */
    public function edit(Student $student)
    {
        $student->load(['guardian.user', 'profiles.gradeLevel']);
        $gradeLevels = GradeLevel::orderBy('level')->get();

        return view('teacher.students.edit', [
            'student' => $student,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    /**
     * Update the specified student in storage.
     */
    public function update(Request $request, Student $student)
    {
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

            // Guardian fields
            'guardian_first_name' => 'nullable|string|max:255',
            'guardian_last_name' => 'nullable|string|max:255',
            'guardian_email' => 'nullable|email|max:255|unique:users,email,'.($student->guardian?->user_id ?? 'NULL'),
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_relationship' => 'nullable|in:parent,sibling,relative,guardian',
        ]);

        try {
            DB::transaction(function () use ($validated, $student) {
                // Update student
                $student->update([
                    'lrn' => $validated['lrn'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'],
                    'distance_km' => $validated['distance_km'] ?? null,
                    'transportation' => $validated['transportation'] ?? null,
                    'family_income' => $validated['family_income'] ?? null,
                ]);

                // Update guardian if present
                if ($student->guardian && $student->guardian->user) {
                    $student->guardian->user->update([
                        'first_name' => $validated['guardian_first_name'] ?? $student->guardian->user->first_name,
                        'last_name' => $validated['guardian_last_name'] ?? $student->guardian->user->last_name,
                        'email' => array_key_exists('guardian_email', $validated) ? $validated['guardian_email'] : $student->guardian->user->email,
                    ]);

                    $student->guardian->update([
                        'phone' => array_key_exists('guardian_phone', $validated) ? $validated['guardian_phone'] : $student->guardian->phone,
                        'relationship' => $validated['guardian_relationship'] ?? $student->guardian->relationship,
                    ]);
                }
            });

            return redirect()->route('teacher.students.show', $student)
                ->with('success', 'Student profile updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update student profile: '.$th->getMessage());
        }
    }
}
