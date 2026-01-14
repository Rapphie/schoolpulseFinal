<?php

namespace App\Exports;

use App\Models\Enrollment;
use App\Models\Attendance;
use App\Models\Grade;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CumulativeExport implements WithMultipleSheets
{
    protected ?int $schoolYearId;
    protected ?int $gradeLevelId;
    protected ?int $classId;

    public function __construct(?int $schoolYearId = null, ?int $gradeLevelId = null, ?int $classId = null)
    {
        $this->schoolYearId = $schoolYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->classId = $classId;
    }

    public function sheets(): array
    {
        return [
            new EnrollmentSheet($this->schoolYearId, $this->gradeLevelId, $this->classId),
            new AttendanceSummarySheet($this->schoolYearId, $this->gradeLevelId, $this->classId),
            new GradesSummarySheet($this->schoolYearId, $this->gradeLevelId, $this->classId),
        ];
    }
}

class EnrollmentSheet implements FromCollection, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected ?int $schoolYearId;
    protected ?int $gradeLevelId;
    protected ?int $classId;

    public function __construct(?int $schoolYearId, ?int $gradeLevelId, ?int $classId)
    {
        $this->schoolYearId = $schoolYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->classId = $classId;
    }

    public function title(): string
    {
        return 'Enrollment';
    }

    public function collection(): Collection
    {
        return Enrollment::query()
            ->select(
                'enrollments.id',
                'enrollments.created_at',
                'students.lrn',
                'students.first_name',
                'students.last_name',
                'students.gender',
                'sections.name as section_name',
                'grade_levels.name as grade_level_name',
                'grade_levels.level as grade_level'
            )
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('classes', 'enrollments.class_id', '=', 'classes.id')
            ->join('sections', 'classes.section_id', '=', 'sections.id')
            ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($this->schoolYearId, fn($q) => $q->where('enrollments.school_year_id', $this->schoolYearId))
            ->when($this->gradeLevelId, fn($q) => $q->where('grade_levels.id', $this->gradeLevelId))
            ->when($this->classId, fn($q) => $q->where('classes.id', $this->classId))
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->orderBy('students.last_name')
            ->get();
    }

    public function headings(): array
    {
        return ['LRN', 'Last Name', 'First Name', 'Gender', 'Grade Level', 'Section', 'Enrolled Date'];
    }

    public function map($row): array
    {
        return [
            $row->lrn ?? 'N/A',
            $row->last_name,
            $row->first_name,
            ucfirst($row->gender ?? 'N/A'),
            $row->grade_level_name ?? ('Grade ' . $row->grade_level),
            $row->section_name ?? 'Unassigned',
            $row->created_at ? $row->created_at->format('Y-m-d') : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class AttendanceSummarySheet implements FromCollection, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected ?int $schoolYearId;
    protected ?int $gradeLevelId;
    protected ?int $classId;

    public function __construct(?int $schoolYearId, ?int $gradeLevelId, ?int $classId)
    {
        $this->schoolYearId = $schoolYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->classId = $classId;
    }

    public function title(): string
    {
        return 'Attendance Summary';
    }

    public function collection(): Collection
    {
        return Attendance::query()
            ->select(
                'sections.name as section_name',
                'grade_levels.name as grade_level_name',
                'grade_levels.level as grade_level',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present"),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late")
            )
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->leftJoin('enrollments', function ($join) {
                $join->on('enrollments.student_id', '=', 'attendances.student_id')
                    ->on('enrollments.school_year_id', '=', 'attendances.school_year_id');
            })
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($this->schoolYearId, fn($q) => $q->where('attendances.school_year_id', $this->schoolYearId))
            ->when($this->gradeLevelId, fn($q) => $q->where('grade_levels.id', $this->gradeLevelId))
            ->when($this->classId, fn($q) => $q->where('classes.id', $this->classId))
            ->groupBy('sections.id', 'sections.name', 'grade_levels.id', 'grade_levels.name', 'grade_levels.level')
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->get();
    }

    public function headings(): array
    {
        return ['Grade Level', 'Section', 'Total Records', 'Present', 'Absent', 'Late', 'Attendance Rate'];
    }

    public function map($row): array
    {
        $total = (int) $row->total;
        $present = (int) $row->present;
        $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0;

        return [
            $row->grade_level_name ?? ('Grade ' . $row->grade_level),
            $row->section_name ?? 'Unassigned',
            $total,
            $present,
            (int) $row->absent,
            (int) $row->late,
            $rate . '%',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class GradesSummarySheet implements FromCollection, WithTitle, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected ?int $schoolYearId;
    protected ?int $gradeLevelId;
    protected ?int $classId;

    public function __construct(?int $schoolYearId, ?int $gradeLevelId, ?int $classId)
    {
        $this->schoolYearId = $schoolYearId;
        $this->gradeLevelId = $gradeLevelId;
        $this->classId = $classId;
    }

    public function title(): string
    {
        return 'Grades Summary';
    }

    public function collection(): Collection
    {
        return Grade::query()
            ->select(
                'sections.name as section_name',
                'grade_levels.name as grade_level_name',
                'grade_levels.level as grade_level',
                'subjects.name as subject_name',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as records'),
                \Illuminate\Support\Facades\DB::raw('AVG(grades.grade) as average'),
                \Illuminate\Support\Facades\DB::raw('MAX(grades.grade) as highest'),
                \Illuminate\Support\Facades\DB::raw('MIN(grades.grade) as lowest'),
                \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN grades.grade >= 75 THEN 1 ELSE 0 END) as passing")
            )
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->leftJoin('enrollments', function ($join) {
                $join->on('enrollments.student_id', '=', 'grades.student_id')
                    ->on('enrollments.school_year_id', '=', 'grades.school_year_id');
            })
            ->leftJoin('classes', 'enrollments.class_id', '=', 'classes.id')
            ->leftJoin('sections', 'classes.section_id', '=', 'sections.id')
            ->leftJoin('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->when($this->schoolYearId, fn($q) => $q->where('grades.school_year_id', $this->schoolYearId))
            ->when($this->gradeLevelId, fn($q) => $q->where('grade_levels.id', $this->gradeLevelId))
            ->when($this->classId, fn($q) => $q->where('classes.id', $this->classId))
            ->groupBy('sections.id', 'sections.name', 'grade_levels.id', 'grade_levels.name', 'grade_levels.level', 'subjects.id', 'subjects.name')
            ->orderBy('grade_levels.level')
            ->orderBy('sections.name')
            ->orderBy('subjects.name')
            ->get();
    }

    public function headings(): array
    {
        return ['Grade Level', 'Section', 'Subject', 'Records', 'Average', 'Highest', 'Lowest', 'Passing Rate'];
    }

    public function map($row): array
    {
        $records = (int) $row->records;
        $passing = (int) $row->passing;
        $passingRate = $records > 0 ? round(($passing / $records) * 100, 1) : 0;

        return [
            $row->grade_level_name ?? ('Grade ' . $row->grade_level),
            $row->section_name ?? 'Unassigned',
            $row->subject_name ?? 'N/A',
            $records,
            round($row->average, 1),
            round($row->highest, 1),
            round($row->lowest, 1),
            $passingRate . '%',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
