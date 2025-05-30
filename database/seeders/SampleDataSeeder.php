<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing students
        $students = Student::all();
        if ($students->isEmpty()) {
            $this->command->info('No students found. Please run the student seeder first.');
            return;
        }

        // Get existing subjects
        $subjects = Subject::all();
        if ($subjects->isEmpty()) {
            $this->command->info('No subjects found. Please run the subject seeder first.');
            return;
        }

        // Get teacher users
        $teachers = User::whereHas('role', function ($query) {
            $query->where('name', 'Teacher');
        })->get();

        if ($teachers->isEmpty()) {
            $this->command->info('No teachers found. Please run the user seeder first.');
            return;
        }

        // Current school year
        $schoolYear = '2025-2026';

        // Current quarter
        $quarter = 1;

        // Assessment types
        $assessmentTypes = ['quiz', 'exam', 'project', 'assignment'];

        // Sample names for assessments
        $assessmentNames = [
            'quiz' => ['Quiz #1', 'Quiz #2', 'Quiz #3', 'Pop Quiz', 'Weekly Quiz'],
            'exam' => ['Midterm Exam', 'Final Exam', 'Chapter Exam', 'Unit Test'],
            'project' => ['Research Paper', 'Group Project', 'Individual Project', 'Presentation'],
            'assignment' => ['Homework #1', 'Homework #2', 'Worksheet', 'Take-home Exercise']
        ];

        // Attendance statuses
        $statuses = ['present', 'absent', 'late', 'excused'];

        // Generate sample data
        $today = Carbon::now();
        $startDate = Carbon::now()->subDays(60);

        foreach ($students as $student) {
            foreach ($subjects as $subject) {
                // Create attendance records
                $attendanceDays = 30;
                for ($i = 0; $i < $attendanceDays; $i++) {
                    $date = $startDate->copy()->addDays($i);

                    // Skip weekends
                    if ($date->isWeekend()) {
                        continue;
                    }

                    // Random distribution of statuses (mostly present)
                    $statusProbabilities = [70, 10, 15, 5]; // present, absent, late, excused
                    $statusIndex = $this->getRandomIndexWithProbabilities($statusProbabilities);
                    $status = $statuses[$statusIndex];

                    $timeIn = null;
                    $timeOut = null;

                    if (in_array($status, ['present', 'late'])) {
                        $baseHour = 8;
                        $baseMinute = 0;

                        if ($status === 'late') {
                            $baseHour = 8;
                            $baseMinute = rand(15, 45);
                        }

                        $timeIn = $date->copy()->setTime($baseHour, $baseMinute);
                        $timeOut = $date->copy()->setTime($baseHour + 4, rand(0, 59));
                    }

                    Attendance::create([
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'status' => $status,
                        'date' => $date,
                        'quarter' => $quarter,
                        'school_year' => $schoolYear,
                        'time_in' => $timeIn,
                        'time_out' => $timeOut,
                        'remarks' => $status === 'excused' ? 'Medical excuse' : null,
                        'user_id' => $teachers->random()->id
                    ]);
                }

                // Create grade records
                $gradeCount = rand(5, 10);
                for ($i = 0; $i < $gradeCount; $i++) {
                    $assessmentType = $assessmentTypes[rand(0, count($assessmentTypes) - 1)];
                    $assessmentName = $assessmentNames[$assessmentType][rand(0, count($assessmentNames[$assessmentType]) - 1)];

                    $maxScore = 100;

                    // Performance trend: start lower, gradually improve
                    $baseGrade = 60 + ($i * 2); // Gradually improve
                    $randomVariation = rand(-10, 10); // Add some randomness
                    $grade = min(max($baseGrade + $randomVariation, 60), 100); // Clamp between 60-100

                    Grade::create([
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'grade' => $grade,
                        'max_score' => $maxScore,
                        'assessment_type' => $assessmentType,
                        'assessment_name' => $assessmentName,
                        'quarter' => $quarter,
                        'school_year' => $schoolYear,
                        'assessment_date' => $startDate->copy()->addDays(rand(0, 50)),
                        'user_id' => $teachers->random()->id
                    ]);
                }
            }
        }
    }

    /**
     * Get a random index based on probabilities array
     *
     * @param array $probabilities Array of probabilities (they should add up to 100)
     * @return int The selected index
     */
    private function getRandomIndexWithProbabilities(array $probabilities)
    {
        $rand = rand(1, 100);
        $cumulativeProbability = 0;

        foreach ($probabilities as $index => $probability) {
            $cumulativeProbability += $probability;
            if ($rand <= $cumulativeProbability) {
                return $index;
            }
        }

        return 0; // Fallback
    }
}
