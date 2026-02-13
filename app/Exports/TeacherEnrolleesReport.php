<?php

namespace App\Exports;

use App\Models\Enrollment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherEnrolleesReport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    protected $teacherId;

    public function __construct(int $teacherId)
    {
        $this->teacherId = $teacherId;
    }

    /**
     * Fetch all enrollments made by the specified teacher.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Enrollment::where('teacher_id', $this->teacherId)
            ->with('student', 'class.section.gradeLevel') // Eager load relationships
            ->get();
    }

    /**
     * Define the headings for the export.
     */
    public function headings(): array
    {
        return [
            'LRN',
            'Student Name',
            'Class',
            'Grade Level',
            'Enrollment Date',
        ];
    }

    /**
     * Map the data for each row.
     *
     * @param  mixed  $enrollment
     */
    public function map($enrollment): array
    {
        return [
            $enrollment->student->lrn ?? 'N/A',
            $enrollment->student->first_name.' '.$enrollment->student->last_name,
            optional($enrollment->class->section)->name ?? 'N/A',
            optional($enrollment->class->section->gradeLevel)->name ?? 'N/A',
            $enrollment->created_at->format('M d, Y'),
        ];
    }

    /**
     * Apply styles to the heading row.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Format the LRN column as text to prevent scientific notation.
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
