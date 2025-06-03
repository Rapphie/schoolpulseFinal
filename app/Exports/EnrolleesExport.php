<?php

namespace App\Exports;

use App\Models\Section;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnrolleesExport
{
    protected $grade;

    public function __construct($grade = null)
    {
        $this->grade = $grade;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Section::with(['students', 'teacher_id'])
            ->withCount('students');

        if ($this->grade) {
            $query->where('grade_level', $this->grade);
        }

        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Section ID',
            'Section Name',
            'Grade Level',
            'Adviser',
            'Number of Students',
            'Boys',
            'Girls',
            'Created At'
        ];
    }

    /**
     * @param mixed $row
     *
     * @return array
     */
    public function map($row): array
    {
        $boyCount = $row->students->where('gender', 'Male')->count();
        $girlCount = $row->students->where('gender', 'Female')->count();

        return [
            $row->id,
            $row->name,
            'Grade ' . $row->grade_level,
            $row->adviser ? $row->adviser->name : 'No Adviser',
            $row->students_count,
            $boyCount,
            $girlCount,
            $row->created_at->format('M d, Y')
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Enrollees Report' . ($this->grade ? ' - Grade ' . $this->grade : '');
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
