<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class GuardianService
{
    public function activeSchoolYear(): ?SchoolYear
    {
        return SchoolYear::active()->first();
    }

    /**
     * @return array<int, string>
     */
    public function quarterLabels(): array
    {
        return [
            1 => '1st Quarter',
            2 => '2nd Quarter',
            3 => '3rd Quarter',
            4 => '4th Quarter',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function quarterSearchValues(int $quarterNumber): array
    {
        $labels = $this->quarterLabels();
        $wordLabels = [
            1 => 'FIRST QUARTER',
            2 => 'SECOND QUARTER',
            3 => 'THIRD QUARTER',
            4 => 'FOURTH QUARTER',
        ];

        return array_values(array_unique([
            (string) $quarterNumber,
            'Q'.$quarterNumber,
            $labels[$quarterNumber] ?? '',
            $wordLabels[$quarterNumber] ?? '',
        ]));
    }

    public function quarterNumberFromValue(?string $value): int
    {
        if ($value === null) {
            return 1;
        }

        $labels = $this->quarterLabels();
        $numeric = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        if ($numeric >= 1 && $numeric <= count($labels)) {
            return $numeric;
        }

        $normalized = strtoupper(trim($value));
        $map = [
            'FIRST QUARTER' => 1,
            'SECOND QUARTER' => 2,
            'THIRD QUARTER' => 3,
            'FOURTH QUARTER' => 4,
            'Q1' => 1,
            'Q2' => 2,
            'Q3' => 3,
            'Q4' => 4,
            '1ST QUARTER' => 1,
            '2ND QUARTER' => 2,
            '3RD QUARTER' => 3,
            '4TH QUARTER' => 4,
        ];

        return $map[$normalized] ?? 1;
    }

    public function normalizeQuarterLabel(?string $value): string
    {
        $labels = $this->quarterLabels();

        if ($value === null || trim($value) === '') {
            return $labels[1];
        }

        $quarterNumber = $this->quarterNumberFromValue($value);

        return $labels[$quarterNumber] ?? trim($value);
    }

    public function gradeRemark(?float $grade): string
    {
        if ($grade === null) {
            return 'No Grade';
        }

        return $grade >= 75 ? 'Passed' : 'Failed';
    }

    public function guardianStudents(Guardian $guardian): EloquentCollection
    {
        return $guardian->students()
            ->with([
                'enrollments' => function ($query): void {
                    $query->with([
                        'class.section.gradeLevel',
                        'schoolYear',
                    ])->orderByDesc('enrollment_date');
                },
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function resolveCurrentEnrollment(Student $student, ?SchoolYear $activeSchoolYear): ?Enrollment
    {
        $student->loadMissing([
            'enrollments.class.section.gradeLevel',
            'enrollments.schoolYear',
        ]);

        if ($activeSchoolYear) {
            $activeMatch = $student->enrollments->firstWhere('school_year_id', $activeSchoolYear->id);
            if ($activeMatch instanceof Enrollment) {
                return $activeMatch;
            }
        }

        $latest = $student->enrollments->sortByDesc('enrollment_date')->first();

        return $latest instanceof Enrollment ? $latest : null;
    }

    /**
     * @return array{
     *     full_name: string,
     *     student_identifier: string,
     *     lrn: string,
     *     grade_level: string,
     *     class_section: string,
     *     school_year: string
     * }
     */
    public function studentSummary(Student $student, ?SchoolYear $activeSchoolYear): array
    {
        $currentEnrollment = $this->resolveCurrentEnrollment($student, $activeSchoolYear);

        return [
            'full_name' => $student->full_name,
            'student_identifier' => $student->student_id ?? $student->lrn ?? 'N/A',
            'lrn' => $student->lrn ?? 'N/A',
            'grade_level' => $currentEnrollment?->class?->section?->gradeLevel?->name ?? 'Unassigned',
            'class_section' => $currentEnrollment?->class?->section?->name ?? 'Unassigned',
            'school_year' => $currentEnrollment?->schoolYear?->name ?? $activeSchoolYear?->name ?? '—',
        ];
    }

    public function gradesForStudent(Student $student, ?SchoolYear $activeSchoolYear): Collection
    {
        return Grade::query()
            ->where('student_id', $student->id)
            ->when($activeSchoolYear, function (Builder $query) use ($activeSchoolYear): void {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->with([
                'subject:id,name',
                'teacher:id,user_id',
                'teacher.user:id,first_name,last_name',
            ])
            ->orderByDesc('quarter_int')
            ->orderBy('subject_id')
            ->get();
    }

    public function gradesByQuarter(Student $student, ?SchoolYear $activeSchoolYear): Collection
    {
        $quarterBuckets = collect($this->quarterLabels())->mapWithKeys(function (string $label): array {
            return [$label => collect()];
        });

        $this->gradesForStudent($student, $activeSchoolYear)
            ->each(function (Grade $grade) use ($quarterBuckets): void {
                $quarterLabel = $this->normalizeQuarterLabel($grade->quarter);
                if (! $quarterBuckets->has($quarterLabel)) {
                    $quarterBuckets->put($quarterLabel, collect());
                }

                $quarterBuckets->get($quarterLabel)->push([
                    'subject' => $grade->subject?->name ?? 'Subject',
                    'teacher' => $grade->teacher?->user?->full_name ?? '—',
                    'grade' => $grade->grade,
                    'remarks' => $this->gradeRemark($grade->grade),
                    'quarter_label' => $quarterLabel,
                    'quarter_number' => $this->quarterNumberFromValue($grade->quarter),
                ]);
            });

        return $quarterBuckets->map(function (Collection $quarterEntries): Collection {
            return $quarterEntries->sortBy('subject')->values();
        });
    }

    /**
     * @return array<string, float|null>
     */
    public function quarterAverages(Collection $gradesByQuarter): array
    {
        $averages = [];

        foreach ($gradesByQuarter as $quarterLabel => $quarterGrades) {
            $gradeValues = collect($quarterGrades)->pluck('grade')
                ->filter(fn ($value): bool => $value !== null)
                ->map(fn ($value): float => (float) $value);

            $averages[$quarterLabel] = $gradeValues->isNotEmpty()
                ? round($gradeValues->avg(), 2)
                : null;
        }

        return $averages;
    }

    public function generalWeightedAverage(Collection $gradesByQuarter): ?float
    {
        $gradeValues = $gradesByQuarter
            ->flatMap(function (Collection $quarterGrades): Collection {
                return $quarterGrades->pluck('grade');
            })
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): float => (float) $value)
            ->values();

        if ($gradeValues->isEmpty()) {
            return null;
        }

        return round($gradeValues->avg(), 2);
    }

    public function latestQuarterLabel(Collection $gradesByQuarter): ?string
    {
        foreach (array_reverse($this->quarterLabels(), true) as $quarterLabel) {
            $entries = $gradesByQuarter->get($quarterLabel, collect());
            if ($entries instanceof Collection && $entries->isNotEmpty()) {
                return $quarterLabel;
            }
        }

        return null;
    }

    public function attendanceQuery(Student $student, ?SchoolYear $activeSchoolYear): Builder
    {
        return Attendance::query()
            ->where('student_id', $student->id)
            ->when($activeSchoolYear, function (Builder $query) use ($activeSchoolYear): void {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->with(['subject:id,name']);
    }

    public function attendanceRecords(Student $student, ?SchoolYear $activeSchoolYear): Collection
    {
        return $this->attendanceQuery($student, $activeSchoolYear)
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->get();
    }

    /**
     * @return array{present: int, absent: int, late: int, excused: int}
     */
    public function attendanceSummary(Collection $attendanceRecords): array
    {
        $summary = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
        ];

        $attendanceRecords->each(function (Attendance $attendance) use (&$summary): void {
            $status = strtolower($attendance->status ?? 'present');
            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            }
        });

        return $summary;
    }

    public function attendanceRate(array $attendanceSummary): float
    {
        $total = array_sum($attendanceSummary);
        if ($total === 0) {
            return 0.0;
        }

        return round((($attendanceSummary['present'] ?? 0) / $total) * 100, 2);
    }

    /**
     * @return array<int, array{status: string, count: int, percentage: float}>
     */
    public function attendanceSummaryWithPercentages(Collection $attendanceRecords): array
    {
        $summary = $this->attendanceSummary($attendanceRecords);
        $total = array_sum($summary);

        return collect($summary)
            ->map(function (int $count, string $status) use ($total): array {
                return [
                    'status' => $status,
                    'count' => $count,
                    'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     formatted_date: string,
     *     subject: string,
     *     status: string,
     *     quarter: string,
     *     time_in: string
     * }
     */
    public function formatAttendanceRecord(Attendance $attendance): array
    {
        return [
            'formatted_date' => optional($attendance->date)->format('M d, Y') ?? '—',
            'subject' => $attendance->subject?->name ?? 'Homeroom',
            'status' => strtolower($attendance->status ?? 'present'),
            'quarter' => $this->normalizeQuarterLabel($attendance->quarter),
            'time_in' => optional($attendance->time_in)->format('h:i A') ?? '—',
        ];
    }

    /**
     * @return Collection<int, array{
     *     type: string,
     *     title: string,
     *     description: string,
     *     occurred_at: int,
     *     occurred_at_label: string
     * }>
     */
    public function recentActivities(Student $student, ?SchoolYear $activeSchoolYear, int $limit = 8): Collection
    {
        $gradeActivities = Grade::query()
            ->where('student_id', $student->id)
            ->when($activeSchoolYear, function (Builder $query) use ($activeSchoolYear): void {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->with('subject:id,name')
            ->latest('updated_at')
            ->take($limit)
            ->get()
            ->map(function (Grade $grade): array {
                $occurredAt = $grade->updated_at ?? $grade->created_at;

                return [
                    'type' => 'grade',
                    'title' => 'Grade updated',
                    'description' => ($grade->subject?->name ?? 'Subject').': '.($grade->grade ?? '—').' ('.$this->normalizeQuarterLabel($grade->quarter).')',
                    'occurred_at' => $occurredAt?->timestamp ?? 0,
                    'occurred_at_label' => $occurredAt?->format('M d, Y h:i A') ?? '—',
                ];
            })
            ->toBase();

        $attendanceActivities = Attendance::query()
            ->where('student_id', $student->id)
            ->when($activeSchoolYear, function (Builder $query) use ($activeSchoolYear): void {
                $query->where('school_year_id', $activeSchoolYear->id);
            })
            ->with('subject:id,name')
            ->latest('date')
            ->take($limit)
            ->get()
            ->map(function (Attendance $attendance): array {
                $occurredAt = $attendance->created_at;

                return [
                    'type' => 'attendance',
                    'title' => 'Attendance recorded',
                    'description' => ucfirst(strtolower($attendance->status ?? 'present')).' in '.($attendance->subject?->name ?? 'Homeroom'),
                    'occurred_at' => $occurredAt?->timestamp ?? 0,
                    'occurred_at_label' => $occurredAt?->format('M d, Y h:i A') ?? (optional($attendance->date)->format('M d, Y') ?? '—'),
                ];
            })
            ->toBase();

        return $gradeActivities
            ->merge($attendanceActivities)
            ->sortByDesc('occurred_at')
            ->take($limit)
            ->values();
    }
}
