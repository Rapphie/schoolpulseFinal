<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolYearQuarter extends Model
{
    protected $fillable = [
        'school_year_id',
        'quarter',
        'name',
        'start_date',
        'end_date',
        'grade_submission_deadline',
        'is_locked',
        'is_manually_set_active',
    ];

    protected $casts = [
        'quarter' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'grade_submission_deadline' => 'date',
        'is_locked' => 'boolean',
        'is_manually_set_active' => 'boolean',
    ];

    /**
     * Quarter name labels.
     */
    public const QUARTER_NAMES = [
        1 => 'First Quarter',
        2 => 'Second Quarter',
        3 => 'Third Quarter',
        4 => 'Fourth Quarter',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to get the current quarter based on today's date or manual override.
     */
    public function scopeCurrent(Builder $query): Builder
    {
        // Prioritize the manually set active quarter
        $manualActive = $query->clone()->where('is_manually_set_active', true)->first();

        if ($manualActive) {
            return $query->where('id', $manualActive->id);
        }

        $today = Carbon::today();
        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Scope to get the manually set active quarter.
     */
    public function scopeManuallyActive(Builder $query): Builder
    {
        return $query->where('is_manually_set_active', true);
    }

    /**
     * Scope to get quarters for the active school year.
     */
    public function scopeForActiveSchoolYear(Builder $query): Builder
    {
        return $query->whereHas('schoolYear', fn($q) => $q->where('is_active', true));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this quarter is currently active (based on date or manual override).
     */
    public function isCurrent(): bool
    {
        if ($this->is_manually_set_active) {
            return true;
        }

        // Fallback for when no quarter is manually set as active
        $manuallyActiveExists = self::where('is_manually_set_active', true)->exists();
        if ($manuallyActiveExists) {
            return false;
        }

        $today = Carbon::today();
        return $today->between($this->start_date, $this->end_date);
    }

    /**
     * Check if this quarter has ended.
     */
    public function hasEnded(): bool
    {
        return Carbon::today()->gt($this->end_date);
    }

    /**
     * Check if this quarter hasn't started yet.
     */
    public function isPending(): bool
    {
        return Carbon::today()->lt($this->start_date);
    }

    /**
     * Check if the grade submission deadline has passed.
     */
    public function isSubmissionDeadlinePassed(): bool
    {
        if (!$this->grade_submission_deadline) {
            return false;
        }
        return Carbon::today()->gt($this->grade_submission_deadline);
    }

    /**
     * Get days remaining until quarter ends (or negative if ended).
     */
    public function daysRemaining(): int
    {
        return Carbon::today()->diffInDays($this->end_date, false);
    }

    /**
     * Get days remaining until submission deadline (or negative if passed).
     */
    public function daysUntilDeadline(): ?int
    {
        if (!$this->grade_submission_deadline) {
            return null;
        }
        return Carbon::today()->diffInDays($this->grade_submission_deadline, false);
    }

    /**
     * Get status label for display.
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_manually_set_active) {
            return 'Active (Manual)';
        }
        if ($this->is_locked) {
            return 'Locked';
        }
        if ($this->isPending()) {
            return 'Upcoming';
        }
        if ($this->isCurrent()) {
            return 'Active';
        }
        return 'Ended';
    }

    /**
     * Get status badge class for display.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'Active (Manual)' => 'bg-primary',
            'Locked' => 'bg-secondary',
            'Upcoming' => 'bg-info',
            'Active' => 'bg-success',
            'Ended' => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    /**
     * Check if quarter dates overlap with another quarter in the same school year.
     */
    public static function findOverlapping(int $schoolYearId, $startDate, $endDate, ?int $excludeId = null): ?self
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return static::query()
            ->where('school_year_id', $schoolYearId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start) {
                    $q->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $start);
                })
                    ->orWhere(function ($q) use ($end) {
                        $q->where('start_date', '<=', $end)
                            ->where('end_date', '>=', $end);
                    })
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '>=', $start)
                            ->where('end_date', '<=', $end);
                    });
            })
            ->first();
    }

    /**
     * Validate that quarter dates are within the school year range.
     */
    public function isWithinSchoolYear(): bool
    {
        $schoolYear = $this->schoolYear;
        if (!$schoolYear) {
            return false;
        }

        return $this->start_date->gte($schoolYear->start_date)
            && $this->end_date->lte($schoolYear->end_date);
    }
}
