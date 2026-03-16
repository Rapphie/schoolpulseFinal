<?php

namespace App\Services;

use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\StudentProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        // Prefer attendance entries linked to a StudentProfile for the requested school year.
        $profile = null;
        if ($schoolYearId) {
            $profile = StudentProfile::where('student_id', $studentId)->where('school_year_id', $schoolYearId)->first();
        }

        $baseMonthly = Attendance::query()
            ->when($profile, fn ($q) => $q->where('student_profile_id', $profile->id), fn ($q) => $q->where('student_id', $studentId))
            ->when(! $profile && $schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->whereBetween('date', [$monthStart, $monthEnd]);

        $monthlyTotal = (clone $baseMonthly)->count();
        $monthlyPresent = (clone $baseMonthly)->where('status', 'present')->count();
        $monthlyAbsent = (clone $baseMonthly)->where('status', 'absent')->count();
        $monthlyExcused = (clone $baseMonthly)->where('status', 'excused')->count();
        $monthlyLate = (clone $baseMonthly)->where('status', 'late')->count();
        $den = max(1, $monthlyTotal);

        // Rolling 3-month counts
        $baseRolling = Attendance::query()
            ->when($profile, fn ($q) => $q->where('student_profile_id', $profile->id), fn ($q) => $q->where('student_id', $studentId))
            ->when(! $profile && $schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
            ->whereBetween('date', [$rollStart, $rollEnd]);
        $r3Absent = (clone $baseRolling)->where('status', 'absent')->count();
        $r3Excused = (clone $baseRolling)->where('status', 'excused')->count();
        $r3Late = (clone $baseRolling)->where('status', 'late')->count();

        // Scores
        $monthlyAvg = $this->avgScoreBetween($studentId, $schoolYearId, $monthStart, $monthEnd);
        $rollingAvg = $this->avgScoreBetween($studentId, $schoolYearId, $rollStart, $rollEnd);

        $named = [
            'monthly_unexcused_absences' => (float) $monthlyAbsent,
            'monthly_excused_absences' => (float) $monthlyExcused,
            'monthly_late_occurrences' => (float) $monthlyLate,
            'monthly_unexcused_absent_rate' => (float) ($monthlyAbsent / $den),
            'monthly_excused_absent_rate' => (float) ($monthlyExcused / $den),
            'monthly_late_rate' => (float) ($monthlyLate / $den),
            'monthly_present_rate' => (float) ($monthlyPresent / $den),
            'rolling_3month_unexcused_absences' => (float) $r3Absent,
            'rolling_3month_excused_absences' => (float) $r3Excused,
            'rolling_3month_late_occurrences' => (float) $r3Late,
            'monthly_avg_score' => (float) $monthlyAvg,
            'rolling_3month_avg_score' => (float) $rollingAvg,
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
     * Compute features for a batch of students in one go to avoid N+1 queries.
     * Returns an associative array keyed by student_id with same structure as computeFeaturesVector.
     *
     * @return array<int,array{ordered:array,named:array}>
     */
    public function computeBatchFeaturesForStudents(array $studentIds, ?int $schoolYearId, Carbon $date): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $monthStart = $date->copy()->startOfMonth()->toDateString();
        $monthEnd = $date->copy()->endOfMonth()->toDateString();
        $rollStart = $date->copy()->startOfMonth()->subMonthsNoOverflow(2)->startOfMonth()->toDateString();
        $rollEnd = $monthEnd;

        // Attempt to prefer attendance/score rows linked to StudentProfile for the school year.
        $profileMap = [];
        $profileIds = [];
        if ($schoolYearId) {
            $profileMap = StudentProfile::whereIn('student_id', $studentIds)
                ->where('school_year_id', $schoolYearId)
                ->pluck('id', 'student_id')
                ->toArray();
            $profileIds = array_values($profileMap);
        }

        // Monthly attendance aggregates per student (profile-aware)
        $monthly = collect();
        if (! empty($profileIds)) {
            $monthlyProfileAgg = DB::table('attendances')
                ->selectRaw('student_profile_id,
                    SUM(status = "present") as present_count,
                    SUM(status = "absent") as absent_count,
                    SUM(status = "excused") as excused_count,
                    SUM(status = "late") as late_count,
                    COUNT(*) as total_count')
                ->whereIn('student_profile_id', $profileIds)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->groupBy('student_profile_id')
                ->get()
                ->keyBy('student_profile_id');

            // Map profile aggregates back to student_id
            foreach ($profileMap as $sid => $pid) {
                if ($monthlyProfileAgg->has($pid)) {
                    $monthly->put($sid, $monthlyProfileAgg->get($pid));
                }
            }
        }

        // Fallback: include attendances keyed by student_id for any students not covered by profiles
        $remaining = array_filter($studentIds, fn ($id) => ! array_key_exists($id, $profileMap));
        if (! empty($remaining)) {
            $monthlyFallback = DB::table('attendances')
                ->selectRaw('student_id,
                    SUM(status = "present") as present_count,
                    SUM(status = "absent") as absent_count,
                    SUM(status = "excused") as excused_count,
                    SUM(status = "late") as late_count,
                    COUNT(*) as total_count')
                ->whereIn('student_id', $remaining)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->groupBy('student_id')
                ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
                ->get()
                ->keyBy('student_id');

            $monthly = $monthly->merge($monthlyFallback);
        }

        // Rolling 3-month attendance aggregates per student (profile-aware similar to monthly)
        $rolling = collect();
        if (! empty($profileIds)) {
            $rollingProfileAgg = DB::table('attendances')
                ->selectRaw('student_profile_id,
                    SUM(status = "absent") as r_absent,
                    SUM(status = "excused") as r_excused,
                    SUM(status = "late") as r_late')
                ->whereIn('student_profile_id', $profileIds)
                ->whereBetween('date', [$rollStart, $rollEnd])
                ->groupBy('student_profile_id')
                ->get()
                ->keyBy('student_profile_id');

            foreach ($profileMap as $sid => $pid) {
                if ($rollingProfileAgg->has($pid)) {
                    $rolling->put($sid, $rollingProfileAgg->get($pid));
                }
            }
        }

        $remaining = array_filter($studentIds, fn ($id) => ! array_key_exists($id, $profileMap));
        if (! empty($remaining)) {
            $rollingFallback = DB::table('attendances')
                ->selectRaw('student_id,
                    SUM(status = "absent") as r_absent,
                    SUM(status = "excused") as r_excused,
                    SUM(status = "late") as r_late')
                ->whereIn('student_id', $remaining)
                ->whereBetween('date', [$rollStart, $rollEnd])
                ->groupBy('student_id')
                ->when($schoolYearId, fn ($q) => $q->where('school_year_id', $schoolYearId))
                ->get()
                ->keyBy('student_id');

            $rolling = $rolling->merge($rollingFallback);
        }

        // Scores: average of (score / max_score) per student for month and rolling window
        // Monthly scores (prefer assessment_scores linked to student_profiles)
        $monthlyScores = collect();
        if (! empty($profileIds)) {
            $ms = DB::table('assessment_scores as sc')
                ->join('assessments as a', 'sc.assessment_id', '=', 'a.id')
                ->selectRaw('sc.student_profile_id, AVG(CASE WHEN a.max_score > 0 THEN sc.score / a.max_score ELSE NULL END) as avg_score')
                ->whereIn('sc.student_profile_id', $profileIds)
                ->whereBetween('a.assessment_date', [$monthStart, $monthEnd])
                ->when($schoolYearId, fn ($q) => $q->where('a.school_year_id', $schoolYearId))
                ->groupBy('sc.student_profile_id')
                ->get()
                ->keyBy('student_profile_id');

            foreach ($profileMap as $sid => $pid) {
                if ($ms->has($pid)) {
                    $monthlyScores->put($sid, $ms->get($pid));
                }
            }
        }

        if (! empty($remaining)) {
            $msFallback = DB::table('assessment_scores as sc')
                ->join('assessments as a', 'sc.assessment_id', '=', 'a.id')
                ->selectRaw('sc.student_id, AVG(CASE WHEN a.max_score > 0 THEN sc.score / a.max_score ELSE NULL END) as avg_score')
                ->whereIn('sc.student_id', $remaining)
                ->whereBetween('a.assessment_date', [$monthStart, $monthEnd])
                ->when($schoolYearId, fn ($q) => $q->where('a.school_year_id', $schoolYearId))
                ->groupBy('sc.student_id')
                ->get()
                ->keyBy('student_id');

            $monthlyScores = $monthlyScores->merge($msFallback);
        }

        $rollingScores = collect();
        if (! empty($profileIds)) {
            $rs = DB::table('assessment_scores as sc')
                ->join('assessments as a', 'sc.assessment_id', '=', 'a.id')
                ->selectRaw('sc.student_profile_id, AVG(CASE WHEN a.max_score > 0 THEN sc.score / a.max_score ELSE NULL END) as avg_score')
                ->whereIn('sc.student_profile_id', $profileIds)
                ->whereBetween('a.assessment_date', [$rollStart, $rollEnd])
                ->when($schoolYearId, fn ($q) => $q->where('a.school_year_id', $schoolYearId))
                ->groupBy('sc.student_profile_id')
                ->get()
                ->keyBy('student_profile_id');

            foreach ($profileMap as $sid => $pid) {
                if ($rs->has($pid)) {
                    $rollingScores->put($sid, $rs->get($pid));
                }
            }
        }

        if (! empty($remaining)) {
            $rsFallback = DB::table('assessment_scores as sc')
                ->join('assessments as a', 'sc.assessment_id', '=', 'a.id')
                ->selectRaw('sc.student_id, AVG(CASE WHEN a.max_score > 0 THEN sc.score / a.max_score ELSE NULL END) as avg_score')
                ->whereIn('sc.student_id', $remaining)
                ->whereBetween('a.assessment_date', [$rollStart, $rollEnd])
                ->when($schoolYearId, fn ($q) => $q->where('a.school_year_id', $schoolYearId))
                ->groupBy('sc.student_id')
                ->get()
                ->keyBy('student_id');

            $rollingScores = $rollingScores->merge($rsFallback);
        }

        $result = [];
        foreach ($studentIds as $sid) {
            $m = $monthly->get($sid);
            $r = $rolling->get($sid);
            $ms = $monthlyScores->get($sid);
            $rs = $rollingScores->get($sid);

            $monthlyTotal = $m->total_count ?? 0;
            $monthlyPresent = $m->present_count ?? 0;
            $monthlyAbsent = $m->absent_count ?? 0;
            $monthlyExcused = $m->excused_count ?? 0;
            $monthlyLate = $m->late_count ?? 0;
            $den = max(1, $monthlyTotal);

            $r3Absent = $r->r_absent ?? 0;
            $r3Excused = $r->r_excused ?? 0;
            $r3Late = $r->r_late ?? 0;

            $monthlyAvg = $ms->avg_score ?? 0.0;
            $rollingAvg = $rs->avg_score ?? 0.0;

            $named = [
                'monthly_unexcused_absences' => (float) $monthlyAbsent,
                'monthly_excused_absences' => (float) $monthlyExcused,
                'monthly_late_occurrences' => (float) $monthlyLate,
                'monthly_unexcused_absent_rate' => (float) ($monthlyAbsent / $den),
                'monthly_excused_absent_rate' => (float) ($monthlyExcused / $den),
                'monthly_late_rate' => (float) ($monthlyLate / $den),
                'monthly_present_rate' => (float) ($monthlyPresent / $den),
                'rolling_3month_unexcused_absences' => (float) $r3Absent,
                'rolling_3month_excused_absences' => (float) $r3Excused,
                'rolling_3month_late_occurrences' => (float) $r3Late,
                'monthly_avg_score' => (float) $monthlyAvg,
                'rolling_3month_avg_score' => (float) $rollingAvg,
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

            $result[$sid] = ['ordered' => $ordered, 'named' => $named];
        }

        return $result;
    }

    /**
     * Persist feature snapshots to `student_feature_snapshots` table.
     * Uses updateOrInsert to avoid duplicate unique key violations.
     *
     * @param  array  $featureMap  keyed by student id => ['ordered'=>[], 'named'=>[]]
     */
    public function persistSnapshots(array $featureMap, ?int $schoolYearId, ?string $modelVersion = null): void
    {
        if (empty($featureMap)) {
            return;
        }
        if ($schoolYearId === null || $schoolYearId <= 0) {
            throw new \InvalidArgumentException('A valid school_year_id is required to persist snapshots.');
        }
        // Use provided version or fall back to date-based version (e.g. v2026-01-12)
        $modelVersion = $modelVersion ?: ('v'.now()->format('Y-m-d'));
        $rows = [];
        foreach ($featureMap as $studentId => $data) {
            $ordered = $data['ordered'] ?? [];
            $named = $data['named'] ?? [];
            $payload = json_encode(['ordered' => $ordered, 'named' => $named]);
            $rows[] = [
                'student_id' => $studentId,
                'school_year_id' => $schoolYearId,
                'features' => $payload,
                'model_version' => $modelVersion,
                'computed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert or update per-student snapshot
        foreach ($rows as $r) {
            DB::table('student_feature_snapshots')->updateOrInsert(
                ['student_id' => $r['student_id'], 'school_year_id' => $r['school_year_id'], 'model_version' => $r['model_version']],
                ['features' => $r['features'], 'computed_at' => $r['computed_at'], 'updated_at' => $r['updated_at']]
            );
        }
    }

    /**
     * Blend attendance + performance signals into a 0-100 engagement score.
     */
    public function calculateEngagementScore(array $namedFeatures): float
    {
        $attendanceRate = (float) ($namedFeatures['monthly_present_rate'] ?? 0.0); // already 0-1 scale
        $absencePenalty = 1.0 - (float) ($namedFeatures['monthly_unexcused_absent_rate'] ?? 0.0);
        $academicPerformance = (float) ($namedFeatures['monthly_avg_score'] ?? 0.0);

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
        // Prefer assessment_scores linked to a StudentProfile for the requested school year.
        $profile = null;
        if ($schoolYearId) {
            $profile = StudentProfile::where('student_id', $studentId)->where('school_year_id', $schoolYearId)->first();
        }

        $query = AssessmentScore::query();
        if ($profile) {
            $query->where('student_profile_id', $profile->id);
        } else {
            $query->where('student_id', $studentId);
        }

        $rows = $query->whereHas('assessment', function ($q) use ($schoolYearId, $startDate, $endDate) {
            if ($schoolYearId) {
                $q->where('school_year_id', $schoolYearId);
            }
            $q->whereBetween('assessment_date', [$startDate, $endDate]);
        })
            ->with(['assessment:id,max_score,assessment_date'])
            ->get();

        if ($rows->isEmpty()) {
            return 0.0;
        }
        $vals = [];
        foreach ($rows as $r) {
            $max = (float) ($r->assessment->max_score ?? 0);
            $score = (float) ($r->score ?? 0);
            if ($max > 0) {
                $vals[] = $score / $max;
            }
        }
        if (empty($vals)) {
            return 0.0;
        }

        return array_sum($vals) / count($vals);
    }
}
