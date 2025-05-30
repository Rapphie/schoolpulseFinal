<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;
    protected $table = "attendances";

    protected $fillable = [
        "student_id",
        "subject_id",
        "status",
        "date",
        "quarter",
        "school_year",
        "time_in",
        "time_out",
        "remarks",
        "teacher_id",
    ];

    protected $casts = [
        'date' => 'date',
        'quarter' => 'integer',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
