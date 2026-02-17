<?php

namespace App\Models;

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

    private static function extractFirstNumber(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/\d+/', $value, $matches)) {
            return $matches[0];
        }

        return null;
    }

    private static function normalizeGradeLevelNumberOrName(?string $gradeLevelName): ?string
    {
        $gradeLevelName = trim((string) $gradeLevelName);
        if ($gradeLevelName === '') {
            return null;
        }

        return self::extractFirstNumber($gradeLevelName) ?? $gradeLevelName;
    }

    private static function ensureGradePrefix(?string $gradeLevelName): ?string
    {
        $gradeLevelName = trim((string) $gradeLevelName);
        if ($gradeLevelName === '') {
            return null;
        }

        if (! preg_match('/^grade\b/i', $gradeLevelName)) {
            return 'Grade '.$gradeLevelName;
        }

        return $gradeLevelName;
    }

    public function getAdvisoryGradeLevelsAttribute(): Collection
    {
        $this->loadMissing('classes.section.gradeLevel');

        return $this->classes
            ->map(fn ($class) => optional(optional($class->section)->gradeLevel)->name)
            ->map(fn ($name) => self::normalizeGradeLevelNumberOrName($name))
            ->filter()
            ->unique()
            ->values();
    }

    public function getAdvisoryGradeLevelSummaryAttribute(): string
    {
        $gradeLevels = $this->advisory_grade_levels;
        if ($gradeLevels->isEmpty()) {
            return '';
        }

        $preview = $gradeLevels->take(3);
        $text = $preview->implode(', ');
        if ($gradeLevels->count() > 3) {
            $text .= ' (+'.($gradeLevels->count() - 3).' more)';
        }

        return (string) $text;
    }

    public function getAdvisorySectionsAttribute(): Collection
    {
        $this->loadMissing('classes.section.gradeLevel');

        return $this->classes
            ->map(fn ($class) => optional($class->section)->name)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();
    }

    public function getAdvisorySectionSummaryAttribute(): string
    {
        $sections = $this->advisory_sections;
        if ($sections->isEmpty()) {
            return '';
        }

        $preview = $sections->take(3);
        $text = $preview->implode(', ');
        if ($sections->count() > 3) {
            $text .= ' (+'.($sections->count() - 3).' more)';
        }

        return (string) $text;
    }

    public function getAdvisorySectionsWithGradeAttribute(): Collection
    {
        $this->loadMissing('classes.section.gradeLevel');

        return $this->classes
            ->map(function ($class) {
                $gradeLevelName = optional(optional($class->section)->gradeLevel)->name;
                $sectionName = optional($class->section)->name;

                $gradeLevelName = self::ensureGradePrefix($gradeLevelName);
                $sectionName = trim((string) $sectionName);

                if ($gradeLevelName === null && $sectionName === '') {
                    return null;
                }
                if ($gradeLevelName === null) {
                    return $sectionName;
                }
                if ($sectionName === '') {
                    return $gradeLevelName;
                }

                return $gradeLevelName.' - '.$sectionName;
            })
            ->filter()
            ->unique()
            ->values();
    }
}
