<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->teacher();
        $teacherId = Teacher::where('user_id', $userId)->value('id');

        $schedules = Schedule::where('teacher_id', $teacherId)
            ->with(['section', 'subject', 'teacher'])
            ->get();

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
                'title' => $schedule->subject->name . ' - ' . $schedule->section->name,
                'startTime' => $schedule->start_time,
                'endTime' => $schedule->end_time,
                'daysOfWeek' => $days,
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
        return view('teacher.schedule.index', ['events' => json_encode($events)]);
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
}
