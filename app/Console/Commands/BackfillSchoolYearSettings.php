<?php

namespace App\Console\Commands;

use App\Models\GradeLevelSubject;
use App\Models\SchoolYear;
use App\Models\SchoolYearMonthDay;
use Illuminate\Console\Command;

class BackfillSchoolYearSettings extends Command
{
    protected $signature = 'settings:backfill-active-school-year';

    protected $description = 'Backfill active school year month-day settings and missing assessment weights.';

    public function handle(): int
    {
        $weightsSummary = $this->backfillAssessmentWeights();

        $this->line(sprintf(
            'Assessment weights: checked=%d backfilled=%d skipped=%d warnings=%d',
            $weightsSummary['checked'],
            $weightsSummary['backfilled'],
            $weightsSummary['skipped'],
            $weightsSummary['warnings'],
        ));

        $schoolYear = SchoolYear::getRealActive();

        if (! $schoolYear) {
            $this->warn('No active school year found. Active school year school days is not set.');

            return self::FAILURE;
        }

        $monthSummary = $this->backfillMonthDays($schoolYear);

        $this->line(sprintf(
            'School year month days (%s): checked=%d backfilled=%d skipped=%d warnings=%d',
            $schoolYear->name,
            $monthSummary['checked'],
            $monthSummary['backfilled'],
            $monthSummary['skipped'],
            $monthSummary['warnings'],
        ));

        $this->info('Backfill completed.');

        return self::SUCCESS;
    }

    /**
     * @return array{checked:int,backfilled:int,skipped:int,warnings:int}
     */
    private function backfillMonthDays(SchoolYear $schoolYear): array
    {
        $summary = [
            'checked' => 0,
            'backfilled' => 0,
            'skipped' => 0,
            'warnings' => 0,
        ];

        $monthsInRange = $schoolYear->getMonthsInRange();
        $existingMonthDays = SchoolYearMonthDay::query()
            ->where('school_year_id', $schoolYear->id)
            ->whereIn('month', $monthsInRange)
            ->get()
            ->keyBy('month');

        foreach ($monthsInRange as $month) {
            $summary['checked']++;

            $defaultSchoolDays = SchoolYearMonthDay::defaultSchoolDaysForMonth($month);

            if ($defaultSchoolDays === null) {
                $summary['warnings']++;
                $summary['skipped']++;
                $this->warn(sprintf(
                    'No default school-day mapping for month %s (%d). Skipped.',
                    SchoolYearMonthDay::getMonthName($month),
                    $month,
                ));

                continue;
            }

            $record = $existingMonthDays->get($month);

            if (! $record) {
                SchoolYearMonthDay::query()->create([
                    'school_year_id' => $schoolYear->id,
                    'month' => $month,
                    'school_days' => $defaultSchoolDays,
                ]);

                $summary['backfilled']++;

                continue;
            }

            if ($record->school_days === null) {
                SchoolYearMonthDay::query()
                    ->whereKey($record->id)
                    ->update(['school_days' => $defaultSchoolDays]);
                $summary['backfilled']++;

                continue;
            }

            $summary['skipped']++;
        }

        return $summary;
    }

    /**
     * @return array{checked:int,backfilled:int,skipped:int,warnings:int}
     */
    private function backfillAssessmentWeights(): array
    {
        $summary = [
            'checked' => 0,
            'backfilled' => 0,
            'skipped' => 0,
            'warnings' => 0,
        ];

        $defaults = GradeLevelSubject::defaultAssessmentWeights();

        GradeLevelSubject::query()
            ->each(function (GradeLevelSubject $gradeLevelSubject) use (&$summary, $defaults): void {
                $summary['checked']++;

                $rawWrittenWorks = $gradeLevelSubject->getRawOriginal('written_works_weight');
                $rawPerformanceTasks = $gradeLevelSubject->getRawOriginal('performance_tasks_weight');
                $rawQuarterlyAssessments = $gradeLevelSubject->getRawOriginal('quarterly_assessments_weight');

                $hasNullWeight = $rawWrittenWorks === null
                    || $rawPerformanceTasks === null
                    || $rawQuarterlyAssessments === null;

                $allWeightsZero = (int) $rawWrittenWorks === 0
                    && (int) $rawPerformanceTasks === 0
                    && (int) $rawQuarterlyAssessments === 0;

                if (! $hasNullWeight && ! $allWeightsZero) {
                    $summary['skipped']++;

                    return;
                }

                $updates = [];

                if ($allWeightsZero) {
                    $updates = $defaults;
                } else {
                    foreach ($defaults as $column => $defaultValue) {
                        if ($gradeLevelSubject->getRawOriginal($column) === null) {
                            $updates[$column] = $defaultValue;
                        }
                    }
                }

                if ($updates === []) {
                    $summary['skipped']++;

                    return;
                }

                $gradeLevelSubject->update($updates);
                $summary['backfilled']++;

                $total = $gradeLevelSubject->written_works_weight
                    + $gradeLevelSubject->performance_tasks_weight
                    + $gradeLevelSubject->quarterly_assessments_weight;

                if ($total !== 100) {
                    $summary['warnings']++;
                    $this->warn(sprintf(
                        'GradeLevelSubject %d totals %d%% after backfill. Review this row manually.',
                        $gradeLevelSubject->id,
                        $total,
                    ));
                }
            });

        return $summary;
    }
}
