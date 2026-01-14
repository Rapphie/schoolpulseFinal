<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    protected $fillable = [
        'student_id',
        'school_year_id',
        'grade_level_id',
        'final_average',
        'status',
        'remarks',
        'created_by_teacher_id',
    ];

    /**
     * The teacher who created this profile.
     */
    public function createdByTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'created_by_teacher_id');
    }

    protected $casts = [
        'final_average' => 'decimal:2',
    ];

    /**
     * The student this profile belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The school year this profile is for.
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * The grade level for this academic year.
     */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * Enrollments linked to this profile.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the formatted grade level label.
     */
    public function getGradeLabelAttribute(): string
    {
        return $this->gradeLevel?->name ?? 'Unknown Grade';
    }

    /**
     * Check if this profile is for the active school year.
     */
    public function getIsCurrentAttribute(): bool
    {
        $activeYear = SchoolYear::where('is_active', true)->first();
        return $activeYear && $this->school_year_id === $activeYear->id;
    }

    /**
     * Find or create a profile for a student in a given school year.
     */
    public static function findOrCreateForStudent(int $studentId, int $schoolYearId, int $gradeLevelId): self
    {
        return static::firstOrCreate(
            [
                'student_id' => $studentId,
                'school_year_id' => $schoolYearId,
            ],
            [
                'grade_level_id' => $gradeLevelId,
                'status' => 'active',
            ]
        );
    }
}
