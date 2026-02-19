<?php

namespace App\Livewire\Guardian;

use App\Livewire\Guardian\Traits\WithStudentSelector;
use App\Services\GuardianService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentGrades extends Component
{
    use WithStudentSelector;

    public int $selectedQuarter = 1;

    protected GuardianService $guardianService;

    public function boot(GuardianService $guardianService): void
    {
        $this->guardianService = $guardianService;
    }

    public function mount(?int $studentId = null): void
    {
        $this->mountWithStudentSelector($studentId);
    }

    public function setQuarter(int $quarter): void
    {
        if (array_key_exists($quarter, $this->guardianService->quarterLabels())) {
            $this->selectedQuarter = $quarter;
        }
    }

    public function render(): View
    {
        $activeSchoolYear = $this->guardianService->activeSchoolYear();
        $quarterLabels = $this->guardianService->quarterLabels();
        $selectedStudent = $this->selectedStudent;

        if (! $selectedStudent) {
            return view('livewire.guardian.student-grades', [
                'students' => $this->students,
                'selectedStudentSummary' => null,
                'quarterLabels' => $quarterLabels,
                'gradesByQuarter' => collect(),
                'selectedQuarterLabel' => $quarterLabels[$this->selectedQuarter],
                'selectedQuarterGrades' => collect(),
                'quarterAverages' => [],
                'selectedQuarterAverage' => null,
            ]);
        }

        if (! array_key_exists($this->selectedQuarter, $quarterLabels)) {
            $this->selectedQuarter = 1;
        }

        $selectedStudentSummary = $this->guardianService->studentSummary($selectedStudent, $activeSchoolYear);
        $gradesByQuarter = $this->guardianService->gradesByQuarter($selectedStudent, $activeSchoolYear);
        $quarterAverages = $this->guardianService->quarterAverages($gradesByQuarter);
        $selectedQuarterLabel = $quarterLabels[$this->selectedQuarter];
        $selectedQuarterGrades = $gradesByQuarter->get($selectedQuarterLabel, collect());
        $selectedQuarterAverage = $quarterAverages[$selectedQuarterLabel] ?? null;

        return view('livewire.guardian.student-grades', [
            'students' => $this->students,
            'selectedStudentSummary' => $selectedStudentSummary,
            'quarterLabels' => $quarterLabels,
            'gradesByQuarter' => $gradesByQuarter,
            'selectedQuarterLabel' => $selectedQuarterLabel,
            'selectedQuarterGrades' => $selectedQuarterGrades,
            'quarterAverages' => $quarterAverages,
            'selectedQuarterAverage' => $selectedQuarterAverage,
        ]);
    }
}
