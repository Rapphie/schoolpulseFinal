<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Classes;
use App\Models\SchoolYear;
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
        // Add DepEd Seal (Left)
        if (file_exists(public_path('images/deped-seal.png'))) {
            $drawingLeft = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
            $drawingLeft->setName('DepEd Seal');
            $drawingLeft->setDescription('DepEd Seal');
            $drawingLeft->setPath(public_path('images/deped-seal.png'));
            $drawingLeft->setHeight(100);
            $drawingLeft->setCoordinates('A1');
            $drawingLeft->setOffsetX(10);
            $drawingLeft->setOffsetY(10);
            $drawingLeft->setWorksheet($sheet);
        }

        // Add DepEd Logo (Right)
        if (file_exists(public_path('images/deped-logo.png'))) {
            $drawingRight = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
            $drawingRight->setName('DepEd Logo');
            $drawingRight->setDescription('DepEd Logo');
            $drawingRight->setPath(public_path('images/deped-logo.png'));
            $drawingRight->setHeight(80);
            $drawingRight->setCoordinates('T1');
            $drawingRight->setOffsetX(10);
            $drawingRight->setOffsetY(10);
            $drawingRight->setWorksheet($sheet);
        }

        // Set row heights for the header area to accommodate logos
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(20);
        $sheet->getRowDimension(5)->setRowHeight(20);
        $sheet->getRowDimension(6)->setRowHeight(15);
        $sheet->getRowDimension(7)->setRowHeight(20);

        $sheet->mergeCells('E1:T1');
        $sheet->setCellValue('E1', 'Class Record');
        $sheet->getStyle('E1')->getFont()->setBold(true)->setSize(28);
        $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('E2:T2');
        $sheet->setCellValue('E2', '(Pursuant to Deped Order 8 series of 2015)');
        $sheet->getStyle('E2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);

        $region = Setting::where('key', 'region')->value('value') ?? 'XI';
        $division = Setting::where('key', 'division')->value('value') ?? 'PANABO CITY';
        $district = Setting::where('key', 'district')->value('value') ?? 'PSD1';
        $schoolName = Setting::where('key', 'school_name')->value('value') ?? 'TAGUROT ELEMENTARY SCHOOL';
        $schoolId = Setting::where('key', 'school_id')->value('value') ?? '129821';
        $schoolYear = SchoolYear::find($this->classroom->school_year_id)?->name ?? '2025-2026';
        $gradeLevel = $this->classroom->gradeLevel->name ?? '';
        $sectionName = $this->classroom->section->name ?? '';
        $teacherName = $this->classroom->teacher->user->name ?? '';

        // Row 4: REGION, DIVISION, DISTRICT
        $sheet->mergeCells('B4:C4');
        $sheet->setCellValue('B4', 'REGION');
        $sheet->getStyle('B4')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('D4:E4');
        $sheet->setCellValue('D4', $region);
        $sheet->getStyle('D4:E4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('F4:H4');
        $sheet->setCellValue('F4', 'DIVISION');
        $sheet->getStyle('F4')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('I4:M4');
        $sheet->setCellValue('I4', $division);
        $sheet->getStyle('I4:M4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('N4:P4');
        $sheet->setCellValue('N4', 'DISTRICT');
        $sheet->getStyle('N4')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('N4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('Q4:S4');
        $sheet->setCellValue('Q4', $district);
        $sheet->getStyle('Q4:S4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('Q4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Row 5: SCHOOL NAME, SCHOOL ID, SCHOOL YEAR
        $sheet->mergeCells('A5:C5');
        $sheet->setCellValue('A5', 'SCHOOL NAME');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('D5:M5');
        $sheet->setCellValue('D5', $schoolName);
        $sheet->getStyle('D5:M5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('N5:P5');
        $sheet->setCellValue('N5', 'SCHOOL ID');
        $sheet->getStyle('N5')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('N5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('Q5:S5');
        $sheet->setCellValue('Q5', $schoolId);
        $sheet->getStyle('Q5:S5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('Q5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('T5:V5');
        $sheet->setCellValue('T5', 'SCHOOL YEAR');
        $sheet->getStyle('T5')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('T5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('W5:Z5');
        $sheet->setCellValue('W5', $schoolYear);
        $sheet->getStyle('W5:Z5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('W5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $subjectName = $this->subject->name ?? '';

        $quarterNames = [1 => 'FIRST', 2 => 'SECOND', 3 => 'THIRD', 4 => 'FOURTH'];
        $quarterName = $quarterNames[$this->quarter] ?? $this->quarter;

        // Row 7: QUARTER, GRADE & SECTION, TEACHER, SUBJECT
        $sheet->mergeCells('B7:D7');
        $sheet->setCellValue('B7', "{$quarterName} QUARTER");
        $sheet->getStyle('B7')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('E7:G7');
        $sheet->setCellValue('E7', 'GRADE & SECTION:');
        $sheet->getStyle('E7')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('E7:G7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('E7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('H7:L7');
        $sheet->setCellValue('H7', trim("$gradeLevel - $sectionName", ' -'));
        $sheet->getStyle('H7:L7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('H7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('M7:N7');
        $sheet->setCellValue('M7', 'TEACHER:');
        $sheet->getStyle('M7')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('M7:N7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('M7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('O7:R7');
        $sheet->setCellValue('O7', $teacherName);
        $sheet->getStyle('O7:R7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('O7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('S7:U7');
        $sheet->setCellValue('S7', 'SUBJECT:');
        $sheet->getStyle('S7')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('S7:U7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('S7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('V7:Z7');
        $sheet->setCellValue('V7', $subjectName);
        $sheet->getStyle('V7:Z7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('V7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('B8:D9');
        $sheet->setCellValue('B8', "LEARNERS'\nNAMES");
        $sheet->getStyle('B8')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B8')->getFont()->setBold(true)->setSize(12);

        $sheet->mergeCells('B10:D10');
        $sheet->setCellValue('B10', 'HIGHEST POSSIBLE SCORE');
        $sheet->getStyle('B10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B10')->getFont()->setBold(true);

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
            $sheet->getStyle("{$startCol}8")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("{$startCol}8")->getFont()->setBold(true)->setSize(12);

            $assessments = $buckets[$type];
            $totalMax = 0;

            for ($i = 1; $i <= $count; $i++) {
                $colStr = Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue("{$colStr}9", $i);
                $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$colStr}9")->getFont()->setBold(true);

                $assessment = $assessments->get($i - 1);
                $maxScore = $assessment && $assessment->max_score > 0 ? (float) $assessment->max_score : 0;
                $totalMax += $maxScore;

                $sheet->setCellValue("{$colStr}10", $maxScore > 0 ? $maxScore : '');
                $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$colStr}10")->getFont()->setBold(true);
                $colIndex++;
            }

            $colStr = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$colStr}9", 'Total');
            $sheet->setCellValue("{$colStr}10", $totalMax > 0 ? $totalMax : '');
            $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}9:{$colStr}10")->getFont()->setBold(true);
            $colIndex++;

            $colStr = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$colStr}9", 'PS');
            $sheet->setCellValue("{$colStr}10", 100);
            $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}9:{$colStr}10")->getFont()->setBold(true);
            $colIndex++;

            $colStr = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$colStr}9", 'WS');
            $weightPercent = (int) (self::ASSESSMENT_TYPE_WEIGHTS[$type] * 100);
            $sheet->setCellValue("{$colStr}10", "{$weightPercent}%");
            $sheet->getStyle("{$colStr}9")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}10")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colStr}9:{$colStr}10")->getFont()->setBold(true);

            // Add medium border around the entire assessment type block
            $sheet->getStyle("{$startCol}8:{$colStr}10")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

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

        // Apply borders to the main data table
        $sheet->getStyle("A8:{$highestColStr}{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Apply medium border around the entire table
        $sheet->getStyle("A8:{$highestColStr}{$highestRow}")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

        $sheet->getStyle("E8:{$highestColStr}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(4);

        for ($i = 5; $i <= $colIndex; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(5);
        }

        // Adjust widths for Total, PS, WS columns
        $col = 5;
        foreach (['written_works', 'performance_tasks', 'quarterly_assessments'] as $type) {
            $count = self::DEFAULT_ASSESSMENT_COUNTS[$type];
            $col += $count;
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(6); // Total
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col + 1))->setWidth(6); // PS
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col + 2))->setWidth(6); // WS
            $col += 3;
        }

        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex - 1))->setWidth(8); // Initial Grade
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(9); // Quarterly Grade

        for ($r = 11; $r <= $highestRow; $r++) {
            $val = $sheet->getCell("B{$r}")->getValue();
            if (in_array($val, ['MALE', 'FEMALE', 'UNSPECIFIED'], true)) {
                $sheet->getStyle("A{$r}:{$highestColStr}{$r}")->getFont()->setBold(true);
            }
        }
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
