<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolYearMonthDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_year_id',
        'month',
        'school_days',
    ];

    protected $casts = [
        'month' => 'integer',
        'school_days' => 'integer',
    ];

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
}
