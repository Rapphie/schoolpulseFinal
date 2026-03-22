<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Collection;

class ScheduleConflictService
{
    /**
     * Check for schedule conflicts within a class on specific days and times.
     *
     * @param  Collection<int, Schedule>  $schedules
     * @param  array<int, string>  $days
     * @return Schedule|null The conflicting schedule or null if no conflict
     */
    public function findClassScheduleConflict(
        Collection $schedules,
        array $days,
        string $startTime,
        string $endTime,
        ?int $excludeScheduleId = null
    ): ?Schedule {
        if (empty($days)) {
            return null;
        }

        $query = $schedules->toQuery();
        $this->applyDayFilter($query, $days);
        $this->applyTimeOverlapFilter($query, $startTime, $endTime);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->with('subject', 'teacher.user')->first();
    }

    /**
     * Check for teacher schedule conflicts on specific days and times.
     *
     * @param  array<int, string>  $days
     * @return Schedule|null The conflicting schedule or null if no conflict
     */
    public function findTeacherScheduleConflict(
        int $teacherId,
        array $days,
        string $startTime,
        string $endTime,
        ?int $excludeScheduleId = null
    ): ?Schedule {
        if (empty($days)) {
            return null;
        }

        $query = Schedule::where('teacher_id', $teacherId);
        $this->applyDayFilter($query, $days);
        $this->applyTimeOverlapFilter($query, $startTime, $endTime);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->with('class.section', 'subject')->first();
    }

    /**
     * Format day_of_week for display.
     */
    private function formatConflictDays(array|string|null $days): string
    {
        if (is_array($days)) {
            return implode(',', $days);
        }

        return (string) $days;
    }

    /**
     * Build a human-readable conflict message for a class schedule conflict.
     */
    public function buildClassConflictMessage(Schedule $conflict): string
    {
        return sprintf(
            'Schedule conflicts with existing class schedule: %s (%s) %s - %s',
            optional($conflict->subject)->name ?? 'Subject',
            $this->formatConflictDays($conflict->day_of_week),
            optional($conflict->start_time)?->format('g:i A') ?? $conflict->start_time,
            optional($conflict->end_time)?->format('g:i A') ?? $conflict->end_time
        );
    }

    /**
     * Build a human-readable conflict message for a teacher schedule conflict.
     */
    public function buildTeacherConflictMessage(Schedule $conflict): string
    {
        return sprintf(
            'Assigned teacher has a conflicting schedule: %s (%s) %s - %s (Class: %s)',
            optional($conflict->subject)->name ?? 'Subject',
            $this->formatConflictDays($conflict->day_of_week),
            optional($conflict->start_time)?->format('g:i A') ?? $conflict->start_time,
            optional($conflict->end_time)?->format('g:i A') ?? $conflict->end_time,
            optional($conflict->class->section)->name ?? 'Class'
        );
    }

    /**
     * Apply day-of-week filter using JSON containment.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int, string>  $days
     */
    private function applyDayFilter($query, array $days): void
    {
        if (empty($days)) {
            return;
        }

        $first = array_shift($days);
        $query->where(function ($q) use ($first, $days) {
            $q->whereJsonContains('day_of_week', $first);
            foreach ($days as $day) {
                $q->orWhereJsonContains('day_of_week', $day);
            }
        });
    }

    /**
     * Apply time overlap filter.
     * A schedule overlaps if its start time is before the given end time
     * AND its end time is after the given start time.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyTimeOverlapFilter($query, string $startTime, string $endTime): void
    {
        $query->where(function ($q) use ($startTime, $endTime) {
            $q->whereTime('start_time', '<', $endTime)
                ->whereTime('end_time', '>', $startTime);
        });
    }
}
