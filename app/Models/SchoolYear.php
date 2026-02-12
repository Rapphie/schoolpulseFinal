<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SchoolYear extends Model
{
    public const ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY = 'admin_view_school_year_id';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'is_promotion_open',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_promotion_open' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     * This is used to register model event listeners.
     */
    protected static function booted(): void
    {
        // This event ensures that only one school year can be active at a time.
        // When a school year is being updated...
        static::updating(function (SchoolYear $schoolYear) {
            // ...and its 'is_active' flag is being set to true...
            if ($schoolYear->isDirty('is_active') && $schoolYear->is_active) {
                // ...then update all other school years to set their 'is_active' flag to false.
                static::where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }
        });
        // This event runs every time a SchoolYear record is being created or updated.
        static::saving(function (SchoolYear $schoolYear) {
            // If the 'is_active' flag is being set to true...
            if ($schoolYear->is_active) {
                // ...then update all other school years to set their 'is_active' flag to false.
                static::where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Define the relationships for the SchoolYear model.
    |
    */

    /**
     * Get all of the quarters for the SchoolYear.
     */
    public function quarters()
    {
        return $this->hasMany(SchoolYearQuarter::class)->orderBy('quarter');
    }

    /**
     * Get the current quarter for this school year.
     */
    public function currentQuarter()
    {
        return $this->quarters()->current()->first();
    }

    /**
     * Get all of the classes for the SchoolYear.
     */
    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    /**
     * Get all of the enrollments for the SchoolYear.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get all of the grades for the SchoolYear.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get all of the attendances for the SchoolYear.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Define reusable query scopes for the SchoolYear model.
    |
    */

    /**
     * Get the active school year, or fallback to the latest one.
     *
     * @return static|null
     */
    public static function getActive(): ?self
    {
        $viewSchoolYearId = static::getAdminViewSchoolYearId();
        if ($viewSchoolYearId) {
            $viewSchoolYear = static::query()->find($viewSchoolYearId);
            if ($viewSchoolYear) {
                return $viewSchoolYear;
            }

            static::clearAdminViewSchoolYear();
        }

        return static::getRealActive();
    }

    /**
     * Get the real globally active school year (ignores admin view mode).
     *
     * @return static|null
     */
    public static function getRealActive(): ?self
    {
        $active = static::query()->where('is_active', true)->first();

        if (! $active) {
            $active = static::query()->latest('end_date')->first();
        }

        return $active;
    }

    /**
     * Scope a query to only include the active school year.
     */
    public function scopeActive(Builder $query): Builder
    {
        $viewSchoolYearId = static::getAdminViewSchoolYearId();
        if ($viewSchoolYearId) {
            return $query->whereKey($viewSchoolYearId);
        }

        return $query->where('is_active', true)
            ->orWhereHas('quarters', function ($q) {
                $q->where('is_manually_set_active', true);
            });
    }

    /**
     * Set admin session-scoped school year view override.
     */
    public static function setAdminViewSchoolYear(int $schoolYearId): void
    {
        if (! app()->bound('request')) {
            return;
        }

        if (! request()->hasSession()) {
            return;
        }

        request()->session()->put(self::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY, $schoolYearId);
    }

    /**
     * Clear admin session-scoped school year view override.
     */
    public static function clearAdminViewSchoolYear(): void
    {
        if (! app()->bound('request')) {
            return;
        }

        if (! request()->hasSession()) {
            return;
        }

        request()->session()->forget(self::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY);
    }

    /**
     * Get admin session-scoped school year view override id.
     */
    public static function getAdminViewSchoolYearId(): ?int
    {
        if (! app()->bound('request')) {
            return null;
        }

        if (! request()->hasSession()) {
            return null;
        }

        if (! Auth::check() || ! Auth::user()->hasRole('admin')) {
            return null;
        }

        $schoolYearId = request()->session()->get(self::ADMIN_VIEW_SCHOOL_YEAR_SESSION_KEY);

        return is_numeric($schoolYearId) ? (int) $schoolYearId : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a date range overlaps with any existing school years.
     *
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     * @param  int|null  $excludeId  ID to exclude (for updates)
     * @return static|null Returns the overlapping school year if found, null otherwise
     */
    public static function findOverlapping($startDate, $endDate, ?int $excludeId = null): ?self
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return static::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($query) use ($start, $end) {
                // Case 1: New start_date falls within existing range
                $query->where(function ($q) use ($start) {
                    $q->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $start);
                })
                    // Case 2: New end_date falls within existing range
                    ->orWhere(function ($q) use ($end) {
                        $q->where('start_date', '<=', $end)
                            ->where('end_date', '>=', $end);
                    })
                    // Case 3: New range completely contains existing range
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '>=', $start)
                            ->where('end_date', '<=', $end);
                    });
            })
            ->first();
    }

    /**
     * Validate that school years remain contiguous with no date gaps.
     *
     * @param  string|Carbon  $startDate
     * @param  string|Carbon  $endDate
     * @param  int|null  $excludeId  ID to exclude (for updates)
     */
    public static function validateContinuity($startDate, $endDate, ?int $excludeId = null): ?string
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        $previousYear = static::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->whereDate('end_date', '<', $start->toDateString())
            ->orderBy('end_date', 'desc')
            ->first();

        if ($previousYear) {
            $expectedStart = Carbon::parse($previousYear->end_date)->addDay()->toDateString();
            if ($start->toDateString() !== $expectedStart) {
                return "School year must start on {$expectedStart} so it directly follows {$previousYear->name}.";
            }
        }

        $nextYear = static::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->whereDate('start_date', '>', $end->toDateString())
            ->orderBy('start_date')
            ->first();

        if ($nextYear) {
            $expectedEnd = Carbon::parse($nextYear->start_date)->subDay()->toDateString();
            if ($end->toDateString() !== $expectedEnd) {
                return "School year must end on {$expectedEnd} so it directly precedes {$nextYear->name}.";
            }
        }

        return null;
    }

    /**
     * Check if this school year has any related data (enrollments, grades, etc.)
     * Note: Empty classes (without enrollments) are allowed to be deleted with the school year.
     */
    public function hasRelatedData(): bool
    {
        return $this->enrollments()->exists()
            || $this->grades()->exists()
            || $this->attendances()->exists();
    }

    /**
     * Get the previous school year (by end date).
     *
     * @return static|null
     */
    public static function previous(): ?self
    {
        $active = static::getActive();

        if (! $active) {
            return static::orderBy('end_date', 'desc')->first();
        }

        return static::where('id', '!=', $active->id)
            ->where('end_date', '<', $active->start_date)
            ->orderBy('end_date', 'desc')
            ->first();
    }

    /**
     * Check if current date falls within this school year.
     */
    public function isCurrent(): bool
    {
        $today = Carbon::today();

        return $today->between($this->start_date, $this->end_date);
    }

    /**
     * Get the current quarter for the active school year.
     * This is a convenience method for use throughout the app.
     */
    public static function getCurrentQuarter(): ?SchoolYearQuarter
    {
        $activeSchoolYear = static::getActive();
        if (! $activeSchoolYear) {
            return null;
        }

        return $activeSchoolYear->quarters()->current()->first();
    }

    /**
     * Check if a specific quarter is locked.
     *
     * @param  int  $quarterNumber  1-4
     */
    public function isQuarterLocked(int $quarterNumber): bool
    {
        $quarter = $this->quarters()->where('quarter', $quarterNumber)->first();

        return $quarter ? $quarter->is_locked : false;
    }

    /**
     * Check if the school year has ended (end_date is in the past).
     */
    public function hasEnded(): bool
    {
        return Carbon::today()->gt($this->end_date);
    }

    /**
     * Check if promotion can be opened for this school year.
     * Promotion should only be allowed after the school year has ended.
     */
    public function canOpenPromotion(): bool
    {
        return $this->hasEnded();
    }
}
