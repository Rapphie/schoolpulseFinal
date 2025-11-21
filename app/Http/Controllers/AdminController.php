<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Setting;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpParser\Node\Stmt\TryCatch;

class AdminController extends Controller
{

    // Teachers Management

    /**
     * Display a listing of teachers.
     */
    public function teachers()
    {
        $teacherUsers = User::whereHas('role', function ($query) {
            $query->where('id', '2');
        })->get();

        $teachers = Teacher::where('user_id', $teacherUsers);
        return view('admin.teachers.index', compact('teachers'));
    }
    /**
     * Admin: Reset a user's password and email a temporary password
     */
    public function resetUserPassword(Request $request, User $user)
    {
        $tempPassword = \Illuminate\Support\Str::random(6);
        $expiresAt = now('Asia\Singapore');
        $user->temporary_password = $tempPassword;
        $user->temporary_password_expires_at = $expiresAt;
        $user->save();
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\TemporaryPasswordMail($user, $tempPassword, $expiresAt));
        return back()->with('success', 'Temporary password sent to user email.');
    }
    public function storeTeacher(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'status' => 'required|string|in:active,on-leave,inactive',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => bcrypt('password'), // Default password, user should change it
            'role_id' => 2, // Teacher role
        ]);

        $teacher = new Teacher($validated);
        $teacher->user_id = $user->id;

        if ($request->hasFile('profile_picture')) {
            $teacher->profile_picture = $request->file('profile_picture')->store('profile_pictures', 'public');
        }

        $teacher->save();

        return redirect()->route('admin.teachers.edit', $teacher->id)
            ->with('success', 'Teacher created successfully. You can now assign subjects.');
    }

    public function editTeacher(Teacher $teacher)
    {
        $subjects = Subject::all();
        $sections = Section::all();
        $assignedSubjects = DB::table('subject_teacher_section')
            ->where('subject_teacher_section.teacher_id', $teacher->id)
            ->join('subjects', 'subject_teacher_section.subject_id', '=', 'subjects.id')
            ->leftJoin('sections', 'subject_teacher_section.section_id', '=', 'sections.id')
            ->select('subjects.id as subject_id', 'subjects.name as subject_name', 'sections.id as section_id', 'sections.name as section_name')
            ->get();

        return view('admin.teachers.edit', compact('teacher', 'subjects', 'sections', 'assignedSubjects'));
    }

    public function updateTeacher(Request $request, Teacher $teacher)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->user_id,
            'phone' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'status' => 'required|string|in:active,on-leave,inactive',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $teacher->user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        $teacher->update($validated);

        if ($request->hasFile('profile_picture')) {
            $teacher->profile_picture = $request->file('profile_picture')->store('profile_pictures', 'public');
            $teacher->save();
        }

        return redirect()->route('admin.teachers.edit', $teacher->id)
            ->with('success', 'Teacher updated successfully.');
    }

    public function destroyTeacher(Teacher $teacher)
    {
        $teacher->user->delete();
        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }

    public function assignSubject(Request $request, Teacher $teacher)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);

        try {
            // Attach the subject and section to the teacher
            $teacher->subjects()->attach($request->subject_id, ['section_id' => $request->section_id]);
            return back()->with('success', 'Subject assigned successfully.');
        } catch (\Throwable $th) {
            return back()->with('error', 'Failed to add Subject');
        }
    }

    public function unassignSubject(Request $request, Teacher $teacher)
    {
        DB::table('classes')
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $request->input('subject_id'))
            ->where('section_id', $request->input('section_id'))
            ->delete();

        return back()->with('success', 'Subject unassigned successfully.');
    }

    // Sections Management

    /**
     * Display a listing of sections.
     */
    public function sections()
    {
        $sections = Section::all();
        $teachers = Teacher::all();
        return view('admin.sections.index', compact('sections', 'teachers'));
    }

    /**
     * Store a newly created section in storage.
     */
    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $insertSection = Section::create($validated);
        } catch (\Throwable $e) {
            Log::error('Section creation failed: ' . $e->getMessage());
            return redirect()->route('admin.sections.index')
                ->with('error', 'Failed to create Section: ' . $e->getMessage());
        }

        if (!$insertSection) {
            return redirect()->route('admin.sections.index')
                ->with('error', 'Failed to create Section.');
        }

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section created successfully.');
    }

    /**
     * Update the specified section in storage.
     */
    public function updateSection(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $section->update($validated);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section updated successfully.');
    }

    /**
     * Remove the specified section from storage.
     */
    public function destroySection(Section $section)
    {
        $section->delete();

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section deleted successfully.');
    }

    // Reports

    /**
     * Display enrollees report.
     */
    public function enrolleesReport()
    {
        $sections = Section::withCount('students')->get();

        $enrollmentTrends = Student::select(
            DB::raw('YEAR(enrollment_date) as enrollment_year'),
            'status',
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('enrollment_year', 'status')
            ->orderBy('enrollment_year', 'asc')
            ->orderBy('status', 'asc')
            ->get();

        return view('admin.reports.enrollees', compact('sections', 'enrollmentTrends'));
    }

    /**
     * Display attendance report.
     */
    public function attendanceReport()
    {
        // Get attendance records with pagination (10 records per page)
        $attendanceRecords = Attendance::with(['subject', 'student.section'])
            ->paginate(10);

        // Setup additional data for charts and stats
        $todayPresentCount = Attendance::whereDate('created_at', now()->toDateString())
            ->where('status', 'present')
            ->count();

        $totalAbsences = Attendance::where('status', 'absent')->count();
        $lateArrivalsCount = Attendance::where('status', 'late')->count();
        $presentCount = Attendance::where('status', 'present')->count();
        $absentCount = Attendance::where('status', 'absent')->count();
        $lateCount = Attendance::where('status', 'late')->count();

        // Calculate monthly attendance rate
        $totalAttendance = max(1, $presentCount + $absentCount + $lateCount);
        $monthlyAttendanceRate = number_format(($presentCount / $totalAttendance) * 100, 2);

        // Get monthly data for the chart
        $monthlyData = [];

        return view('admin.reports.attendance', compact(
            'attendanceRecords',
            'todayPresentCount',
            'monthlyAttendanceRate',
            'totalAbsences',
            'lateArrivalsCount',
            'presentCount',
            'absentCount',
            'lateCount',
            'monthlyData'
        ));
    }

    /**
     * Display grades report.
     */
    public function gradesReport()
    {
        $grades = Grade::with(['student', 'subject'])
            ->select('subject_id', DB::raw('avg(grade) as average_grade'))
            ->groupBy('subject_id')
            ->get();

        return view('admin.reports.grades', compact('grades'));
    }

    /**
     * Display least learned competencies report.
     */
    public function leastLearnedReport()
    {
        $subjects = Subject::with(['llcItems' => function ($query) {
            $query->orderBy('score', 'asc')->take(5);
        }])->get();

        return view('admin.least-learned', compact('subjects'));
    }

    /**
     * Display cumulative report.
     */
    public function cumulativeReport()
    {
        $cumulativeData = [
            'total_students' => Student::count(),
            'total_teachers' => User::whereHas('role', function ($q) {
                $q->where('name', 'teacher');
            })->count(),
            'total_subjects' => Subject::count(),
            'average_grade' => number_format(Grade::avg('grade') ?? 0, 2),
            'attendance_rate' => number_format((Attendance::where('status', 'present')->count() / max(1, Attendance::count())) * 100, 2)
        ];

        return view('admin.reports.cumulative', compact('cumulativeData'));
    }

    public function settings()
    {
        return view('profile');
    }
    public function test()
    {
        $users = User::all();
        $subjects = Subject::all();
        $subject = $subjects->first();

        // Create a fake attendance object for testing
        $attendance = (object)[
            'date' => now(),
        ];

        return view('welcome', compact('users', 'subjects', 'subject', 'attendance'));
    }
}
