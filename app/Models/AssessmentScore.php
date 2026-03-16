<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'student_profile_id',
        'student_id',
        'score',
        'remarks',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

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
     * Usage: AssessmentScore::profileAware($studentId, $schoolYearId?)
     */
    public function scopeProfileAware($query, int $studentId, ?int $schoolYearId = null)
    {
        if ($schoolYearId) {
            $profile = \App\Models\StudentProfile::where('student_id', $studentId)->where('school_year_id', $schoolYearId)->first();
            if ($profile) {
                return $query->where('student_profile_id', $profile->id);
            }

            return $query->where('student_id', $studentId);
        }

        return $query->where('student_id', $studentId);
    }
}
