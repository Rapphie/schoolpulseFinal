<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = \App\Models\Schedule::class;

    public function definition(): array
    {
        $section = \App\Models\Section::inRandomOrder()->first() ?? \App\Models\Section::factory()->create();
        $subject = \App\Models\Subject::inRandomOrder()->first();
        $teacher = \App\Models\Teacher::inRandomOrder()->first();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $startHour = $this->faker->numberBetween(7, 15);
        $startTime = sprintf('%02d:00:00', $startHour);
        $endTime = sprintf('%02d:00:00', $startHour + 1);

        return [
            'section_id' => $section,
            'subject_id' => $subject,
            'teacher_id' => $teacher,
            'day_of_week' => $this->faker->randomElement($days),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => 'Room ' . strtoupper($this->faker->randomLetter()) . $this->faker->numberBetween(1, 20),
        ];
    }

    public function forSection($sectionId)
    {
        return $this->state(function (array $attributes) use ($sectionId) {
            return [
                'section_id' => $sectionId,
            ];
        });
    }

    public function forSubject($subjectId)
    {
        return $this->state(function (array $attributes) use ($subjectId) {
            return [
                'subject_id' => $subjectId,
            ];
        });
    }

    public function forTeacher($teacherId)
    {
        return $this->state(function (array $attributes) use ($teacherId) {
            return [
                'teacher_id' => $teacherId,
            ];
        });
    }
}
