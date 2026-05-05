<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeacherScheduleController extends Controller
{
    /**
     * Display teacher's schedule calendar.
     */
    public function index()
    {
        try {
            $userId = Auth::id();
            $teacherId = Teacher::where('user_id', $userId)->value('id');
            $activeSchoolYear = SchoolYear::active()->first();

            $schedules = Schedule::query()
                ->with(['subject', 'class.section'])
                ->where('teacher_id', $teacherId)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->whereTime('start_time', '!=', '00:00:00')
                ->whereTime('end_time', '!=', '00:00:00')
                ->when($activeSchoolYear, function ($query) use ($activeSchoolYear) {
                    $query->whereHas('class', function ($classQuery) use ($activeSchoolYear) {
                        $classQuery->where('school_year_id', $activeSchoolYear->id);
                    });
                })
                ->get();

            $events = [];
            $subjectColors = [];
            $colorPalette = ['#4C51BF', '#6B46C1', '#9F7AEA', '#ED64A6', '#F56565', '#ED8936', '#ECC94B', '#48BB78', '#38B2AC', '#4299E1'];
            $colorIndex = 0;

            foreach ($schedules as $schedule) {
                if (! isset($subjectColors[$schedule->subject_id])) {
                    $subjectColors[$schedule->subject_id] = $colorPalette[$colorIndex % count($colorPalette)];
                    $colorIndex++;
                }

                $daysOfWeekRaw = $schedule->day_of_week;

                if (is_string($daysOfWeekRaw)) {
                    $decoded = json_decode($daysOfWeekRaw, true);
                    $daysOfWeek = is_array($decoded) ? $decoded : [$daysOfWeekRaw];
                } else {
                    $daysOfWeek = is_array($daysOfWeekRaw) ? $daysOfWeekRaw : [$daysOfWeekRaw];
                }
                $days = array_map([$this, 'dayToNumber'], $daysOfWeek);

                $events[] = [
                    'title' => $schedule->subject->name,
                    'startTime' => $schedule->start_time->format('H:i:s'),
                    'endTime' => $schedule->end_time->format('H:i:s'),
                    'daysOfWeek' => $days,
                    'url' => route('teacher.classes.view', ['class' => $schedule->class_id]),
                    'allDay' => false,
                    'extendedProps' => [
                        'section' => $schedule->class->section->name,
                        'subject' => $schedule->subject->name,
                        'room' => $schedule->room,
                    ],
                    'backgroundColor' => $subjectColors[$schedule->subject_id],
                    'borderColor' => $subjectColors[$schedule->subject_id],
                ];
            }

            return view('teacher.schedules.index', ['events' => json_encode($events)]);
        } catch (Throwable $e) {
            Log::error('TeacherScheduleController@index error: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Unable to load schedules: '.$e->getMessage());
        }
    }

    /**
     * Convert day name to number for FullCalendar.
     */
    private function dayToNumber($day): int
    {
        return match (strtolower($day)) {
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            default => 0,
        };
    }
}
