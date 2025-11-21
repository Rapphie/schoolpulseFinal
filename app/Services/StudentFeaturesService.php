<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AssessmentScore;
use App\Models\SchoolYear;
use Carbon\Carbon;

class StudentFeaturesService
{
    /**
     * Compute ordered features vector (12 floats) expected by the Python model.
     * Returns ['ordered' => [...], 'named' => [name=>value,...]]
     */
    public function computeFeaturesVector(int $studentId, ?int $schoolYearId, Carbon $date): array
    {
        $monthStart = $date->copy()->startOfMonth()->toDateString();
        $monthEnd = $date->copy()->endOfMonth()->toDateString();
        $rollStart = $date->copy()->startOfMonth()->subMonthsNoOverflow(2)->startOfMonth()->toDateString();
        $rollEnd = $monthEnd;

        // Monthly counts (row-based as per current data collection)
        $baseMonthly = Attendance::query()
            ->where('student_id', $studentId)
            ->when($schoolYearId, fn($q) => $q->where('school_year_id', $schoolYearId))
            ->whereBetween('date', [$monthStart, $monthEnd]);

        $monthlyTotal = (clone $baseMonthly)->count();
        $monthlyPresent = (clone $baseMonthly)->where('status', 'present')->count();
        $monthlyAbsent = (clone $baseMonthly)->where('status', 'absent')->count();
        $monthlyExcused = (clone $baseMonthly)->where('status', 'excused')->count();
        $monthlyLate = (clone $baseMonthly)->where('status', 'late')->count();
        $den = max(1, $monthlyTotal);

        // Rolling 3-month counts
        $baseRolling = Attendance::query()
            ->where('student_id', $studentId)
            ->when($schoolYearId, fn($q) => $q->where('school_year_id', $schoolYearId))
            ->whereBetween('date', [$rollStart, $rollEnd]);
        $r3Absent = (clone $baseRolling)->where('status', 'absent')->count();
        $r3Excused = (clone $baseRolling)->where('status', 'excused')->count();
        $r3Late = (clone $baseRolling)->where('status', 'late')->count();

        // Scores
        $monthlyAvg = $this->avgScoreBetween($studentId, $schoolYearId, $monthStart, $monthEnd);
        $rollingAvg = $this->avgScoreBetween($studentId, $schoolYearId, $rollStart, $rollEnd);

        $named = [
            'monthly_unexcused_absences' => (float)$monthlyAbsent,
            'monthly_excused_absences' => (float)$monthlyExcused,
            'monthly_late_occurrences' => (float)$monthlyLate,
            'monthly_unexcused_absent_rate' => (float)($monthlyAbsent / $den),
            'monthly_excused_absent_rate' => (float)($monthlyExcused / $den),
            'monthly_late_rate' => (float)($monthlyLate / $den),
            'monthly_present_rate' => (float)($monthlyPresent / $den),
            'rolling_3month_unexcused_absences' => (float)$r3Absent,
            'rolling_3month_excused_absences' => (float)$r3Excused,
            'rolling_3month_late_occurrences' => (float)$r3Late,
            'monthly_avg_score' => (float)$monthlyAvg,
            'rolling_3month_avg_score' => (float)$rollingAvg,
        ];

        $ordered = [
            $named['monthly_unexcused_absences'],
            $named['monthly_excused_absences'],
            $named['monthly_late_occurrences'],
            $named['monthly_unexcused_absent_rate'],
            $named['monthly_excused_absent_rate'],
            $named['monthly_late_rate'],
            $named['monthly_present_rate'],
            $named['rolling_3month_unexcused_absences'],
            $named['rolling_3month_excused_absences'],
            $named['rolling_3month_late_occurrences'],
            $named['monthly_avg_score'],
            $named['rolling_3month_avg_score'],
        ];

        return ['ordered' => $ordered, 'named' => $named];
    }

    /**
     * Blend attendance + performance signals into a 0-100 engagement score.
     */
    public function calculateEngagementScore(array $namedFeatures): float
    {
        $attendanceRate = (float)($namedFeatures['monthly_present_rate'] ?? 0.0); // already 0-1 scale
        $absencePenalty = 1.0 - (float)($namedFeatures['monthly_unexcused_absent_rate'] ?? 0.0);
        $academicPerformance = (float)($namedFeatures['monthly_avg_score'] ?? 0.0);

        $composite = (
            ($attendanceRate * 0.5) +
            ($academicPerformance * 0.3) +
            ($absencePenalty * 0.2)
        );

        $bounded = max(0.0, min(1.0, $composite));
        return round($bounded * 100, 2);
    }

    private function avgScoreBetween(int $studentId, ?int $schoolYearId, string $startDate, string $endDate): float
    {
        $rows = AssessmentScore::query()
            ->where('student_id', $studentId)
            ->whereHas('assessment', function ($q) use ($schoolYearId, $startDate, $endDate) {
                if ($schoolYearId) {
                    $q->where('school_year_id', $schoolYearId);
                }
                $q->whereBetween('assessment_date', [$startDate, $endDate]);
            })
            ->with(['assessment:id,max_score,assessment_date'])
            ->get();

        if ($rows->isEmpty()) return 0.0;
        $vals = [];
        foreach ($rows as $r) {
            $max = (float)($r->assessment->max_score ?? 0);
            $score = (float)($r->score ?? 0);
            if ($max > 0) {
                $vals[] = $score / $max;
            }
        }
        if (empty($vals)) return 0.0;
        return array_sum($vals) / count($vals);
    }
}
