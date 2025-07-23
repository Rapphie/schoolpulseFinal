<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $sections = Section::with('gradeLevel')->get();
        $guardians = Guardian::all();

        $currentYear = Carbon::now()->year;
        // Assuming the school year starts in June.
        // This might need to be a setting in the future.
        $schoolYearStart = Carbon::create($currentYear, 6, 1);

        $query = Student::query();

        // Get students who are not enrolled in the current school year
        $query->where('status', '!=', 'active')
            ->where(function ($q) use ($schoolYearStart) {
                $q->where('enrollment_date', '<', $schoolYearStart)
                    ->orWhereNull('enrollment_date');
            });

        // if ($request->has('search') && $request->input('search') != '') {
        //     $searchTerm = $request->input('search');
        //     $query->where(function ($q) use ($searchTerm) {
        //         $q->where('first_name', 'like', "%{$searchTerm}%")
        //             ->orWhere('last_name', 'like', "%{$searchTerm}%");
        //     });
        // }

        $oldStudents = $query->paginate(10);

        return view('teacher.enrollment.index', compact('sections', 'oldStudents', 'guardians'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'student_id' => 'required|string|unique:students,student_id',
            'section_id' => 'required|exists:sections,id',
            'birthdate' => 'required|date',
            'gender' => 'required|in:male,female',
            'guardian_id' => 'required|exists:guardians,id',
            'teacher_id' => 'required|exists:teachers,id',
        ]);

        Student::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'student_id' => $request->student_id,
            'section_id' => $request->section_id,
            'birthdate' => $request->birthdate,
            'gender' => $request->gender,
            'guardian_id' => $request->guardian_id,
            'status' => 'enrolled',
            'enrollment_date' => now(),
        ]);

        return redirect()->route('teacher.enrollment.index')->with('success', 'Student enrolled successfully.');
    }
    public function enrollment()
    {

        return view('teacher.enrollment.index');
    }
}
