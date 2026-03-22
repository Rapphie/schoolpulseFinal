<?php

namespace App\Services;

use App\Models\SchoolYearQuarter;

class QuarterLockService
{
    public function contextForSchoolYear(int $schoolYearId): array
    {
        $quartersByNumber = SchoolYearQuarter::query()
            ->where('school_year_id', $schoolYearId)
            ->whereIn('quarter', [1, 2, 3, 4])
            ->get()
            ->keyBy(fn (SchoolYearQuarter $quarter) => (int) $quarter->quarter);

        $activeQuarter = $quartersByNumber->firstWhere('is_current', true);
        $activeQuarterNumber = $activeQuarter ? (int) $activeQuarter->quarter : null;

        $quarterLocks = [];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $quarterModel = $quartersByNumber->get($quarter);
            $isExplicitlyLocked = (bool) ($quarterModel?->is_locked ?? false);
            $isPastActiveQuarter = $activeQuarterNumber !== null && $quarter < $activeQuarterNumber;

            $lockReasonLabel = null;
            if ($isExplicitlyLocked) {
                $lockReasonLabel = 'Locked by Admin';
            } elseif ($isPastActiveQuarter) {
                $lockReasonLabel = 'Quarter Ended';
            }

            $quarterLocks[$quarter] = [
                'quarter' => $quarter,
                'name' => $quarterModel?->name ?? "Quarter {$quarter}",
                'is_explicitly_locked' => $isExplicitlyLocked,
                'is_locked' => $isExplicitlyLocked || $isPastActiveQuarter,
                'lock_reason_label' => $lockReasonLabel,
            ];
        }

        return [
            'activeQuarter' => $activeQuarter,
            'quarterLocks' => $quarterLocks,
        ];
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
}
