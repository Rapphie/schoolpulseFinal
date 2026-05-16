@extends('base')
@section('title', 'Manage Quarters - ' . $schoolYear->name)
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Quarters</li>
                </ol>
            </nav>
            <h2 class="h4 mb-1 fw-bold text-dark">Manage Quarters</h2>
            <p class="text-muted mb-0">
                School Year: <span class="fw-semibold text-primary">{{ $schoolYear->name }}</span>
                ({{ \Carbon\Carbon::parse($schoolYear->start_date)->format('M d, Y') }} -
                {{ \Carbon\Carbon::parse($schoolYear->end_date)->format('M d, Y') }})
            </p>
        </div>
        <div class="d-flex gap-2">
            @if ($quarters->count() === 0)
                <form action="{{ route('admin.school-year.quarters.auto-generate', $schoolYear) }}" method="POST"
                    class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary"
                        onclick="return confirm('This will auto-generate all 4 quarters with equal duration. Continue?')">
                        <i data-feather="zap" class="feather-sm me-1"></i> Auto-Generate
                    </button>
                </form>
            @endif
            @if ($quarters->count() < 4)
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuarterModal">
                    <i data-feather="plus" class="feather-sm me-1"></i> Add Quarter
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Current Quarter Highlight --}}
    @php
        $currentQuarter = $quarters->first(fn($q) => $q->isCurrent());
    @endphp
    @if ($currentQuarter)
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i data-feather="info" class="feather-sm me-2"></i>
            <div>
                <strong>Current Quarter:</strong> {{ $currentQuarter->name }}
                @if ($currentQuarter->daysRemaining() > 0)
                    ({{ $currentQuarter->daysRemaining() }} days remaining)
                @endif
                @if ($currentQuarter->grade_submission_deadline)
                    | <strong>Grade Deadline:</strong>
                    {{ \Carbon\Carbon::parse($currentQuarter->grade_submission_deadline)->format('M d, Y') }}
                    @if (
                        $currentQuarter->daysUntilDeadline() !== null &&
                            $currentQuarter->daysUntilDeadline() <= 7 &&
                            $currentQuarter->daysUntilDeadline() >= 0)
                        <span class="badge bg-warning ms-1">{{ $currentQuarter->daysUntilDeadline() }} days left</span>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if ($quarters->count() > 0)
                <div class="row g-4">
                    @foreach ($quarters as $quarter)
                        <div class="col-md-6 col-lg-3">
                            <div class="card h-100 {{ $quarter->isCurrent() ? 'border-primary border-2' : '' }}">
                                <div
                                    class="card-header d-flex justify-content-between align-items-center {{ $quarter->isCurrent() ? 'bg-primary text-white' : 'bg-light' }}">
                                    <h6 class="mb-0 fw-bold">{{ $quarter->name }}</h6>
                                    <span class="badge {{ $quarter->status_badge_class }}">{{ $quarter->status }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Period</small>
                                        <span>{{ \Carbon\Carbon::parse($quarter->start_date)->format('M d') }} -
                                            {{ \Carbon\Carbon::parse($quarter->end_date)->format('M d, Y') }}</span>
                                    </div>
                                    @if ($quarter->grade_submission_deadline)
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Grade Deadline</small>
                                            <span
                                                class="{{ $quarter->isSubmissionDeadlinePassed() ? 'text-danger' : '' }}">
                                                {{ \Carbon\Carbon::parse($quarter->grade_submission_deadline)->format('M d, Y') }}
                                                @if ($quarter->isSubmissionDeadlinePassed())
                                                    <small class="text-danger">(Passed)</small>
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Duration</small>
                                        <span>{{ \Carbon\Carbon::parse($quarter->start_date)->diffInDays($quarter->end_date) + 1 }}
                                            days</span>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-0 d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="modal"
                                        data-bs-target="#editQuarterModal{{ $quarter->id }}">
                                        <i data-feather="edit-2" class="feather-sm"></i> Edit
                                    </button>
                                    @php
                                        $quarterLockInfo = $quarterLockContext['quarterLocks'][(int) $quarter->quarter] ?? null;
                                        $isExplicitlyLocked = $quarterLockInfo['is_explicitly_locked'] ?? $quarter->is_locked === true;
                                        $isExplicitlyUnlocked = $quarterLockInfo['is_explicitly_unlocked'] ?? $quarter->is_locked === false;
                                        $isEffectivelyLocked = $quarterLockInfo['is_locked'] ?? false;
                                        $lockReasonLabel = $quarterLockInfo['lock_reason_label'] ?? null;

                                        if ($isExplicitlyLocked) {
                                            $toggleTooltip = 'Click to unlock' . ($lockReasonLabel ? ' (' . $lockReasonLabel . ')' : '');
                                        } elseif ($isExplicitlyUnlocked) {
                                            $toggleTooltip = 'Click to lock (Explicitly unlocked, overrides auto-lock)';
                                        } elseif ($isEffectivelyLocked && $lockReasonLabel) {
                                            $toggleTooltip = 'Click to unlock (Effective: ' . $lockReasonLabel . ')';
                                        } else {
                                            $toggleTooltip = 'Click to lock';
                                        }
                                    @endphp
                                    @if ($schoolYear->is_active)
                                        <form
                                            action="{{ route('admin.school-year.quarters.toggle-lock', [$schoolYear, $quarter]) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm {{ $isExplicitlyLocked ? 'btn-warning' : ($isExplicitlyUnlocked ? 'btn-outline-success' : ($isEffectivelyLocked ? 'btn-secondary' : 'btn-outline-secondary')) }}"
                                                title="{{ $toggleTooltip }}">
                                                <i data-feather="{{ $isEffectivelyLocked ? 'lock' : 'unlock' }}"
                                                    class="feather-sm"></i>
                                        </button>
                                    </form>
                                        @if ($quarter->is_locked !== null)
                                            <form
                                                action="{{ route('admin.school-year.quarters.reset-lock', [$schoolYear, $quarter]) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-info"
                                                    title="Reset to auto mode">
                                                    <i data-feather="rotate-ccw" class="feather-sm"></i>
                                                </button>
                                            </form>
                                        @endif
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                        title="Locking is only available for the active school year.">
                                        <i data-feather="{{ $isEffectivelyLocked ? 'lock' : 'unlock' }}" class="feather-sm"></i>
                                    </button>
                                @endif
                                    <form
                                        action="{{ route('admin.school-year.quarters.destroy', [$schoolYear, $quarter]) }}"
                                        method="POST" class="d-inline" onsubmit="return confirm('Delete this quarter?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i data-feather="trash-2" class="feather-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Edit Quarter Modal --}}
                        <div class="modal fade" id="editQuarterModal{{ $quarter->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form
                                        action="{{ route('admin.school-year.quarters.update', [$schoolYear, $quarter]) }}"
                                        method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit {{ $quarter->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Start Date</label>
                                                <input type="date" class="form-control" name="start_date"
                                                    value="{{ \Carbon\Carbon::parse($quarter->start_date)->format('Y-m-d') }}"
                                                    min="{{ \Carbon\Carbon::parse($schoolYear->start_date)->format('Y-m-d') }}"
                                                    max="{{ \Carbon\Carbon::parse($schoolYear->end_date)->format('Y-m-d') }}"
                                                    required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">End Date</label>
                                                <input type="date" class="form-control" name="end_date"
                                                    value="{{ \Carbon\Carbon::parse($quarter->end_date)->format('Y-m-d') }}"
                                                    min="{{ \Carbon\Carbon::parse($schoolYear->start_date)->format('Y-m-d') }}"
                                                    max="{{ \Carbon\Carbon::parse($schoolYear->end_date)->format('Y-m-d') }}"
                                                    required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Grade Submission Deadline <small
                                                        class="text-muted">(optional)</small></label>
                                                <input type="date" class="form-control"
                                                    name="grade_submission_deadline"
                                                    value="{{ $quarter->grade_submission_deadline ? \Carbon\Carbon::parse($quarter->grade_submission_deadline)->format('Y-m-d') : '' }}">
                                                <small class="text-muted">Teachers must submit grades before this
                                                    date.</small>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_locked"
                                                    id="isLocked{{ $quarter->id }}"
                                                    {{ $quarter->is_locked ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isLocked{{ $quarter->id }}">
                                                    Lock quarter (prevents grade/attendance modifications)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Timeline visualization --}}
                <div class="mt-4 pt-4 border-top">
                    <h6 class="fw-bold mb-3">Quarter Timeline</h6>
                    <div class="position-relative" style="height: 60px;">
                        @php
                            $syStart = \Carbon\Carbon::parse($schoolYear->start_date);
                            $syEnd = \Carbon\Carbon::parse($schoolYear->end_date);
                            $totalDays = $syStart->diffInDays($syEnd);
                        @endphp
                        <div class="progress" style="height: 30px;">
                            @foreach ($quarters->sortBy('quarter') as $q)
                                @php
                                    $qStart = \Carbon\Carbon::parse($q->start_date);
                                    $qEnd = \Carbon\Carbon::parse($q->end_date);
                                    $offsetDays = $syStart->diffInDays($qStart);
                                    $duration = $qStart->diffInDays($qEnd) + 1;
                                    $widthPercent = ($duration / $totalDays) * 100;
                                    $leftPercent = ($offsetDays / $totalDays) * 100;
                                    $bgClass = $q->isCurrent()
                                        ? 'bg-primary'
                                        : ($q->hasEnded()
                                            ? 'bg-secondary'
                                            : 'bg-info');
                                @endphp
                                <div class="progress-bar {{ $bgClass }}" role="progressbar"
                                    style="width: {{ $widthPercent }}%; position: relative;"
                                    title="{{ $q->name }}: {{ $qStart->format('M d') }} - {{ $qEnd->format('M d') }}">
                                    <small>Q{{ $q->quarter }}</small>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">{{ $syStart->format('M d, Y') }}</small>
                            <small class="text-muted">{{ $syEnd->format('M d, Y') }}</small>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i data-feather="calendar" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                    <h5 class="text-muted">No Quarters Configured</h5>
                    <p class="text-muted mb-4">Set up quarters for this school year to track grading periods.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <form action="{{ route('admin.school-year.quarters.auto-generate', $schoolYear) }}"
                            method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">
                                <i data-feather="zap" class="feather-sm me-1"></i> Auto-Generate Quarters
                            </button>
                        </form>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuarterModal">
                            <i data-feather="plus" class="feather-sm me-1"></i> Add Manually
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Add Quarter Modal --}}
    <div class="modal fade" id="addQuarterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.school-year.quarters.store', $schoolYear) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Quarter</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Quarter</label>
                            <select class="form-select" name="quarter" required>
                                <option value="">Select Quarter</option>
                                @foreach ($quarterNames as $num => $name)
                                    @if (!$quarters->contains('quarter', $num))
                                        <option value="{{ $num }}">{{ $name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date"
                                min="{{ \Carbon\Carbon::parse($schoolYear->start_date)->format('Y-m-d') }}"
                                max="{{ \Carbon\Carbon::parse($schoolYear->end_date)->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date"
                                min="{{ \Carbon\Carbon::parse($schoolYear->start_date)->format('Y-m-d') }}"
                                max="{{ \Carbon\Carbon::parse($schoolYear->end_date)->format('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Submission Deadline <small
                                    class="text-muted">(optional)</small></label>
                            <input type="date" class="form-control" name="grade_submission_deadline">
                            <small class="text-muted">Usually a few days after the quarter ends.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Quarter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();
        });
    </script>
@endpush
