<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['level' => '1', 'name' => 'Mother Tongue 1', 'code' => 'MT1', 'is_active' => true],
            ['level' => '2', 'name' => 'Mother Tongue 2', 'code' => 'MT2', 'is_active' => true],
            ['level' => '3', 'name' => 'Mother Tongue 3', 'code' => 'MT3', 'is_active' => true],
            ['level' => '1', 'name' => 'Filipino 1', 'code' => 'F1', 'is_active' => true],
            ['level' => '2', 'name' => 'Filipino 2', 'code' => 'F2', 'is_active' => true],
            ['level' => '3', 'name' => 'Filipino 3', 'code' => 'F3', 'is_active' => true],
            ['level' => '4', 'name' => 'Filipino 4', 'code' => 'F4', 'is_active' => true],
            ['level' => '5', 'name' => 'Filipino 5', 'code' => 'F5', 'is_active' => true],
            ['level' => '6', 'name' => 'Filipino 6', 'code' => 'F6', 'is_active' => true],
            ['level' => '1', 'name' => 'Mathematics 1', 'code' => 'M1', 'is_active' => true],
            ['level' => '2', 'name' => 'Mathematics 2', 'code' => 'M2', 'is_active' => true],
            ['level' => '3', 'name' => 'Mathematics 3', 'code' => 'M3', 'is_active' => true],
            ['level' => '4', 'name' => 'Mathematics 4', 'code' => 'M4', 'is_active' => true],
            ['level' => '5', 'name' => 'Mathematics 5', 'code' => 'M5', 'is_active' => true],
            ['level' => '6', 'name' => 'Mathematics 6', 'code' => 'M6', 'is_active' => true],
            ['level' => '3', 'name' => 'Science 3', 'code' => 'S3', 'is_active' => true],
            ['level' => '4', 'name' => 'Science 4', 'code' => 'S4', 'is_active' => true],
            ['level' => '5', 'name' => 'Science 5', 'code' => 'S5', 'is_active' => true],
            ['level' => '6', 'name' => 'Science 6', 'code' => 'S6', 'is_active' => true],
            ['level' => '1', 'name' => 'Araling Panlipunan 1', 'code' => 'AP1', 'is_active' => true],
            ['level' => '2', 'name' => 'Araling Panlipunan 2', 'code' => 'AP2', 'is_active' => true],
            ['level' => '3', 'name' => 'Araling Panlipunan 3', 'code' => 'AP3', 'is_active' => true],
            ['level' => '4', 'name' => 'Araling Panlipunan 4', 'code' => 'AP4', 'is_active' => true],
            ['level' => '5', 'name' => 'Araling Panlipunan 5', 'code' => 'AP5', 'is_active' => true],
            ['level' => '6', 'name' => 'Araling Panlipunan 6', 'code' => 'AP6', 'is_active' => true],
            ['level' => '1', 'name' => 'Music 1', 'code' => 'MU1', 'is_active' => true],
            ['level' => '2', 'name' => 'Music 2', 'code' => 'MU2', 'is_active' => true],
            ['level' => '3', 'name' => 'Music 3', 'code' => 'MU3', 'is_active' => true],
            ['level' => '4', 'name' => 'Music 4', 'code' => 'MU4', 'is_active' => true],
            ['level' => '5', 'name' => 'Music 5', 'code' => 'MU5', 'is_active' => true],
            ['level' => '6', 'name' => 'Music 6', 'code' => 'MU6', 'is_active' => true],
            ['level' => '1', 'name' => 'Arts 1', 'code' => 'A1', 'is_active' => true],
            ['level' => '2', 'name' => 'Arts 2', 'code' => 'A2', 'is_active' => true],
            ['level' => '3', 'name' => 'Arts 3', 'code' => 'A3', 'is_active' => true],
            ['level' => '4', 'name' => 'Arts 4', 'code' => 'A4', 'is_active' => true],
            ['level' => '5', 'name' => 'Arts 5', 'code' => 'A5', 'is_active' => true],
            ['level' => '6', 'name' => 'Arts 6', 'code' => 'A6', 'is_active' => true],
            ['level' => '1', 'name' => 'Physical Education 1', 'code' => 'PE1', 'is_active' => true],
            ['level' => '2', 'name' => 'Physical Education 2', 'code' => 'PE2', 'is_active' => true],
            ['level' => '3', 'name' => 'Physical Education 3', 'code' => 'PE3', 'is_active' => true],
            ['level' => '4', 'name' => 'Physical Education 4', 'code' => 'PE4', 'is_active' => true],
            ['level' => '5', 'name' => 'Physical Education 5', 'code' => 'PE5', 'is_active' => true],
            ['level' => '6', 'name' => 'Physical Education 6', 'code' => 'PE6', 'is_active' => true],
            ['level' => '1', 'name' => 'Health 1', 'code' => 'H1', 'is_active' => true],
            ['level' => '2', 'name' => 'Health 2', 'code' => 'H2', 'is_active' => true],
            ['level' => '3', 'name' => 'Health 3', 'code' => 'H3', 'is_active' => true],
            ['level' => '4', 'name' => 'Health 4', 'code' => 'H4', 'is_active' => true],
            ['level' => '5', 'name' => 'Health 5', 'code' => 'H5', 'is_active' => true],
            ['level' => '6', 'name' => 'Health 6', 'code' => 'H6', 'is_active' => true],
        ];

        foreach ($subjects as $subject) {
            $gradeLevelId = DB::table('grade_levels')
                ->where('level', $subject['level'])
                ->value('id');

            if (! $gradeLevelId) {
                continue;
            }

            $subjectExists = DB::table('subjects')->where('level', $gradeLevelId)->where('name', $subject['name'])->exists();

            if (! $subjectExists) {
                DB::table('subjects')->insert($subject);
            }
        }
    }
}
