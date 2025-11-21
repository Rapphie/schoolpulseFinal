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

class EnrolleesExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
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
        $query = Section::with(['gradeLevel', 'classes.enrollments.student'])
            ->withCount(['classes as students_count' => function ($q) {
                $q->join('enrollments', 'enrollments.class_id', '=', 'classes.id')
                    ->where('enrollments.status', '!=', 'unenrolled');
            }]);

        if ($this->grade) {
            $query->whereHas('gradeLevel', function ($q) {
                $q->where('level', $this->grade);
            });
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
        // Get all enrolled students for this section
        $students = collect();
        foreach ($row->classes as $class) {
            foreach ($class->enrollments as $enrollment) {
                if ($enrollment->status != 'unenrolled' && $enrollment->student) {
                    $students->push($enrollment->student);
                }
            }
        }

        $boyCount = $students->where('gender', 'male')->count();
        $girlCount = $students->where('gender', 'female')->count();

        // Get class adviser (teacher from the main class)
        $adviser = $row->classes->first()?->teacher;

        return [
            $row->id,
            $row->name,
            'Grade ' . ($row->gradeLevel ? $row->gradeLevel->level : 'N/A'),
            $adviser ? $adviser->user->first_name . ' ' . $adviser->user->last_name : 'No Adviser',
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
