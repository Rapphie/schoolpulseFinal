<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardianController extends Controller
{
    /**
     * Display the student's grades for the guardian.
     * This method provides dummy data for demonstration purposes.
     * In a real application, this would fetch actual grades from the database
     * based on the authenticated guardian and their associated students.
     */
    public function viewStudentGrades()
    {
        // Dummy data for a single student
        $studentInfo = [
            'name' => 'Dummy Student Name',
            'student_id' => 'STD12345',
            'grade_level' => 'Grade 7',
            'school_year' => '2024-2025',
            'class_section' => 'Emerald',
            'lrn' => '123456789012',
        ];

        // Dummy grades data for 1st Quarter
        $grades = [
            '1st Quarter' => [
                [
                    'subject' => 'Mathematics 7',
                    'teacher' => 'Mr. John Doe',
                    'grade' => 88,
                    'remarks' => 'Good effort, needs to review algebra.',
                ],
                [
                    'subject' => 'Science 7',
                    'teacher' => 'Ms. Jane Smith',
                    'grade' => 92,
                    'remarks' => 'Excellent work in experiments.',
                ],
                [
                    'subject' => 'English 7',
                    'teacher' => 'Mrs. Emily White',
                    'grade' => 85,
                    'remarks' => 'Participates well, focus on grammar.',
                ],
                [
                    'subject' => 'Filipino 7',
                    'teacher' => 'Gng. Maria Reyes',
                    'grade' => 90,
                    'remarks' => 'Mahusay! Continue reading Filipino literature.',
                ],
                [
                    'subject' => 'Araling Panlipunan 7',
                    'teacher' => 'G. Jose Cruz',
                    'grade' => 87,
                    'remarks' => 'Understands concepts, improve research skills.',
                ],
            ],
            // Other quarters would be empty or contain similar dummy data
            '2nd Quarter' => [],
            '3rd Quarter' => [],
            '4th Quarter' => [],
        ];

        return view('guardian.index', compact('studentInfo', 'grades'));
    }
}
