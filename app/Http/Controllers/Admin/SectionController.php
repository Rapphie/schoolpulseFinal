<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    /**
     * Display a listing of sections.
     */
    public function index()
    {
        $sections = Section::withCount('students')
            ->with(['adviser', 'gradeLevel'])
            ->latest()
            ->get(); // Using get() instead of paginate since we filter by grade level in view

        return view('admin.sections.index', compact('sections'));
    }

    /**
     * Show the form for creating a new section.
     */
    public function create()
    {
        $advisers = User::where('role', 'teacher')->get();
        $subjects = Subject::all();

        return view('admin.sections.create', compact('advisers', 'subjects'));
    }

    /**
     * Store a newly created section in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sections',
            'grade_level_id' => 'required|integer|min:1|max:12',
            'description' => 'nullable|string',
            'adviser_id' => 'nullable|exists:users,id',
            'capacity' => 'nullable|integer|min:1|max:50',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
        ]);

        DB::beginTransaction();

        try {
            $section = Section::create($validated);

            // Attach subjects if any
            if (isset($validated['subjects'])) {
                $section->subjects()->attach($validated['subjects']);
            }

            DB::commit();

            return redirect()->route('admin.sections.show', $section)
                ->with('success', 'Section created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create section. ' . $e->getMessage());
        }
    }

    /**
     * Display the specified section.
     */
    public function show(Section $section)
    {
        $section->load([
            'adviser',
            'students',
            'subjects',
            'schedules' => function ($query) {
                $query->orderBy('day_of_week')->orderBy('start_time');
            }
        ]);

        $availableStudents = Student::whereDoesntHave('section')
            ->orderBy('last_name')
            ->get();

        $allSubjects = Subject::all();

        return view('admin.sections.show', compact('section', 'availableStudents', 'allSubjects'));
    }

    /**
     * Show the form for editing the specified section.
     */
    public function edit(Section $section)
    {
        $advisers = User::where('role', 'teacher')->get();
        $subjects = Subject::all();
        $section->load('subjects');

        return view('admin.sections.edit', compact('section', 'advisers', 'subjects'));
    }

    /**
     * Update the specified section in storage.
     */
    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sections,name,' . $section->id,
            'grade_level_id' => 'required|integer|min:1|max:12',
            'adviser_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1|max:50',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
        ]);

        DB::beginTransaction();

        try {
            $section->update($validated);

            // Sync subjects
            if (isset($validated['subjects'])) {
                $section->subjects()->sync($validated['subjects']);
            } else {
                $section->subjects()->detach();
            }

            DB::commit();

            return redirect()->route('admin.sections.show', $section)
                ->with('success', 'Section updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update section. ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified section from storage.
     */
    public function destroy(Section $section)
    {
        DB::beginTransaction();

        try {
            // Detach all students and subjects before deleting
            $section->students()->update(['section_id' => null]);
            $section->subjects()->detach();
            $section->schedules()->delete();

            $section->delete();

            DB::commit();

            return redirect()->route('admin.sections.index')
                ->with('success', 'Section deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete section. ' . $e->getMessage());
        }
    }

    /**
     * Add a student to the section.
     */
    public function addStudent(Request $request, Section $section)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Check if student is already in a section
        if ($student->section_id) {
            return back()->with('error', 'Student is already assigned to a section.');
        }

        // Check section capacity
        if ($section->students()->count() >= $section->capacity) {
            return back()->with('error', 'Section has reached its capacity.');
        }

        $student->section()->associate($section);
        $student->save();

        return back()->with('success', 'Student added to section successfully.');
    }

    /**
     * Remove a student from the section.
     */
    public function removeStudent(Section $section, Student $student)
    {
        if ($student->section_id !== $section->id) {
            return back()->with('error', 'Student is not in this section.');
        }

        $student->section()->dissociate();
        $student->save();

        return back()->with('success', 'Student removed from section successfully.');
    }

    /**
     * Add a subject to the section.
     */
    public function addSubject(Request $request, Section $section)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
        ]);

        // Check if subject is already assigned to the section
        if ($section->subjects()->where('subject_id', $validated['subject_id'])->exists()) {
            return back()->with('error', 'Subject is already assigned to this section.');
        }

        $section->subjects()->attach($validated['subject_id'], [
            'teacher_id' => $validated['teacher_id']
        ]);

        return back()->with('success', 'Subject added to section successfully.');
    }

    /**
     * Remove a subject from the section.
     */
    public function removeSubject(Section $section, Subject $subject)
    {
        $section->subjects()->detach($subject->id);

        // Also remove any schedules related to this subject in this section
        $section->schedules()->where('subject_id', $subject->id)->delete();

        return back()->with('success', 'Subject removed from section successfully.');
    }

    /**
     * Show the section schedule form.
     */
    public function schedule(Section $section)
    {
        $section->load([
            'schedules' => function ($query) {
                $query->orderBy('day_of_week')->orderBy('start_time');
            },
            'subjects',
            'subjects.teachers'
        ]);

        $days = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
        ];

        return view('admin.sections.schedule', compact('section', 'days'));
    }

    /**
     * Store the section schedule.
     */
    public function storeSchedule(Request $request, Section $section)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.subject_id' => 'required|exists:subjects,id',
            'schedules.*.teacher_id' => 'required|exists:users,id',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
        ]);

        DB::beginTransaction();

        try {
            // Delete existing schedules
            $section->schedules()->delete();

            // Add new schedules
            foreach ($validated['schedules'] as $scheduleData) {
                $section->schedules()->create([
                    'subject_id' => $scheduleData['subject_id'],
                    'teacher_id' => $scheduleData['teacher_id'],
                    'day_of_week' => $scheduleData['day_of_week'],
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                ]);
            }

            DB::commit();

            return redirect()->route('admin.sections.show', $section)
                ->with('success', 'Section schedule updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update schedule. ' . $e->getMessage());
        }
    }

    /**
     * Get section data for editing in modal
     */
    public function getSectionData(Section $section)
    {
        $section->load('gradeLevel');
        return response()->json($section);
    }
}
