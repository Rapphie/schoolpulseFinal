<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\PredictionClient;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    private const ANALYTICS_CACHE_TTL_SECONDS = 300;

    private const RISK_HIGH_THRESHOLD = 70.0;

    private const RISK_MEDIUM_THRESHOLD = 40.0;

    private const ATTENDANCE_DROP_WARNING = 5.0;

    private const ATTENDANCE_DROP_CRITICAL = 10.0;

    /**
     * Display absenteeism analytics for the teacher.
     *
     * @return \Illuminate\View\View
     */
    public function absenteeismAnalytics(Request $request)
    {
        $requestStartedAt = microtime(true);
        $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();
        $syId = $activeSchoolYear->id;

        // Determine role/scope: teacher sees own advisory classes; admin sees all classes.
        $authUser = Auth::user();
        $isTeacherRole = (bool) ($authUser && $authUser->hasRole('teacher'));
        $teacher = Teacher::where('user_id', Auth::id())->first();
        $availableClassIds = $this->getAccessibleClassIds($activeSchoolYear, $isTeacherRole, $teacher);

        $selectedGradeLevelId = $request->query('grade_level_id');
        if ($selectedGradeLevelId !== null && $selectedGradeLevelId !== '') {
            $selectedGradeLevelId = (int) $selectedGradeLevelId;
        } else {
            $selectedGradeLevelId = null;
        }

        $gradeLevels = Classes::whereIn('id', $availableClassIds)
            ->with(['section.gradeLevel'])
            ->get()
            ->map(function ($class) {
                $grade = optional($class->section)->gradeLevel;
                if (! $grade) {
                    return null;
                }

                return [
                    'id' => $grade->id,
                    'label' => $this->formatGradeLabel($grade),
                    'level' => $grade->level ?? null,
                ];
            })
            ->filter()
            ->unique('id')
            ->sortBy(fn ($item) => $item['level'] ?? $item['label'])
            ->values()
            ->map(function ($item) {
                return [
                    'id' => $item['id'],
                    'label' => $item['label'],
                ];
            })
            ->toArray();

        $gradeFilteredClassIds = $selectedGradeLevelId
            ? Classes::whereIn('id', $availableClassIds)
                ->whereHas('section', function ($query) use ($selectedGradeLevelId) {
                    $query->where('grade_level_id', $selectedGradeLevelId);
                })
                ->pluck('id')
                ->unique()
                ->values()
            : $availableClassIds->values();

        $classesForSelect = [];
        if ($selectedGradeLevelId && $gradeFilteredClassIds->isNotEmpty()) {
            $classesForSelect = Classes::whereIn('id', $gradeFilteredClassIds)
                ->with(['section.gradeLevel'])
                ->orderBy('id')
                ->get()
                ->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'label' => $this->formatClassLabel($class),
                    ];
                })
                ->values()
                ->toArray();
        }

        // Optional filter by class_id
        $selectedClassId = $request->query('class_id');
        if ($selectedClassId && $gradeFilteredClassIds->contains((int) $selectedClassId)) {
            $classIds = collect([(int) $selectedClassId]);
        } else {
            $classIds = $gradeFilteredClassIds;
            $selectedClassId = null; // ignore invalid
        }

        $cacheKey = $this->buildAnalyticsCacheKey(
            (int) Auth::id(),
            $syId,
            $selectedGradeLevelId,
            $selectedClassId
        );

        $cacheHit = Cache::has($cacheKey);
        $analyticsPayload = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::ANALYTICS_CACHE_TTL_SECONDS),
            function () use ($classIds, $syId) {
                return $this->buildAbsenteeismPayload($classIds, $syId);
            }
        );

        $analyticsServiceRunning = (bool) ($analyticsPayload['analyticsServiceRunning'] ?? true);
        if ($cacheHit) {
            $healthCheckStartedAt = microtime(true);
            $analyticsServiceRunning = (new PredictionClient)->isAnalyticsServiceRunning();
            Log::debug('Teacher absenteeism analytics health check completed', [
                'user_id' => Auth::id(),
                'school_year_id' => $syId,
                'is_running' => $analyticsServiceRunning,
                'duration_ms' => round((microtime(true) - $healthCheckStartedAt) * 1000, 1),
            ]);
        }

        $analyticsServiceWarning = $this->buildAnalyticsServiceWarning(
            $analyticsServiceRunning,
            $cacheHit,
            $analyticsPayload
        );

        Log::debug('Teacher absenteeism analytics request completed', [
            'user_id' => Auth::id(),
            'school_year_id' => $syId,
            'selected_grade_level_id' => $selectedGradeLevelId,
            'selected_class_id' => $selectedClassId,
            'cache_hit' => $cacheHit,
            'analytics_service_running' => $analyticsServiceRunning,
            'duration_ms' => round((microtime(true) - $requestStartedAt) * 1000, 1),
        ]);

        return view('teacher.analytics.absenteeism', array_merge(
            [
                'classesForSelect' => $classesForSelect,
                'selectedClassId' => $selectedClassId,
                'gradeLevels' => $gradeLevels,
                'selectedGradeLevelId' => $selectedGradeLevelId,
            ],
            $analyticsPayload,
            [
                'analyticsServiceRunning' => $analyticsServiceRunning,
                'analyticsServiceWarning' => $analyticsServiceWarning,
            ]
        ));
    }

    public function classesByGrade(Request $request)
    {
        $gradeLevelId = (int) $request->query('grade_level_id');

        if (! $gradeLevelId) {
            return response()->json(['classes' => []]);
        }

        $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();
        $authUser = Auth::user();
        $isTeacherRole = (bool) ($authUser && $authUser->hasRole('teacher'));
        $teacher = Teacher::where('user_id', Auth::id())->first();

        $availableClassIds = $this->getAccessibleClassIds($activeSchoolYear, $isTeacherRole, $teacher);

        if ($availableClassIds->isEmpty()) {
            return response()->json(['classes' => []]);
        }

        $classes = Classes::whereIn('id', $availableClassIds)
            ->whereHas('section', function ($query) use ($gradeLevelId) {
                $query->where('grade_level_id', $gradeLevelId);
            })
            ->with(['section.gradeLevel'])
            ->orderBy('id')
            ->get();

        $classOptions = $classes->map(function ($class) {
            return [
                'id' => $class->id,
                'label' => $this->formatClassLabel($class),
            ];
        })->values();

        return response()->json(['classes' => $classOptions]);
    }

    private function buildAbsenteeismPayload(Collection $classIds, int $schoolYearId): array
    {
        $payloadStartedAt = microtime(true);
        $riskCalibrationMeta = [
            'method' => 'python_label_raw_probability',
            'display_range' => ['min' => 0.0, 'max' => 100.0],
            'thresholds' => [
                'high' => self::RISK_HIGH_THRESHOLD,
                'medium' => self::RISK_MEDIUM_THRESHOLD,
            ],
            'note' => 'Risk labels and percentages follow Python model output.',
        ];

        $pythonStartedAt = microtime(true);
        $predictClient = new PredictionClient;
        $featureTables = $predictClient->getFeatureTables();
        $pythonDurationMs = round((microtime(true) - $pythonStartedAt) * 1000, 1);

        if (! is_array($featureTables)) {
            Log::warning('Teacher absenteeism analytics could not fetch feature tables', [
                'school_year_id' => $schoolYearId,
                'class_ids' => $classIds->all(),
                'python_fetch_ms' => $pythonDurationMs,
            ]);

            return [
                'featureTables' => null,
                'recognitionTop5' => [],
                'honorsSummary' => $this->buildHonorsSummary([]),
                'interventionQueue' => [],
                'decliningTrendRows' => [],
                'riskCalibrationMeta' => $riskCalibrationMeta,
                'analyticsServiceRunning' => false,
                'analyticsGeneratedAt' => null,
            ];
        }

        $students = Student::whereHas('enrollments', function ($query) use ($classIds, $schoolYearId) {
            $query->whereIn('class_id', $classIds)->where('school_year_id', $schoolYearId);
        })->get(['id', 'first_name', 'last_name']);

        $filteredStudentIds = $students->pluck('id')->map(fn ($id) => (int) $id)->all();
        $filteredStudentIdSet = array_fill_keys($filteredStudentIds, true);
        $filteredStudentNameSet = array_fill_keys(
            $students
                ->map(fn ($student) => $this->normalizeNameToken($student->first_name.' '.$student->last_name))
                ->filter()
                ->all(),
            true
        );

        $featureTables = $this->filterFeatureTables($featureTables, $filteredStudentIdSet, $filteredStudentNameSet);
        $featureTables['table1']['data'] = $this->applyRiskCalibration($featureTables['table1']['data'] ?? []);
        $featureTables['table3']['data'] = $this->applyRiskCalibration($featureTables['table3']['data'] ?? []);

        $studentClassMap = $this->buildStudentClassMap($filteredStudentIds, $classIds, $schoolYearId);
        $honorsGradeCompletenessByStudentId = $this->buildHonorsGradeCompletenessMap(
            $filteredStudentIds,
            $studentClassMap,
            $classIds,
            $schoolYearId
        );
        $featureTables['table2']['data'] = $this->enrichEngagementRows(
            $featureTables['table2']['data'] ?? [],
            $honorsGradeCompletenessByStudentId
        );

        $recognitionTop5 = $this->buildRecognitionTopFive($featureTables['table2']['data']);
        $honorsSummary = $this->buildHonorsSummary($featureTables['table2']['data']);

        $decliningTrendRows = $this->buildDecliningTrendRows($featureTables['table3']['data'] ?? [], $studentClassMap);
        $interventionQueue = $this->buildInterventionQueue($featureTables['table3']['data'] ?? [], $studentClassMap);

        Log::debug('Teacher absenteeism analytics payload prepared', [
            'school_year_id' => $schoolYearId,
            'class_ids' => $classIds->all(),
            'students_count' => count($filteredStudentIds),
            'table1_rows' => count($featureTables['table1']['data'] ?? []),
            'table2_rows' => count($featureTables['table2']['data'] ?? []),
            'table3_rows' => count($featureTables['table3']['data'] ?? []),
            'intervention_rows' => count($interventionQueue),
            'declining_rows' => count($decliningTrendRows),
            'python_fetch_ms' => $pythonDurationMs,
            'payload_build_ms' => round((microtime(true) - $payloadStartedAt) * 1000, 1),
        ]);

        return [
            'featureTables' => $featureTables,
            'recognitionTop5' => $recognitionTop5,
            'honorsSummary' => $honorsSummary,
            'interventionQueue' => $interventionQueue,
            'decliningTrendRows' => $decliningTrendRows,
            'riskCalibrationMeta' => $riskCalibrationMeta,
            'analyticsServiceRunning' => true,
            'analyticsGeneratedAt' => now()->toIso8601String(),
        ];
    }

    private function buildAnalyticsServiceWarning(
        bool $analyticsServiceRunning,
        bool $cacheHit,
        array $analyticsPayload
    ): ?string {
        if ($analyticsServiceRunning) {
            return null;
        }

        $hasCachedPayload = $cacheHit && ! empty($analyticsPayload['analyticsGeneratedAt']);
        if ($hasCachedPayload) {
            return 'Analytics service is currently unavailable. Showing cached analytics from the latest successful request.';
        }

        return 'Analytics service is currently unavailable. Please start the analytics service and refresh this page.';
    }

    private function buildAnalyticsCacheKey(
        int $userId,
        int $schoolYearId,
        ?int $selectedGradeLevelId,
        ?int $selectedClassId
    ): string {
        return implode(':', [
            'teacher_absenteeism_analytics_v2',
            "user_{$userId}",
            "sy_{$schoolYearId}",
            'grade_'.($selectedGradeLevelId ?? 'all'),
            'class_'.($selectedClassId ?? 'all'),
        ]);
    }

    private function filterFeatureTables(array $featureTables, array $studentIdSet, array $studentNameSet): array
    {
        foreach (['table1', 'table2', 'table3'] as $tableKey) {
            $rows = $featureTables[$tableKey]['data'] ?? [];
            if (! is_array($rows)) {
                $featureTables[$tableKey]['data'] = [];

                continue;
            }

            $featureTables[$tableKey]['data'] = array_values(array_filter(
                $rows,
                function ($row) use ($studentIdSet, $studentNameSet) {
                    if (! is_array($row)) {
                        return false;
                    }

                    $studentId = isset($row['Student_ID']) ? (int) $row['Student_ID'] : 0;
                    $nameRaw = $row['Name'] ?? ($row['Student_Name'] ?? ($row['Student Name'] ?? ''));
                    $nameToken = $this->normalizeNameToken((string) $nameRaw);

                    return ($studentId > 0 && isset($studentIdSet[$studentId]))
                        || ($nameToken !== '' && isset($studentNameSet[$nameToken]));
                }
            ));
        }

        return $featureTables;
    }

    private function applyRiskCalibration(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        foreach ($rows as $index => $row) {
            $rawRisk = $this->toFloat($row['Prob_HighRisk_pct'] ?? null, 1) ?? 0.0;
            $normalizedLabel = $this->normalizeRiskLabel($row['Risk_Label'] ?? null, $rawRisk);

            $rows[$index]['raw_prob_highrisk_pct'] = round($rawRisk, 1);
            $rows[$index]['display_prob_highrisk_pct'] = round($rawRisk, 1);
            $rows[$index]['Display_Risk_Label'] = $normalizedLabel;
            $rows[$index]['Display_Risk_Category'] = strtolower($normalizedLabel);
        }

        return $rows;
    }

    private function normalizeRiskLabel(?string $riskLabel, float $rawRisk): string
    {
        $normalized = strtolower(trim((string) $riskLabel));
        if ($normalized === 'high') {
            return 'High';
        }

        if (in_array($normalized, ['mid', 'medium', 'moderate'], true)) {
            return 'Medium';
        }

        if ($normalized === 'low') {
            return 'Low';
        }

        // Fallback if Python response does not provide a label.
        if ($rawRisk >= self::RISK_HIGH_THRESHOLD) {
            return 'High';
        }

        if ($rawRisk >= self::RISK_MEDIUM_THRESHOLD) {
            return 'Medium';
        }

        return 'Low';
    }

    private function enrichEngagementRows(array $rows, array $honorsGradeCompletenessByStudentId = []): array
    {
        $enriched = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $studentId = isset($row['Student_ID']) ? (int) $row['Student_ID'] : 0;
            $engagementScore = $this->toFloat($row['EngagementScore'] ?? null, 2) ?? 0.0;
            $performancePercentage = $this->toFloat($row['PerformancePercentage'] ?? null, 1) ?? 0.0;
            $attendancePercentage = $this->toFloat($row['AttendancePercentage'] ?? null, 1) ?? 0.0;
            $hasCompleteQuarterGrades = $studentId > 0
                && (bool) ($honorsGradeCompletenessByStudentId[$studentId] ?? false);

            $row['EngagementScore'] = $engagementScore;
            $row['PerformancePercentage'] = $performancePercentage;
            $row['AttendancePercentage'] = $attendancePercentage;
            $row['HasCompleteQuarterGrades'] = $hasCompleteQuarterGrades;
            $row['HonorsClassification'] = $this->classifyHonorsEligibility(
                $performancePercentage,
                $attendancePercentage,
                $hasCompleteQuarterGrades
            );

            $enriched[] = $row;
        }

        return $enriched;
    }

    private function classifyHonorsEligibility(
        float $performancePercentage,
        float $attendancePercentage,
        bool $hasCompleteQuarterGrades
    ): string {
        if (! $hasCompleteQuarterGrades) {
            return 'Regular';
        }

        if ($performancePercentage >= 95.0 && $attendancePercentage >= 95.0) {
            return 'With High Honors';
        }

        if ($performancePercentage >= 90.0 && $attendancePercentage >= 95.0) {
            return 'With Honors';
        }

        return 'Regular';
    }

    private function buildHonorsSummary(array $engagementRows): array
    {
        $withHighHonorsRows = array_values(array_filter(
            $engagementRows,
            fn ($row) => ($row['HonorsClassification'] ?? null) === 'With High Honors'
        ));
        $withHonorsRows = array_values(array_filter(
            $engagementRows,
            fn ($row) => ($row['HonorsClassification'] ?? null) === 'With Honors'
        ));
        $regularRows = array_values(array_filter(
            $engagementRows,
            fn ($row) => ($row['HonorsClassification'] ?? null) === 'Regular'
        ));

        return [
            'with_high_honors_count' => count($withHighHonorsRows),
            'with_honors_count' => count($withHonorsRows),
            'regular_count' => count($regularRows),
            'with_high_honors_rows' => $withHighHonorsRows,
            'with_honors_rows' => $withHonorsRows,
            'regular_rows' => $regularRows,
        ];
    }

    private function buildRecognitionTopFive(array $engagementRows): array
    {
        usort($engagementRows, function ($a, $b) {
            $engagementCmp = (($b['EngagementScore'] ?? 0.0) <=> ($a['EngagementScore'] ?? 0.0));
            if ($engagementCmp !== 0) {
                return $engagementCmp;
            }

            $performanceCmp = (($b['PerformancePercentage'] ?? 0.0) <=> ($a['PerformancePercentage'] ?? 0.0));
            if ($performanceCmp !== 0) {
                return $performanceCmp;
            }

            return strcmp((string) ($a['Name'] ?? ''), (string) ($b['Name'] ?? ''));
        });

        return array_values(array_slice($engagementRows, 0, 5));
    }

    private function buildHonorsGradeCompletenessMap(
        array $studentIds,
        array $studentClassMap,
        Collection $classIds,
        int $schoolYearId
    ): array {
        if (empty($studentIds) || $classIds->isEmpty()) {
            return [];
        }

        $schoolYear = SchoolYear::find($schoolYearId);
        $activeQuarterNumber = (int) ($schoolYear?->currentQuarter()?->quarter ?? 0);
        if ($activeQuarterNumber < 1) {
            return array_fill_keys($studentIds, false);
        }

        $expectedSubjectCountByClassId = $this->buildExpectedSubjectCountByClass($classIds);
        $quarterValues = $this->quarterSearchValues($activeQuarterNumber);

        $gradedSubjectCountsByStudentId = Grade::query()
            ->selectRaw('student_id, COUNT(DISTINCT subject_id) as graded_subject_count')
            ->whereIn('student_id', $studentIds)
            ->where('school_year_id', $schoolYearId)
            ->whereIn('quarter', $quarterValues)
            ->groupBy('student_id')
            ->pluck('graded_subject_count', 'student_id')
            ->map(fn ($count) => (int) $count)
            ->all();

        $result = [];
        foreach ($studentIds as $studentId) {
            $classId = $studentClassMap[$studentId]['class_id'] ?? null;
            $expectedCount = $classId ? (int) ($expectedSubjectCountByClassId[$classId] ?? 0) : 0;
            $gradedCount = (int) ($gradedSubjectCountsByStudentId[$studentId] ?? 0);

            $result[$studentId] = $expectedCount > 0 && $gradedCount >= $expectedCount;
        }

        return $result;
    }

    private function buildExpectedSubjectCountByClass(Collection $classIds): array
    {
        if ($classIds->isEmpty()) {
            return [];
        }

        $countsByClassId = Schedule::query()
            ->whereIn('class_id', $classIds->all())
            ->selectRaw('class_id, COUNT(DISTINCT subject_id) as subject_count')
            ->groupBy('class_id')
            ->pluck('subject_count', 'class_id')
            ->map(fn ($count) => (int) $count)
            ->all();

        $missingClassIds = $classIds
            ->filter(fn ($classId) => ! isset($countsByClassId[(int) $classId]))
            ->map(fn ($classId) => (int) $classId)
            ->values()
            ->all();

        if (empty($missingClassIds)) {
            return $countsByClassId;
        }

        $classRows = Classes::query()
            ->whereIn('id', $missingClassIds)
            ->with('section:id,grade_level_id')
            ->get(['id', 'section_id']);

        $gradeLevelIds = $classRows
            ->map(fn ($class) => (int) (optional($class->section)->grade_level_id ?? 0))
            ->filter(fn ($gradeLevelId) => $gradeLevelId > 0)
            ->unique()
            ->values()
            ->all();

        $subjectCountsByGradeLevelId = Subject::query()
            ->whereIn('grade_level_id', $gradeLevelIds)
            ->where('is_active', true)
            ->selectRaw('grade_level_id, COUNT(DISTINCT id) as subject_count')
            ->groupBy('grade_level_id')
            ->pluck('subject_count', 'grade_level_id')
            ->map(fn ($count) => (int) $count)
            ->all();

        foreach ($classRows as $classRow) {
            $classId = (int) $classRow->id;
            $gradeLevelId = (int) (optional($classRow->section)->grade_level_id ?? 0);
            $countsByClassId[$classId] = (int) ($subjectCountsByGradeLevelId[$gradeLevelId] ?? 0);
        }

        return $countsByClassId;
    }

    private function quarterSearchValues(int $quarterNumber): array
    {
        $quarterNumber = max(1, min(4, $quarterNumber));

        $labelMap = [
            1 => ['1', 'Q1', '1ST QUARTER', 'FIRST QUARTER'],
            2 => ['2', 'Q2', '2ND QUARTER', 'SECOND QUARTER'],
            3 => ['3', 'Q3', '3RD QUARTER', 'THIRD QUARTER'],
            4 => ['4', 'Q4', '4TH QUARTER', 'FOURTH QUARTER'],
        ];

        return $labelMap[$quarterNumber];
    }

    private function buildStudentClassMap(array $studentIds, Collection $classIds, int $schoolYearId): array
    {
        if (empty($studentIds) || $classIds->isEmpty()) {
            return [];
        }

        $map = [];
        $enrollments = Enrollment::with('class.section')
            ->whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('school_year_id', $schoolYearId)
            ->get(['student_id', 'class_id']);

        foreach ($enrollments as $enrollment) {
            $studentId = (int) $enrollment->student_id;
            if (isset($map[$studentId])) {
                continue;
            }

            $classId = (int) $enrollment->class_id;
            $sectionName = optional(optional($enrollment->class)->section)->name;
            $map[$studentId] = [
                'class_id' => $classId,
                'class_label' => $sectionName ?: ('Class #'.$classId),
            ];
        }

        return $map;
    }

    private function buildDecliningTrendRows(array $table3Rows, array $studentClassMap): array
    {
        $rows = [];
        foreach ($table3Rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $attCurrent = $this->toFloat($row['Att_Current'] ?? null, 1);
            $attPast1 = $this->toFloat($row['Att_Past1'] ?? null, 1);
            if ($attCurrent === null || $attPast1 === null) {
                continue;
            }

            $drop = round($attPast1 - $attCurrent, 1);
            if ($drop < self::ATTENDANCE_DROP_WARNING) {
                continue;
            }

            $studentId = isset($row['Student_ID']) ? (int) $row['Student_ID'] : 0;
            $displayRisk = $this->toFloat($row['display_prob_highrisk_pct'] ?? null, 1);
            $severity = $drop >= self::ATTENDANCE_DROP_CRITICAL ? 'critical' : 'warning';
            $classData = $studentClassMap[$studentId] ?? ['class_id' => null, 'class_label' => 'N/A'];

            $rows[] = [
                'student_id' => $studentId,
                'name' => $row['Name'] ?? 'N/A',
                'class_id' => $classData['class_id'],
                'class_label' => $classData['class_label'],
                'att_current' => $attCurrent,
                'att_past1' => $attPast1,
                'attendance_drop' => $drop,
                'severity' => $severity,
                'risk_display_pct' => $displayRisk,
                'weighted_trend' => $this->toFloat($row['Weighted_Trend'] ?? null, 1),
                'performance_trend' => $this->toFloat($row['Performance_Trend'] ?? null, 1),
            ];
        }

        usort($rows, function ($a, $b) {
            $severityOrder = ['critical' => 0, 'warning' => 1];
            $severityCmp = ($severityOrder[$a['severity']] ?? 99) <=> ($severityOrder[$b['severity']] ?? 99);
            if ($severityCmp !== 0) {
                return $severityCmp;
            }

            $dropCmp = ($b['attendance_drop'] ?? 0) <=> ($a['attendance_drop'] ?? 0);
            if ($dropCmp !== 0) {
                return $dropCmp;
            }

            return ($b['risk_display_pct'] ?? 0) <=> ($a['risk_display_pct'] ?? 0);
        });

        return $rows;
    }

    private function buildInterventionQueue(array $table3Rows, array $studentClassMap): array
    {
        $queue = [];
        foreach ($table3Rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $studentId = isset($row['Student_ID']) ? (int) $row['Student_ID'] : 0;
            $displayRisk = $this->toFloat($row['display_prob_highrisk_pct'] ?? null, 1);
            $displayRiskLabel = $this->normalizeRiskLabel(
                $row['Display_Risk_Label'] ?? ($row['Risk_Label'] ?? null),
                $displayRisk ?? 0.0
            );
            $attCurrent = $this->toFloat($row['Att_Current'] ?? null, 1);
            $attPast1 = $this->toFloat($row['Att_Past1'] ?? null, 1);
            $drop = ($attCurrent !== null && $attPast1 !== null) ? round($attPast1 - $attCurrent, 1) : null;

            $reasonTags = [];
            if ($drop !== null && $drop >= self::ATTENDANCE_DROP_WARNING) {
                $reasonTags[] = "Attendance dropped by {$drop} points vs previous month";
            }
            if ($displayRiskLabel === 'High') {
                $reasonTags[] = "Python model risk label is High ({$displayRisk}%)";
            }

            if (empty($reasonTags)) {
                continue;
            }

            $severity = 'warning';
            if (($drop !== null && $drop >= self::ATTENDANCE_DROP_CRITICAL) || $displayRiskLabel === 'High') {
                $severity = 'critical';
            }

            $classData = $studentClassMap[$studentId] ?? ['class_id' => null, 'class_label' => 'N/A'];
            $queue[] = [
                'student_id' => $studentId,
                'name' => $row['Name'] ?? 'N/A',
                'class_id' => $classData['class_id'],
                'class_label' => $classData['class_label'],
                'severity' => $severity,
                'reason_tags' => $reasonTags,
                'attendance_drop' => $drop,
                'risk_display_pct' => $displayRisk,
                'recommended_action' => $severity === 'critical'
                    ? 'Schedule counseling and guardian contact within this week.'
                    : 'Set a monitoring check-in and attendance follow-up.',
            ];
        }

        usort($queue, function ($a, $b) {
            $severityOrder = ['critical' => 0, 'warning' => 1];
            $severityCmp = ($severityOrder[$a['severity']] ?? 99) <=> ($severityOrder[$b['severity']] ?? 99);
            if ($severityCmp !== 0) {
                return $severityCmp;
            }

            $dropCmp = ($b['attendance_drop'] ?? 0) <=> ($a['attendance_drop'] ?? 0);
            if ($dropCmp !== 0) {
                return $dropCmp;
            }

            return ($b['risk_display_pct'] ?? 0) <=> ($a['risk_display_pct'] ?? 0);
        });

        return $queue;
    }

    private function normalizeNameToken(?string $value): string
    {
        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    private function toFloat(mixed $value, ?int $precision = null): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $numeric = (float) $value;
        if ($precision !== null) {
            return round($numeric, $precision);
        }

        return $numeric;
    }

    private function getAccessibleClassIds(SchoolYear $activeSchoolYear, bool $isTeacherRole, ?Teacher $teacher): Collection
    {
        if ($isTeacherRole) {
            if (! $teacher) {
                // Teacher-role users without linked teacher profile should not see all classes.
                return collect();
            }

            // Return only advisory classes for teachers (where teacher_id = this teacher).
            return Classes::where('teacher_id', $teacher->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->pluck('id')
                ->unique()
                ->values();
        }

        // Non-teacher roles (e.g. admin) can access all classes for the active school year.
        return Classes::where('school_year_id', $activeSchoolYear->id)
            ->pluck('id')
            ->unique()
            ->values();
    }

    private function formatClassLabel(Classes $class): string
    {
        if ($class->section && $class->section->name) {
            return $class->section->name;
        }

        return 'Class #'.$class->id;
    }

    private function formatGradeLabel(?GradeLevel $grade): string
    {
        if (! $grade) {
            return 'Unassigned Grade';
        }

        $levelPart = $grade->level ? 'Grade '.$grade->level : null;
        $name = trim((string) $grade->name);

        if ($name && $levelPart) {
            // Avoid repeating "Grade" when the name already includes it
            return stripos($name, 'grade') !== false ? $name : $levelPart.' • '.$name;
        }

        return $name ?: ($levelPart ?? 'Grade '.$grade->id);
    }
}
