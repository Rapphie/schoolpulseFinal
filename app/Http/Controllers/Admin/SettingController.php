<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminSettingsRequest;
use App\Models\GradeLevel;
use App\Models\GradeLevelSubject;
use App\Models\SchoolYear;
use App\Models\SchoolYearMonthDay;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $panel = $request->query('panel', 'teacher_enrollment');
        $teacherEnrollment = Setting::query()->where('key', 'teacher_enrollment')->first();
        $schoolYear = SchoolYear::getRealActive();

        $gradeLevels = GradeLevel::query()->orderBy('level')->get();
        $hasGradeLevelSubjectsTable = Schema::hasTable('grade_level_subjects');
        $hasSchoolYearMonthDaysTable = Schema::hasTable('school_year_month_days');
        $gradeLevelSubjects = collect();

        if ($hasGradeLevelSubjectsTable) {
            $gradeLevelSubjects = GradeLevelSubject::query()
                ->with(['gradeLevel', 'subject'])
                ->whereHas('gradeLevel')
                ->whereHas('subject')
                ->orderBy('grade_level_id')
                ->get()
                ->groupBy('grade_level_id')
                ->map(function (Collection $subjects): Collection {
                    return $subjects->sortBy(
                        fn (GradeLevelSubject $gradeLevelSubject) => mb_strtolower(
                            (string) $gradeLevelSubject->subject?->name
                        )
                    )->values();
                });
        }

        $monthsInRange = [];
        $monthDays = collect();
        $monthDaysSetupWarning = null;

        if ($schoolYear && $hasSchoolYearMonthDaysTable) {
            $monthsInRange = $schoolYear->getMonthsInRange();
            $monthDays = $schoolYear->monthDays()
                ->whereIn('month', $monthsInRange)
                ->get()
                ->keyBy('month');

            $hasMissingMonths = collect($monthsInRange)
                ->contains(fn (int $month): bool => ! $monthDays->has($month));

            $hasUnmappedMonths = collect($monthsInRange)
                ->contains(fn (int $month): bool => ! SchoolYearMonthDay::hasDefaultSchoolDaysForMonth($month));

            if ($hasMissingMonths || $hasUnmappedMonths) {
                $monthDaysSetupWarning = 'Active school year school days is not set for all months. '
                    .'Run "php artisan settings:backfill-active-school-year" and review this panel.';
            }
        }

        return view('admin.settings.index', compact(
            'panel',
            'teacherEnrollment',
            'schoolYear',
            'gradeLevels',
            'gradeLevelSubjects',
            'hasGradeLevelSubjectsTable',
            'hasSchoolYearMonthDaysTable',
            'monthsInRange',
            'monthDays',
            'monthDaysSetupWarning'
        ));
    }

    public function update(UpdateAdminSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $panel = $validated['panel'];

        switch ($panel) {
            case 'teacher_enrollment':
                Setting::query()->updateOrCreate(
                    ['key' => 'teacher_enrollment'],
                    ['value' => $request->boolean('teacher_enrollment') ? '1' : '0']
                );
                break;

            case 'assessment_weights':
                if (! Schema::hasTable('grade_level_subjects')) {
                    return redirect()
                        ->route('admin.settings.index', ['panel' => 'assessment_weights'])
                        ->with('error', 'Assessment weights are unavailable because required tables are not migrated yet.');
                }

                $this->updateAssessmentWeights($validated['weights'] ?? []);
                break;

            case 'school_year_month_days':
                if (! Schema::hasTable('school_year_month_days')) {
                    return redirect()
                        ->route('admin.settings.index', ['panel' => 'school_year_month_days'])
                        ->with('error', 'School year month days are unavailable because required tables are not migrated yet.');
                }

                $result = $this->updateSchoolYearMonthDays($validated['school_days'] ?? []);

                if ($result instanceof RedirectResponse) {
                    return $result;
                }
                break;
        }

        Cache::forget('sidebar_settings');

        return redirect()
            ->route('admin.settings.index', ['panel' => $panel])
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * @param  array<int|string, array<string, int|string|null>>  $weights
     */
    private function updateAssessmentWeights(array $weights): void
    {
        $updatedSubjectIds = [];

        foreach ($weights as $id => $data) {
            $gradeLevelSubject = GradeLevelSubject::query()->find($id);

            if ($gradeLevelSubject) {
                $changed = $gradeLevelSubject->written_works_weight !== (int) $data['written_works_weight'] ||
                           $gradeLevelSubject->performance_tasks_weight !== (int) $data['performance_tasks_weight'] ||
                           $gradeLevelSubject->quarterly_assessments_weight !== (int) $data['quarterly_assessments_weight'];

                if ($changed) {
                    $gradeLevelSubject->update([
                        'written_works_weight' => (int) $data['written_works_weight'],
                        'performance_tasks_weight' => (int) $data['performance_tasks_weight'],
                        'quarterly_assessments_weight' => (int) $data['quarterly_assessments_weight'],
                    ]);
                    $updatedSubjectIds[] = $gradeLevelSubject->subject_id;
                }
            }
        }

        if (count($updatedSubjectIds) > 0) {
            $activeSchoolYear = SchoolYear::where('is_active', true)->first();
            if ($activeSchoolYear) {
                $quarterLockService = app(\App\Services\QuarterLockService::class);

                $assessmentsGrouped = \App\Models\Assessment::whereIn('subject_id', $updatedSubjectIds)
                    ->where('school_year_id', $activeSchoolYear->id)
                    ->select('class_id', 'subject_id', 'quarter', 'teacher_id')
                    ->distinct()
                    ->get();

                foreach ($assessmentsGrouped as $agg) {
                    // We bypass the lock check for admin weight changes to ensure consistency across the whole school year
                    \App\Jobs\RecalculateQuarterGradesJob::dispatch(
                        $agg->class_id,
                        $agg->subject_id,
                        $agg->quarter,
                        $agg->teacher_id,
                        $activeSchoolYear->id
                    );
                }
            }
        }
    }

    /**
     * @param  array<int|string, int|string|null>  $monthDays
     */
    private function updateSchoolYearMonthDays(array $monthDays): ?RedirectResponse
    {
        $schoolYear = SchoolYear::getRealActive();

        if (! $schoolYear) {
            return redirect()
                ->route('admin.settings.index', ['panel' => 'school_year_month_days'])
                ->with('error', 'No active school year found.');
        }

        foreach ($monthDays as $month => $days) {
            SchoolYearMonthDay::query()->updateOrCreate(
                [
                    'school_year_id' => $schoolYear->id,
                    'month' => (int) $month,
                ],
                [
                    'school_days' => max(0, (int) $days),
                ]
            );
        }

        return null;
    }
}
