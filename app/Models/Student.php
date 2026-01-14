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

    /**
     * Academic profiles (one per school year) showing grade level progression.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    /**
     * Get the current (active school year) profile.
     */
    public function currentProfile()
    {
        return $this->profiles()
            ->whereHas('schoolYear', fn($q) => $q->where('is_active', true))
            ->first();
    }

    public function assessmentScores()
    {
        return $this->hasMany(AssessmentScore::class);
    }

    /**
     * Generate a unique student ID based on the current school year.
     * Format: YYYYY_XXX (e.g., 2526_001 for school year 2025-2026)
     *
     * @param SchoolYear|null $schoolYear The school year to use (defaults to active school year)
     * @return string The generated student ID
     */
    public static function generateStudentId(?SchoolYear $schoolYear = null): string
    {
        // Get the active school year if not provided
        if (!$schoolYear) {
            $schoolYear = SchoolYear::where('is_active', true)->first();
        }

        if (!$schoolYear) {
            throw new \RuntimeException('No active school year found to generate student ID.');
        }

        // Extract years from school year name (e.g., "2025-2026" -> "2526")
        $prefix = self::extractYearPrefix($schoolYear->name);

        // Find the highest existing student ID with this prefix
        $latestStudent = self::where('student_id', 'LIKE', $prefix . '_%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(student_id, "_", -1) AS UNSIGNED) DESC')
            ->first();

        // Determine the next sequence number
        $nextNumber = 1;
        if ($latestStudent && $latestStudent->student_id) {
            $parts = explode('_', $latestStudent->student_id);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        // Format: PREFIX_XXX (zero-padded to 3 digits)
        return $prefix . '_' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Extract year prefix from school year name.
     * Example: "2025-2026" -> "2526"
     *
     * @param string $schoolYearName
     * @return string
     */
    protected static function extractYearPrefix(string $schoolYearName): string
    {
        // Match pattern like "2025-2026"
        if (preg_match('/(\d{4})-(\d{4})/', $schoolYearName, $matches)) {
            $startYear = substr($matches[1], -2); // Last 2 digits of start year
            $endYear = substr($matches[2], -2);   // Last 2 digits of end year
            return $startYear . $endYear;
        }

        // Fallback: use current year
        return date('y') . (date('y') + 1);
    }
}
