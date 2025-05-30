<?php

namespace App\Exports;

use App\Models\Section;
use App\Models\Subject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GradesExport
{
    protected $sectionId;
    protected $subjectId;
    protected $gradingPeriod;

    public function __construct($sectionId, $subjectId, $gradingPeriod)
    {
        $this->sectionId = $sectionId;
        $this->subjectId = $subjectId;
        $this->gradingPeriod = $gradingPeriod;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $section = Section::with(['students' => function ($query) {
            $query->with(['grades' => function ($q) {
                $q->where('subject_id', $this->subjectId)
                    ->where('grading_period', $this->gradingPeriod);
            }])->orderBy('last_name')->orderBy('first_name');
        }])->find($this->sectionId);

        if (!$section) {
            return collect([]);
        }

        return $section->students;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Student ID',
            'LRN',
            'Name',
            'Grade',
            'Remarks'
        ];
    }

    /**
     * @param mixed $student
     *
     * @return array
     */
    public function map($student): array
    {
        $grade = $student->grades
            ->where('subject_id', $this->subjectId)
            ->where('grading_period', $this->gradingPeriod)
            ->first();

        $gradeValue = $grade ? $grade->grade : null;
        $remarks = $this->getGradeRemarks($gradeValue);

        return [
            $student->id,
            $student->lrn,
            $student->full_name,
            $gradeValue,
            $remarks
        ];
    }

    /**
     * Get remarks based on grade.
     */
    private function getGradeRemarks($grade)
    {
        if ($grade === null) return 'No Grade';

        if ($grade >= 90) return 'Outstanding';
        if ($grade >= 85) return 'Very Satisfactory';
        if ($grade >= 80) return 'Satisfactory';
        if ($grade >= 75) return 'Fairly Satisfactory';
        return 'Did Not Meet Expectations';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        $section = Section::find($this->sectionId);
        $subject = Subject::find($this->subjectId);

        $sectionName = $section ? $section->name : 'Unknown Section';
        $subjectName = $subject ? $subject->name : 'Unknown Subject';
        $period = ucfirst($this->gradingPeriod) . ' Grading';

        return "$subjectName - $sectionName - $period";
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        // Style the header row
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF']
            ]
        ]);

        // Apply style to grade column
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        // Add conditional formatting for grade remarks
        for ($i = 2; $i <= $lastRow; $i++) {
            $grade = $sheet->getCell('D' . $i)->getValue();
            $color = '';

            if ($grade !== null) {
                if ($grade >= 90) {
                    $color = '28A745'; // Outstanding - Green
                } elseif ($grade >= 85) {
                    $color = '17A2B8'; // Very Satisfactory - Blue
                } elseif ($grade >= 80) {
                    $color = '007BFF'; // Satisfactory - Primary blue
                } elseif ($grade >= 75) {
                    $color = 'FFC107'; // Fairly Satisfactory - Yellow
                } else {
                    $color = 'DC3545'; // Did Not Meet Expectations - Red
                }

                $sheet->getStyle('D' . $i . ':E' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $color]
                    ],
                    'font' => [
                        'color' => ['rgb' => $grade < 80 ? 'FFFFFF' : '000000']
                    ]
                ]);
            }
        }

        // Add average, highest, lowest at the bottom
        $sheet->setCellValue('C' . ($lastRow + 2), 'Class Average:');
        $sheet->setCellValue('D' . ($lastRow + 2), '=AVERAGE(D2:D' . $lastRow . ')');

        $sheet->setCellValue('C' . ($lastRow + 3), 'Highest Grade:');
        $sheet->setCellValue('D' . ($lastRow + 3), '=MAX(D2:D' . $lastRow . ')');

        $sheet->setCellValue('C' . ($lastRow + 4), 'Lowest Grade:');
        $sheet->setCellValue('D' . ($lastRow + 4), '=MIN(D2:D' . $lastRow . ')');

        $sheet->getStyle('C' . ($lastRow + 2) . ':C' . ($lastRow + 4))->getFont()->setBold(true);
        $sheet->getStyle('D' . ($lastRow + 2) . ':D' . ($lastRow + 4))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        return [];
    }
}
