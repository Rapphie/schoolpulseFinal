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
        "student_profile_id",
        "subject_id",
        "teacher_id",
        'class_id',
        "time_in",
        "status",
        "date",
        "quarter",
        "school_year_id",
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    /**
     * Scope to prefer student_profile_id when available for a given student and optional school year.
     * Usage: Attendance::profileAware($studentId, $schoolYearId?)
     */
    public function scopeProfileAware($query, int $studentId, ?int $schoolYearId = null)
    {
        if ($schoolYearId) {
            $profile = \App\Models\StudentProfile::where('student_id', $studentId)->where('school_year_id', $schoolYearId)->first();
            if ($profile) {
                return $query->where('student_profile_id', $profile->id);
            }
            return $query->where('student_id', $studentId)->where('school_year_id', $schoolYearId);
        }

        return $query->where('student_id', $studentId);
    }
}
