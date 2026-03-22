<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolYearMonthDay extends Model
{
    use HasFactory;

    private const DEFAULT_SCHOOL_DAYS_BY_MONTH = [
        1 => 21,
        2 => 19,
        3 => 23,
        4 => 0,
        6 => 11,
        7 => 23,
        8 => 20,
        9 => 22,
        10 => 23,
        11 => 21,
        12 => 14,
    ];

    protected $fillable = [
        'school_year_id',
        'month',
        'school_days',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'school_days' => 'integer',
        ];
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public static function getMonthName(int $month): string
    {
        return date('F', mktime(0, 0, 0, $month, 1));
    }

    public static function getMonthsInRange($startDate, $endDate): array
    {
        $start = \Carbon\Carbon::parse($startDate)->startOfMonth();
        $end = \Carbon\Carbon::parse($endDate)->endOfMonth();

        $months = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $months[] = (int) $current->format('n');
            $current->addMonth();
        }

        return $months;
    }

    /**
     * @return array<int, int>
     */
    public static function defaultSchoolDaysByMonth(): array
    {
        return self::DEFAULT_SCHOOL_DAYS_BY_MONTH;
    }

    public static function defaultSchoolDaysForMonth(int $month): ?int
    {
        return self::DEFAULT_SCHOOL_DAYS_BY_MONTH[$month] ?? null;
    }

    public static function hasDefaultSchoolDaysForMonth(int $month): bool
    {
        return array_key_exists($month, self::DEFAULT_SCHOOL_DAYS_BY_MONTH);
    }
}
