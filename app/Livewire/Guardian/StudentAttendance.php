<?php

namespace App\Livewire\Guardian;

use App\Livewire\Guardian\Traits\WithStudentSelector;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\GuardianService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class StudentAttendance extends Component
{
    use WithPagination;
    use WithStudentSelector;

    public string $quarterFilter = 'all';

    public string $statusFilter = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 10;

    /**
     * @var array<string, string>
     */
    public array $statusVariants = [
        'present' => 'bg-success',
        'absent' => 'bg-danger',
        'late' => 'bg-warning text-dark',
        'excused' => 'bg-info text-dark',
    ];

    protected string $paginationTheme = 'bootstrap';

    protected GuardianService $guardianService;

    public function boot(GuardianService $guardianService): void
    {
        $this->guardianService = $guardianService;
    }

    public function mount(?int $studentId = null): void
    {
        $this->mountWithStudentSelector($studentId);
    }

    public function updatedQuarterFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $activeSchoolYear = $this->guardianService->activeSchoolYear();
        $quarterLabels = $this->guardianService->quarterLabels();
        $selectedStudent = $this->selectedStudent;

        if (! $selectedStudent) {
            return view('livewire.guardian.student-attendance', [
                'students' => $this->students,
                'selectedStudentSummary' => null,
                'quarterLabels' => $quarterLabels,
                'statusVariants' => $this->statusVariants,
                'attendanceSummaryCards' => $this->guardianService->attendanceSummaryWithPercentages(collect()),
                'attendanceRecords' => collect(),
            ]);
        }

        $selectedStudentSummary = $this->guardianService->studentSummary($selectedStudent, $activeSchoolYear);
        $summaryQuery = $this->attendanceQuery(false, $selectedStudent);
        $summaryRecords = $summaryQuery->get();
        $attendanceSummaryCards = $this->guardianService->attendanceSummaryWithPercentages($summaryRecords);

        $recordsQuery = $this->attendanceQuery(true, $selectedStudent);
        $attendanceRecords = $recordsQuery
            ->orderByDesc('date')
            ->orderByDesc('time_in')
            ->paginate($this->perPage)
            ->through(fn (Attendance $attendance): array => $this->guardianService->formatAttendanceRecord($attendance));

        return view('livewire.guardian.student-attendance', [
            'students' => $this->students,
            'selectedStudentSummary' => $selectedStudentSummary,
            'quarterLabels' => $quarterLabels,
            'statusVariants' => $this->statusVariants,
            'attendanceSummaryCards' => $attendanceSummaryCards,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }

    private function attendanceQuery(bool $withStatusFilter, Student $selectedStudent): Builder
    {
        $query = $this->guardianService->attendanceQuery(
            $selectedStudent,
            $this->guardianService->activeSchoolYear()
        );

        if ($this->quarterFilter !== 'all') {
            $quarterNumber = (int) $this->quarterFilter;
            if ($quarterNumber > 0) {
                $quarterValues = $this->guardianService->quarterSearchValues($quarterNumber);

                $query->where(function (Builder $quarterQuery) use ($quarterNumber, $quarterValues): void {
                    $quarterQuery->where('quarter_int', $quarterNumber)
                        ->orWhereIn('quarter', $quarterValues);
                });
            }
        }

        if ($withStatusFilter && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        return $query;
    }
}
