<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Schedule;
use App\Models\GradeLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');


        $tables = [
            'attendances',
            'grades',
            'grade_levels',
            'schedules',
            'section_subject',
            'students',
            'sections',
            'subjects',
            'users',
            'roles',
            'grade_levels',
        ];

        $roles = [
            ['name' => 'admin', 'description' => 'Administrator with full access'],
            ['name' => 'teacher', 'description' => 'Teacher with subject and class access'],
            ['name' => 'parent', 'description' => 'Parent access to view their children\'s progress'],
        ];

        // Insert roles
        DB::table('roles')->insert($roles);

        // Create admin user
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Nistrator',
            'email' => 'temp@gmail.com',
            'password' => Hash::make('temp'),
            'role_id' => 1,
        ]);
        User::create([
            'first_name' => 'Christian',
            'last_name' => 'Plasabas',
            'email' => 'teacher@gmail.com',
            'password' => Hash::make('123'),
            'role_id' => 2,
        ]);

        // Create teachers
        $teachers = User::factory(10)->create([
            'role_id' => 2,
            'password' => Hash::make('123'),
        ]);

        // Create subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH', 'units' => 1, 'hours_per_week' => 4],
            ['name' => 'Science', 'code' => 'SCI', 'units' => 1, 'hours_per_week' => 4],
            ['name' => 'English', 'code' => 'ENG', 'units' => 1, 'hours_per_week' => 4],
            ['name' => 'Filipino', 'code' => 'FI ', 'units' => 1, 'hours_per_week' => 3],
            ['name' => 'Araling Panlipunan', 'code' => 'AP', 'units' => 1, 'hours_per_week' => 3],
            ['name' => 'MAPEH', 'code' => 'MAPEH', 'units' => 1, 'hours_per_week' => 4],
            ['name' => 'TLE', 'code' => 'TLE', 'units' => 1, 'hours_per_week' => 4],
            ['name' => 'Values Education', 'code' => 'VALED', 'units' => 1, 'hours_per_week' => 2],
        ];

        foreach ($subjects as $subject) {
            Subject::create(array_merge($subject, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create sections
        $sections = [];
        for ($grade = 1; $grade <= 6; $grade++) {


            for ($i = 1; $i <= 10; $i++) {
                $teacher = $teachers->random();
                $section = Section::create([
                    'name' => chr(64 + $i), // A, B, C, etc.
                    'grade_level' => $grade,
                    'description' => "Grade $grade - Section " . chr(64 + $i),
                    'adviser_id' => 3,
                    'capacity' => 40,
                ]);
                $sections[] = $section;

                // Assign subjects to section with teachers
                foreach (Subject::inRandomOrder()->take(rand(5, 8))->get() as $subject) {
                    $teacher = $teachers->random();
                    $section->subjects()->attach($subject->id, ['teacher_id' => $teacher->id]);

                    // Create schedule for this subject
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                    $day = $days[array_rand($days)];
                    $startHour = rand(7, 15);

                    Schedule::create([
                        'section_id' => $section->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'day_of_week' => $day,
                        'start_time' => sprintf('%02d:00:00', $startHour),
                        'end_time' => sprintf('%02d:00:00', $startHour + 1),
                        'room' => 'Room ' . strtoupper(chr(rand(65, 70))) . rand(1, 20),
                    ]);
                }
            }
        }

        // Create student users first, then create student profiles
        foreach ($sections as $section) {
            // Create student users first to get valid user IDs
            $studentUsers = Student::factory(rand(25, 40))->create();

            // Now create student profiles with the valid user IDs
            foreach ($studentUsers as $studentUser) {
                $studentModel = Student::create([
                    'section_id' => $section->id,
                    'first_name' => $studentUser->first_name,
                    'last_name' => $studentUser->last_name,
                    'qr_code' => bin2hex(random_bytes(16)),
                    'lrn' => rand(100000000000, 999999999999),
                    'birthdate' => now()->subYears(rand(12, 18))->subMonths(rand(0, 11))->subDays(rand(0, 30)),
                    'gender' => ['male', 'female'][rand(0, 1)],
                    'address' => 'Sample Address',
                    'contact_number' => '09' . rand(100000000, 999999999),
                    'guardian_name' => $studentUser->fullName,
                    'guardian_contact' => '09' . rand(100000000, 999999999),
                    'status' => 'active',
                    'enrollment_date' => now()->startOfYear(),
                    'user_id' => 3
                ]);

                // Create grades for each subject in the section
                foreach ($section->subjects as $subject) {
                    // Get the teacher for this subject in this section
                    $teacherId = DB::table('section_subject')
                        ->where('section_id', $section->id)
                        ->where('subject_id', $subject->id)
                        ->value('teacher_id');

                    // If no teacher is assigned, get a random teacher with teacher role
                    if (!$teacherId) {
                        $teacherId = DB::table('users')
                            ->where('role_id', 2) // Teacher role
                            ->inRandomOrder()
                            ->value('id');
                    }

                    // Only create grade if we have a valid teacher ID
                    if ($teacherId) {
                        Grade::create([
                            'student_id' => $studentModel->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacherId,
                            'grading_period' => 'first',
                            'grade' => rand(75, 100),
                        ]);
                    }

                    // Create attendance records
                    $uniqueDates = [];
                    for ($i = 1; $i <= 5; $i++) {
                        // Get the teacher for this subject in this section
                        $teacherId = DB::table('section_subject')
                            ->where('section_id', $section->id)
                            ->where('subject_id', $subject->id)
                            ->value('teacher_id');

                        // If no teacher is assigned, get a random teacher with teacher role
                        if (!$teacherId) {
                            $teacherId = DB::table('users')
                                ->where('role_id', 2) // Teacher role
                                ->inRandomOrder()
                                ->value('id');
                        }

                        // Only create attendance if we have a valid teacher ID
                        if ($teacherId) {
                            // Ensure unique date for each attendance record
                            do {
                                $date = now()->subDays(rand(1, 30))->format('Y-m-d');
                            } while (in_array($date, $uniqueDates));

                            $uniqueDates[] = $date;

                            try {
                                Attendance::create([
                                    'student_id' => $studentModel->id,
                                    'subject_id' => $subject->id,
                                    'teacher_id' => $teacherId,
                                    'date' => $date,
                                    'status' => ['present', 'absent', 'late', 'excused'][rand(0, 3)],
                                    'remarks' => rand(0, 1) ? 'Sample remarks' : null,
                                ]);
                            } catch (\Exception $e) {
                                // Skip if there's a duplicate entry error
                                continue;
                            }
                        }
                    }
                }
            }
        }

        // Create parent users and link to students
        $parents = User::factory(20)->create([
            'role_id' => 3, // Parent role
            'password' => Hash::make('password'),
        ]);

        // Get all students
        $students = Student::all();

        // Make sure we have parents and students
        if ($parents->isNotEmpty() && $students->isNotEmpty()) {
            foreach ($students as $student) {
                // Assign 1-2 random parents to each student
                $parentCount = min(rand(1, 2), $parents->count());
                $randomParents = $parents->random($parentCount);

                foreach ($randomParents as $parent) {
                    try {
                        // Use syncWithoutDetaching to avoid duplicate entries
                        $student->parents()->syncWithoutDetaching([
                            $parent->id => [
                                'relationship' => $parent->id % 3 == 0 ? 'Mother' : ($parent->id % 3 == 1 ? 'Father' : 'Guardian')
                            ]
                        ]);
                    } catch (\Exception $e) {
                        // Skip if there's an error (e.g., duplicate entry)
                        continue;
                    }
                }
            }
        }
    }
}
