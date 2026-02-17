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

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'day_of_week' => 'array',
    ];

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

    public function getDayNamesAttribute(): array
    {
        $days = $this->day_of_week;

        if (is_string($days) && $days !== '') {
            $decoded = json_decode($days, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $days = $decoded;
            } else {
                $days = explode(',', $days);
            }
        }

        if ($days instanceof \Illuminate\Support\Collection) {
            $days = $days->all();
        }

        if (! is_array($days)) {
            $days = [];
        }

        $days = array_map(static function ($day) {
            $normalized = ucfirst(trim((string) $day));

            return $normalized !== '' ? $normalized : null;
        }, $days);

        return array_values(array_filter($days));
    }

    public function getDayNamesLabelAttribute(): string
    {
        $names = $this->day_names;

        return $names ? implode(', ', $names) : 'Not Set';
    }
}
