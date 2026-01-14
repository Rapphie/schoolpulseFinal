<?php

namespace App\Exports;

use App\Models\Grade;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class GradesExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    private ?int $schoolYearId;
    private ?int $gradeLevelId;
    private ?int $classId;
    private ?string $filterType;

    public function __construct(?int $schoolYearId = null, ?int $gradeLevelId = null, ?int $classId = null, ?string $filterType = null)
    {
        $this->schoolYearId = $schoolYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->classId = $classId;
        $this->filterType = $filterType;
    }

    public function collection(): Collection
    {
        $query = Grade::query()
            ->select(
                'grades.*',
                'students.lrn',
                'students.first_name',
                'students.last_name',
                'subjects.name as subject_name',
                'sections.name as section_name',
                'grade_levels.name as grade_level_name',
                'grade_levels.level as grade_level',
                'users.first_name as teacher_first_name',
                'users.last_name as teacher_last_name'
            )
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->leftJoin('teachers', 'grades.teacher_id', '=', 'teachers.id')
            ->leftJoin('users', 'teachers.user_id', '=', 'users.id')
            ->leftJoin('enrollments', function ($join) {
                $join->on('enrollments.student_id', '=', 'grades.student_id')
                    ->on('enrollments.school_year_id', '=', 'grades.school_year_id');
            })
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($this->schoolYearId, function ($query) {
                $query->where('grades.school_year_id', $this->schoolYearId);
            })
            ->when($this->gradeLevelId, function ($query) {
                $query->where('grade_levels.id', $this->gradeLevelId);
            })
            ->when($this->classId, function ($query) {
                $query->where('classes.id', $this->classId);
            });

        // Apply filter based on type
        if ($this->filterType === 'passing') {
            $query->where('grades.grade', '>=', 75);
        } elseif ($this->filterType === 'failing') {
            $query->where('grades.grade', '<', 75);
        } elseif ($this->filterType === 'highest') {
            // Get the max grade value first
            $maxGrade = (clone $query)->max('grades.grade');
            if ($maxGrade) {
                $query->where('grades.grade', '=', $maxGrade);
            }
        }

        return $query
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->orderBy('students.last_name')
            ->orderBy('grades.quarter')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Student LRN',
            'Student Name',
            'Grade Level',
            'Section',
            'Subject',
            'Quarter',
            'Grade',
            'Status',
            'Teacher',
        ];
    }

    public function map($grade): array
    {
        $gradeLabel = $grade->grade_level_name ?? ($grade->grade_level ? 'Grade ' . $grade->grade_level : 'N/A');
        $teacherName = $grade->teacher_first_name
            ? trim($grade->teacher_first_name . ' ' . $grade->teacher_last_name)
            : 'N/A';
        $status = $grade->grade >= 75 ? 'Passing' : 'Failing';

        return [
            $grade->lrn ?? 'N/A',
            trim(($grade->last_name ?? '') . ', ' . ($grade->first_name ?? '')),
            $gradeLabel,
            $grade->section_name ?? 'N/A',
            $grade->subject_name ?? 'N/A',
            $grade->quarter ?? 'N/A',
            number_format($grade->grade, 1),
            $status,
            $teacherName,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
    }
}
