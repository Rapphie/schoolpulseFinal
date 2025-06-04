<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Section;
use App\Models\User;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // Subjects Management

    /**
     * Display a listing of subjects.
     */
    public function index()
    {
        $subjects = Subject::all();
        return view('admin.subjects.index', compact('subjects'));
    }

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'description' => 'nullable|string',
            'units' => 'required|integer|min:1',
            'hours_per_week' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        // Handle is_active checkbox
        $validated['is_active'] = $request->has('is_active');

        Subject::create($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code,' . $subject->id,
            'description' => 'nullable|string',
            'units' => 'required|integer|min:1',
            'hours_per_week' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        // Handle is_active checkbox
        $validated['is_active'] = $request->has('is_active');

        $subject->update($validated);

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified subject from storage.
     */
    public function destroy(Subject $subject)
    {
        try {
            $subject->delete();

            return redirect()->route('admin.subjects.index')
                ->with('success', 'subject deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') { // Integrity constraint violation
                return redirect()->route('admin.subjects.index')
                    ->with('error', 'Cannot delete subject because they are referenced in other records.');
            }
            return redirect()->route('admin.subjects.index')
                ->with('error', 'An error occurred while deleting the subject.');
        } catch (\Throwable $th) {
            return redirect()->route('admin.subjects.index')
                ->with('error', 'An unexpected error occurred.');
        }
    }

    // Teachers Management

    /**
     * Display a listing of teachers.
     */
    public function teachers()
    {
        $teachers = User::whereHas('role', function ($query) {
            $query->where('id', '2');
        })->get();

        return view('admin.teachers.index', compact('teachers'));
    }

    // Sections Management

    /**
     * Display a listing of sections.
     */
    public function sections()
    {
        $sections = Section::all();
        return view('admin.sections.index', compact('sections'));
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

        Section::create($validated);

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
        return view('admin.reports.enrollees', compact('sections'));
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
        $todayPresentCount = Attendance::whereDate('date', now())
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
}
