<?php

namespace App\Helpers;

class ScheduleHelper
{
    public static function dayToNumber(string $day): int
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
