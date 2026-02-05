<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\SchoolYear;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    /**
     * Display a listing of the classes for the active school year.
     */
    public function index()
    {
        $activeSchoolYear = SchoolYear::getActive();
        if (!$activeSchoolYear) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'No school year found. Please create one first.');
        }

        // Fetch classes for the active year and eager load relationships for efficiency
        $classes = Classes::where('school_year_id', $activeSchoolYear->id)
            ->with(['section.gradeLevel', 'teacher.user', 'enrollments'])
            ->get()
            ->sortBy('section.gradeLevel.level'); // Sort by grade level

        $teachers = Teacher::with('user')->get(); // For the 'Add Section' modal

        return view('admin.sections.index', compact('classes', 'teachers'));
    }

    /**
     * Store a newly created section and its corresponding class for the active year.
     */
    public function store(Request $request)
    {
        $activeSchoolYear = SchoolYear::getActive();

        if (!$activeSchoolYear) {
            abort(404, 'No school year found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level_id' => 'required|integer|exists:grade_levels,id',
            'teacher_id' => 'nullable|exists:teachers,id', // This is the Adviser
            'capacity' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $section = Section::firstOrCreate(
                [
                    'name' => $validated['name'],
                    'grade_level_id' => $validated['grade_level_id']
                ],
                [
                    'description' => $validated['description']
                ]
            );

            // Create the specific Class record for this section and the active school year
            Classes::create([
                'section_id' => $section->id, // Now using the ID from the found or created section
                'school_year_id' => $activeSchoolYear->id,
                'teacher_id' => $validated['teacher_id'],
                'capacity' => $validated['capacity'],
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Section created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Check for unique constraint violation on the classes table
            if (str_contains($e->getMessage(), 'classes_section_id_school_year_id_unique')) {
                return back()->withInput()->with('error', 'This section already has a class for the active school year.');
            }
            return back()->withInput()->with('error', 'Failed to create class. ' . $e->getMessage());
        }
    }

    /**
     * Display the manage page for a specific section.
     */
    public function manage(Section $section)
    {
        $activeSchoolYear = SchoolYear::active()->firstOrFail();

        // Find the specific class for this section and active year
        $class = Classes::where('section_id', $section->id)
            ->where('school_year_id', $activeSchoolYear->id)
            ->with([
                'teacher.user',
                'schoolYear',
                'enrollments.student.guardian.user',
                'schedules.subject', // Eager load schedules
                'schedules.teacher.user' // Eager load schedule's teacher
            ])
            ->first();

        if (!$class) {
            return redirect()->route('admin.sections.index')
                ->with('error', "No active class found for section '{$section->name}' for the current school year.");
        }

        $subjects = Subject::where('grade_level_id', $section->grade_level_id)->get();
        $teachers = Teacher::with('user')->get();

        // Section/Class History: get all classes for this section, sorted by school year (desc)
        $allClasses = \App\Models\Classes::where('section_id', $section->id)
            ->with(['schoolYear', 'teacher.user', 'schedules'])
            ->orderByDesc('school_year_id')
            ->get();

        $sectionHistory = $allClasses->map(function ($c) {
            $adviser = $c->teacher && $c->teacher->user ? ($c->teacher->user->first_name . ' ' . $c->teacher->user->last_name) : 'N/A';
            $rooms = $c->schedules->pluck('room')->filter()->unique()->implode(', ');
            return [
                'school_year' => $c->schoolYear ? $c->schoolYear->name : 'N/A',
                'adviser' => $adviser,
                'capacity' => $c->capacity,
                'rooms' => $rooms ?: '—',
            ];
        });

        // For debugging: always add a fake row to confirm DataTables is working
        $sectionHistory->push([
            'school_year' => 'Test Year',
            'adviser' => 'Test Adviser',
            'capacity' => 99,
            'rooms' => 'Test Room',
        ]);

        return view('admin.sections.manage', compact('section', 'class', 'subjects', 'teachers', 'sectionHistory'));
    }

    public function assignAdviser(Request $request, Classes $class)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id'
        ]);

        $class->update(['teacher_id' => $validated['teacher_id']]);

        return redirect()->back()->with('success', 'Adviser assigned successfully.');
    }

    /**
     * Store a new schedule entry for a class.
     */
    public function storeSchedule(Request $request, Classes $class)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day_of_week' => 'required|array|min:1',
            'day_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
        ]);

        // Convert the array of days to a JSON string before storing
        $validated['day_of_week'] = json_encode($validated['day_of_week']);

        $schedule = new Schedule($validated);
        $schedule->class_id = $class->id; // Explicitly assign the class ID
        $schedule->save();

        return redirect()->back()->with('success', 'Schedule entry added successfully.');
    }

    public function destroy(Request $request, Classes $class)
    {
        $class->delete();
        return redirect()->back()->with('Success', 'Successfully deleted class section');
    }
}
