<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected static function booted(): void
    {
        static::updating(function (SchoolYear $schoolYear) {
            if ($schoolYear->isDirty('is_active') && $schoolYear->is_active) {
                static::where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }
        });
        static::saving(function (SchoolYear $schoolYear) {
            if ($schoolYear->is_active) {
                static::where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }
        });
    }

    public function quarters(): HasMany
    {
        return $this->hasMany(SchoolYearQuarter::class)->orderBy('quarter');
    }

    public function currentQuarter()
    {
        return $this->quarters()->current()->first();
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

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

    public static function getRealActive(): ?self
    {
        $manualActiveQuarter = SchoolYearQuarter::query()
            ->where('is_manually_set_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if ($manualActiveQuarter) {
            static::resolveManualQuarterActivationConflicts($manualActiveQuarter);

            return static::query()->find($manualActiveQuarter->school_year_id);
        }

        $activeYears = static::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        if ($activeYears->count() > 1) {
            $activeYear = $activeYears->first();
            static::query()->where('id', '!=', $activeYear->id)->update(['is_active' => false]);

            return $activeYear;
        }

        return $activeYears->first();
    }

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

    public static function findOverlapping($startDate, $endDate, ?int $excludeId = null): ?self
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return static::query()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
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

    public function hasRelatedData(): bool
    {
        return $this->enrollments()->exists()
            || $this->grades()->exists()
            || $this->attendances()->exists();
    }

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

    public function isCurrent(): bool
    {
        $today = Carbon::today();

        return $today->between($this->start_date, $this->end_date);
    }

    public static function getCurrentQuarter(): ?SchoolYearQuarter
    {
        $activeSchoolYear = static::getActive();
        if (! $activeSchoolYear) {
            return null;
        }

        return $activeSchoolYear->quarters()->current()->first();
    }

    public function isQuarterLocked(int $quarterNumber): bool
    {
        $quarter = $this->quarters()->where('quarter', $quarterNumber)->first();

        return $quarter ? $quarter->is_locked : false;
    }

    public function hasEnded(): bool
    {
        return Carbon::today()->gt($this->end_date);
    }

    public function canOpenPromotion(): bool
    {
        return $this->hasEnded();
    }

    private static function resolveManualQuarterActivationConflicts(SchoolYearQuarter $winner): void
    {
        SchoolYearQuarter::query()
            ->where('is_manually_set_active', true)
            ->where('id', '!=', $winner->id)
            ->update(['is_manually_set_active' => false]);

        static::query()
            ->where('id', '!=', $winner->school_year_id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        static::query()
            ->whereKey($winner->school_year_id)
            ->where('is_active', false)
            ->update(['is_active' => true]);
    }
}
