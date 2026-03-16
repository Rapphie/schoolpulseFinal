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
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $panel = $request->query('panel', 'teacher_enrollment');
        $teacherEnrollment = Setting::query()->where('key', 'teacher_enrollment')->first();
        $schoolYear = SchoolYear::getRealActive();

        $gradeLevels = GradeLevel::query()->orderBy('level')->get();
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

        $monthsInRange = [];
        $monthDays = [];

        if ($schoolYear) {
            $monthsInRange = $schoolYear->getMonthsInRange();

            foreach ($monthsInRange as $month) {
                SchoolYearMonthDay::query()->firstOrCreate([
                    'school_year_id' => $schoolYear->id,
                    'month' => $month,
                ], [
                    'school_days' => 0,
                ]);
            }

            $monthDays = $schoolYear->monthDays()->get()->keyBy('month');
        }

        return view('admin.settings.index', compact(
            'panel',
            'teacherEnrollment',
            'schoolYear',
            'gradeLevels',
            'gradeLevelSubjects',
            'monthsInRange',
            'monthDays'
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
                $this->updateAssessmentWeights($validated['weights'] ?? []);
                break;

            case 'school_year_month_days':
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
        foreach ($weights as $id => $data) {
            $gradeLevelSubject = GradeLevelSubject::query()->find($id);

            if ($gradeLevelSubject) {
                $gradeLevelSubject->update([
                    'written_works_weight' => (int) $data['written_works_weight'],
                    'performance_tasks_weight' => (int) $data['performance_tasks_weight'],
                    'quarterly_assessments_weight' => (int) $data['quarterly_assessments_weight'],
                ]);
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
