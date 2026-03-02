<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Sf2WorkbookExportService
{
    private const TEMPLATE_PATHS = [
        'storage/templates/School Form 2 (SF2).xlsx',
        'School Form 2 (SF2).xlsx',
    ];

    private const MALE_START_ROW = 13;

    private const MALE_END_ROW = 33;

    private const FEMALE_START_ROW = 35;

    private const FEMALE_END_ROW = 59;

    private const MALE_TOTAL_ROW = 34;

    private const FEMALE_TOTAL_ROW = 60;

    private const GRAND_TOTAL_ROW = 61;

    private const FIRST_DAY_COLUMN_INDEX = 4;

    private const MAX_DAY_COLUMNS = 25;

    private Spreadsheet $spreadsheet;

    /**
     * @var array<int, array{date: Carbon, day: int}>
     */
    private array $schoolDays = [];

    /**
     * @var array<int, array<string, string>>
     */
    private array $attendanceData = [];

    private ?SchoolYear $activeSchoolYear = null;

    private ?Classes $class = null;

    private Collection $students;

    public function __construct(
        private Section $section,
        private string $month,
        private ?string $schoolId = null,
        private ?string $schoolName = null,
    ) {}

    public function build(): Spreadsheet
    {
        $templatePath = $this->resolveTemplatePath();

        $this->spreadsheet = IOFactory::load($templatePath);
        $this->activeSchoolYear = SchoolYear::where('is_active', true)->first();
        $this->class = $this->resolveClass();

        $this->calculateSchoolDays();
        $this->students = $this->getEnrolledStudents();
        $this->loadAttendanceData();

        $pages = $this->paginateStudentsByGender($this->students);
        $totalPages = count($pages);
        $templateSheet = $this->spreadsheet->getSheet(0);

        for ($pageNumber = 2; $pageNumber <= $totalPages; $pageNumber++) {
            $clonedSheet = clone $templateSheet;
            $clonedSheet->setTitle('Page '.$pageNumber);
            $this->spreadsheet->addSheet($clonedSheet);
        }

        foreach ($pages as $index => $page) {
            $sheet = $this->spreadsheet->getSheet($index);

            $this->populateSheet(
                $sheet,
                $page['male'],
                $page['female'],
                $this->students,
                $index + 1,
                $totalPages
            );
        }

        return $this->spreadsheet;
    }

    private function resolveTemplatePath(): string
    {
        foreach (self::TEMPLATE_PATHS as $path) {
            $absolutePath = base_path($path);

            if (file_exists($absolutePath)) {
                return $absolutePath;
            }
        }

        throw new \RuntimeException('SF2 template not found in the expected template locations.');
    }

    private function resolveClass(): ?Classes
    {
        if (! $this->activeSchoolYear) {
            return null;
        }

        return Classes::where('section_id', $this->section->id)
            ->where('school_year_id', $this->activeSchoolYear->id)
            ->first();
    }

    private function calculateSchoolDays(): void
    {
        $startDate = Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $schoolDays = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday()) {
                $schoolDays[] = [
                    'date' => $currentDate->copy(),
                    'day' => (int) $currentDate->format('j'),
                ];
            }

            $currentDate->addDay();
        }

        $this->schoolDays = array_slice($schoolDays, 0, self::MAX_DAY_COLUMNS);
    }

    private function loadAttendanceData(): void
    {
        if (! $this->class) {
            return;
        }

        $monthStart = $this->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $attendances = Attendance::where('class_id', $this->class->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        $subjectOrder = $this->getSubjectScheduleOrder();

        /** @var array<int, array<string, array<int, string>>> $grouped */
        $grouped = [];

        foreach ($attendances as $attendance) {
            $dateKey = Carbon::parse($attendance->date)->toDateString();
            $grouped[$attendance->student_id][$dateKey][$attendance->subject_id] = $attendance->status;
        }

        foreach ($grouped as $studentId => $dateStatuses) {
            foreach ($dateStatuses as $dateKey => $subjectStatuses) {
                $this->attendanceData[$studentId][$dateKey] = $this->resolveDailyStatus($subjectStatuses, $subjectOrder);
            }
        }
    }

    /**
     * Return subject IDs ordered by their scheduled start_time for this class.
     *
     * @return array<int, int>
     */
    private function getSubjectScheduleOrder(): array
    {
        return Schedule::where('class_id', $this->class?->id)
            ->orderBy('start_time')
            ->orderBy('subject_id')
            ->pluck('subject_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve a single daily attendance status from per-subject statuses.
     *
     * DepEd SF2 rules:
     * - Absent: only if the student was absent/excused in ALL recorded subjects that day.
     * - Late:   if the earliest-scheduled subject recorded that day has status "late"
     *           and the student is not absent in all subjects.
     * - Present: otherwise.
     *
     * @param  array<int, string>  $subjectStatuses  subject_id => status
     * @param  array<int, int>  $subjectOrder  subject IDs in schedule order
     */
    private function resolveDailyStatus(array $subjectStatuses, array $subjectOrder): string
    {
        if (empty($subjectStatuses)) {
            return 'present';
        }

        $allAbsent = true;

        foreach ($subjectStatuses as $status) {
            if ($status !== 'absent' && $status !== 'excused') {
                $allAbsent = false;

                break;
            }
        }

        if ($allAbsent) {
            return 'absent';
        }

        $firstSubjectStatus = $this->getFirstScheduledSubjectStatus($subjectStatuses, $subjectOrder);

        if ($firstSubjectStatus === 'late') {
            return 'late';
        }

        return 'present';
    }

    /**
     * Get the status of the earliest-scheduled subject recorded that day.
     *
     * @param  array<int, string>  $subjectStatuses  subject_id => status
     * @param  array<int, int>  $subjectOrder  subject IDs in schedule order
     */
    private function getFirstScheduledSubjectStatus(array $subjectStatuses, array $subjectOrder): ?string
    {
        foreach ($subjectOrder as $subjectId) {
            if (isset($subjectStatuses[$subjectId])) {
                return $subjectStatuses[$subjectId];
            }
        }

        return array_values($subjectStatuses)[0] ?? null;
    }

    private function getEnrolledStudents(): Collection
    {
        if (! $this->activeSchoolYear || ! $this->class) {
            return collect();
        }

        return Student::whereHas('enrollments', function ($query): void {
            $query->where('class_id', $this->class?->id)
                ->where('school_year_id', $this->activeSchoolYear?->id);
        })
            ->with(['profiles' => function ($query): void {
                $query->where('school_year_id', $this->activeSchoolYear?->id);
            }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * @return array<int, array{male: Collection, female: Collection}>
     */
    private function paginateStudentsByGender(Collection $students): array
    {
        $maleStudents = $students
            ->filter(fn (Student $student): bool => $this->isMale($student))
            ->values();

        $femaleStudents = $students
            ->reject(fn (Student $student): bool => $this->isMale($student))
            ->values();

        $malePages = (int) ceil($maleStudents->count() / $this->maleCapacity());
        $femalePages = (int) ceil($femaleStudents->count() / $this->femaleCapacity());
        $pageCount = max(1, $malePages, $femalePages);
        $pages = [];

        for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
            $pages[] = [
                'male' => $maleStudents->slice($pageIndex * $this->maleCapacity(), $this->maleCapacity())->values(),
                'female' => $femaleStudents->slice($pageIndex * $this->femaleCapacity(), $this->femaleCapacity())->values(),
            ];
        }

        return $pages;
    }

    private function populateSheet(
        Worksheet $sheet,
        Collection $maleStudents,
        Collection $femaleStudents,
        Collection $allStudents,
        int $currentPage,
        int $totalPages
    ): void {
        $this->populateHeaders($sheet);
        $this->populateDayNumbers($sheet);
        $this->populateLearners($sheet, $maleStudents, $femaleStudents);
        $this->populateDailyTotals($sheet, $maleStudents, $femaleStudents);
        $this->populateSummaryFooter($sheet, $allStudents);
        $this->updatePageLabel($sheet, $currentPage, $totalPages);
    }

    private function populateHeaders(Worksheet $sheet): void
    {
        $monthDate = $this->monthStart();
        $schoolYear = $this->activeSchoolYear?->name ?? '';
        $gradeLevel = $this->section->gradeLevel?->name ?? '';

        $sheet->setCellValue('C6', $this->schoolId ?? '');
        $sheet->setCellValue('K6', $schoolYear);
        $sheet->setCellValue('X6', $monthDate->format('F Y'));
        $sheet->setCellValue('C8', $this->schoolName ?? config('app.name', 'School'));
        $sheet->setCellValue('X8', $gradeLevel);
        $sheet->setCellValue('AC8', $this->section->name ?? '');
    }

    private function populateDayNumbers(Worksheet $sheet): void
    {
        $columnIndex = self::FIRST_DAY_COLUMN_INDEX;

        foreach ($this->schoolDays as $dayInfo) {
            $column = $this->getColumnLetter($columnIndex);
            $sheet->setCellValue($column.'11', $dayInfo['day']);
            $columnIndex++;
        }
    }

    private function populateLearners(Worksheet $sheet, Collection $maleStudents, Collection $femaleStudents): void
    {
        $maleRow = self::MALE_START_ROW;
        foreach ($maleStudents as $student) {
            $this->populateLearnerRow($sheet, $maleRow, $student);
            $maleRow++;
        }

        $femaleRow = self::FEMALE_START_ROW;
        foreach ($femaleStudents as $student) {
            $this->populateLearnerRow($sheet, $femaleRow, $student);
            $femaleRow++;
        }
    }

    private function populateLearnerRow(Worksheet $sheet, int $row, Student $student): void
    {
        $sheet->setCellValue('B'.$row, $this->formatStudentName($student));

        $absentCount = 0;
        $lateCount = 0;
        $columnIndex = self::FIRST_DAY_COLUMN_INDEX;

        foreach ($this->schoolDays as $dayInfo) {
            $dateKey = $dayInfo['date']->toDateString();
            $status = $this->attendanceData[$student->id][$dateKey] ?? null;
            $column = $this->getColumnLetter($columnIndex);

            if ($status === 'absent') {
                $sheet->setCellValue($column.$row, 'X');
                $absentCount++;
            } elseif ($status === 'late') {
                $sheet->setCellValue($column.$row, 'T');
                $lateCount++;
            }

            $columnIndex++;
        }

        $sheet->setCellValue('AC'.$row, $this->blankIfZero($absentCount));
        $sheet->setCellValue('AD'.$row, $this->blankIfZero($lateCount));
    }

    private function populateDailyTotals(Worksheet $sheet, Collection $maleStudents, Collection $femaleStudents): void
    {
        $columnIndex = self::FIRST_DAY_COLUMN_INDEX;

        foreach ($this->schoolDays as $dayInfo) {
            $column = $this->getColumnLetter($columnIndex);
            $dateKey = $dayInfo['date']->toDateString();
            $maleAttendance = $this->countPresentStudents($maleStudents, $dateKey);
            $femaleAttendance = $this->countPresentStudents($femaleStudents, $dateKey);
            $combinedAttendance = $maleAttendance + $femaleAttendance;

            $sheet->setCellValue($column.self::MALE_TOTAL_ROW, $this->blankIfZero($maleAttendance));
            $sheet->setCellValue($column.self::FEMALE_TOTAL_ROW, $this->blankIfZero($femaleAttendance));
            $sheet->setCellValue($column.self::GRAND_TOTAL_ROW, $this->blankIfZero($combinedAttendance));

            $columnIndex++;
        }
    }

    private function populateSummaryFooter(Worksheet $sheet, Collection $students): void
    {
        if (! $this->activeSchoolYear || ! $this->class) {
            return;
        }

        $monthStart = $this->monthStart();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $firstFriday = $this->firstFridayOfSchoolYear();
        $lateEnrollmentStart = $monthStart->greaterThan($firstFriday)
            ? $monthStart
            : $firstFriday->copy()->addDay();

        $enrollments = Enrollment::with('student')
            ->where('class_id', $this->class->id)
            ->where('school_year_id', $this->activeSchoolYear->id)
            ->get();

        $profiles = StudentProfile::with('student')
            ->where('school_year_id', $this->activeSchoolYear->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get();

        $initialEnrollment = $this->countByGender(
            $enrollments->filter(fn (Enrollment $enrollment): bool => $this->dateOnOrBefore($enrollment->enrollment_date, $firstFriday)),
            fn (Enrollment $enrollment): ?Student => $enrollment->student
        );

        $lateEnrollment = $this->countByGender(
            $enrollments->filter(
                fn (Enrollment $enrollment): bool => $this->dateBetween(
                    $enrollment->enrollment_date,
                    $lateEnrollmentStart,
                    $monthEnd
                )
            ),
            fn (Enrollment $enrollment): ?Student => $enrollment->student
        );

        $registeredLearners = $this->countByGender(
            $enrollments->filter(fn (Enrollment $enrollment): bool => $this->dateOnOrBefore($enrollment->enrollment_date, $monthEnd)),
            fn (Enrollment $enrollment): ?Student => $enrollment->student
        );

        $dailyAttendanceAverage = $this->dailyAttendanceAverage($students);
        $fiveDayAbsences = $this->countStudentsWithFiveConsecutiveAbsences($students);
        $droppedOut = $this->countByGender(
            $profiles->filter(fn (StudentProfile $profile): bool => $profile->status === 'dropped'),
            fn (StudentProfile $profile): ?Student => $profile->student
        );
        $transferredOut = $this->countByGender(
            $profiles->filter(fn (StudentProfile $profile): bool => $profile->status === 'transferred'),
            fn (StudentProfile $profile): ?Student => $profile->student
        );
        $transferredIn = $this->countByGender(
            $enrollments->filter(
                fn (Enrollment $enrollment): bool => $enrollment->status === 'transferred'
                    && $this->dateBetween($enrollment->enrollment_date, $monthStart, $monthEnd)
            ),
            fn (Enrollment $enrollment): ?Student => $enrollment->student
        );

        $enrollmentPercentage = [
            'male' => $this->percentage($registeredLearners['male'], $initialEnrollment['male']),
            'female' => $this->percentage($registeredLearners['female'], $initialEnrollment['female']),
            'total' => $this->percentage($registeredLearners['total'], $initialEnrollment['total']),
        ];

        $attendancePercentage = [
            'male' => $this->percentage($dailyAttendanceAverage['male'], $registeredLearners['male']),
            'female' => $this->percentage($dailyAttendanceAverage['female'], $registeredLearners['female']),
            'total' => $this->percentage($dailyAttendanceAverage['total'], $registeredLearners['total']),
        ];

        $sheet->setCellValue('AC63', $monthStart->format('F Y'));
        $sheet->setCellValue('AG63', $this->blankIfZero(count($this->schoolDays)));
        $sheet->setCellValue('C67', $this->blankIfNull($enrollmentPercentage['total']));
        if ($enrollmentPercentage['total'] !== null) {
            $sheet->getStyle('C67')->getNumberFormat()->setFormatCode('0%');
        }
        $sheet->setCellValue('C69', $this->blankIfNull($dailyAttendanceAverage['total']));
        $sheet->setCellValue('C71', $this->blankIfNull($attendancePercentage['total']));
        if ($attendancePercentage['total'] !== null) {
            $sheet->getStyle('C71')->getNumberFormat()->setFormatCode('0%');
        }

        $this->fillSummaryRow($sheet, 65, $initialEnrollment, true);
        $this->fillSummaryRow($sheet, 67, $lateEnrollment, true);
        $this->fillSummaryRow($sheet, 69, $registeredLearners, true);
        $this->fillSummaryRow($sheet, 71, $enrollmentPercentage, false, true);
        $this->fillSummaryRow($sheet, 73, $dailyAttendanceAverage, false);
        $this->fillSummaryRow($sheet, 75, $attendancePercentage, false, true);
        $this->fillSummaryRow($sheet, 77, $fiveDayAbsences, true);
        $this->fillSummaryRow($sheet, 79, $droppedOut, true);
        $this->fillSummaryRow($sheet, 81, $transferredOut, true);
        $this->fillSummaryRow($sheet, 83, $transferredIn, true);
    }

    private function fillSummaryRow(Worksheet $sheet, int $row, array $values, bool $blankZero, bool $isPercentage = false): void
    {
        $columns = ['AH', 'AI', 'AJ'];
        $keys = ['male', 'female', 'total'];

        foreach ($columns as $i => $col) {
            $cell = $col.$row;
            $sheet->setCellValue($cell, $this->formatSummaryValue($values[$keys[$i]], $blankZero));

            if ($isPercentage && $values[$keys[$i]] !== null) {
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0%');
            }
        }
    }

    /**
     * @return array{male: int, female: int, total: int}
     */
    private function countByGender(Collection $items, callable $studentResolver): array
    {
        $students = $items
            ->map($studentResolver)
            ->filter(fn ($student): bool => $student instanceof Student)
            ->unique('id')
            ->values();

        $maleCount = $students->filter(fn (Student $student): bool => $this->isMale($student))->count();
        $femaleCount = $students->count() - $maleCount;

        return [
            'male' => $maleCount,
            'female' => $femaleCount,
            'total' => $students->count(),
        ];
    }

    /**
     * @return array{male: ?float, female: ?float, total: ?float}
     */
    private function dailyAttendanceAverage(Collection $students): array
    {
        $maleStudents = $students
            ->filter(fn (Student $student): bool => $this->isMale($student))
            ->values();
        $femaleStudents = $students
            ->reject(fn (Student $student): bool => $this->isMale($student))
            ->values();
        $schoolDayCount = count($this->schoolDays);

        if ($schoolDayCount === 0) {
            return [
                'male' => null,
                'female' => null,
                'total' => null,
            ];
        }

        $maleAttendance = 0;
        $femaleAttendance = 0;
        $totalAttendance = 0;

        foreach ($this->schoolDays as $dayInfo) {
            $dateKey = $dayInfo['date']->toDateString();
            $maleDayAttendance = $this->countPresentStudents($maleStudents, $dateKey);
            $femaleDayAttendance = $this->countPresentStudents($femaleStudents, $dateKey);

            $maleAttendance += $maleDayAttendance;
            $femaleAttendance += $femaleDayAttendance;
            $totalAttendance += $maleDayAttendance + $femaleDayAttendance;
        }

        return [
            'male' => round($maleAttendance / $schoolDayCount, 1),
            'female' => round($femaleAttendance / $schoolDayCount, 1),
            'total' => round($totalAttendance / $schoolDayCount, 1),
        ];
    }

    /**
     * @return array{male: int, female: int, total: int}
     */
    private function countStudentsWithFiveConsecutiveAbsences(Collection $students): array
    {
        $maleCount = 0;
        $femaleCount = 0;

        foreach ($students as $student) {
            $consecutiveAbsences = 0;

            foreach ($this->schoolDays as $dayInfo) {
                $dateKey = $dayInfo['date']->toDateString();
                $status = $this->attendanceData[$student->id][$dateKey] ?? null;

                if ($status === 'absent') {
                    $consecutiveAbsences++;
                } else {
                    $consecutiveAbsences = 0;
                }

                if ($consecutiveAbsences >= 5) {
                    if ($this->isMale($student)) {
                        $maleCount++;
                    } else {
                        $femaleCount++;
                    }

                    break;
                }
            }
        }

        return [
            'male' => $maleCount,
            'female' => $femaleCount,
            'total' => $maleCount + $femaleCount,
        ];
    }

    private function countPresentStudents(Collection $students, string $dateKey): int
    {
        return $students->filter(function (Student $student) use ($dateKey): bool {
            $status = $this->attendanceData[$student->id][$dateKey] ?? null;

            return $status !== 'absent';
        })->count();
    }

    private function updatePageLabel(Worksheet $sheet, int $currentPage, int $totalPages): void
    {
        $sheet->setCellValue('A91', "School Form 2 :  Page {$currentPage} of {$totalPages}");
    }

    private function monthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfMonth();
    }

    private function firstFridayOfSchoolYear(): Carbon
    {
        $schoolYearStart = $this->activeSchoolYear?->start_date
            ? Carbon::parse($this->activeSchoolYear->start_date)->startOfMonth()
            : $this->monthStart()->copy()->startOfMonth();

        while (! $schoolYearStart->isFriday()) {
            $schoolYearStart->addDay();
        }

        return $schoolYearStart;
    }

    private function formatStudentName(Student $student): string
    {
        return trim($student->last_name.', '.$student->first_name);
    }

    private function isMale(Student $student): bool
    {
        return in_array(strtolower((string) $student->gender), ['m', 'male'], true);
    }

    private function maleCapacity(): int
    {
        return self::MALE_END_ROW - self::MALE_START_ROW + 1;
    }

    private function femaleCapacity(): int
    {
        return self::FEMALE_END_ROW - self::FEMALE_START_ROW + 1;
    }

    private function dateOnOrBefore(mixed $date, Carbon $compareDate): bool
    {
        if (! $date) {
            return false;
        }

        return Carbon::parse($date)->lte($compareDate);
    }

    private function dateBetween(mixed $date, Carbon $startDate, Carbon $endDate): bool
    {
        if (! $date) {
            return false;
        }

        return Carbon::parse($date)->between($startDate, $endDate, true);
    }

    private function percentage(float|int $numerator, float|int $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round($numerator / $denominator, 4);
    }

    private function blankIfZero(int $value): int|string
    {
        return $value === 0 ? '' : $value;
    }

    private function blankIfNull(float|int|null $value): float|int|string
    {
        return $value === null ? '' : $value;
    }

    private function formatSummaryValue(float|int|null $value, bool $blankZero): float|int|string
    {
        if ($value === null) {
            return '';
        }

        if ($blankZero && (float) $value === 0.0) {
            return '';
        }

        return $value;
    }

    private function getColumnLetter(int $index): string
    {
        return match ($index) {
            1 => 'A',
            2 => 'B',
            3 => 'C',
            4 => 'D',
            5 => 'E',
            6 => 'F',
            7 => 'G',
            8 => 'H',
            9 => 'I',
            10 => 'J',
            11 => 'K',
            12 => 'L',
            13 => 'M',
            14 => 'N',
            15 => 'O',
            16 => 'P',
            17 => 'Q',
            18 => 'R',
            19 => 'S',
            20 => 'T',
            21 => 'U',
            22 => 'V',
            23 => 'W',
            24 => 'X',
            25 => 'Y',
            26 => 'Z',
            27 => 'AA',
            28 => 'AB',
            default => 'A',
        };
    }
}
