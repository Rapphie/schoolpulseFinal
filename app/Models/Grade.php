<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    /** @use HasFactory<\Database\Factories\GradeFactory> */
    use HasFactory;

    protected $table = 'grades';

    protected $fillable = [
        'student_id',
        'student_profile_id',
        'subject_id',
        'teacher_id',
        'school_year_id',
        'grade',
        'quarter',
    ];

    // After adding a surrogate primary key `id` via migration
    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $casts = [
        'grade' => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    /**
     * Scope to prefer student_profile_id when available for a given student and optional school year.
     * Usage: Grade::profileAware($studentId, $schoolYearId?)
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
