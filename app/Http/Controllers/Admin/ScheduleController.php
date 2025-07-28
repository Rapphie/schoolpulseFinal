<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Classes;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $activeSchoolYear = SchoolYear::active()->first();
        $events = [];

        if ($activeSchoolYear) {
            $schedules = Schedule::whereHas('class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            })->with(['class.section.gradeLevel', 'subject', 'teacher.user'])->get();

            foreach ($schedules as $schedule) {
                $daysOfWeek = $schedule->day_of_week;
                if (!is_array($daysOfWeek)) continue;

                $dayNumbers = array_map(fn($day) => $this->dayToNumber($day), $daysOfWeek);

                $subjectName = $schedule->subject?->name ?? 'No Subject';
                $teacherName = $schedule->teacher?->user ? $schedule->teacher->user->first_name . ' ' . $schedule->teacher->user->last_name : 'No Teacher';
                $sectionName = $schedule->class?->section?->name ?? 'No Section';

                $events[] = [
                    'title' => $subjectName,
                    'startTime' => $schedule->start_time->format('H:i:s'),
                    'endTime' => $schedule->end_time->format('H:i:s'),
                    'daysOfWeek' => $dayNumbers,
                    'allDay' => false,
                    'extendedProps' => [
                        'section' => $sectionName,
                        'subject' => $subjectName,
                        'teacher' => $teacherName,
                        'room' => $schedule->room,
                    ],
                ];
            }
        }

        return view('admin.schedules.index', [
            'events' => json_encode($events),
            'activeSchoolYear' => $activeSchoolYear
        ]);
    }

    private function dayToNumber($day)
    {
        switch (strtolower($day)) {
            case 'monday':
                return 1;
            case 'tuesday':
                return 2;
            case 'wednesday':
                return 3;
            case 'thursday':
                return 4;
            case 'friday':
                return 5;
            default:
                return 0; // Should not happen
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $activeSchoolYear = SchoolYear::active()->first();
        $classes = collect();
        if ($activeSchoolYear) {
            $classes = Classes::where('school_year_id', $activeSchoolYear->id)->with('section.gradeLevel')->get();
        }

        $teachers = Teacher::with('user')->get();
        $subjects = Subject::with('gradeLevel')->get();
        $gradeLevels = GradeLevel::all();

        return view('admin.schedules.create', compact('classes', 'teachers', 'subjects', 'gradeLevels'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day_of_week' => 'required|array|min:1',
            'day_of_week.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
        ]);

        // The 'class_id' is now correctly included in the validated data from the form.
        Schedule::create($validated);

        return redirect()->route('admin.schedules.index')->with('success', 'Schedule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        return view('admin.schedules.show', compact('schedule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        $sections = Section::all();
        $subjects = Subject::all();
        $teachers = Teacher::all();
        $gradeLevels = GradeLevel::all();
        return view('admin.schedules.edit', compact('schedule', 'sections', 'subjects', 'teachers', 'gradeLevels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day_of_week' => 'required|array',
            'start_time' => 'required',
            'end_time' => 'required',
            'room' => 'nullable|string',
        ]);

        $schedule->update($request->all());

        return redirect()->route('admin.schedules.index')->with('success', 'Schedule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Schedule $schedule)
    {
        $classId = $schedule->class_id;
        $schedule->delete();

        return redirect()->route('admin.sections.manage', $classId)->with('success', 'Assigned subject schedule deleted successfully.');
    }
}
