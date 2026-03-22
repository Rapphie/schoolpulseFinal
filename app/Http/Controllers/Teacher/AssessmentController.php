<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\SaveGradesRequest;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classes;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\AssessmentDataBuilder;
use App\Services\GradeSubmissionService;
use App\Services\QuarterLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
{
    public function __construct(
        private QuarterLockService $quarterLockService,
        private AssessmentDataBuilder $assessmentDataBuilder,
        private GradeSubmissionService $gradeSubmissionService
    ) {}

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

    private function authorizeTeacherForAssessment(Assessment $assessment): Teacher
    {
        $teacher = Auth::user()->teacher;
        if (! $teacher) {
            abort(403, 'You must be a registered teacher.');
        }
        if ($assessment->teacher_id !== $teacher->id) {
            abort(403, 'You do not have permission to modify this assessment.');
        }

        return $teacher;
    }

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

        // Get all students enrolled in this class with profiles eager loaded
        $students = $class->students()
            ->with('profiles')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

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
        $assessments = $this->assessmentDataBuilder->consolidateOralParticipation($class, $selectedSubject, $assessments);

        // Organize data by student
        $studentsData = $this->assessmentDataBuilder->buildStudentGradesData($students, $assessments, $class);

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

        $isScheduled = $teacher->schedules()
            ->where('class_id', $class->id)
            ->where('subject_id', $request->subject_id)
            ->exists();

        if (! $isScheduled) {
            return redirect()->back()->with('error', 'You are not authorized to add assessments for this subject in this class.');
        }

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
        $this->authorizeTeacherForAssessment($assessment);

        // Build lookup of scores for this assessment (both by student_profile_id and student_id)
        $scores = AssessmentScore::where('assessment_id', $assessment->id)->get();
        $scoresByProfile = $scores->whereNotNull('student_profile_id')->keyBy('student_profile_id');
        $scoresByStudent = $scores->whereNotNull('student_id')->keyBy('student_id');

        $students = $class->students()
            ->with(['profiles' => function ($q) use ($class) {
                $q->where('school_year_id', $class->school_year_id);
            }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($student) use ($scoresByProfile, $scoresByStudent, $class) {
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
        $this->authorizeTeacherForAssessment($assessment);

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

        $studentIds = array_keys($request->scores);
        $students = Student::with(['profiles' => function ($q) use ($assessment) {
            $q->where('school_year_id', $assessment->school_year_id);
        }])->whereIn('id', $studentIds)->get()->keyBy('id');

        foreach ($request->scores as $studentId => $data) {
            $score = $data['score'] ?? null;
            $remarks = $data['remarks'] ?? null;

            $student = $students->get($studentId);
            $profile = $student?->profiles->first();

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

            // Build match keys: always use student_id as the unique constraint match point
            $match = [
                'assessment_id' => $assessment->id,
                'student_id' => $studentId,
            ];

            // Update or create the assessment score record
            AssessmentScore::updateOrCreate(
                $match,
                [
                    'score' => $score,
                    'remarks' => $remarks,
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
        $this->authorizeTeacherForAssessment($assessment);

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
            $result = $this->gradeSubmissionService->submitBatch(
                $class,
                Auth::user()->teacher,
                $request->validated('grades', [])
            );

            if (! $result->success) {
                return response()->json($result->toArray(), $result->statusCode);
            }

            return response()->json($result->toArray());
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error saving grades.',
            ], 500);
        }
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

        $isScheduled = $teacher->schedules()
            ->where('class_id', $class->id)
            ->where('subject_id', $request->subject_id)
            ->exists();

        if (! $isScheduled) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to add assessments for this subject in this class.',
            ], 403);
        }

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
