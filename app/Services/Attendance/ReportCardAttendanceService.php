<?php

namespace App\Services\Attendance;

use App\Models\SchoolYear;
use App\Models\SchoolYearMonthDay;
use Illuminate\Support\Collection;

class ReportCardAttendanceService
{
    /**
     * @return array<int, string>
     */
    public static function reportCardMonthMapping(): array
    {
        return [
            6 => 'jun',
            7 => 'jul',
            8 => 'aug',
            9 => 'sep',
            10 => 'oct',
            11 => 'nov',
            12 => 'dec',
            1 => 'jan',
            2 => 'feb',
            3 => 'mar',
            4 => 'apr',
        ];
    }

    /**
     * @param  Collection<int, object>  $attendanceByMonth
     * @return array<string, array<string, array<string, int>>|int>
     */
    public static function summarizeAttendanceByReportCardMonth(Collection $attendanceByMonth, SchoolYear $schoolYear): array
    {
        $monthMapping = self::reportCardMonthMapping();
        $monthNumbers = array_keys($monthMapping);

        $configuredMonthDays = $schoolYear->monthDays()
            ->whereIn('month', $monthNumbers)
            ->get()
            ->keyBy('month');

        $attendanceData = [];
        $totalSchoolDays = 0;
        $totalDaysPresent = 0;
        $totalDaysAbsent = 0;

        foreach ($monthMapping as $monthNumber => $monthAbbr) {
            $schoolDays = $configuredMonthDays->has($monthNumber)
                ? (int) $configuredMonthDays->get($monthNumber)->school_days
                : (SchoolYearMonthDay::defaultSchoolDaysForMonth($monthNumber) ?? 0);

            $monthlyData = $attendanceByMonth->get($monthNumber);
            $presentDays = $monthlyData ? min((int) $monthlyData->present_days, $schoolDays) : 0;
            $absentDays = $monthlyData ? min((int) $monthlyData->absent_days, max(0, $schoolDays - $presentDays)) : 0;

            $attendanceData[$monthAbbr] = [
                'school_days' => $schoolDays,
                'present' => $presentDays,
                'absent' => $absentDays,
            ];

            $totalSchoolDays += $schoolDays;
            $totalDaysPresent += $presentDays;
            $totalDaysAbsent += $absentDays;
        }

        return [
            'attendanceData' => $attendanceData,
            'totalSchoolDays' => $totalSchoolDays,
            'totalDaysPresent' => $totalDaysPresent,
            'totalDaysAbsent' => $totalDaysAbsent,
        ];
    }
}
