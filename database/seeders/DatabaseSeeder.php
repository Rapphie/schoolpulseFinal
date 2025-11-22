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
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
            ['name' => 'guardian', 'description' => 'Guardian access to view their children\'s progress'],
        ];

        // Insert roles
        DB::table('roles')->insert($roles);

        // Create admin user
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'Nistrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('temp'),
            'role_id' => 1,
        ]);
        $teacherUser   =  User::create([
            'first_name' => 'Christian',
            'last_name' => 'Plasabas',
            'email' => 'tempinnovision@gmail.com',
            'password' => Hash::make('123'),
            'role_id' => 2,
        ]);
        Teacher::create([
            'user_id' => $teacherUser->id,
            'phone' => '09123456789',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'address' => '123 Teacher St, City, Country',
            'qualification' => 'Bachelor of Education',
            'status' => 'active',
        ]);


        $guardianUser = User::create([
            'first_name' => 'Kim',
            'last_name' => 'Lee',
            'email' => 'guardian@gmail.com',
            'password' => Hash::make(12345678),
            'role_id' => 3,
        ]);


        $guardian = Guardian::create([
            'user_id' => $guardianUser->id,
            'phone' => '09123456789',
            'relationship' => 'parent',
        ]);
        Mail::to($guardianUser->email)->send(new \App\Mail\WelcomeEmail($guardianUser, '12345678'));
        Mail::to($teacherUser->email)->send(new \App\Mail\WelcomeEmail($teacherUser, '123'));

        // GradeLevel::factory(6)->create();
        // // Create teachers
        // $teacherUsers = User::factory(10)->create([
        //     'role_id' => 2,
        //     'password' => Hash::make('123'),
        // ]);

        // // Create parent users
        // $parentUsers = User::factory(30)->create([
        //     'role_id' => 3,
        //     'password' => Hash::make('123'),
        // ]);

        // // Create teacher records linked to existing users
        // foreach ($teacherUsers as $user) {
        //     Teacher::factory()->create([
        //         'user_id' => $user->id
        //     ]);
        // }

        // $teachers = Teacher::all();

        // GradeLevel::factory(6)->create();
        // Section::factory(9)->create();
        // Subject::factory(10)->create();
        // LLC::factory(10)->create();
        // LLCItem::factory(70)->create();

        // // Create guardians linked to existing parent users
        // $guardians = [];
        // foreach ($parentUsers as $user) {
        //     $guardians[] = Guardian::create([
        //         'user_id' => $user->id,
        //         'first_name' => $user->first_name,
        //         'last_name' => $user->last_name,
        //         'phone' => '09' . fake()->numerify('#########'),
        //         'relationship' => fake()->randomElement(['parent', 'sibling', 'relative', 'guardian']),
        //     ]);
        // }
        // $faker = \Faker\Factory::create();
        // // Create students without creating new users/teachers/guardians
        // foreach (range(1, 355) as $index) {
        //     $sectionId = rand(1, 9);
        //     Student::create([
        //         'first_name' => fake()->firstName(),
        //         'last_name' => fake()->lastName(),
        //         'section_id' => $sectionId,
        //         // Use index for uniqueness
        //         'student_id' => date('Y') . '-' . $sectionId . '-' . $index,
        //         'birthdate' => fake()->dateTimeBetween('-18 years', '-12 years'),
        //         'gender' => fake()->randomElement(['male', 'female']),
        //         'guardian_id' => $guardians[array_rand($guardians)]->id,
        //         'status' => 'active',
        //         'enrollment_date' => fake()->dateTimeBetween('-1 year', 'now'),
        //         'teacher_id' => $teachers->random()->id,
        //     ]);
        // }

        // Schedule::factory(30)->create();
        // Grade::factory(355)->create();
        // Attendance::factory(200)->create();
    }
}
