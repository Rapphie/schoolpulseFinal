<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\StudentFeaturesService;
use App\Services\PredictionClient;

class AnalyticsController extends Controller
{
    /**
     * Display absenteeism analytics for the teacher.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function absenteeismAnalytics(Request $request)
    {
        $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();

        // Determine role/scope: teacher sees own classes; admin sees all classes
        $teacher = Teacher::where('user_id', Auth::id())->first();
        $isTeacher = (bool) $teacher;

        $availableClassIds = $this->getAccessibleClassIds($activeSchoolYear, $teacher);

        $selectedGradeLevelId = $request->query('grade_level_id');
        if ($selectedGradeLevelId !== null && $selectedGradeLevelId !== '') {
            $selectedGradeLevelId = (int)$selectedGradeLevelId;
        } else {
            $selectedGradeLevelId = null;
        }

        $gradeLevels = Classes::whereIn('id', $availableClassIds)
            ->with(['section.gradeLevel'])
            ->get()
            ->map(function ($class) {
                $grade = optional($class->section)->gradeLevel;
                if (!$grade) {
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
            ->sortBy(fn($item) => $item['level'] ?? $item['label'])
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
        if ($selectedClassId && $gradeFilteredClassIds->contains((int)$selectedClassId)) {
            $classIds = collect([(int)$selectedClassId]);
        } else {
            $classIds = $gradeFilteredClassIds;
            $selectedClassId = null; // ignore invalid
        }

        // --- 1. Monthly Attendance Percentage Trend ---
        $monthlyTrendQuery = Attendance::whereIn('class_id', $classIds);
        if ($isTeacher) {
            $monthlyTrendQuery->where('teacher_id', $teacher->id);
        }
        $monthlyTrend = $monthlyTrendQuery
            ->select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("COUNT(*) as total_count")
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->mapWithKeys(function ($item) {
                $percentage = ($item->total_count > 0) ? ($item->present_count / $item->total_count) * 100 : 0;
                return [\Carbon\Carbon::parse($item->month)->format('M Y') => round($percentage, 2)];
            });

        // --- 2. Absences by Subject ---
        $absencesBySubject = Subject::whereHas('schedules', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })
            ->withCount(['attendances' => function ($query) use ($classIds, $isTeacher, $teacher) {
                $query->where('status', 'absent')
                    ->whereIn('class_id', $classIds);
                if ($isTeacher) {
                    $query->where('teacher_id', $teacher->id);
                }
            }])
            ->get()
            ->mapWithKeys(function ($subject) {
                return [$subject->name => $subject->attendances_count];
            });


        // --- 3. Students with Highest Absence Rates ---
        $topAbsentees = Student::whereHas('enrollments', function ($query) use ($classIds) {
            $query->whereIn('class_id', $classIds);
        })
            ->withCount(['attendances as absent_count' => function ($query) use ($isTeacher, $teacher) {
                $query->where('status', 'absent');
                if ($isTeacher) {
                    $query->where('teacher_id', $teacher->id);
                }
            }])
            ->orderBy('absent_count', 'desc')
            ->take(10) // Get the top 10
            ->get();

        // --- 4. Predictions & engagement metrics by class/student ---
        $classPredictions = [];
        $featuresService = new StudentFeaturesService();
        $predictClient = new PredictionClient();
        $refDate = Carbon::now();
        $syId = $activeSchoolYear->id;
        $studentMetrics = [];

        // Load classes with minimal info
        // Classes to compute predictions for (filtered if selected)
        $classes = Classes::whereIn('id', $classIds)->with(['section.gradeLevel'])->get();

        foreach ($classes as $class) {
            $classLabel = $this->formatClassLabel($class);
            $gradeLevelId = optional($class->section)->grade_level_id;
            // Students enrolled in this class for the active year
            $students = Student::whereHas('enrollments', function ($q) use ($class, $syId) {
                $q->where('class_id', $class->id)->where('school_year_id', $syId);
            })->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'lrn']);

            if ($students->isEmpty()) {
                $classPredictions[$class->id] = [
                    'class' => $class,
                    'label' => $classLabel,
                    'students' => [],
                ];
                continue;
            }

            // Build batch of feature vectors in the expected order
            $batch = [];
            $featureCache = [];
            foreach ($students as $stu) {
                $vec = $featuresService->computeFeaturesVector($stu->id, $syId, $refDate);
                $batch[] = $vec['ordered'];
                $featureCache[$stu->id] = $vec['named'];
            }

            $confidences = $predictClient->predictBatch($batch); // returns array of percentages

            // Attach predictions back to students
            $studentsWithPred = [];
            foreach ($students as $idx => $stu) {
                $studentId = $stu->id;
                $namedFeatures = $featureCache[$studentId] ?? [];
                $prediction = $confidences[$idx] ?? null;
                $engagementScore = $featuresService->calculateEngagementScore($namedFeatures);

                $studentsWithPred[] = [
                    'id' => $studentId,
                    'name' => $stu->last_name . ', ' . $stu->first_name,
                    'lrn' => $stu->lrn,
                    'prediction_confidence' => $prediction,
                    'engagement_score' => $engagementScore,
                ];

                $studentMetrics[$studentId] = [
                    'id' => $studentId,
                    'name' => $stu->last_name . ', ' . $stu->first_name,
                    'lrn' => $stu->lrn,
                    'class_label' => $classLabel,
                    'class_id' => $class->id,
                    'grade_level_id' => $gradeLevelId,
                    'prediction_confidence' => $prediction,
                    'engagement_score' => $engagementScore,
                    'monthly_unexcused_absences' => $namedFeatures['monthly_unexcused_absences'] ?? 0,
                ];
            }

            // Sort by prediction desc (nulls last)
            usort($studentsWithPred, function ($a, $b) {
                if ($a['prediction_confidence'] === null) return 1;
                if ($b['prediction_confidence'] === null) return -1;
                return $b['prediction_confidence'] <=> $a['prediction_confidence'];
            });

            $classPredictions[$class->id] = [
                'class' => $class,
                'label' => $classLabel,
                'grade_level_id' => $gradeLevelId,
                'students' => $studentsWithPred,
            ];
        }

        // --- Derived engagement + risk datasets ---
        $studentMetricsCollection = collect($studentMetrics);
        $engagementCollection = $studentMetricsCollection->filter(fn($item) => $item['engagement_score'] !== null);
        $engagementRanking = $engagementCollection->sortByDesc('engagement_score')->values();

        foreach ($engagementRanking as $index => $entry) {
            $studentMetrics[$entry['id']]['engagement_rank'] = $index + 1;
        }

        foreach ($classPredictions as &$bundle) {
            foreach ($bundle['students'] as &$stu) {
                $stuId = $stu['id'];
                if (isset($studentMetrics[$stuId]['engagement_rank'])) {
                    $stu['engagement_rank'] = $studentMetrics[$stuId]['engagement_rank'];
                }
                if (isset($studentMetrics[$stuId]['engagement_score'])) {
                    $stu['engagement_score'] = $studentMetrics[$stuId]['engagement_score'];
                }
            }
        }
        unset($bundle, $stu);

        $engagementSummary = [
            'average' => $engagementCollection->avg('engagement_score'),
            'high_count' => $engagementCollection->filter(fn($item) => $item['engagement_score'] >= 80)->count(),
            'total_students' => $engagementCollection->count(),
            'top_student' => optional($engagementRanking->first(), function ($entry) {
                return [
                    'name' => $entry['name'],
                    'score' => $entry['engagement_score'],
                    'class_label' => $entry['class_label'] ?? null,
                ];
            }),
        ];

        $engagementTop = $engagementRanking->take(5);
        $engagementBottom = $engagementCollection->sortBy('engagement_score')->values()->take(5);

        $highRiskThreshold = 5;
        $highRiskStudents = $studentMetricsCollection
            ->filter(fn($item) => ($item['monthly_unexcused_absences'] ?? 0) >= $highRiskThreshold)
            ->sortByDesc('monthly_unexcused_absences')
            ->values();

        $predictiveHighRisk = $studentMetricsCollection
            ->filter(fn($item) => ($item['prediction_confidence'] ?? 0) >= 70)
            ->sortByDesc('prediction_confidence')
            ->values();

        $riskSummary = [
            'good' => $studentMetricsCollection
                ->filter(fn($item) => !is_null($item['prediction_confidence']) && $item['prediction_confidence'] < 40)
                ->count(),
            'medium' => $studentMetricsCollection
                ->filter(fn($item) => !is_null($item['prediction_confidence']) && $item['prediction_confidence'] >= 40 && $item['prediction_confidence'] < 70)
                ->count(),
        ];

        return view('teacher.analytics.absenteeism', compact(
            'monthlyTrend',
            'absencesBySubject',
            'topAbsentees',
            'classPredictions',
            'classesForSelect',
            'selectedClassId',
            'studentMetrics',
            'engagementSummary',
            'engagementTop',
            'engagementBottom',
            'highRiskStudents',
            'predictiveHighRisk',
            'riskSummary',
            'highRiskThreshold',
            'gradeLevels',
            'selectedGradeLevelId'
        ));
    }

    public function classesByGrade(Request $request)
    {
        $gradeLevelId = (int)$request->query('grade_level_id');

        if (!$gradeLevelId) {
            return response()->json(['classes' => []]);
        }

        $activeSchoolYear = SchoolYear::where('is_active', true)->firstOrFail();
        $teacher = Teacher::where('user_id', Auth::id())->first();

        $availableClassIds = $this->getAccessibleClassIds($activeSchoolYear, $teacher);

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

    private function getAccessibleClassIds(SchoolYear $activeSchoolYear, ?Teacher $teacher)
    {
        if ($teacher) {
            return Schedule::where('teacher_id', $teacher->id)
                ->whereHas('class', function ($query) use ($activeSchoolYear) {
                    $query->where('school_year_id', $activeSchoolYear->id);
                })
                ->pluck('class_id')
                ->unique()
                ->values();
        }

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

        return 'Class #' . $class->id;
    }

    private function formatGradeLabel(?GradeLevel $grade): string
    {
        if (!$grade) {
            return 'Unassigned Grade';
        }

        $levelPart = $grade->level ? 'Grade ' . $grade->level : null;
        $name = trim((string) $grade->name);

        if ($name && $levelPart) {
            // Avoid repeating "Grade" when the name already includes it
            return stripos($name, 'grade') !== false ? $name : $levelPart . ' • ' . $name;
        }

        return $name ?: ($levelPart ?? 'Grade ' . $grade->id);
    }
}
