<?php

namespace App\Exports;

use App\Models\Section;
use App\Models\Student;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AttendanceExport
{
    protected $sectionId;
    protected $month;
    protected $startDate;
    protected $endDate;
    protected $dates = [];

    public function __construct($sectionId, $month)
    {
        $this->sectionId = $sectionId;
        $this->month = $month;
        $this->startDate = Carbon::parse($month)->startOfMonth();
        $this->endDate = Carbon::parse($month)->endOfMonth();

        $this->generateDates();
    }

    /**
     * Generate all weekdays in the date range
     */
    private function generateDates()
    {
        $currentDate = $this->startDate->copy();
        while ($currentDate->lte($this->endDate)) {
            if ($currentDate->isWeekday()) { // Only include weekdays
                $this->dates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $section = Section::with(['students' => function ($query) {
            $query->with(['attendances' => function ($q) {
                $q->whereBetween('date', [$this->startDate, $this->endDate]);
            }]);
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
        $headings = [
            'Student ID',
            'LRN',
            'Name',
        ];

        // Add a column for each date
        foreach ($this->dates as $date) {
            $headings[] = Carbon::parse($date)->format('M d');
        }

        $headings = array_merge($headings, [
            'Present',
            'Absent',
            'Late',
            'Excused'
        ]);

        return $headings;
    }

    /**
     * @param mixed $student
     *
     * @return array
     */
    public function map($student): array
    {
        $row = [
            $student->id,
            $student->lrn,
            $student->full_name,
        ];

        $present = 0;
        $absent = 0;
        $late = 0;
        $excused = 0;

        // Map attendance status for each date
        foreach ($this->dates as $date) {
            $attendance = $student->attendances->where('date', $date)->first();

            $status = 'N/A';
            if ($attendance) {
                $status = ucfirst($attendance->status);

                switch ($attendance->status) {
                    case 'present':
                        $present++;
                        break;
                    case 'absent':
                        $absent++;
                        break;
                    case 'late':
                        $late++;
                        break;
                    case 'excused':
                        $excused++;
                        break;
                }
            }

            $row[] = $status;
        }

        // Add summary counts
        $row[] = $present;
        $row[] = $absent;
        $row[] = $late;
        $row[] = $excused;

        return $row;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        $section = Section::find($this->sectionId);
        $sectionName = $section ? $section->name : 'Unknown Section';
        $monthYear = Carbon::parse($this->month)->format('F Y');

        return "Attendance - $sectionName - $monthYear";
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Style the header row
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF']
            ]
        ]);

        // Format the date columns
        $dateColumnsStart = 4; // Column D (0-indexed would be 3, but PhpSpreadsheet is 1-indexed)
        $dateColumnsEnd = $dateColumnsStart + count($this->dates) - 1;

        for ($i = $dateColumnsStart; $i <= $dateColumnsEnd; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);

            // Conditional formatting for attendance status
            $sheet->getStyle($colLetter . '2:' . $colLetter . $lastRow)->getAlignment()->setHorizontal('center');

            // Color present cells green
            $sheet->getConditionalStyles($colLetter . '2:' . $colLetter . $lastRow)[] = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditionalPresent = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditionalPresent->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CONTAINSTEXT);
            $conditionalPresent->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_CONTAINSTEXT);
            $conditionalPresent->setText('Present');
            $conditionalPresent->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $conditionalPresent->getStyle()->getFill()->getStartColor()->setARGB('D4EDDA');
            $sheet->getConditionalStyles($colLetter . '2:' . $colLetter . $lastRow)[] = $conditionalPresent;

            // Color absent cells red
            $conditionalAbsent = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
            $conditionalAbsent->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CONTAINSTEXT);
            $conditionalAbsent->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_CONTAINSTEXT);
            $conditionalAbsent->setText('Absent');
            $conditionalAbsent->getStyle()->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $conditionalAbsent->getStyle()->getFill()->getStartColor()->setARGB('F8D7DA');
            $sheet->getConditionalStyles($colLetter . '2:' . $colLetter . $lastRow)[] = $conditionalAbsent;
        }

        // Format the summary columns
        $summaryStart = $dateColumnsEnd + 1;
        $summaryEnd = $summaryStart + 3;
        for ($i = $summaryStart; $i <= $summaryEnd; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
            $sheet->getStyle($colLetter . '1:' . $colLetter . $lastRow)->getAlignment()->setHorizontal('center');
        }

        return [];
    }
}
