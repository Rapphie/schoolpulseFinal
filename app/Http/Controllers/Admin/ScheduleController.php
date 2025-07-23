<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schedules = Schedule::with(['section', 'subject', 'teacher'])->get();
        $events = [];
        $subjectColors = [];
        $colorPalette = ['#4C51BF', '#6B46C1', '#9F7AEA', '#ED64A6', '#F56565', '#ED8936', '#ECC94B', '#48BB78', '#38B2AC', '#4299E1'];
        $colorIndex = 0;

        foreach ($schedules as $schedule) {
            if (!isset($subjectColors[$schedule->subject_id])) {
                $subjectColors[$schedule->subject_id] = $colorPalette[$colorIndex % count($colorPalette)];
                $colorIndex++;
            }

            $daysOfWeek = is_array($schedule->day_of_week) ? $schedule->day_of_week : [$schedule->day_of_week];
            $days = array_map([$this, 'dayToNumber'], $daysOfWeek);
            $teacherName = $schedule->teacher && $schedule->teacher->user ? $schedule->teacher->user->name : 'No Teacher';

            $events[] = [
                'title' => $schedule->subject->name . ' - ' . $teacherName,
                'startTime' => $schedule->start_time,
                'endTime' => $schedule->end_time,
                'daysOfWeek' => $days,
                'url' => route('admin.schedules.show', $schedule),
                'extendedProps' => [
                    'section' => $schedule->section->name,
                    'subject' => $schedule->subject->name,
                    'teacher' => $teacherName,
                    'room' => $schedule->room,
                ],
                'backgroundColor' => $subjectColors[$schedule->subject_id],
                'borderColor' => $subjectColors[$schedule->subject_id],
            ];
        }
        return view('admin.schedules.index', ['events' => json_encode($events)]);
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
        $sections = Section::all();
        $subjects = Subject::all();
        $teachers = Teacher::all();
        $gradeLevels = GradeLevel::all();
        return view('admin.schedules.create', compact('sections', 'subjects', 'teachers', 'gradeLevels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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

        Schedule::create($request->all());

        return redirect()->route('admin.sections.manage', $request->section_id)->with('success', 'Schedule created successfully.');
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
        $sectionId = $schedule->section_id;
        $schedule->delete();

        return redirect()->route('admin.sections.manage', $sectionId)->with('success', 'Assigned subject schedule deleted successfully.');
    }
}
