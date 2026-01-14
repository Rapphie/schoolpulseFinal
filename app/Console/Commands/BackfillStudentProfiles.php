<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\StudentProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStudentProfiles extends Command
{
    protected $signature = 'profiles:backfill {--dry-run : Show what would be created without making changes}';
    protected $description = 'Create student profiles for existing enrollments that do not have one.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Get all enrollments without a student_profile_id
        $enrollments = Enrollment::whereNull('student_profile_id')
            ->with(['student', 'class.section.gradeLevel', 'schoolYear'])
            ->get();

        if ($enrollments->isEmpty()) {
            $this->info('All enrollments already have linked student profiles.');
            return 0;
        }

        $this->info("Found {$enrollments->count()} enrollments without profiles.");

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made.');
        }

        $created = 0;
        $linked = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($enrollments->count());
        $bar->start();

        foreach ($enrollments as $enrollment) {
            $gradeLevelId = $enrollment->class?->section?->grade_level_id;

            if (!$gradeLevelId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (!$dryRun) {
                DB::transaction(function () use ($enrollment, $gradeLevelId, &$created, &$linked) {
                    // Find or create the profile
                    $profile = StudentProfile::firstOrCreate(
                        [
                            'student_id' => $enrollment->student_id,
                            'school_year_id' => $enrollment->school_year_id,
                        ],
                        [
                            'grade_level_id' => $gradeLevelId,
                            'status' => 'active',
                        ]
                    );

                    if ($profile->wasRecentlyCreated) {
                        $created++;
                    }

                    // Link enrollment to profile
                    $enrollment->update(['student_profile_id' => $profile->id]);
                    $linked++;
                });
            } else {
                $created++;
                $linked++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Profiles created: {$created}");
        $this->info("Enrollments linked: {$linked}");
        if ($skipped > 0) {
            $this->warn("Skipped (missing grade level): {$skipped}");
        }

        return 0;
    }
}
