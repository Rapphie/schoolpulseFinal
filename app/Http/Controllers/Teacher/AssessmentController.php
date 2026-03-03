<?php

namespace App\Http\Controllers\Teacher;

use App\Exports\AssessmentClassRecordExport;
use App\Exports\AssessmentClassRecordWorkbookExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ExportClassRecordRequest;
use App\Http\Requests\Teacher\SaveGradesRequest;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classes;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\QuarterLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssessmentController extends Controller
{
    public function __construct(private QuarterLockService $quarterLockService) {}

    private const DEFAULT_ASSESSMENT_COUNTS = [
        'written_works' => 10,
        'performance_tasks' => 10,
        'quarterly_assessments' => 1,
    ];

    private const ASSESSMENT_TYPE_WEIGHTS = [
        'written_works' => 0.20,
        'performance_tasks' => 0.60,
        'quarterly_assessments' => 0.20,
    ];

    private const ASSESSMENT_TYPE_LABELS = [
        'written_works' => 'WRITTEN WORKS',
        'performance_tasks' => 'PERFORMANCE TASKS',
        'quarterly_assessments' => 'QUARTERLY ASSESSMENT',
    ];

    /**
     * Show grade management interface for a specific class.
     */
    public function index(Classes $class, Request $request)
    {
        $class->load('section.gradeLevel');
        $teacher = Auth::user()->teacher;

        if (! $teacher) {
            abort(403, 'You must be a registered teacher to manage assessments.');
        }

        $activeSchoolYear = SchoolYear::active()->first();

        $subjectsQuery = $teacher->schedules()
            ->where('class_id', $class->id);

        if ($activeSchoolYear) {
            $subjectsQuery->whereHas('class', function ($query) use ($activeSchoolYear) {
                $query->where('school_year_id', $activeSchoolYear->id);
            });
        }

        $subjects = $subjectsQuery
            ->with('subject')
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->values();

        if ($subjects->isEmpty()) {
            return redirect()
                ->route('teacher.assessments.list')
                ->with('error', 'You do not handle any subject for this class in the active school year.');
        }

        $quarterLockContext = $this->quarterLockService->contextForSchoolYear((int) $class->school_year_id);
        $activeQuarter = $quarterLockContext['activeQuarter'];
        $quarterLocks = $quarterLockContext['quarterLocks'];

        if ($request->has('subject_id') && $request->subject_id) {
            $selectedSubject = $subjects->firstWhere('id', (int) $request->subject_id);
        } else {
            $selectedSubject = $subjects->first();
        }

        if (! $selectedSubject) {
            return redirect()
                ->route('teacher.assessments.list')
                ->with('error', 'You do not handle any subject for this class in the active school year.');
        }

        $this->ensureDefaultAssessments($class, $selectedSubject, $teacher);

        // Get all students enrolled in this class
        $students = $class->students()->orderBy('last_name')->orderBy('first_name')->get();

        // Get all assessments for the selected subject grouped by quarter and type
        // Include oral_participation from any teacher so OP is consistent across all quarters/subjects
        $assessments = $class->assessments()
            ->where('subject_id', $selectedSubject->id)
            ->where(function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id)
                    ->orWhere('type', 'oral_participation');
            })
            ->with('scores')
            ->orderBy('quarter')
            ->orderBy('type')
            ->orderBy('assessment_date')
            ->get()
            ->groupBy(['quarter', 'type']);

        // Merge and Consolidate 'oral_participation' type into 'performance_tasks'
        foreach ($assessments as $quarter => $types) {
            $ops = $types->get('oral_participation', collect());
            $pts = $types->get('performance_tasks', collect());

            if ($ops->isNotEmpty()) {
                // Consolidate multiple OP sessions into one virtual assessment for the grade sheet
                $consolidatedOP = new Assessment;
                $consolidatedOP->id = -999; // Unique virtual ID to identify consolidated OP
                $consolidatedOP->name = 'Oral Participation';
                $consolidatedOP->type = 'oral_participation';
                $consolidatedOP->max_score = $ops->sum('max_score');
                $consolidatedOP->quarter = $quarter;
                $consolidatedOP->class_id = $class->id;
                $consolidatedOP->subject_id = $selectedSubject->id;

                // Prepend the consolidated OP to PTs
                $pts = collect([$consolidatedOP])->merge($pts);
                $types->put('performance_tasks', $pts);
            }
        }

        // Organize data by student
        $studentsData = $students->map(function ($student) use ($assessments, $class) {
            $studentData = [
                'student' => $student,
                'quarters' => [],
            ];

            // Process each quarter (1-4)
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $quarterAssessments = $assessments->get($quarter, collect());

                $quarterData = [
                    'written_works' => [],
                    'performance_tasks' => [],
                    'quarterly_assessments' => [],
                ];

                // Get scores for each assessment type
                foreach (['written_works', 'performance_tasks', 'quarterly_assessments'] as $type) {
                    $typeAssessments = $quarterAssessments->get($type, collect());

                    foreach ($typeAssessments as $assessment) {
                        $scoreValue = null;
                        $maxScoreValue = $assessment->max_score;

                        if ($assessment->id === -999) {
                            // Consolidated Oral Participation logic: sum up scores from all OP sessions
                            $quarterOps = $assessments->get($quarter, collect())->get('oral_participation', collect());
                            $totalScore = 0;
                            $hasAnyScore = false;

                            foreach ($quarterOps as $op) {
                                $profile = $student->profileFor($class->school_year_id);
                                $s = null;
                                if ($profile) {
                                    $s = $op->scores->firstWhere('student_profile_id', $profile->id);
                                }
                                if (! $s) {
                                    $s = $op->scores->firstWhere('student_id', $student->id);
                                }

                                if ($s) {
                                    $totalScore += $s->score;
                                    $hasAnyScore = true;
                                }
                            }
                            if ($hasAnyScore) {
                                $scoreValue = $totalScore;
                            }
                        } else {
                            // Normal assessment logic
                            $profile = $student->profileFor($class->school_year_id);
                            $score = null;
                            if ($profile) {
                                $score = $assessment->scores->firstWhere('student_profile_id', $profile->id);
                            }
                            if (! $score) {
                                $score = $assessment->scores->firstWhere('student_id', $student->id);
                            }
                            $scoreValue = $score ? $score->score : null;
                        }

                        $quarterData[$type][] = [
                            'assessment' => $assessment,
                            'score' => $scoreValue,
                            'max_score' => $maxScoreValue,
                            'percentage' => $scoreValue !== null && $maxScoreValue > 0
                                ? ($scoreValue / $maxScoreValue) * 100
                                : null,
                        ];
                    }
                }

                $studentData['quarters'][$quarter] = $quarterData;
            }

            return $studentData;
        });

        $highlightStudentId = $request->filled('highlight_student')
            ? (int) $request->input('highlight_student')
            : null;

        return view('teacher.assessments.index', [
            'class' => $class,
            'subjects' => $subjects,
            'selectedSubject' => $selectedSubject,
            'studentsData' => $studentsData,
            'assessments' => $assessments,
            'highlightStudentId' => $highlightStudentId,
            'fixedAssessmentCounts' => self::DEFAULT_ASSESSMENT_COUNTS,
            'assessmentTypeWeights' => self::ASSESSMENT_TYPE_WEIGHTS,
            'assessmentTypeLabels' => self::ASSESSMENT_TYPE_LABELS,
            'activeQuarter' => $activeQuarter,
            'quarterLocks' => $quarterLocks,
        ]);
    }

    public function create(Classes $class)
    {
        // Get subjects assigned to this class via schedules
        $subjects = $class->schedules()->with('subject')->get()->pluck('subject')->unique();

        return view('teacher.assessments.create', compact('class', 'subjects'));
    }

    public function list()
    {
        $teacher = Auth::user()->teacher;

        if (! $teacher) {
            abort(403, 'You must be a registered teacher to view assessments.');
        }

        $activeSchoolYear = SchoolYear::active()->first();

        if (! $activeSchoolYear) {
            return view('teacher.classes')->with('error', 'No active school year has been set.');
        }

        $scheduledClassIds = $teacher->schedules()
            ->whereHas('class', fn ($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->pluck('class_id')
            ->unique();

        $classes = Classes::whereIn('id', $scheduledClassIds)
            ->with([
                'section.gradeLevel',
                'teacher.user',
                'enrollments',
                'schedules' => function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id)->with('subject');
                },
            ])
            ->get()
            ->sortBy('section.gradeLevel.level')
            ->values();

        return view('teacher.assessments.list', compact('classes', 'teacher'));
    }

    /**
     * Store a new assessment in the database.
     */
    public function store(Request $request, Classes $class)
    {
        $userId = Auth::id();
        $teacher = Teacher::where('user_id', $userId)->first();
        // Check if the authenticated user is actually a teacher
        if (! $teacher) {
            return redirect()->back()->with('error', 'You must be a registered teacher to create assessments.');
        }
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:quiz,exam,assignment,project,performance_task',
            'max_score' => 'nullable|numeric|min:1|max:1000',
            'quarter' => 'required|integer|in:1,2,3,4',
            'assessment_date' => 'required|date',
        ]);

        $class->assessments()->create([
            'subject_id' => $request->subject_id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $class->school_year_id,
            'name' => $request->name,
            'type' => $request->type,
            'max_score' => $request->max_score,
            'quarter' => $request->quarter,
            'assessment_date' => $request->assessment_date,
        ]);

        return redirect()->route('teacher.assessments.index', $class)->with('success', 'Assessment created successfully.');
    }

    /**
     * Show the page for editing scores for an assessment.
     */
    public function editScores(Classes $class, Assessment $assessment)
    {
        // Build lookup of scores for this assessment (both by student_profile_id and student_id)
        $scores = AssessmentScore::where('assessment_id', $assessment->id)->get();
        $scoresByProfile = $scores->whereNotNull('student_profile_id')->keyBy('student_profile_id');
        $scoresByStudent = $scores->whereNotNull('student_id')->keyBy('student_id');

        $students = $class->students()->orderBy('last_name')->orderBy('first_name')->get()->map(function ($student) use ($scoresByProfile, $scoresByStudent, $class) {
            $profile = $student->profileFor($class->school_year_id);
            $score = null;
            if ($profile) {
                $score = $scoresByProfile->get($profile->id);
            }
            if (! $score) {
                $score = $scoresByStudent->get($student->id);
            }

            // Provide a collection compatible with previous view expectations
            $student->setRelation('assessmentScores', $score ? collect([$score]) : collect());

            return $student;
        });

        return view('teacher.assessments.edit', compact('class', 'assessment', 'students', 'scores'));
    }

    /**
     * Update or create scores for students.
     */
    public function updateScores(Request $request, Classes $class, Assessment $assessment)
    {
        if ($assessment->max_score === null) {
            return redirect()->back()->with('error', 'Please set the maximum score (total items) for this assessment before entering scores.');
        }

        // Validate the incoming scores data
        $rules = [
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:students,id',
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:'.$assessment->max_score],
            'scores.*.remarks' => 'nullable|string|max:255',
        ];

        $messages = [
            'scores.*.score.max' => 'The score for a student cannot exceed the maximum score of '.$assessment->max_score.'.',
        ];

        $request->validate($rules, $messages);

        foreach ($request->scores as $studentId => $data) {
            $score = $data['score'] ?? null;
            $remarks = $data['remarks'] ?? null;

            $student = Student::find($studentId);
            $profile = $student ? $student->profileFor($assessment->school_year_id) : null;

            // If both score and remarks are empty, delete any existing record (by student_id or student_profile_id)
            if (is_null($score) && is_null($remarks)) {
                AssessmentScore::where('assessment_id', $assessment->id)
                    ->where(function ($q) use ($studentId, $profile) {
                        $q->where('student_id', $studentId);
                        if ($profile) {
                            $q->orWhere('student_profile_id', $profile->id);
                        }
                    })->delete();

                continue; // Move to the next student
            }

            // Build match keys: prefer matching by student_profile_id when available
            if ($profile) {
                $match = [
                    'assessment_id' => $assessment->id,
                    'student_profile_id' => $profile->id,
                ];
            } else {
                $match = [
                    'assessment_id' => $assessment->id,
                    'student_id' => $studentId,
                ];
            }

            // Update or create the assessment score record and store both identifiers for compatibility
            AssessmentScore::updateOrCreate(
                $match,
                [
                    'score' => $score,
                    'remarks' => $remarks,
                    'student_id' => $studentId,
                    'student_profile_id' => $profile ? $profile->id : null,
                ]
            );
        }

        // Recalculate and persist quarter grades for the affected subject/quarter
        \App\Jobs\RecalculateQuarterGradesJob::dispatch(
            $class->id,
            $assessment->subject_id,
            (int) $assessment->quarter,
            $assessment->teacher_id,
            $class->school_year_id
        );

        return redirect()->route('teacher.assessments.index', $class)->with('success', 'Scores updated successfully.');
    }

    /**
     * Delete an assessment.
     */
    public function destroy(Classes $class, Assessment $assessment)
    {
        $assessment->delete(); // The onDelete('cascade') on the assessment_scores table will handle the rest.

        // If AJAX / expects JSON, return JSON response
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Assessment deleted successfully.',
            ]);
        }

        return redirect()->route('teacher.assessments.index', $class)->with('success', 'Assessment deleted successfully.');
    }

    /**
     * Save multiple assessment scores at once.
     */
    public function saveGrades(SaveGradesRequest $request, Classes $class): JsonResponse
    {
        try {
            $quarterLockContext = $this->quarterLockService->contextForSchoolYear((int) $class->school_year_id);
            $quarterLocks = $quarterLockContext['quarterLocks'];

            $teacher = Auth::user()->teacher;
            if (! $teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user is not a teacher.',
                ], 403);
            }

            $validatedGrades = $request->validated('grades', []);

            // Preload assessments and students
            $assessmentIds = collect($validatedGrades)->pluck('assessment_id')->unique();
            $studentIds = collect($validatedGrades)->pluck('student_id')->unique();

            $assessments = Assessment::whereIn('id', $assessmentIds)
                ->where('class_id', $class->id)
                ->where('teacher_id', $teacher->id)
                ->get()
                ->keyBy('id');

            $students = $class->students()->with(['profiles' => function ($q) use ($class) {
                $q->where('school_year_id', $class->school_year_id);
            }])
                ->whereIn('students.id', $studentIds)
                ->get()
                ->keyBy('id');

            $successCount = 0;
            $clearedCount = 0;
            $rejectedCells = [];
            $affectedCombos = [];

            $scoresToUpsert = [];
            $scoresToDelete = [];

            foreach ($validatedGrades as $gradeData) {
                $assessment = $assessments->get($gradeData['assessment_id']);

                if (! $assessment) {
                    $rejectedCells[] = [
                        'student_id' => (int) $gradeData['student_id'],
                        'assessment_id' => (int) $gradeData['assessment_id'],
                        'reason' => 'Assessment is not accessible for this class/teacher.',
                    ];

                    continue;
                }

                $assessmentQuarter = (int) $assessment->quarter;
                if (($quarterLocks[$assessmentQuarter]['is_locked'] ?? false) === true) {
                    $rejectedCells[] = [
                        'student_id' => (int) $gradeData['student_id'],
                        'assessment_id' => (int) $gradeData['assessment_id'],
                        'reason' => "Quarter {$assessmentQuarter} is locked. Grade changes are disabled.",
                    ];

                    continue;
                }

                $student = $students->get($gradeData['student_id']);
                if (! $student) {
                    $rejectedCells[] = [
                        'student_id' => (int) $gradeData['student_id'],
                        'assessment_id' => (int) $gradeData['assessment_id'],
                        'reason' => 'Student record was not found.',
                    ];

                    continue;
                }

                $profile = $student->profiles->first();
                $scoreValue = $gradeData['score'];

                if ($scoreValue === null || $scoreValue === '') {
                    $scoresToDelete[] = [
                        'assessment_id' => $assessment->id,
                        'student_id' => $student->id,
                    ];
                } else {
                    if ($assessment->max_score === null) {
                        $rejectedCells[] = [
                            'student_id' => (int) $gradeData['student_id'],
                            'assessment_id' => (int) $gradeData['assessment_id'],
                            'reason' => 'Set a maximum score before entering student scores.',
                        ];

                        continue;
                    }

                    if ((float) $scoreValue > (float) $assessment->max_score) {
                        $rejectedCells[] = [
                            'student_id' => (int) $gradeData['student_id'],
                            'assessment_id' => (int) $gradeData['assessment_id'],
                            'reason' => 'Score exceeds the maximum allowed score of '.$assessment->max_score.'.',
                        ];

                        continue;
                    }

                    $scoresToUpsert[] = [
                        'assessment_id' => $assessment->id,
                        'student_id' => $student->id,
                        'score' => $scoreValue,
                        'remarks' => null,
                        'student_profile_id' => $profile ? $profile->id : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $key = $assessment->subject_id.'-'.$assessment->quarter;
                if (! isset($affectedCombos[$key])) {
                    $affectedCombos[$key] = [
                        'subject_id' => $assessment->subject_id,
                        'quarter' => (int) $assessment->quarter,
                        'teacher_id' => $assessment->teacher_id,
                    ];
                }
            }

            // Perform DB operations within a transaction
            \Illuminate\Support\Facades\DB::transaction(function () use (&$scoresToUpsert, &$scoresToDelete, $class, $affectedCombos, $studentIds, &$successCount, &$clearedCount) {
                if (! empty($scoresToDelete)) {
                    foreach (collect($scoresToDelete)->groupBy('assessment_id') as $assessmentId => $deletes) {
                        $deleted = AssessmentScore::where('assessment_id', $assessmentId)
                            ->whereIn('student_id', $deletes->pluck('student_id'))
                            ->delete();
                        $clearedCount += $deleted;
                    }
                }

                if (! empty($scoresToUpsert)) {
                    AssessmentScore::upsert(
                        $scoresToUpsert,
                        ['assessment_id', 'student_id'], // Unique key constraint
                        ['score', 'remarks', 'student_profile_id', 'updated_at']
                    );
                    $successCount += count($scoresToUpsert);
                }

                // Dispatch jobs for affected subject/quarter
                foreach ($affectedCombos as $combo) {
                    \App\Jobs\RecalculateQuarterGradesJob::dispatch(
                        $class->id,
                        $combo['subject_id'],
                        $combo['quarter'],
                        $combo['teacher_id'],
                        $class->school_year_id,
                        $studentIds->toArray()
                    )->afterCommit();
                }
            }, 5);

            $summaryParts = [];
            if ($successCount > 0) {
                $summaryParts[] = "{$successCount} grades saved";
            }
            if ($clearedCount > 0) {
                $summaryParts[] = "{$clearedCount} grades cleared";
            }
            if (! empty($rejectedCells)) {
                $summaryParts[] = count($rejectedCells).' grade entries rejected';
            }

            return response()->json([
                'success' => true,
                'message' => empty($summaryParts) ? 'No grade changes were saved.' : implode(', ', $summaryParts).'.',
                'saved_count' => $successCount,
                'cleared_count' => $clearedCount,
                'rejected_count' => count($rejectedCells),
                'rejected_cells' => $rejectedCells,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error saving grades.',
            ], 500);
        }
    }

    // public function exportClassRecord(ExportClassRecordRequest $request, Classes $class): BinaryFileResponse
    // {
    //     $teacher = Auth::user()->teacher;
    //     if (! $teacher) {
    //         abort(403, 'You must be a registered teacher to export class records.');
    //     }

    //     $validated = $request->validated();
    //     $quarter = (int) ($validated['quarter'] ?? 0);
    //     if ($quarter < 1 || $quarter > 4) {
    //         abort(422, 'A valid quarter is required to export a class record.');
    //     }

    //     $subject = $this->resolveExportSubject($class, (int) $validated['subject_id'], $teacher->id);

    //     return Excel::download(
    //         new AssessmentClassRecordExport($class, $subject, $teacher->id, $quarter),
    //         $this->buildClassRecordFilename($class, $subject, "q{$quarter}")
    //     );
    // }

    // public function exportClassRecordAll(ExportClassRecordRequest $request, Classes $class): BinaryFileResponse
    // {
    //     $teacher = Auth::user()->teacher;
    //     if (! $teacher) {
    //         abort(403, 'You must be a registered teacher to export class records.');
    //     }

    //     $validated = $request->validated();
    //     $subject = $this->resolveExportSubject($class, (int) $validated['subject_id'], $teacher->id);

    //     return Excel::download(
    //         new AssessmentClassRecordWorkbookExport($class, $subject, $teacher->id),
    //         $this->buildClassRecordFilename($class, $subject, 'all-quarters')
    //     );
    // }

    private function resolveExportSubject(Classes $class, int $subjectId, int $teacherId): Subject
    {
        $subject = Subject::findOrFail($subjectId);
        $isClassAdviser = (int) $class->teacher_id === $teacherId;
        $hasScheduleAccess = $class->schedules()
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->exists();

        if (! $isClassAdviser && ! $hasScheduleAccess) {
            abort(403, 'You do not have permission to export records for this subject.');
        }

        return $subject;
    }

    private function buildClassRecordFilename(Classes $class, Subject $subject, string $scope): string
    {
        $class->loadMissing('section');

        $sectionSlug = Str::slug((string) ($class->section?->name ?? "class-{$class->id}"));
        $subjectSlug = Str::slug((string) ($subject->name ?: "subject-{$subject->id}"));
        $timestamp = now()->format('Ymd_His');

        return "class-record_{$sectionSlug}_{$subjectSlug}_{$scope}_{$timestamp}.xlsx";
    }

    /**
     * Get subjects for a specific class that the authenticated teacher teaches.
     */
    public function getSubjectsForClass(Classes $class)
    {
        $teacher = Auth::user()->teacher;

        // Get subjects that this teacher teaches in this specific class
        $subjects = $teacher->schedules()
            ->where('class_id', $class->id)
            ->with('subject')
            ->get()
            ->pluck('subject')
            ->unique('id')
            ->values()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code ?? null,
                ];
            });

        return response()->json([
            'subjects' => $subjects,
        ]);
    }

    /**
     * Quick add assessment directly from the grade management page.
     */
    public function quickAddAssessment(Request $request, Classes $class)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:written_works,performance_tasks,quarterly_assessments,oral_participation',
            'max_score' => 'nullable|numeric|min:1|max:1000',
            'quarter' => 'required|integer|in:1,2,3,4',
        ]);

        if ($this->quarterLockService->isLocked((int) $class->school_year_id, (int) $request->quarter)) {
            return response()->json([
                'success' => false,
                'message' => 'This quarter is locked. Assessment changes are disabled.',
            ], 423);
        }

        $teacher = Auth::user()->teacher;

        $assessment = $class->assessments()->create([
            'subject_id' => $request->subject_id,
            'teacher_id' => $teacher->id,
            'school_year_id' => $class->school_year_id,
            'name' => $request->name,
            'type' => $request->type,
            'max_score' => $request->max_score,
            'quarter' => $request->quarter,
            'assessment_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assessment added successfully!',
            'assessment' => [
                'id' => $assessment->id,
                'name' => $assessment->name,
                'max_score' => $assessment->max_score,
                'type' => $assessment->type,
                'quarter' => $assessment->quarter,
            ],
        ]);
    }

    /**
     * Update maximum score for an assessment.
     */
    public function updateMaxScore(Request $request, Classes $class)
    {
        $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'max_score' => 'nullable|numeric|min:1|max:1000',
        ]);

        $teacher = Auth::user()->teacher;

        $assessment = Assessment::where('id', $request->assessment_id)
            ->where('class_id', $class->id)
            ->where('teacher_id', $teacher->id)
            ->first();

        if (! $assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found or you do not have permission to edit it.',
            ], 403);
        }

        if ($this->quarterLockService->isLocked((int) $class->school_year_id, (int) $assessment->quarter)) {
            return response()->json([
                'success' => false,
                'message' => 'This quarter is locked. Assessment changes are disabled.',
            ], 423);
        }

        $assessment->update(['max_score' => $request->max_score]);

        // After changing max score, quarter grade may shift; recompute
        \App\Jobs\RecalculateQuarterGradesJob::dispatch(
            $class->id,
            $assessment->subject_id,
            (int) $assessment->quarter,
            $assessment->teacher_id,
            $class->school_year_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Maximum score updated successfully!',
            'max_score' => $assessment->max_score,
        ]);
    }

    /**
     * Ensure the fixed number of assessments exists for each type/quarter combination.
     */
    private function ensureDefaultAssessments(Classes $class, Subject $subject, Teacher $teacher): void
    {
        $typeLabels = [
            'written_works' => 'Written Work',
            'performance_tasks' => 'Performance Task',
            'quarterly_assessments' => 'Quarterly Assessment',
        ];

        // Fetch all existing counts in a single query instead of 12 individual COUNT queries
        $existingCounts = Assessment::where('class_id', $class->id)
            ->where('subject_id', $subject->id)
            ->where('teacher_id', $teacher->id)
            ->selectRaw('quarter, type, COUNT(*) as cnt')
            ->groupBy('quarter', 'type')
            ->get()
            ->groupBy('quarter')
            ->map(fn ($group) => $group->pluck('cnt', 'type'));

        $newAssessments = [];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            foreach (self::DEFAULT_ASSESSMENT_COUNTS as $type => $requiredCount) {
                $existingCount = (int) ($existingCounts->get($quarter)?->get($type) ?? 0);

                for ($sequence = $existingCount + 1; $sequence <= $requiredCount; $sequence++) {
                    $newAssessments[] = [
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'school_year_id' => $class->school_year_id,
                        'name' => $this->buildDefaultAssessmentName($type, $typeLabels[$type], $sequence, $quarter),
                        'type' => $type,
                        'max_score' => null,
                        'quarter' => $quarter,
                        'assessment_date' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (! empty($newAssessments)) {
            Assessment::insert($newAssessments);
        }
    }

    private function buildDefaultAssessmentName(string $type, string $baseLabel, int $sequence, int $quarter): string
    {
        if ($type === 'quarterly_assessments') {
            return "Quarter {$quarter} {$baseLabel}";
        }

        return "Quarter {$quarter} {$baseLabel} {$sequence}";
    }

    /**
     * Recalculate and persist quarter grades for all students in a class for a given subject & quarter.
     */
}
