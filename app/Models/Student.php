<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lrn',
        'first_name',
        'last_name',
        'birthdate',
        'gender',
        'guardian_id',
        'enrollment_date',
        'address',
        'distance_km',
        'transportation',
        'family_income',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'enrollment_date' => 'date',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function currentProfile()
    {
        return $this->profiles()
            ->whereHas('schoolYear', fn ($q) => $q->where('is_active', true))
            ->first();
    }

    /**
     * Memoization cache for profileFor() to avoid repeated queries in loops.
     */
    protected array $profileCache = [];

    public function profileFor(?int $schoolYearId = null)
    {
        $key = $schoolYearId ?? 'latest';

        if (! array_key_exists($key, $this->profileCache)) {
            $this->profileCache[$key] = $schoolYearId
                ? $this->profiles()->where('school_year_id', $schoolYearId)->first()
                : $this->profiles()->orderByDesc('school_year_id')->first();
        }

        return $this->profileCache[$key];
    }

    public function assessmentScores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public static function generateStudentId(?SchoolYear $schoolYear = null): string
    {
        if (! $schoolYear) {
            $schoolYear = SchoolYear::where('is_active', true)->first();
        }

        if (! $schoolYear) {
            throw new \RuntimeException('No active school year found to generate student ID.');
        }

        $prefix = self::extractYearPrefix($schoolYear->name);

        $latestStudent = self::where('student_id', 'LIKE', $prefix.'_%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(student_id, "_", -1) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($latestStudent && $latestStudent->student_id) {
            $parts = explode('_', $latestStudent->student_id);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        return $prefix.'_'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    protected static function extractYearPrefix(string $schoolYearName): string
    {
        if (preg_match('/(\d{4})-(\d{4})/', $schoolYearName, $matches)) {
            $startYear = substr($matches[1], -2);
            $endYear = substr($matches[2], -2);

            return $startYear.$endYear;
        }

        return date('y').(date('y') + 1);
    }
}
