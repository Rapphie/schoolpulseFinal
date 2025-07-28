<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Section;
use Carbon\Carbon;

class EnrollmentController extends Controller
{
    public function index()
    {
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();

        if (!$currentSchoolYear) {
            return view('teacher.enrollment.index', ['error' => 'No active school year found.']);
        }

        $classes = Classes::where('school_year_id', $currentSchoolYear->id)
            ->with('section.gradeLevel', 'enrollments')
            ->get()
            ->sortBy('section.gradeLevel.level');

        $previousSchoolYear = SchoolYear::where('is_active', false)->orderBy('end_date', 'desc')->first();

        // Initialize $studentsToEnroll as an empty collection to prevent the error
        $studentsToEnroll = collect();

        if ($previousSchoolYear) {
            $studentsToEnroll = Student::whereDoesntHave('enrollments', function ($query) use ($currentSchoolYear) {
                $query->where('school_year_id', $currentSchoolYear->id);
            })->whereHas('enrollments', function ($query) use ($previousSchoolYear) {
                $query->where('school_year_id', $previousSchoolYear->id);
            })->get();
        }

        return view('teacher.enrollment.index', [
            'classes' => $classes,
            'students' => $studentsToEnroll, // The variable name has been changed to match the view.
        ]);
    }
    public function create()
    {
        $previousSchoolYear = SchoolYear::where('is_active', false)->orderBy('end_date', 'desc')->first();
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();

        if (!$previousSchoolYear || !$currentSchoolYear) {
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

    public function store(Request $request, Classes $class)
    {

        $class = $class->find($request->class_id);
        // 1. Validate all the fields from the "Enroll New Student" modal
        $validated = $request->validate([
            // Student fields
            'lrn' => 'nullable|string|max:12|unique:students,lrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birthdate' => 'required|date',
            'address' => 'nullable|string',

            // Guardian fields
            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'required|email|max:255|unique:users,email',
            'guardian_phone' => 'required|string|max:20',
            'guardian_relationship' => 'required|in:parent,sibling,relative,guardian',
        ]);

        try {

            DB::transaction(function () use ($validated, $class) {
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
                    'lrn' => $validated['lrn'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'],
                    'guardian_id' => $guardian->id,
                ]);

                // 6. Create the final Enrollment Record, linking the Student to the Class
                Enrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                    'status' => 'enrolled',
                ]);
            });

            return redirect()->back()->with('success', 'Student enrolled successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Error student enrolment failed.' . $th->getMessage());
        }
    }

    public function storePastStudent(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
        ]);

        $student = Student::find($request->student_id);
        $class = Classes::find($request->class_id);

        // Check for an active school year
        $currentSchoolYear = SchoolYear::where('is_active', true)->first();
        if (!$currentSchoolYear) {
            return redirect()->route('teacher.enrollment.index')->with('error', 'No active school year found.');
        }

        // Check if the student is already enrolled in the current school year
        $isAlreadyEnrolled = $student->enrollments()->where('school_year_id', $currentSchoolYear->id)->exists();
        if ($isAlreadyEnrolled) {
            return redirect()->route('teacher.enrollment.index')->with('error', 'Student is already enrolled for the current school year.');
        }

        // Create the new enrollment record
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'school_year_id' => $currentSchoolYear->id,
            'enrollment_date' => now(),
        ]);

        return redirect()->route('teacher.enrollment.index')->with('success', 'Student enrolled successfully!');
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
                    'lrn' => $validated['lrn'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'gender' => $validated['gender'],
                    'birthdate' => $validated['birthdate'],
                    'address' => $validated['address'],
                    'guardian_id' => $guardian->id,
                ]);

                // 6. Create the final Enrollment Record, linking the Student to the Class
                Enrollment::create([
                    'student_id' => $student->id,
                    'class_id' => $class->id,
                    'school_year_id' => $class->school_year_id,
                    'status' => 'enrolled',
                ]);
            });

            return redirect()->back()->with('success', 'Student enrolled successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Error student enrolment failed.' . $th->getMessage());
        }
    }
    public function enrollment()
    {

        return view('teacher.enrollment.index');
    }
}
