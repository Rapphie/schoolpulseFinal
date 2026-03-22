<?php

namespace App\Models;

use App\Services\TeacherProfileService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teachers';

    protected $fillable = [
        'user_id',
        'phone',
        'gender',
        'date_of_birth',
        'address',
        'qualification',
        'status',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function llcs(): HasMany
    {
        return $this->hasMany(LLC::class);
    }

    public function advisoryClasses(): HasMany
    {
        return $this->hasMany(Classes::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjects(): HasManyThrough
    {
        return $this->hasManyThrough(Subject::class, Schedule::class, 'teacher_id', 'id', 'id', 'subject_id');
    }

    public function getAdvisoryGradeLevelsAttribute(): Collection
    {
        $this->loadMissing('classes.section.gradeLevel');

        return TeacherProfileService::getAdvisoryGradeLevelsFromClasses($this->classes);
    }

    public function getAdvisoryGradeLevelSummaryAttribute(): string
    {
        return TeacherProfileService::formatGradeLevelSummary($this->advisory_grade_levels);
    }

    public function getAdvisorySectionsAttribute(): Collection
    {
        $this->loadMissing('classes.section.gradeLevel');

        return TeacherProfileService::getAdvisorySectionsFromClasses($this->classes);
    }

    public function getAdvisorySectionSummaryAttribute(): string
    {
        return TeacherProfileService::formatSectionSummary($this->advisory_sections);
    }

    public function getAdvisorySectionsWithGradeAttribute(): Collection
    {
        $this->loadMissing('classes.section.gradeLevel');

        return TeacherProfileService::getAdvisorySectionsWithGradeFromClasses($this->classes);
    }
}
