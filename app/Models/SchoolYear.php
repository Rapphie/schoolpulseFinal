<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
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
        $active = static::where('is_active', true)->first();

        if (! $active) {
            $active = static::latest('end_date')->first();
        }

        return $active;
    }

    /**
     * Scope a query to only include the active school year.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orWhereHas('quarters', function ($q) {
                $q->where('is_manually_set_active', true);
            });
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
        $active = static::active()->first();

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
        $activeSchoolYear = static::active()->first();
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
