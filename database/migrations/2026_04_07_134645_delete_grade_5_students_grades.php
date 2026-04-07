<?php

use App\Models\GradeLevel;
use App\Models\StudentProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Ensure grades triggers use a valid definer for this environment.
     */
    private function ensureGradesTriggerDefiner(): void
    {
        $triggerDefiners = collect(DB::select("SHOW TRIGGERS WHERE `Table` = 'grades'"))
            ->pluck('Definer')
            ->filter()
            ->unique();

        if ($triggerDefiners->isEmpty() || ($triggerDefiners->count() === 1 && $triggerDefiners->first() === 'root@localhost')) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS after_grades_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_grades_update');
        DB::unprepared('DROP TRIGGER IF EXISTS after_grades_delete');

        DB::unprepared('CREATE DEFINER=`root`@`localhost` TRIGGER after_grades_insert
            AFTER INSERT ON grades
            FOR EACH ROW
            BEGIN
                UPDATE student_profiles
                SET final_average = (
                    SELECT AVG(grade)
                    FROM grades
                    WHERE student_profile_id = NEW.student_profile_id
                )
                WHERE id = NEW.student_profile_id;
            END');

        DB::unprepared('CREATE DEFINER=`root`@`localhost` TRIGGER after_grades_update
            AFTER UPDATE ON grades
            FOR EACH ROW
            BEGIN
                UPDATE student_profiles
                SET final_average = (
                    SELECT AVG(grade)
                    FROM grades
                    WHERE student_profile_id = NEW.student_profile_id
                )
                WHERE id = NEW.student_profile_id;
            END');

        DB::unprepared('CREATE DEFINER=`root`@`localhost` TRIGGER after_grades_delete
            AFTER DELETE ON grades
            FOR EACH ROW
            BEGIN
                UPDATE student_profiles
                SET final_average = (
                    SELECT AVG(grade)
                    FROM grades
                    WHERE student_profile_id = OLD.student_profile_id
                )
                WHERE id = OLD.student_profile_id;
            END');
    }

    /**
     * Run the migrations.
     *
     * Deletes all Grade 5 students' grades data and their relationships.
     * This includes: grades, assessment scores, attendance records, enrollments, and student profiles.
     */
    public function up(): void
    {
        $this->ensureGradesTriggerDefiner();

        // Find Grade 5 level
        $gradeLevel = GradeLevel::where('level', 5)->first();

        if (! $gradeLevel) {
            Log::info('No Grade 5 found in the database. Skipping deletion.');

            return;
        }

        Log::info("Found Grade 5 with ID: {$gradeLevel->id}, Name: {$gradeLevel->name}");

        // Get all Grade 5 student profiles
        $studentProfiles = StudentProfile::where('grade_level_id', $gradeLevel->id)->get();

        if ($studentProfiles->isEmpty()) {
            Log::info('No Grade 5 student profiles found. Skipping deletion.');

            return;
        }

        $studentIds = $studentProfiles->pluck('student_id')->unique()->toArray();
        $profileIds = $studentProfiles->pluck('id')->toArray();

        Log::info('Found '.count($studentIds).' Grade 5 students to process');

        // Disable foreign key checks temporarily to avoid constraint issues
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // Delete assessment scores for these students (both by student_id and student_profile_id)
            $assessmentScoresDeleted = DB::table('assessment_scores')
                ->whereIn('student_id', $studentIds)
                ->orWhereIn('student_profile_id', $profileIds)
                ->delete();
            Log::info("Deleted {$assessmentScoresDeleted} assessment scores");

            // Delete grades for these students (both by student_id and student_profile_id)
            $gradesDeleted = DB::table('grades')
                ->whereIn('student_id', $studentIds)
                ->orWhereIn('student_profile_id', $profileIds)
                ->delete();
            Log::info("Deleted {$gradesDeleted} grades");

            // Delete attendance records for these students (both by student_id and student_profile_id)
            $attendancesDeleted = DB::table('attendances')
                ->whereIn('student_id', $studentIds)
                ->orWhereIn('student_profile_id', $profileIds)
                ->delete();
            Log::info("Deleted {$attendancesDeleted} attendance records");

            // Delete enrollments for these students (both by student_id and student_profile_id)
            $enrollmentsDeleted = DB::table('enrollments')
                ->whereIn('student_id', $studentIds)
                ->orWhereIn('student_profile_id', $profileIds)
                ->delete();
            Log::info("Deleted {$enrollmentsDeleted} enrollments");

            // Delete the student profiles for Grade 5
            $profilesDeleted = DB::table('student_profiles')
                ->where('grade_level_id', $gradeLevel->id)
                ->delete();
            Log::info("Deleted {$profilesDeleted} student profiles");

            Log::info('Grade 5 students grades data and relationships deleted successfully');
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration deletes data permanently and cannot be reversed.
     * The down() method is intentionally left empty as data restoration
     * would require backups.
     */
    public function down(): void
    {
        // This migration permanently deletes data and cannot be reversed.
        // To restore data, you would need to restore from a backup.
    }
};
