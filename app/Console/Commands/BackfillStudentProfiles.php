<?php

namespace App\Console\Commands;

use App\Models\AssessmentScore;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
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

        // Process enrollments
        $enrollments = Enrollment::whereNull('student_profile_id')
            ->with(['student', 'class.section.gradeLevel', 'schoolYear'])
            ->get();

        $this->info("Found {$enrollments->count()} enrollments without profiles.");

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made.');
        }

        $created = 0;
        $linked = 0;
        $skipped = 0;

        // Backfill enrollments first
        $bar = $this->output->createProgressBar(max(1, $enrollments->count()));
        $bar->start();

        foreach ($enrollments as $enrollment) {
            $gradeLevelId = $enrollment->class?->section?->grade_level_id;

            if (! $gradeLevelId) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if (! $dryRun) {
                DB::transaction(function () use ($enrollment, $gradeLevelId, &$created, &$linked) {
                    $profile = StudentProfile::firstOrCreate(
                        [
                            'student_id' => $enrollment->student_id,
                            'school_year_id' => $enrollment->school_year_id,
                        ],
                        [
                            'grade_level_id' => $gradeLevelId,
                            'status' => 'enrolled',
                        ]
                    );

                    if ($profile->wasRecentlyCreated) {
                        $created++;
                    }

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
        $this->newLine();

        $this->info("Enrollments: created={$created} linked={$linked} skipped={$skipped}");

        // Backfill grades
        $grades = Grade::whereNull('student_profile_id')
            ->whereNotNull('school_year_id')
            ->get();

        $this->info("Found {$grades->count()} grades without student_profile_id.");
        $gBar = $this->output->createProgressBar(max(1, $grades->count()));
        $gBar->start();
        $gLinked = 0;
        $gSkipped = 0;
        foreach ($grades as $grade) {
            $profile = StudentProfile::where('student_id', $grade->student_id)
                ->where('school_year_id', $grade->school_year_id)
                ->first();

            if ($profile) {
                if (! $dryRun) {
                    $grade->update(['student_profile_id' => $profile->id]);
                }
                $gLinked++;
            } else {
                $gSkipped++;
            }

            $gBar->advance();
        }
        $gBar->finish();
        $this->newLine();
        $this->info("Grades linked: {$gLinked} skipped: {$gSkipped}");

        // Backfill attendances
        $attendances = Attendance::whereNull('student_profile_id')
            ->whereNotNull('school_year_id')
            ->with('class.section.gradeLevel')
            ->get();

        $this->info("Found {$attendances->count()} attendances without student_profile_id.");
        $aBar = $this->output->createProgressBar(max(1, $attendances->count()));
        $aBar->start();
        $aLinked = 0;
        $aSkipped = 0;
        foreach ($attendances as $att) {
            $gradeLevelId = $att->class?->section?->grade_level_id;
            $profile = StudentProfile::where('student_id', $att->student_id)
                ->where('school_year_id', $att->school_year_id)
                ->first();

            if (! $profile && $gradeLevelId) {
                // create profile when grade level can be inferred
                if (! $dryRun) {
                    $profile = StudentProfile::firstOrCreate(
                        ['student_id' => $att->student_id, 'school_year_id' => $att->school_year_id],
                        ['grade_level_id' => $gradeLevelId, 'status' => 'enrolled']
                    );
                }
            }

            if ($profile) {
                if (! $dryRun) {
                    $att->update(['student_profile_id' => $profile->id]);
                }
                $aLinked++;
            } else {
                $aSkipped++;
            }

            $aBar->advance();
        }
        $aBar->finish();
        $this->newLine();
        $this->info("Attendances linked: {$aLinked} skipped: {$aSkipped}");

        // Backfill assessment scores
        $scores = AssessmentScore::whereNull('student_profile_id')
            ->with(['assessment.class.section.gradeLevel'])
            ->get();

        $this->info("Found {$scores->count()} assessment scores without student_profile_id.");
        $sBar = $this->output->createProgressBar(max(1, $scores->count()));
        $sBar->start();
        $sLinked = 0;
        $sSkipped = 0;
        foreach ($scores as $score) {
            $assessment = $score->assessment;
            $gradeLevelId = $assessment?->class?->section?->grade_level_id;
            $profile = StudentProfile::where('student_id', $score->student_id)
                ->where('school_year_id', $assessment?->school_year_id)
                ->first();

            if (! $profile && $gradeLevelId && $assessment?->school_year_id) {
                if (! $dryRun) {
                    $profile = StudentProfile::firstOrCreate(
                        ['student_id' => $score->student_id, 'school_year_id' => $assessment->school_year_id],
                        ['grade_level_id' => $gradeLevelId, 'status' => 'enrolled']
                    );
                }
            }

            if ($profile) {
                if (! $dryRun) {
                    $score->update(['student_profile_id' => $profile->id]);
                }
                $sLinked++;
            } else {
                $sSkipped++;
            }

            $sBar->advance();
        }
        $sBar->finish();
        $this->newLine();
        $this->info("Assessment scores linked: {$sLinked} skipped: {$sSkipped}");

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
