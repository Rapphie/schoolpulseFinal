<?php

namespace App\Services;

use App\Models\SchoolYearQuarter;

class QuarterLockService
{
    private static array $contextCache = [];

    public function contextForSchoolYear(int $schoolYearId): array
    {
        if (isset(self::$contextCache[$schoolYearId])) {
            return self::$contextCache[$schoolYearId];
        }

        $activeQuarter = SchoolYearQuarter::query()
            ->where('school_year_id', $schoolYearId)
            ->current()
            ->first();

        $activeQuarterNumber = $activeQuarter ? (int) $activeQuarter->quarter : null;

        $quartersByNumber = SchoolYearQuarter::query()
            ->where('school_year_id', $schoolYearId)
            ->whereIn('quarter', [1, 2, 3, 4])
            ->get()
            ->keyBy(fn (SchoolYearQuarter $quarter) => (int) $quarter->quarter);

        $quarterLocks = [];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $quarterModel = $quartersByNumber->get($quarter);
            $isLockedField = $quarterModel?->is_locked;

            $isExplicitlyLocked = $isLockedField === true;
            $isExplicitlyUnlocked = $isLockedField === false;
            $isPastActiveQuarter = $quarterModel !== null && $quarterModel->hasEnded();

            $lockReasonLabel = null;
            if ($isExplicitlyLocked) {
                $lockReasonLabel = 'Locked by Admin';
            } elseif ($isPastActiveQuarter && ! $isExplicitlyUnlocked) {
                $lockReasonLabel = 'Quarter Ended';
            }

            $quarterLocks[$quarter] = [
                'quarter' => $quarter,
                'name' => $quarterModel?->name ?? "Quarter {$quarter}",
                'is_explicitly_locked' => $isExplicitlyLocked,
                'is_explicitly_unlocked' => $isExplicitlyUnlocked,
                'is_locked' => $isExplicitlyLocked || ($isPastActiveQuarter && ! $isExplicitlyUnlocked),
                'lock_reason_label' => $lockReasonLabel,
            ];
        }

        $context = [
            'activeQuarter' => $activeQuarter,
            'quarterLocks' => $quarterLocks,
        ];

        self::$contextCache[$schoolYearId] = $context;

        return $context;
    }

    public function isLocked(int $schoolYearId, int $quarter): bool
    {
        $context = $this->contextForSchoolYear($schoolYearId);
        $lockInfo = $context['quarterLocks'][$quarter] ?? null;

        return $lockInfo['is_locked'] ?? false;
    }

    public function getActiveQuarterNumber(int $schoolYearId): ?int
    {
        $activeQuarter = SchoolYearQuarter::query()
            ->where('school_year_id', $schoolYearId)
            ->current()
            ->first();

        return $activeQuarter ? (int) $activeQuarter->quarter : null;
    }

    public static function clearCache(?int $schoolYearId = null): void
    {
        if ($schoolYearId !== null) {
            unset(self::$contextCache[$schoolYearId]);
        } else {
            self::$contextCache = [];
        }
    }
}
