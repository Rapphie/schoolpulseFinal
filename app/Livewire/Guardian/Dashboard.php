<?php

namespace App\Livewire\Guardian;

use App\Livewire\Guardian\Traits\WithStudentSelector;
use App\Services\GuardianService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    use WithStudentSelector;

    /**
     * @var array<string, string>
     */
    public array $statusVariants = [
        'present' => 'bg-success',
        'absent' => 'bg-danger',
        'late' => 'bg-warning text-dark',
        'excused' => 'bg-info text-dark',
    ];

    protected GuardianService $guardianService;

    public function boot(GuardianService $guardianService): void
    {
        $this->guardianService = $guardianService;
    }

    public function mount(?int $studentId = null): void
    {
        $this->mountWithStudentSelector($studentId);
    }

    public function render(): View
    {
        $activeSchoolYear = $this->guardianService->activeSchoolYear();
        $selectedStudent = $this->selectedStudent;

        if (! $selectedStudent) {
            return view('livewire.guardian.dashboard', [
                'students' => $this->students,
                'selectedStudentSummary' => null,
                'studentInitials' => null,
                'quarterAverages' => [],
                'gwa' => null,
                'attendanceSummary' => [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'excused' => 0,
                ],
                'attendanceRate' => 0.0,
                'latestQuarterLabel' => null,
                'latestQuarterGrades' => collect(),
                'recentAttendanceRecords' => collect(),
                'recentActivities' => collect(),
                'statusVariants' => $this->statusVariants,
            ]);
        }

        $selectedStudentSummary = $this->guardianService->studentSummary($selectedStudent, $activeSchoolYear);
        $gradesByQuarter = $this->guardianService->gradesByQuarter($selectedStudent, $activeSchoolYear);
        $quarterAverages = $this->guardianService->quarterAverages($gradesByQuarter);
        $gwa = $this->guardianService->generalWeightedAverage($gradesByQuarter);
        $latestQuarterLabel = $this->guardianService->latestQuarterLabel($gradesByQuarter);
        $latestQuarterGrades = $latestQuarterLabel
            ? $gradesByQuarter->get($latestQuarterLabel, collect())
            : collect();

        $attendanceRecords = $this->guardianService->attendanceRecords($selectedStudent, $activeSchoolYear);
        $attendanceSummary = $this->guardianService->attendanceSummary($attendanceRecords);
        $attendanceRate = $this->guardianService->attendanceRate($attendanceSummary);
        $recentAttendanceRecords = $attendanceRecords
            ->take(10)
            ->map(fn ($attendance): array => $this->guardianService->formatAttendanceRecord($attendance));

        $recentActivities = $this->guardianService->recentActivities($selectedStudent, $activeSchoolYear, 8);
        $studentInitials = $this->studentInitials($selectedStudentSummary['full_name']);

        return view('livewire.guardian.dashboard', [
            'students' => $this->students,
            'selectedStudentSummary' => $selectedStudentSummary,
            'studentInitials' => $studentInitials,
            'quarterAverages' => $quarterAverages,
            'gwa' => $gwa,
            'attendanceSummary' => $attendanceSummary,
            'attendanceRate' => $attendanceRate,
            'latestQuarterLabel' => $latestQuarterLabel,
            'latestQuarterGrades' => $latestQuarterGrades,
            'recentAttendanceRecords' => $recentAttendanceRecords,
            'recentActivities' => $recentActivities,
            'statusVariants' => $this->statusVariants,
        ]);
    }

    private function studentInitials(string $fullName): string
    {
        $initials = collect(explode(' ', $fullName))
            ->filter(fn (string $part): bool => $part !== '')
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return $initials !== '' ? $initials : 'N/A';
    }
}
