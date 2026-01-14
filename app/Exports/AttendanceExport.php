<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    private ?int $schoolYearId;
    private ?int $gradeLevelId;
    private ?int $classId;
    private ?string $status;

    public function __construct(?int $schoolYearId = null, ?int $gradeLevelId = null, ?int $classId = null, ?string $status = null)
    {
        $this->schoolYearId = $schoolYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->classId = $classId;
        $this->status = $status;
    }

    public function collection(): Collection
    {
        return Attendance::query()
            ->with([
                'student',
                'subject',
                'teacher.user',
            ])
            ->leftJoin('classes', 'attendances.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->select('attendances.*', 'sections.name as section_name', 'grade_levels.name as grade_name', 'grade_levels.level as grade_level')
            ->when($this->schoolYearId, function ($query) {
                $query->where('attendances.school_year_id', $this->schoolYearId);
            })
            ->when($this->gradeLevelId, function ($query) {
                $query->where('grade_levels.id', $this->gradeLevelId);
            })
            ->when($this->classId, function ($query) {
                $query->where('attendances.class_id', $this->classId);
            })
            ->when($this->status, function ($query) {
                $query->where('attendances.status', $this->status);
            })
            ->orderByDesc('attendances.date')
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Student LRN',
            'Student Name',
            'Grade Level',
            'Section',
            'Subject',
            'Status',
            'Time In',
            'Quarter',
            'Teacher',
        ];
    }

    public function map($attendance): array
    {
        $student = $attendance->student;
        $subject = $attendance->subject;
        $teacher = $attendance->teacher;

        $gradeLabel = $attendance->grade_name ?? ($attendance->grade_level ? 'Grade ' . $attendance->grade_level : 'N/A');

        return [
            $attendance->date ? Carbon::parse($attendance->date)->format('M d, Y') : 'N/A',
            $student->lrn ?? 'N/A',
            $student ? trim(($student->last_name ?? '') . ', ' . ($student->first_name ?? '')) : 'N/A',
            $gradeLabel,
            $attendance->section_name ?? 'N/A',
            $subject->name ?? 'N/A',
            ucfirst($attendance->status ?? 'N/A'),
            $attendance->time_in ? Carbon::parse($attendance->time_in)->format('g:i A') : 'N/A',
            $attendance->quarter ?? 'N/A',
            $teacher ? optional($teacher->user)->full_name ?? 'N/A' : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
    }
}
