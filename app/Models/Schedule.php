<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'day_of_week' => 'array',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get day names with proper capitalization.
     *
     * @return array<int, string>
     */
    public function getDayNamesAttribute(): array
    {
        $days = $this->day_of_week;

        if (! is_array($days)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn ($day) => ucfirst(trim((string) $day)), $days)));
    }

    public function getDayNamesLabelAttribute(): string
    {
        $names = $this->day_names;

        return $names ? implode(', ', $names) : 'Not Set';
    }
}
