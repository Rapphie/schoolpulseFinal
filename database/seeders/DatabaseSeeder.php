<?php

namespace Database\Seeders;

use App\Models\LLC;
use App\Models\LLCItem;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Schedule;
use App\Models\GradeLevel;
use App\Models\Guardian;
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



        $roles = [
            ['name' => 'admin', 'description' => 'Administrator with full access'],
            ['name' => 'teacher', 'description' => 'Teacher with subject and class access'],
            ['name' => 'parent', 'description' => 'Parent access to view their children\'s progress'],
        ];

        // Insert roles
        DB::table('roles')->insert($roles);

        // Create admin user
        User::create([
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
        $teacherUsers = User::factory(10)->create([
            'role_id' => 2,
            'password' => Hash::make('123'),
        ]);

        // Create parent users
        $parentUsers = User::factory(30)->create([
            'role_id' => 3,
            'password' => Hash::make('123'),
        ]);

        // Create teacher records linked to existing users
        foreach ($teacherUsers as $user) {
            Teacher::factory()->create([
                'user_id' => $user->id
            ]);
        }

        $teachers = Teacher::all();

        GradeLevel::factory(6)->create();
        Section::factory(9)->create();
        Subject::factory(10)->create();
        LLC::factory(10)->create();
        LLCItem::factory(70)->create();

        // Create guardians linked to existing parent users
        $guardians = [];
        foreach ($parentUsers as $user) {
            $guardians[] = Guardian::create([
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'contact_number' => '09' . fake()->numerify('#########'),
                'relationship' => fake()->randomElement(['parent', 'sibling', 'relative', 'guardian']),
            ]);
        }

        // Create students without creating new users/teachers/guardians
        foreach (range(1, 355) as $index) {
            Student::create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'section_id' => rand(1, 9),
                'qr_code' => bin2hex(random_bytes(16)),
                'birthdate' => fake()->dateTimeBetween('-18 years', '-12 years'),
                'gender' => fake()->randomElement(['male', 'female']),
                'guardian_id' => $guardians[array_rand($guardians)]->id,
                'status' => 'active',
                'enrollment_date' => fake()->dateTimeBetween('-1 year', 'now'),
                'teacher_id' => $teachers->random()->id,
            ]);
        }

        Schedule::factory(30)->create();
        Grade::factory(355)->create();
        Attendance::factory(200)->create();
    }
}
