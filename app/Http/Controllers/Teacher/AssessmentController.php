<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Classes;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentController extends Controller
{
    /**
     * Show all assessments for a specific class.
     */
    public function index(Classes $class)
    {
        $class->load('section.gradeLevel');

        $assessments = $class->assessments()->with('subject')->latest()->get();
        return view('teacher.assessments.index', compact('class', 'assessments'));
    }

    public function create(Classes $class)
    {
        // Get subjects assigned to this class via schedules
        $subjects = $class->schedules()->with('subject')->get()->pluck('subject')->unique();
        return view('teacher.assessments.create', compact('class', 'subjects'));
    }

    /**
     * Store a new assessment in the database.
     */
    public function store(Request $request, Classes $class)
    {
        // $userId = Auth::id();
        // $teacher = Teacher::where('user_id', $userId)->first();
        // // Check if the authenticated user is actually a teacher
        // if (!$teacher) {
        //     return redirect()->back()->with('error', 'You must be a registered teacher to create assessments.');
        // }
        // $request->validate([
        //     'subject_id' => 'required|exists:subjects,id',
        //     'name' => 'required|string|max:255',
        //     'type' => 'required|in:quiz,exam,assignment,project,performance_task',
        //     'max_score' => 'required|numeric|min:1',
        //     'quarter' => 'required|integer|in:1,2,3,4',
        //     'assessment_date' => 'required|date',
        // ]);

        // $class->assessments()->create([
        //     'subject_id' => $request->subject_id,
        //     'teacher_id' => $teacher->id,
        //     'school_year_id' => $class->school_year_id,
        //     'name' => $request->name,
        //     'type' => $request->type,
        //     'max_score' => $request->max_score,
        //     'quarter' => $request->quarter,
        //     'assessment_date' => $request->assessment_date,
        // ]);

        return redirect()->route('teacher.assessments.index', $class)->with('success', 'Assessment created successfully.');
    }

    /**
     * Show the page for editing scores for an assessment.
     */
    public function editScores(Classes $class, Assessment $assessment)
    {
        // Eager load assessmentScores for each student
        // The `where('assessment_id', $assessment->id)` ensures we only get scores for this specific assessment.
        $students = $class->students()->with(['assessmentScores' => function ($query) use ($assessment) {
            $query->where('assessment_id', $assessment->id);
        }])->get();

        return view('teacher.assessments.scores.edit', compact('class', 'assessment', 'students'));
    }

    /**
     * Update or create scores for students.
     */
    public function updateScores(Request $request, Classes $class, Assessment $assessment)
    {
        // Validate the incoming scores data
        $rules = [
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:students,id',
            'scores.*.score' => 'nullable|numeric|min:0|max:' . $assessment->max_score,
            'scores.*.remarks' => 'nullable|string|max:255',
        ];

        $messages = [
            'scores.*.score.max' => 'The score for a student cannot exceed the maximum score of ' . $assessment->max_score . '.',
        ];

        $request->validate($rules, $messages);

        foreach ($request->scores as $studentId => $data) {
            $score = $data['score'] ?? null;
            $remarks = $data['remarks'] ?? null;

            // If both score and remarks are empty, delete the existing record if any
            if (is_null($score) && is_null($remarks)) {
                AssessmentScore::where('assessment_id', $assessment->id)
                    ->where('student_id', $studentId)
                    ->delete();
                continue; // Move to the next student
            }

            // Update or create the assessment score record
            AssessmentScore::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'student_id' => $studentId,
                ],
                [
                    'score' => $score,
                    'remarks' => $remarks,
                ]
            );
        }

        return redirect()->route('teacher.assessments.index', $class)->with('success', 'Scores updated successfully.');
    }

    /**
     * Delete an assessment.
     */
    public function destroy(Classes $class, Assessment $assessment)
    {
        $assessment->delete(); // The onDelete('cascade') on the assessment_scores table will handle the rest.
        return redirect()->route('teacher.assessments.index', $class)->with('success', 'Assessment deleted successfully.');
    }
}
