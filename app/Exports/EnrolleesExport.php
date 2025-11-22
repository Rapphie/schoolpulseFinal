<?php

namespace App\Exports;

use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnrolleesExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    private ?int $classId;
    private $gradeLevel;
    private ?int $schoolYearId;

    public function __construct(?int $classId = null, $gradeLevel = null, ?int $schoolYearId = null)
    {
        $this->classId = $classId;
        $this->gradeLevel = $gradeLevel;
        $this->schoolYearId = $schoolYearId;
    }

    public function collection(): Collection
    {
        return Enrollment::query()
            ->with([
                'student',
                'class.section.gradeLevel',
                'class.teacher.user',
                'teacher.user',
                'schoolYear',
            ])
            ->when($this->classId, function ($query) {
                $query->where('class_id', $this->classId);
            })
            ->when($this->gradeLevel, function ($query) {
                $query->whereHas('class.section.gradeLevel', function ($gradeQuery) {
                    $gradeQuery->where('level', $this->gradeLevel);
                });
            })
            ->when($this->schoolYearId, function ($query) {
                $query->where('school_year_id', $this->schoolYearId);
            })
            ->orderByDesc('school_year_id')
            ->orderBy('class_id')
            ->orderBy('student_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'School Year',
            'Grade Level',
            'Section',
            'Class ID',
            'Student LRN',
            'Student Name',
            'Gender',
            'Enrollment Status',
            'Enrollment Date',
            'Adviser / Teacher',
        ];
    }

    public function map($enrollment): array
    {
        $section = optional(optional($enrollment->class)->section);
        $gradeLevel = optional($section->gradeLevel);
        $schoolYearName = optional($enrollment->schoolYear)->name ?? 'N/A';
        $gradeLabel = $gradeLevel->name ?? ($gradeLevel && $gradeLevel->level ? 'Grade ' . $gradeLevel->level : 'N/A');
        $student = $enrollment->student;
        $teacher = $enrollment->teacher ?? optional($enrollment->class)->teacher;
        $teacherName = $teacher ? (optional($teacher->user)->full_name ?? 'Teacher #' . $teacher->id) : 'N/A';
        $enrollmentDate = $enrollment->enrollment_date
            ? Carbon::parse($enrollment->enrollment_date)
            : ($enrollment->created_at ? Carbon::parse($enrollment->created_at) : null);

        return [
            $schoolYearName,
            $gradeLabel,
            $section->name ?? 'N/A',
            optional($enrollment->class)->id ?? 'N/A',
            $student->lrn ?? 'N/A',
            $student ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) : 'N/A',
            $student->gender ?? 'N/A',
            ucfirst($enrollment->status ?? 'enrolled'),
            $enrollmentDate ? $enrollmentDate->format('M d, Y') : 'N/A',
            $teacherName,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
    }
}
