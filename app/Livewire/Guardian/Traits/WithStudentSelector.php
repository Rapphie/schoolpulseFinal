<?php

namespace App\Livewire\Guardian\Traits;

use App\Models\Guardian;
use App\Models\Student;
use App\Services\GuardianService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait WithStudentSelector
{
    public ?int $selectedStudentId = null;

    public Collection $students;

    public function mountWithStudentSelector(?int $studentId = null): void
    {
        $guardian = $this->guardianProfile();
        $this->students = $this->guardianService()->guardianStudents($guardian);

        $sessionSelectedId = (int) session()->get($this->studentSelectionSessionKey());
        $preferredStudentId = $studentId ?? ($sessionSelectedId > 0 ? $sessionSelectedId : null);

        if ($preferredStudentId !== null && $this->students->contains('id', $preferredStudentId)) {
            $this->selectedStudentId = $preferredStudentId;
        } else {
            $this->selectedStudentId = $this->students->first()?->id;
        }

        if ($this->selectedStudentId !== null) {
            session()->put($this->studentSelectionSessionKey(), $this->selectedStudentId);
        }
    }

    public function updatedSelectedStudentId(mixed $studentId): void
    {
        $studentId = (int) $studentId;
        $this->authorizeStudentSelection($studentId);
        $this->selectedStudentId = $studentId;
        session()->put($this->studentSelectionSessionKey(), $studentId);

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        $this->dispatch('guardian-student-selected', studentId: $studentId);
    }

    public function selectStudent(int $studentId): void
    {
        $this->updatedSelectedStudentId($studentId);
    }

    public function getSelectedStudentProperty(): ?Student
    {
        if (! isset($this->students) || $this->students->isEmpty() || $this->selectedStudentId === null) {
            return null;
        }

        $student = $this->students->firstWhere('id', $this->selectedStudentId);

        return $student instanceof Student ? $student : null;
    }

    protected function studentSelectionSessionKey(): string
    {
        return 'guardian.selected_student_id.'.Auth::id();
    }

    protected function authorizeStudentSelection(int $studentId): void
    {
        if ($studentId <= 0 || ! isset($this->students) || ! $this->students->contains('id', $studentId)) {
            abort(403, 'You are not allowed to access this student.');
        }
    }

    protected function guardianProfile(): Guardian
    {
        $guardian = Auth::user()?->guardian;
        if (! $guardian instanceof Guardian) {
            abort(403, 'Guardian profile not found.');
        }

        return $guardian;
    }

    protected function guardianService(): GuardianService
    {
        return app(GuardianService::class);
    }
}
