<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OralParticipationController extends Controller
{
    /**
     * Display list of classes for oral participation management.
     * Shows all classes the teacher is involved with, with grade level and section filtering.
     */
    public function list(Request $request)
    {
        $teacher = Auth::user()->teacher;
        $activeSchoolYear = SchoolYear::active()->first();

        if (!$activeSchoolYear) {
            return view('teacher.oral-participation.list')->with('error', 'No active school year has been set.');
        }

        // Get all grade levels for the filter dropdown
        $gradeLevels = GradeLevel::orderBy('level')->get();

        // Get IDs of classes where the teacher is the adviser
        $advisoryClassIds = $teacher->advisoryClasses()
            ->where('school_year_id', $activeSchoolYear->id)
            ->pluck('id');

        // Get IDs of classes where the teacher has a schedule
        $scheduledClassIds = $teacher->schedules()
            ->whereHas('class', fn($q) => $q->where('school_year_id', $activeSchoolYear->id))
            ->pluck('class_id');

        // Merge and get unique IDs
        $allClassIds = $advisoryClassIds->merge($scheduledClassIds)->unique();

        // Build the query with optional filters
        $query = Classes::whereIn('id', $allClassIds)
            ->with(['section.gradeLevel', 'teacher.user', 'enrollments', 'schedules.subject', 'schedules.teacher']);

        // Apply grade level filter
        if ($request->filled('grade_level_id')) {
            $query->whereHas('section', function ($q) use ($request) {
                $q->where('grade_level_id', $request->grade_level_id);
            });
        }

        // Apply section filter
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        $classes = $query->get()->sortBy('section.gradeLevel.level');

        // Get sections for the selected grade level (for AJAX filtering)
        $selectedGradeLevelId = $request->grade_level_id;
        $sections = [];
        if ($selectedGradeLevelId) {
            $sections = \App\Models\Section::where('grade_level_id', $selectedGradeLevelId)
                ->orderBy('name')
                ->get();
        }

        return view('teacher.oral-participation.list', compact(
            'classes',
            'teacher',
            'gradeLevels',
            'selectedGradeLevelId',
            'sections'
        ));
    }

    /**
     * Display oral participation management for a specific class.
     */
    public function index(Classes $class, Request $request)
    {
        $class->load('section.gradeLevel');

        // Get the selected subject or default to the first subject
        $subjects = $class->schedules()->with('subject')->get()->pluck('subject')->unique('id');

        if ($request->has('subject_id') && $request->subject_id) {
            $selectedSubject = Subject::find($request->subject_id);
        } else {
            $selectedSubject = $subjects->first();
        }

        if (!$selectedSubject) {
            return redirect()->back()->with('error', 'No subjects found for this class.');
        }

        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            abort(403, 'You must be a registered teacher to manage oral participation.');
        }

        // Get all students enrolled in this class
        $students = $class->students()->orderBy('last_name')->orderBy('first_name')->get();

        // Get Performance Task 1 assessments (used for Oral Participation) for all quarters
        $oralParticipationAssessments = $this->getOralParticipationAssessments($class, $selectedSubject);

        // Organize data by student and quarter
        $studentsData = $students->map(function ($student) use ($oralParticipationAssessments) {
            $studentData = [
                'student' => $student,
                'quarters' => []
            ];

            // Process each quarter (1-4)
            for ($quarter = 1; $quarter <= 4; $quarter++) {
                $assessment = $oralParticipationAssessments->get($quarter);
                $score = null;
                $maxScore = 10; // Default max score for oral participation

                if ($assessment) {
                    $scoreRecord = $assessment->scores->firstWhere('student_id', $student->id);
                    $score = $scoreRecord ? $scoreRecord->score : null;
                    $maxScore = $assessment->max_score ?? 10;
                }

                $studentData['quarters'][$quarter] = [
                    'assessment' => $assessment,
                    'score' => $score,
                    'max_score' => $maxScore,
                ];
            }

            return $studentData;
        });

        return view('teacher.oral-participation.index', [
            'class' => $class,
            'subjects' => $subjects,
            'selectedSubject' => $selectedSubject,
            'studentsData' => $studentsData,
            'oralParticipationAssessments' => $oralParticipationAssessments,
        ]);
    }

    /**
     * Save oral participation scores.
     */
    public function saveScores(Request $request, Classes $class)
    {
        try {
            $validated = $request->validate([
                'scores' => 'required|array',
                'scores.*.student_id' => 'required|exists:students,id',
                'scores.*.assessment_id' => 'required|exists:assessments,id',
                'scores.*.score' => 'nullable|numeric|min:0',
            ]);

            $teacher = Auth::user()->teacher;
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user is not a teacher.'
                ], 403);
            }

            $successCount = 0;

            foreach ($request->scores as $scoreData) {
                // Verify the assessment belongs to this teacher and class
                $assessment = Assessment::where('id', $scoreData['assessment_id'])
                    ->where('class_id', $class->id)
                    ->where('teacher_id', $teacher->id)
                    ->first();

                if (!$assessment) {
                    continue; // Skip invalid assessments
                }

                $score = $scoreData['score'];

                // If score is null or empty, delete existing record
                if ($score === null || $score === '') {
                    AssessmentScore::where('assessment_id', $scoreData['assessment_id'])
                        ->where('student_id', $scoreData['student_id'])
                        ->delete();
                    continue;
                }

                // Validate score against max score
                if ($assessment->max_score !== null && $score > $assessment->max_score) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A score exceeds its maximum allowed score.'
                    ], 422);
                }

                // Update or create the assessment score
                AssessmentScore::updateOrCreate(
                    [
                        'assessment_id' => $scoreData['assessment_id'],
                        'student_id' => $scoreData['student_id'],
                    ],
                    [
                        'score' => $score,
                        'remarks' => 'Oral Participation',
                    ]
                );

                $successCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$successCount} oral participation scores saved successfully.",
                'saved_count' => $successCount
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error saving scores.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update maximum score for oral participation assessments.
     * Dynamically creates Performance Task 1 if it doesn't exist.
     */
    public function updateMaxScore(Request $request, Classes $class)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'quarter' => 'required|integer|in:1,2,3,4',
            'max_score' => 'required|numeric|min:1|max:100',
        ]);

        $teacher = Auth::user()->teacher;

        // Get active school year
        $activeSchoolYear = SchoolYear::active()->first();
        if (!$activeSchoolYear) {
            return response()->json([
                'success' => false,
                'message' => 'No active school year found.'
            ], 404);
        }

        // Find the first performance task for the specified quarter (which is Oral Participation)
        $assessment = Assessment::where('class_id', $class->id)
            ->where('subject_id', $request->subject_id)
            ->where('quarter', $request->quarter)
            ->where('type', 'performance_tasks')
            ->where('teacher_id', $teacher->id)
            ->orderBy('assessment_date')
            ->orderBy('id')
            ->first();

        if (!$assessment) {
            // Create Performance Task 1 (Oral Participation) dynamically
            $assessment = Assessment::create([
                'class_id' => $class->id,
                'subject_id' => $request->subject_id,
                'teacher_id' => $teacher->id,
                'school_year_id' => $activeSchoolYear->id,
                'name' => 'PT 1',
                'type' => 'performance_tasks',
                'max_score' => $request->max_score,
                'quarter' => $request->quarter,
                'assessment_date' => now()->toDateString(),
            ]);
        } else {
            $assessment->update(['max_score' => $request->max_score]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maximum score updated successfully!',
            'max_score' => $assessment->max_score,
            'assessment_id' => $assessment->id
        ]);
    }

    /**
     * Get oral participation assessments (Performance Task 1) for each quarter.
     * Performance Task 1 is identified by being the first performance_tasks type assessment
     * for each quarter when ordered by assessment_date and id.
     */
    private function getOralParticipationAssessments(Classes $class, Subject $subject)
    {
        $assessments = collect();

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            // Get the first performance task for this quarter (which is Performance Task 1 / Oral Participation)
            $assessment = Assessment::where('class_id', $class->id)
                ->where('subject_id', $subject->id)
                ->where('quarter', $quarter)
                ->where('type', 'performance_tasks')
                ->orderBy('assessment_date')
                ->orderBy('id')
                ->with('scores')
                ->first();

            if ($assessment) {
                $assessments->put($quarter, $assessment);
            }
        }

        return $assessments;
    }

    /**
     * Get sections by grade level for AJAX filtering.
     */
    public function getSectionsByGradeLevel(Request $request)
    {
        $gradeLevelId = $request->grade_level_id;

        if (!$gradeLevelId) {
            return response()->json(['sections' => []]);
        }

        $sections = \App\Models\Section::where('grade_level_id', $gradeLevelId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get students with their existing oral participation scores for a specific quarter.
     * Used by the quick add modal.
     */
    public function getStudentsWithScores(Request $request, Classes $class)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'quarter' => 'required|integer|in:1,2,3,4',
        ]);

        $teacher = Auth::user()->teacher;
        if (!$teacher) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $subjectId = $request->subject_id;
        $quarter = (int) $request->quarter;

        // Get the oral participation assessment (first performance task) for this quarter
        $assessment = Assessment::where('class_id', $class->id)
            ->where('subject_id', $subjectId)
            ->where('quarter', $quarter)
            ->where('type', 'performance_tasks')
            ->orderBy('assessment_date')
            ->orderBy('id')
            ->with('scores')
            ->first();

        // Get all students enrolled in this class
        $students = $class->students()->orderBy('last_name')->orderBy('first_name')->get();

        $studentsData = $students->map(function ($student) use ($assessment) {
            $score = null;
            if ($assessment) {
                $scoreRecord = $assessment->scores->firstWhere('student_id', $student->id);
                $score = $scoreRecord ? $scoreRecord->score : null;
            }

            return [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'score' => $score,
            ];
        });

        return response()->json([
            'students' => $studentsData,
            'max_score' => $assessment ? $assessment->max_score : 10,
            'assessment_id' => $assessment ? $assessment->id : null,
        ]);
    }

    /**
     * Quick save oral participation scores from the modal.
     * Updates max score and saves all student scores in one request.
     * Dynamically creates Performance Task 1 if it doesn't exist.
     */
    public function quickSave(Request $request, Classes $class)
    {
        try {
            $request->validate([
                'subject_id' => 'required|exists:subjects,id',
                'quarter' => 'required|integer|in:1,2,3,4',
                'max_score' => 'required|numeric|min:1|max:100',
                'scores' => 'required|array',
                'scores.*.student_id' => 'required|exists:students,id',
                'scores.*.score' => 'required|numeric|min:0',
            ]);

            $teacher = Auth::user()->teacher;
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user is not a teacher.'
                ], 403);
            }

            $subjectId = $request->subject_id;
            $quarter = (int) $request->quarter;
            $maxScore = $request->max_score;

            // Get active school year
            $activeSchoolYear = SchoolYear::active()->first();
            if (!$activeSchoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active school year found.'
                ], 404);
            }

            // Get or create the oral participation assessment (first performance task)
            $assessment = Assessment::where('class_id', $class->id)
                ->where('subject_id', $subjectId)
                ->where('quarter', $quarter)
                ->where('type', 'performance_tasks')
                ->where('teacher_id', $teacher->id)
                ->orderBy('assessment_date')
                ->orderBy('id')
                ->first();

            if (!$assessment) {
                // Create Performance Task 1 (Oral Participation) dynamically
                $assessment = Assessment::create([
                    'class_id' => $class->id,
                    'subject_id' => $subjectId,
                    'teacher_id' => $teacher->id,
                    'school_year_id' => $activeSchoolYear->id,
                    'name' => 'PT 1',
                    'type' => 'performance_tasks',
                    'max_score' => $maxScore,
                    'quarter' => $quarter,
                    'assessment_date' => now()->toDateString(),
                ]);
            } else {
                // Update max score for existing assessment
                $assessment->update(['max_score' => $maxScore]);
            }

            // Save scores
            $successCount = 0;
            foreach ($request->scores as $scoreData) {
                $score = $scoreData['score'];

                // Validate score doesn't exceed max
                if ($score > $maxScore) {
                    $score = $maxScore;
                }

                AssessmentScore::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'student_id' => $scoreData['student_id'],
                    ],
                    [
                        'score' => $score,
                        'remarks' => 'Oral Participation',
                    ]
                );

                $successCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$successCount} scores saved successfully.",
                'saved_count' => $successCount,
                'assessment_id' => $assessment->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error saving scores.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
