<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <p class="text-uppercase small fw-semibold text-muted mb-1">Selected Student</p>
                <h5 class="mb-1">{{ $selectedStudentSummary['full_name'] ?? '—' }}</h5>
                <p class="text-muted mb-0">
                    {{ $selectedStudentSummary['grade_level'] ?? 'Unassigned' }}
                    <span class="mx-1">·</span>
                    {{ $selectedStudentSummary['class_section'] ?? 'Unassigned' }}
                </p>
            </div>
            <div class="text-muted small text-md-end">
                <div><strong>Student ID:</strong> {{ $selectedStudentSummary['student_identifier'] ?? 'N/A' }}</div>
                <div><strong>LRN:</strong> {{ $selectedStudentSummary['lrn'] ?? 'N/A' }}</div>
                <div><strong>School Year:</strong> {{ $selectedStudentSummary['school_year'] ?? '—' }}</div>
            </div>
        </div>

        @if ($students->count() > 1)
            <div class="row g-2 mt-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1" for="studentSelector">Switch Student</label>
                    <select id="studentSelector" class="form-select" wire:model.live="selectedStudentId">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 text-md-end">
                    <span wire:loading wire:target="selectedStudentId"
                        class="badge border border-primary text-primary bg-white px-3 py-2">
                        Loading student data...
                    </span>
                </div>
            </div>
        @else
            <p class="text-muted small mt-3 mb-0">Only one linked student is associated with this guardian account.</p>
        @endif
    </div>
</div>
