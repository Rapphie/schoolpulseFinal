<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Classes;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GradeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssessmentClassRecordExport implements FromArray, WithCustomStartCell, WithEvents, WithTitle
{
    private const DEFAULT_ASSESSMENT_COUNTS = [
        'written_works' => 10,
        'performance_tasks' => 10,
        'quarterly_assessments' => 1,
    ];

    private const ASSESSMENT_TYPE_WEIGHTS = [
        'written_works' => 0.20,
        'performance_tasks' => 0.60,
        'quarterly_assessments' => 0.20,
    ];

    private ?array $assessmentBuckets = null;

    private ?Collection $oralParticipationAssessments = null;

    private int $totalColumns = 0;

    public function __construct(
        private readonly Classes $classroom,
        private readonly Subject $subject,
        private readonly int $teacherId,
        private readonly int $quarter
    ) {}

    public function startCell(): string
    {
        return 'A11';
    }

    public function array(): array
    {
        $students = $this->classroom->students()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $males = $students->filter(fn ($s) => strtolower($s->gender ?? '') === 'male')->values();
        $females = $students->filter(fn ($s) => strtolower($s->gender ?? '') === 'female')->values();
        $unspecified = $students->filter(fn ($s) => ! in_array(strtolower($s->gender ?? ''), ['male', 'female'], true))->values();

        $rows = [];

        if ($males->isNotEmpty()) {
            $rows[] = ['', 'MALE', '', ''];
            foreach ($males as $index => $student) {
                $rows[] = $this->mapStudentRow($student, $index + 1);
            }
        }

        if ($females->isNotEmpty()) {
            $rows[] = ['', 'FEMALE', '', ''];
            foreach ($females as $index => $student) {
                $rows[] = $this->mapStudentRow($student, $index + 1);
            }
        }

        if ($unspecified->isNotEmpty()) {
            $rows[] = ['', 'UNSPECIFIED', '', ''];
            foreach ($unspecified as $index => $student) {
                $rows[] = $this->mapStudentRow($student, $index + 1);
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->formatDepEdTemplate($event->sheet->getDelegate());
            },
        ];
    }

    public function title(): string
    {
        return "Q{$this->quarter}";
    }

    private function formatDepEdTemplate($sheet): void
    {
        $sheet->setCellValue('A1', 'Class Record');
        $sheet->setCellValue('A2', '(Pursuant to Deped Order 8 series of 2015)');

        $region = Setting::where('key', 'region')->value('value') ?? 'XI';
        $division = Setting::where('key', 'division')->value('value') ?? 'PANABO CITY';
        $district = Setting::where('key', 'district')->value('value') ?? 'PSD1';
        $schoolName = Setting::where('key', 'school_name')->value('value') ?? 'TAGUROT ELEMENTARY SCHOOL';
        $schoolId = Setting::where('key', 'school_id')->value('value') ?? '129821';

        $sheet->setCellValue('C4', 'REGION');
        $sheet->setCellValue('E4', $region);
        $sheet->setCellValue('H4', 'DIVISION');
        $sheet->setCellValue('K4', $division);
        $sheet->setCellValue('P4', 'DISTRICT');
        $sheet->setCellValue('T4', $district);

        $sheet->setCellValue('B5', 'SCHOOL NAME');
        $sheet->setCellValue('E5', $schoolName);
        $sheet->setCellValue('P5', 'SCHOOL ID');
        $sheet->setCellValue('T5', $schoolId);

        $sectionName = $this->classroom->section->name ?? '';
        $gradeLevel = $this->classroom->section->gradeLevel->name ?? '';
        $teacherName = $this->classroom->teacher->user->full_name ?? '';
        $subjectName = $this->subject->name ?? '';

        $sheet->setCellValue('B7', 'QUARTER '.$this->quarter);
        $sheet->setCellValue('D7', 'GRADE & SECTION:');
        $sheet->setCellValue('I7', trim("$gradeLevel - $sectionName", ' -'));
        $sheet->setCellValue('N7', 'TEACHER:');
        $sheet->setCellValue('P7', $teacherName);
        $sheet->setCellValue('T7', 'SUBJECT:');
        $sheet->setCellValue('W7', $subjectName);

        $sheet->mergeCells('B8:D9');
        $sheet->setCellValue('B8', "LEARNERS' NAMES");
        $sheet->getStyle('B8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B8')->getFont()->setBold(true);

        $sheet->mergeCells('B10:D10');
        $sheet->setCellValue('B10', 'HIGHEST POSSIBLE SCORE');
        $sheet->getStyle('B10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $colIndex = 5;
        $buckets = $this->resolveAssessmentBuckets();

        foreach (['written_works', 'performance_tasks', 'quarterly_assessments'] as $type) {
            $count = self::DEFAULT_ASSESSMENT_COUNTS[$type];
            $weight = (int) (self::ASSESSMENT_TYPE_WEIGHTS[$type] * 100);
            $label = strtoupper(str_replace('_', ' ', $type))." ({$weight}%)";

            $startCol = Coordinate::stringFromColumnIndex($colIndex);
            $endCol = Coordinate::stringFromColumnIndex($colIndex + $count + 2);

            $sheet->mergeCells("{$startCol}8:{$endCol}8");
            $sheet->setCellValue("{$startCol}8", $label);
            $sheet->getStyle("{$startCol}8")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$startCol}8")->getFont()->setBold(true);

            $assessments = $buckets[$type];
            $totalMax = 0;

            for ($i = 1; $i <= $count; $i++) {
                $colStr = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue("{$colStr}9", $i);
                $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $assessment = $assessments->get($i - 1);
                $maxScore = $assessment && $assessment->max_score > 0 ? (float) $assessment->max_score : 0;
                $totalMax += $maxScore;

                $sheet->setCellValue("{$colStr}10", $maxScore > 0 ? $maxScore : '');
                $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $colIndex++;
            }

            $colStr = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$colStr}9", 'Total');
            $sheet->setCellValue("{$colStr}10", $totalMax > 0 ? $totalMax : '');
            $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyHeaderColor($sheet, "{$colStr}9:{$colStr}10", 'FFE2E8F0');
            $colIndex++;

            $colStr = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$colStr}9", 'PS');
            $sheet->setCellValue("{$colStr}10", 100);
            $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $colIndex++;

            $colStr = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$colStr}9", 'WS');
            $weightPercent = (int) (self::ASSESSMENT_TYPE_WEIGHTS[$type] * 100);
            $sheet->setCellValue("{$colStr}10", "{$weightPercent}%");
            $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $this->applyHeaderColor($sheet, "{$colStr}9:{$colStr}10", 'FFE2E8F0');
            $colIndex++;
        }

        $colStr = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->mergeCells("{$colStr}8:{$colStr}9");
        $sheet->setCellValue("{$colStr}8", "Initial\nGrade");
        $sheet->getStyle("{$colStr}8")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("{$colStr}8")->getFont()->setBold(true);
        $colIndex++;

        $colStr = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->mergeCells("{$colStr}8:{$colStr}9");
        $sheet->setCellValue("{$colStr}8", "Quarterly\nGrade");
        $sheet->getStyle("{$colStr}8")->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("{$colStr}8")->getFont()->setBold(true);

        $this->totalColumns = $colIndex;
        $highestColStr = Coordinate::stringFromColumnIndex($colIndex);
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle("A8:{$highestColStr}{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(10);

        $sheet->getStyle("E8:{$highestColStr}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(4);

        for ($i = 5; $i <= $colIndex; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(6);
        }

        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex - 1))->setWidth(8);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(9);

        for ($r = 11; $r <= $highestRow; $r++) {
            $val = $sheet->getCell("B{$r}")->getValue();
            if (in_array($val, ['MALE', 'FEMALE', 'UNSPECIFIED'], true)) {
                $sheet->getStyle("A{$r}:{$highestColStr}{$r}")->getFont()->setBold(true);
                $sheet->getStyle("A{$r}:{$highestColStr}{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF1F5F9');
            }
        }

        $sheet->getStyle("A8:{$highestColStr}10")->getFont()->setBold(true);
    }

    private function applyHeaderColor($sheet, string $range, string $hexColor): void
    {
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($hexColor);
    }

    private function mapStudentRow(Student $student, int $number): array
    {
        $assessmentBuckets = $this->resolveAssessmentBuckets();
        $profileId = $student->profileFor($this->classroom->school_year_id)?->id;
        $typeRows = [];
        $quarterInitialGrade = 0.0;
        $quarterHasScores = false;

        foreach (self::ASSESSMENT_TYPE_WEIGHTS as $type => $weight) {
            $entries = $assessmentBuckets[$type]->map(function ($assessment) use ($student, $profileId) {
                $score = $this->resolveAssessmentScore($assessment, $student, $profileId);
                $maxScore = $assessment instanceof Assessment ? (float) $assessment->max_score : 0.0;

                return [
                    'score' => $score,
                    'max_score' => $maxScore,
                ];
            })->values();

            $stats = $this->calculateTypeStats($entries, $weight);
            $typeRows[$type] = [
                'entries' => $entries,
                'total' => $stats['total'],
                'percentage_score' => $stats['percentage_score'],
                'weighted_score' => $stats['weighted_score'],
            ];

            $quarterInitialGrade += $stats['weighted_score'];
            if ($stats['has_scores']) {
                $quarterHasScores = true;
            }
        }

        $initialGrade = $quarterHasScores ? round($quarterInitialGrade, 2) : null;
        $transmutedGrade = $quarterHasScores ? GradeService::transmute($initialGrade) : null;

        $row = [
            $number,
            $student->last_name ?? '',
            $student->first_name ?? '',
            '',
        ];

        $row = array_merge($row, $this->buildTypeRow($typeRows['written_works']));
        $row = array_merge($row, $this->buildTypeRow($typeRows['performance_tasks']));
        $row = array_merge($row, $this->buildTypeRow($typeRows['quarterly_assessments']));

        $row[] = $this->formatNullableNumeric($initialGrade);
        $row[] = $this->formatNullableNumeric($transmutedGrade !== null ? (float) $transmutedGrade : null);

        return $row;
    }

    private function buildTypeRow(array $typeData): array
    {
        $values = [];

        foreach ($typeData['entries'] as $entry) {
            $values[] = $this->formatNullableNumeric($entry['score']);
        }

        $values[] = $this->formatNullableNumeric($typeData['total']);
        $values[] = round($typeData['percentage_score'], 2);
        $values[] = round($typeData['weighted_score'], 2);

        return $values;
    }

    private function resolveAssessmentBuckets(): array
    {
        if ($this->assessmentBuckets !== null) {
            return $this->assessmentBuckets;
        }

        $assessments = $this->classroom->assessments()
            ->where('subject_id', $this->subject->id)
            ->where('quarter', $this->quarter)
            ->where(function ($query) {
                $query->where('teacher_id', $this->teacherId)
                    ->orWhere('type', 'oral_participation');
            })
            ->with('scores')
            ->orderBy('type')
            ->orderBy('assessment_date')
            ->get();

        $grouped = $assessments->groupBy('type');
        $this->oralParticipationAssessments = $grouped->get('oral_participation', collect());

        $performanceTasks = $grouped->get('performance_tasks', collect());
        if ($this->oralParticipationAssessments->isNotEmpty()) {
            $consolidatedOralParticipation = new Assessment;
            $consolidatedOralParticipation->id = -999;
            $consolidatedOralParticipation->name = 'Oral Participation';
            $consolidatedOralParticipation->type = 'oral_participation';
            $consolidatedOralParticipation->max_score = $this->oralParticipationAssessments->sum('max_score');

            $performanceTasks = collect([$consolidatedOralParticipation])->merge($performanceTasks);
        }

        $this->assessmentBuckets = [
            'written_works' => $this->padAssessments(
                $grouped->get('written_works', collect()),
                self::DEFAULT_ASSESSMENT_COUNTS['written_works']
            ),
            'performance_tasks' => $this->padAssessments(
                $performanceTasks,
                self::DEFAULT_ASSESSMENT_COUNTS['performance_tasks']
            ),
            'quarterly_assessments' => $this->padAssessments(
                $grouped->get('quarterly_assessments', collect()),
                self::DEFAULT_ASSESSMENT_COUNTS['quarterly_assessments']
            ),
        ];

        return $this->assessmentBuckets;
    }

    private function padAssessments(Collection $assessments, int $targetCount): Collection
    {
        $limited = $assessments->take($targetCount)->values();
        while ($limited->count() < $targetCount) {
            $limited->push(null);
        }

        return $limited;
    }

    private function resolveAssessmentScore($assessment, Student $student, ?int $profileId): ?float
    {
        if (! $assessment instanceof Assessment) {
            return null;
        }

        if ((int) $assessment->id === -999) {
            return $this->resolveConsolidatedOralParticipationScore($student, $profileId);
        }

        $scoreModel = null;
        if ($profileId) {
            $scoreModel = $assessment->scores->firstWhere('student_profile_id', $profileId);
        }
        if (! $scoreModel) {
            $scoreModel = $assessment->scores->firstWhere('student_id', $student->id);
        }

        return $scoreModel ? (float) $scoreModel->score : null;
    }

    private function resolveConsolidatedOralParticipationScore(Student $student, ?int $profileId): ?float
    {
        $totalScore = 0.0;
        $hasAnyScore = false;

        foreach ($this->oralParticipationAssessments ?? collect() as $assessment) {
            $scoreModel = null;
            if ($profileId) {
                $scoreModel = $assessment->scores->firstWhere('student_profile_id', $profileId);
            }
            if (! $scoreModel) {
                $scoreModel = $assessment->scores->firstWhere('student_id', $student->id);
            }

            if ($scoreModel) {
                $totalScore += (float) $scoreModel->score;
                $hasAnyScore = true;
            }
        }

        return $hasAnyScore ? $totalScore : null;
    }

    private function calculateTypeStats(Collection $entries, float $weight): array
    {
        $totalScore = 0.0;
        $totalMax = 0.0;

        foreach ($entries as $entry) {
            if ($entry['score'] === null) {
                continue;
            }

            $maxScore = (float) $entry['max_score'];
            if ($maxScore <= 0) {
                continue;
            }

            $totalScore += (float) $entry['score'];
            $totalMax += $maxScore;
        }

        $percentageScore = $totalMax > 0 ? ($totalScore / $totalMax) * 100 : 0.0;
        $weightedScore = $percentageScore * $weight;

        return [
            'total' => round($totalScore, 2),
            'percentage_score' => round($percentageScore, 2),
            'weighted_score' => round($weightedScore, 2),
            'has_scores' => $totalMax > 0,
        ];
    }

    private function formatNullableNumeric(?float $value): float|int|string
    {
        if ($value === null) {
            return '';
        }

        if (fmod($value, 1.0) === 0.0) {
            return (int) $value;
        }

        return round($value, 2);
    }
}
