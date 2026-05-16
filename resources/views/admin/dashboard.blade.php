@extends('base')
@section('title', 'Admin - Dashboard')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1 fw-bold text-dark">Welcome back, {{ Auth::user()->first_name }}</h2>
            <p class="text-muted mb-0">Here's what's happening with your school today</p>
        </div>
    </div>

    {{-- School Year Warnings Section --}}
    @php
        $warnings = [];
        $today = \Carbon\Carbon::today();

        // Check if no school year exists
        if ($schoolYears->isEmpty()) {
            $warnings[] = [
                'type' => 'danger',
                'icon' => 'alert-circle',
                'title' => 'No School Year Setup',
                'message' => 'There are no school years configured in the system. Please add a school year to begin.',
                'action' => 'addSchoolYearModal',
                'actionText' => 'Add School Year',
            ];
        } else {
            // Check if no real globally active school year
            if (!$realActiveSchoolYear || !$realActiveSchoolYear->is_active) {
                $warnings[] = [
                    'type' => 'warning',
                    'icon' => 'alert-triangle',
                    'title' => 'No Active School Year',
                    'message' =>
                        'No school year is currently set as active. Please activate a school year to enable full system functionality.',
                    'action' => null,
                    'actionText' => null,
                ];
            } elseif (($viewSchoolYearId ?? null) && $activeSchoolYear && $realActiveSchoolYear && $activeSchoolYear->id !== $realActiveSchoolYear->id) {
                $warnings[] = [
                    'type' => 'info',
                    'icon' => 'eye',
                    'title' => 'Admin View Mode Enabled',
                    'message' =>
                        "You are viewing \"{$activeSchoolYear->name}\" for this session only. The real active school year for all users remains \"{$realActiveSchoolYear->name}\".",
                    'action' => null,
                    'actionText' => null,
                ];
            } elseif ($activeSchoolYear) {
                // Check if active school year is not within current date range
                $isWithinDateRange = $today->between($activeSchoolYear->start_date, $activeSchoolYear->end_date);

                if (!$isWithinDateRange) {
                    if ($today->lt($activeSchoolYear->start_date)) {
                        $daysUntilStart = $today->diffInDays($activeSchoolYear->start_date);
                        $warnings[] = [
                            'type' => 'info',
                            'icon' => 'clock',
                            'title' => 'School Year Not Yet Started',
                            'message' =>
                                "The active school year \"{$activeSchoolYear->name}\" hasn't started yet. It begins on " .
                                $activeSchoolYear->start_date->format('F j, Y') .
                                " ({$daysUntilStart} days from now).",
                            'action' => null,
                            'actionText' => null,
                        ];
                    } else {
                        $daysPastEnd = $today->diffInDays($activeSchoolYear->end_date);
                        $warnings[] = [
                            'type' => 'danger',
                            'icon' => 'alert-octagon',
                            'title' => 'School Year Has Ended',
                            'message' =>
                                "The active school year \"{$activeSchoolYear->name}\" ended on " .
                                $activeSchoolYear->end_date->format('F j, Y') .
                                " ({$daysPastEnd} days ago). Please set up a new school year or update the current one.",
                            'action' => 'addSchoolYearModal',
                            'actionText' => 'Add New School Year',
                        ];
                    }
                } else {
                    // Check if school year is ending soon (within 30 days)
                    $daysUntilEnd = $today->diffInDays($activeSchoolYear->end_date, false);
                    if ($daysUntilEnd >= 0 && $daysUntilEnd <= 30) {
                        $warnings[] = [
                            'type' => 'warning',
                            'icon' => 'calendar',
                            'title' => 'School Year Ending Soon',
                            'message' =>
                                "The current school year \"{$activeSchoolYear->name}\" will end on " .
                                $activeSchoolYear->end_date->format('F j, Y') .
                                ". Only {$daysUntilEnd} days remaining. Consider preparing for the next school year.",
                            'action' => 'addSchoolYearModal',
                            'actionText' => 'Prepare Next School Year',
                        ];
                    }
                }

                // Check if no quarters are set up
                $quartersCount = $activeSchoolYear->quarters()->count();
                if ($quartersCount === 0) {
                    $warnings[] = [
                        'type' => 'warning',
                        'icon' => 'layers',
                        'title' => 'No Quarters Configured',
                        'message' => "The active school year \"{$activeSchoolYear->name}\" has no quarters set up. Quarter configuration is required for grade management.",
                        'action' => 'quartersModal' . $activeSchoolYear->id,
                        'actionText' => 'Set Up Quarters',
                    ];
                } else {
                    // Check current quarter status
                    $currentQuarter = $activeSchoolYear
                        ->quarters()
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today)
                        ->first();

                    if ($currentQuarter) {
                        // Check if quarter is ending soon (within 14 days)
                        $quarterDaysRemaining = $currentQuarter->daysRemaining();
                        if ($quarterDaysRemaining >= 0 && $quarterDaysRemaining <= 14) {
                            $warnings[] = [
                                'type' => 'warning',
                                'icon' => 'clock',
                                'title' => 'Quarter Ending Soon',
                                'message' =>
                                    "{$currentQuarter->name} will end on " .
                                    $currentQuarter->end_date->format('F j, Y') .
                                    ". Only {$quarterDaysRemaining} days remaining. Ensure all grades are submitted before the deadline.",
                                'action' => null,
                                'actionText' => null,
                            ];
                        }

                        // Check if grade submission deadline is approaching
                        if ($currentQuarter->grade_submission_deadline) {
                            $daysUntilDeadline = $currentQuarter->daysUntilDeadline();
                            if ($daysUntilDeadline !== null && $daysUntilDeadline >= 0 && $daysUntilDeadline <= 7) {
                                $warnings[] = [
                                    'type' => 'warning',
                                    'icon' => 'edit-3',
                                    'title' => 'Grade Submission Deadline Approaching',
                                    'message' =>
                                        "The grade submission deadline for {$currentQuarter->name} is on " .
                                        $currentQuarter->grade_submission_deadline->format('F j, Y') .
                                        ". Only {$daysUntilDeadline} days remaining.",
                                    'action' => null,
                                    'actionText' => null,
                                ];
                            } elseif ($daysUntilDeadline !== null && $daysUntilDeadline < 0) {
                                $daysPassed = abs($daysUntilDeadline);
                                $warnings[] = [
                                    'type' => 'danger',
                                    'icon' => 'alert-circle',
                                    'title' => 'Grade Submission Deadline Passed',
                                    'message' =>
                                        "The grade submission deadline for {$currentQuarter->name} was " .
                                        $currentQuarter->grade_submission_deadline->format('F j, Y') .
                                        " ({$daysPassed} days ago).",
                                    'action' => null,
                                    'actionText' => null,
                                ];
                            }
                        }
                    } else {
                        // No current quarter - check if we're between quarters or before/after all quarters
                $nextQuarter = $activeSchoolYear
                    ->quarters()
                    ->where('start_date', '>', $today)
                    ->orderBy('start_date', 'asc')
                    ->first();

                $lastQuarter = $activeSchoolYear
                    ->quarters()
                    ->where('end_date', '<', $today)
                    ->orderBy('end_date', 'desc')
                    ->first();

                if ($nextQuarter && $lastQuarter) {
                    // Between quarters
                    $daysUntilNextQuarter = $today->diffInDays($nextQuarter->start_date);
                    $warnings[] = [
                        'type' => 'info',
                        'icon' => 'pause-circle',
                        'title' => 'Between Quarters',
                        'message' =>
                            "{$lastQuarter->name} has ended. {$nextQuarter->name} will begin on " .
                            $nextQuarter->start_date->format('F j, Y') .
                            " ({$daysUntilNextQuarter} days from now).",
                        'action' => null,
                        'actionText' => null,
                    ];
                } elseif ($lastQuarter && !$nextQuarter) {
                    // All quarters have ended
                    $warnings[] = [
                        'type' => 'warning',
                        'icon' => 'check-square',
                        'title' => 'All Quarters Completed',
                        'message' =>
                            "All quarters for the current school year have ended. The last quarter ({$lastQuarter->name}) ended on " .
                            $lastQuarter->end_date->format('F j, Y') .
                            '.',
                        'action' => null,
                        'actionText' => null,
                    ];
                }
            }

            // Check if quarters are incomplete (less than 4)
            if ($quartersCount > 0 && $quartersCount < 4) {
                $warnings[] = [
                    'type' => 'info',
                    'icon' => 'info',
                    'title' => 'Incomplete Quarter Setup',
                    'message' => "Only {$quartersCount} out of 4 quarters are configured for the current school year. Consider adding the remaining quarters.",
                    'action' => 'quartersModal' . $activeSchoolYear->id,
                    'actionText' => 'Manage Quarters',
                        ];
                    }
                }
            }
        }
    @endphp

    @if (count($warnings) > 0)
        <div class="row mb-4">
            <div class="col-12">
                @foreach ($warnings as $warning)
                    <div class="alert alert-{{ $warning['type'] }} alert-dismissible fade show d-flex align-items-start shadow-sm mb-3"
                        role="alert">
                        <i data-feather="{{ $warning['icon'] }}" class="me-3 flex-shrink-0"
                            style="width: 24px; height: 24px;"></i>
                        <div class="flex-grow-1">
                            <strong>{{ $warning['title'] }}</strong>
                            <p class="mb-0 mt-1">{{ $warning['message'] }}</p>
                        </div>
                        @if ($warning['action'])
                            <button type="button" class="btn btn-sm btn-{{ $warning['type'] }} ms-3 flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#{{ $warning['action'] }}">
                                {{ $warning['actionText'] }}
                            </button>
                        @endif
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">School Year Management</h5>
                    <button class="btn btn-sm btn-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#addSchoolYearModal">
                        <i data-feather="plus" class="feather-sm me-1"></i> Add School Year
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-soft-primary">
                                <div class="card-body">
                                    @if ($activeSchoolYear)
                                        <h6 class="text-primary fw-bold">
                                            {{ ($viewSchoolYearId ?? null) ? 'Viewed School Year' : 'Current School Year' }}
                                        </h6>
                                        <h3 id="currentSchoolYear">{{ $activeSchoolYear->name }}</h3>
                                        <p class="mb-2">
                                            Status:
                                            @if (($viewSchoolYearId ?? null))
                                                <span class="badge bg-warning text-dark">Viewing (Admin Session)</span>
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        </p>
                                        <p class="mb-2">Duration:
                                            {{ \Carbon\Carbon::parse($activeSchoolYear->start_date)->format('F j, Y') }}
                                            -
                                            {{ \Carbon\Carbon::parse($activeSchoolYear->end_date)->format('F j, Y') }}
                                        </p>
                                        @php
                                            $currentQuarter = $activeSchoolYear
                                                ->quarters()
                                                ->where('start_date', '<=', now())
                                                ->where('end_date', '>=', now())
                                                ->first();
                                        @endphp
                                        @if ($currentQuarter)
                                            <p class="mb-0">
                                                <span class="badge bg-primary">{{ $currentQuarter->name }}</span>
                                                @if ($currentQuarter->daysRemaining() <= 14 && $currentQuarter->daysRemaining() >= 0)
                                                    <small
                                                        class="text-warning ms-1">({{ $currentQuarter->daysRemaining() }}
                                                        days left)</small>
                                                @endif
                                            </p>
                                        @elseif ($activeSchoolYear->quarters()->count() === 0)
                                            <p class="mb-0">
                                                <button type="button" class="btn btn-sm btn-warning text-white"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#quartersModal{{ $activeSchoolYear->id }}">
                                                    <i data-feather="alert-circle" class="feather-sm me-1"></i> Set Up
                                                    Quarters
                                                </button>
                                            </p>
                                        @endif
                                    @else
                                        <h6>No Active School Year</h6>
                                    @endif

                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-3">School Year Timeline</h6>
                            <div class="position-relative" id="schoolYearTimeline" style="height: 100px;">
                                <div class="text-center py-2 d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="schoolYearsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>School Year</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Quarters</th>
                                    <th>Status</th>
                                    <th>Promotion</th>
                                    <th width="250">Action</th>
                                </tr>
                            </thead>
                            <tbody id="schoolYearTableBody">
                                @forelse ($schoolYears as $year)
                                    {{-- The loop here ONLY contains the table row <tr> --}}
                                    <tr>
                                        <td>{{ $year->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($year->start_date)->format('F j, Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($year->end_date)->format('F j, Y') }}</td>
                                        <td>
                                            @php
                                                $quartersQuery = $year->quarters();
                                                $quarterCount = $quartersQuery->count();
                                                $currentQuarter = $quartersQuery
                                                    ->where('start_date', '<=', now())
                                                    ->where('end_date', '>=', now())
                                                    ->first();
                                            @endphp
                                            @if ($currentQuarter)
                                                <span class="badge bg-success">{{ $currentQuarter->quarter }}/4</span>
                                            @elseif ($quarterCount === 4)
                                                <span class="badge bg-success">4/4</span>
                                            @elseif ($quarterCount > 0)
                                                <span class="badge bg-warning">{{ $quarterCount }}/4</span>
                                            @else
                                                <span class="badge bg-secondary">Not Set</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($year->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @elseif (($viewSchoolYearId ?? null) === $year->id)
                                                <span class="badge bg-warning text-dark">Viewing (Admin)</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST"
                                                action="{{ route('admin.school-year.toggle-promotion', $year->id) }}"
                                                class="d-inline">
                                                @csrf
@if ($year->is_promotion_open)
<button type="submit" class="btn btn-sm btn-success"
    data-bs-toggle="tooltip"
    title="Promotion enrollment is OPEN. Teachers can now enroll returning students from this year into the next grade level. Click to close.">
    <i data-feather="unlock" class="feather-sm me-1"></i> Open
</button>
@elseif ($year->canOpenPromotion())
<button type="submit" class="btn btn-sm btn-outline-warning"
    data-bs-toggle="tooltip" title="Promotion enrollment is CLOSED. Click to open and allow teachers to promote returning students to the next grade level.&#10;&#10;Only available after school year ends ({{ $year->end_date->format('M d, Y') }}).&#10;&#10;Effects:&#10;- Teachers can see returning students from this year&#10;- Enrollment will advance students to next grade level&#10;- Student profiles auto-marked as 'promoted'&#10;&#10;Click to enable promotion enrollment">
    <i data-feather="lock" class="feather-sm me-1"></i> Closed
</button>
@else
<span class="badge bg-light text-muted" data-bs-toggle="tooltip"
    title="Promotion enrollment unavailable.&#10;&#10;This school year must end ({{ $year->end_date->format('M d, Y') }}) before promotion can be opened.&#10;&#10;Available {{ $year->end_date->diffForHumans() }}">
    <i data-feather="clock" class="feather-sm me-1"></i> Not Yet
</span>
@endif
                                            </form>
                                        </td>
                                        <td>

                                            <a href="#" data-bs-toggle="modal"
                                                data-bs-target="#editSchoolYearModal{{ $year->id }}"
                                                class="btn btn-sm btn-primary">
                                                Edit
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-year-btn" data-bs-toggle="modal"
                                                data-bs-target="#deleteSchoolYearModal"
                                                data-year-id="{{ $year->id }}">Delete</button>
                                            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal"
                                                data-bs-target="#quartersModal{{ $year->id }}"
                                                title="Manage Quarters">
                                                Quarters
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No school years found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-primary rounded p-2">
                            <i data-feather="users" class="text-primary"></i>
                        </div>
                        <span class="badge bg-soft-primary text-primary" data-bs-toggle="tooltip"
                            title="Current school year">
                            <i data-feather="trending-up" class="feather-sm"></i> Active
                        </span>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($enrolledStudents) }}</h3>
                    <p class="text-muted mb-2">Enrolled Students</p>
                    @if (isset($recentEnrolledStudents) && $recentEnrolledStudents > 0)
                        <small class="text-success"><i data-feather="arrow-up" class="feather-sm"></i>
                            +{{ $recentEnrolledStudents }} this year</small>
                    @else
                        <small class="text-muted">Total enrolled students</small>
                    @endif
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100" aria-label="Enrolled students"
                            aria-valuetext="{{ number_format($enrolledStudents) }} students"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-success rounded p-2">
                            <i data-feather="user-check" class="text-success"></i>
                        </div>
                        @if (isset($teacherStudentRatio))
                            <span class="badge bg-soft-success text-success" data-bs-toggle="tooltip"
                                title="Student-Teacher Ratio">
                                1:{{ $teacherStudentRatio }}
                            </span>
                        @endif
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($teacherCount) }}</h3>
                    <p class="text-muted mb-2">Teaching Staff</p>
                    <small class="text-muted">Active teachers</small>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100" aria-label="Teaching staff"
                            aria-valuetext="{{ number_format($teacherCount) }} teachers"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-warning rounded p-2">
                            <i data-feather="book-open" class="text-warning"></i>
                        </div>
                        @if (isset($averageClassSize))
                            <span class="badge bg-soft-warning text-warning" data-bs-toggle="tooltip"
                                title="Average students per class">
                                Avg: {{ $averageClassSize }}
                            </span>
                        @endif
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($sectionCount) }}</h3>
                    <p class="text-muted mb-2">Active Classes</p>
                    <small class="text-muted">Across all grade levels</small>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100" aria-label="Active classes"
                            aria-valuetext="{{ number_format($sectionCount) }} classes"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-info rounded p-2">
                            <i data-feather="check-circle" class="text-info"></i>
                        </div>
                        @if (isset($attendancePercentage))
                            <span class="badge bg-soft-info text-info" data-bs-toggle="tooltip" title="Attendance rate">
                                {{ number_format($attendancePercentage, 1) }}%
                            </span>
                        @endif
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($todaysAttendance) }}</h3>
                    <p class="text-muted mb-2">Present Today</p>
                    @if (isset($absentToday))
                        <small class="text-danger"><i data-feather="user-x" class="feather-sm"></i> {{ $absentToday }}
                            absent</small>
                    @endif
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-info" role="progressbar"
                            style="width: {{ $attendancePercentage ?? 0 }}%"
                            aria-valuenow="{{ $attendancePercentage ?? 0 }}" aria-valuemin="0" aria-valuemax="100"
                            aria-label="Today's attendance rate"
                            aria-valuetext="{{ number_format($attendancePercentage ?? 0, 1) }} percent">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Available Slots Per Grade Level --}}
    @if (isset($slotsPerGrade) && $slotsPerGrade->count() > 0)
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Available Slots Per Grade Level</h5>
                        <span class="badge bg-soft-primary text-primary">
                            Total Available: {{ $slotsPerGrade->sum('available') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($slotsPerGrade as $grade)
                                @php
                                    $fillPercent =
                                        $grade['total_capacity'] > 0
                                            ? round(($grade['enrolled'] / $grade['total_capacity']) * 100)
                                            : 0;
                                    $barColor =
                                        $fillPercent >= 95 ? 'danger' : ($fillPercent >= 80 ? 'warning' : 'success');
                                @endphp
                                <div class="col-md-4 col-lg-2">
                                    <div class="card border h-100">
                                        <div class="card-body text-center py-3">
                                            <h6 class="fw-bold text-muted mb-1">{{ $grade['name'] }}</h6>
                                            <h3 class="fw-bold text-{{ $barColor }} mb-1">{{ $grade['available'] }}
                                            </h3>
                                            <small class="text-muted d-block">slots remaining</small>
                                            <div class="progress mt-2" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $barColor }}" role="progressbar"
                                                    style="width: {{ $fillPercent }}%"
                                                    aria-valuenow="{{ $fillPercent }}" aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted mt-1 d-block">
                                                {{ $grade['enrolled'] }}/{{ $grade['total_capacity'] }} enrolled
                                                ({{ $grade['class_count'] }}
                                                {{ Str::plural('class', $grade['class_count']) }})
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Student Enrollment Trends</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-soft-primary text-primary">Total: <span
                                id="totalEnrollment">{{ number_format($enrolledStudents) }}</span></span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="enrollmentChart" style="height: 300px;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3 flex-wrap">
                        <div class="small text-muted">Enrollment trends across school years</div>
                        <div class="d-flex gap-3 flex-wrap" id="enrollmentLegend">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Active</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-warning rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Transferee</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-success rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Graduated</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-danger rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Dropped</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">Grade-Level Distribution</h5>
                </div>
                <div class="card-body">
                    <div id="classDistributionChart" style="height: 300px;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">Attendance Trend (Last 14 Days)</h5>
                </div>
                <div class="card-body">
                    <div id="attendanceTrendChart" style="height: 250px;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.sections.index') }}"
                            class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-between">
                            <span><i data-feather="users" class="feather-sm me-2"></i>Manage Classes</span>
                            <i data-feather="chevron-right" class="feather-sm"></i>
                        </a>
                        <a href="{{ route('admin.teachers.index') }}"
                            class="btn btn-outline-success btn-sm d-flex align-items-center justify-content-between">
                            <span><i data-feather="user-check" class="feather-sm me-2"></i>Manage Teachers</span>
                            <i data-feather="chevron-right" class="feather-sm"></i>
                        </a>
                        <a href="{{ route('admin.subjects.index') }}"
                            class="btn btn-outline-warning btn-sm d-flex align-items-center justify-content-between">
                            <span><i data-feather="book-open" class="feather-sm me-2"></i>Manage Subjects</span>
                            <i data-feather="chevron-right" class="feather-sm"></i>
                        </a>
                        <a href="{{ route('admin.reports.attendance') }}"
                            class="btn btn-outline-info btn-sm d-flex align-items-center justify-content-between">
                            <span><i data-feather="calendar" class="feather-sm me-2"></i>Attendance Report</span>
                            <i data-feather="chevron-right" class="feather-sm"></i>
                        </a>
                        <a href="{{ route('admin.reports.enrollees') }}"
                            class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-between">
                            <span><i data-feather="file-text" class="feather-sm me-2"></i>Enrollment Report</span>
                            <i data-feather="chevron-right" class="feather-sm"></i>
                        </a>
                        @if (isset($pendingAdmissions) && $pendingAdmissions > 0)
                            <a href="{{ route('admin.sections.index') }}"
                                class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-between">
                                <span><i data-feather="alert-circle" class="feather-sm me-2"></i>Pending
                                    Enrollments</span>
                                <span class="badge bg-danger">{{ $pendingAdmissions }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Recent Activities</h5>
                    <div class="d-flex align-items-center">
                        <label for="activityTypeFilter" class="visually-hidden">Filter activities by type</label>
                        <select class="form-select form-select-sm" id="activityTypeFilter" style="width: auto;"
                            aria-label="Filter activities by type" title="Filter activities by type">
                            <option value="all">All Activities</option>
                            <option value="Enrollment">Enrollments</option>
                            <option value="Assessment">Assessments</option>
                            <option value="Absence">Absences</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="activitiesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentActivities ?? [] as $activity)
                                    <tr class="activity-row" data-type="{{ $activity['type'] ?? 'Other' }}">
                                        <td>
                                            @php
                                                $type = $activity['type'] ?? 'Other';
                                                $badgeClass = match ($type) {
                                                    'Enrollment' => 'bg-primary',
                                                    'Assessment' => 'bg-info',
                                                    'Absence' => 'bg-warning text-dark',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                                        </td>
                                        <td>{{ $activity['description'] ?? 'No description' }}</td>
                                        <td>
                                            <small class="text-muted">
                                                {{ isset($activity['created_at']) ? $activity['created_at']->diffForHumans() : 'Recently' }}
                                            </small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No recent activities found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="addSchoolYearModal" tabindex="-1" aria-labelledby="addSchoolYearModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSchoolYearModalLabel">Add School Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="schoolYearForm" method="POST" action="{{ route('admin.school-year.store') }}">
                        @csrf
                        {{-- Hidden inputs for actual date values --}}
                        <input type="hidden" id="start_date" name="start_date" value="{{ old('start_date') }}">
                        <input type="hidden" id="end_date" name="end_date" value="{{ old('end_date') }}">

                        {{-- School Year Selection --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">School Year</label>
                            <div class="d-flex align-items-center gap-2">
                                @php
                                    $currentYear = (int) date('Y');
                                    $yearRangeStart = $currentYear - 5;
                                    $yearRangeEnd = $currentYear + 5;
                                @endphp
                                <select class="form-select" id="addStartYear" style="width: auto;">
                                    @for ($y = $yearRangeStart; $y <= $yearRangeEnd; $y++)
                                        <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endfor
                                </select>
                                <span class="text-muted">—</span>
                                <select class="form-select" id="addEndYear" style="width: auto;" disabled>
                                    @for ($y = $yearRangeStart + 1; $y <= $yearRangeEnd + 1; $y++)
                                        <option value="{{ $y }}"
                                            {{ $y == $currentYear + 1 ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-primary fs-6" id="addSchoolYearPreview">S.Y.
                                    {{ $currentYear }}-{{ $currentYear + 1 }}</span>
                            </div>
                            @error('start_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @error('end_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- School Year Period --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">School Year Period</label>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="addEnableCustomDates"
                                        role="switch">
                                    <label class="form-check-label small text-muted" for="addEnableCustomDates">Customize
                                        dates</label>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="addStartMonth" class="form-label small text-muted">Start Month</label>
                                    <select class="form-select" id="addStartMonth" disabled>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6" selected>June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                    <small class="text-muted">Defaults to June 1</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="addEndMonth" class="form-label small text-muted">End Month</label>
                                    <select class="form-select" id="addEndMonth" disabled>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3" selected>March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                    <small class="text-muted">Defaults to March 31</small>
                                </div>
                            </div>
                            <div class="alert alert-light border mt-3 py-2 px-3">
                                <small>
                                    <i data-feather="calendar" class="feather-sm me-1"></i>
                                    <strong>Period:</strong> <span id="addDateRangePreview">June 1, {{ $currentYear }} —
                                        March 31, {{ $currentYear + 1 }}</span>
                                </small>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isCurrentYear" name="is_active"
                                value="1" {{ old('is_active') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isCurrentYear">
                                Set as current school year
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="schoolYearForm" class="btn btn-primary"
                        id="saveSchoolYear">Save</button>
                </div>
            </div>
        </div>
    </div>

    @foreach ($schoolYears as $year)
        @php
            $yearStartDate = \Carbon\Carbon::parse($year->start_date);
            $yearEndDate = \Carbon\Carbon::parse($year->end_date);
            $editStartYear = $yearStartDate->year;
            $editEndYear = $yearEndDate->year;
            $editStartMonth = $yearStartDate->month;
            $editEndMonth = $yearEndDate->month;
            $currentYear = (int) date('Y');
            $yearRangeStart = $currentYear - 5;
            $yearRangeEnd = $currentYear + 5;
        @endphp
        <div class="modal fade text-left" id="editSchoolYearModal{{ $year->id }}" tabindex="-1" role="dialog"
            aria-labelledby="editSchoolYearModalLabel{{ $year->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editSchoolYearModalLabel{{ $year->id }}">Edit School Year</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.school-year.update', $year->id) }}"
                        class="edit-school-year-form">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            {{-- Hidden inputs for actual date values --}}
                            <input type="hidden" class="edit-start-date" name="start_date"
                                value="{{ $yearStartDate->format('Y-m-d') }}">
                            <input type="hidden" class="edit-end-date" name="end_date"
                                value="{{ $yearEndDate->format('Y-m-d') }}">

                            {{-- School Year Selection --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold">School Year</label>
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select edit-start-year" style="width: auto;"
                                        data-year-id="{{ $year->id }}">
                                        @for ($y = $yearRangeStart; $y <= $yearRangeEnd; $y++)
                                            <option value="{{ $y }}"
                                                {{ $y == $editStartYear ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                    <span class="text-muted">—</span>
                                    <select class="form-select edit-end-year" style="width: auto;" disabled
                                        data-year-id="{{ $year->id }}">
                                        @for ($y = $yearRangeStart + 1; $y <= $yearRangeEnd + 1; $y++)
                                            <option value="{{ $y }}"
                                                {{ $y == $editEndYear ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-primary fs-6 edit-school-year-preview">S.Y.
                                        {{ $editStartYear }}-{{ $editEndYear }}</span>
                                </div>
                            </div>

                            {{-- School Year Period --}}
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0">School Year Period</label>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input edit-enable-custom-dates" type="checkbox"
                                            id="editEnableCustomDates{{ $year->id }}" role="switch"
                                            data-year-id="{{ $year->id }}">
                                        <label class="form-check-label small text-muted"
                                            for="editEnableCustomDates{{ $year->id }}">Customize dates</label>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">Start Month</label>
                                        <select class="form-select edit-start-month" disabled
                                            data-year-id="{{ $year->id }}">
                                            <option value="1" {{ $editStartMonth == 1 ? 'selected' : '' }}>January
                                            </option>
                                            <option value="2" {{ $editStartMonth == 2 ? 'selected' : '' }}>February
                                            </option>
                                            <option value="3" {{ $editStartMonth == 3 ? 'selected' : '' }}>March
                                            </option>
                                            <option value="4" {{ $editStartMonth == 4 ? 'selected' : '' }}>April
                                            </option>
                                            <option value="5" {{ $editStartMonth == 5 ? 'selected' : '' }}>May
                                            </option>
                                            <option value="6" {{ $editStartMonth == 6 ? 'selected' : '' }}>June
                                            </option>
                                            <option value="7" {{ $editStartMonth == 7 ? 'selected' : '' }}>July
                                            </option>
                                            <option value="8" {{ $editStartMonth == 8 ? 'selected' : '' }}>August
                                            </option>
                                            <option value="9" {{ $editStartMonth == 9 ? 'selected' : '' }}>September
                                            </option>
                                            <option value="10" {{ $editStartMonth == 10 ? 'selected' : '' }}>October
                                            </option>
                                            <option value="11" {{ $editStartMonth == 11 ? 'selected' : '' }}>November
                                            </option>
                                            <option value="12" {{ $editStartMonth == 12 ? 'selected' : '' }}>December
                                            </option>
                                        </select>
                                        <small class="text-muted">Defaults to June 1</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">End Month</label>
                                        <select class="form-select edit-end-month" disabled
                                            data-year-id="{{ $year->id }}">
                                            <option value="1" {{ $editEndMonth == 1 ? 'selected' : '' }}>January
                                            </option>
                                            <option value="2" {{ $editEndMonth == 2 ? 'selected' : '' }}>February
                                            </option>
                                            <option value="3" {{ $editEndMonth == 3 ? 'selected' : '' }}>March
                                            </option>
                                            <option value="4" {{ $editEndMonth == 4 ? 'selected' : '' }}>April
                                            </option>
                                            <option value="5" {{ $editEndMonth == 5 ? 'selected' : '' }}>May
                                            </option>
                                            <option value="6" {{ $editEndMonth == 6 ? 'selected' : '' }}>June
                                            </option>
                                            <option value="7" {{ $editEndMonth == 7 ? 'selected' : '' }}>July
                                            </option>
                                            <option value="8" {{ $editEndMonth == 8 ? 'selected' : '' }}>August
                                            </option>
                                            <option value="9" {{ $editEndMonth == 9 ? 'selected' : '' }}>September
                                            </option>
                                            <option value="10" {{ $editEndMonth == 10 ? 'selected' : '' }}>October
                                            </option>
                                            <option value="11" {{ $editEndMonth == 11 ? 'selected' : '' }}>November
                                            </option>
                                            <option value="12" {{ $editEndMonth == 12 ? 'selected' : '' }}>December
                                            </option>
                                        </select>
                                        <small class="text-muted">Defaults to March 31</small>
                                    </div>
                                </div>
                                <div class="alert alert-light border mt-3 py-2 px-3">
                                    <small>
                                        <i data-feather="calendar" class="feather-sm me-1"></i>
                                        <strong>Period:</strong> <span
                                            class="edit-date-range-preview">{{ $yearStartDate->format('F j, Y') }} —
                                            {{ $yearEndDate->format('F j, Y') }}</span>
                                    </small>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input edit-set-active-checkbox" type="checkbox"
                                    id="editIsCurrentYear{{ $year->id }}" name="is_active"
                                    data-year-id="{{ $year->id }}" data-year-name="{{ $year->name }}"
                                    {{ $year->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="editIsCurrentYear{{ $year->id }}">
                                    Set as current school year
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Quarters Modal for this school year --}}
        <div class="modal fade" id="quartersModal{{ $year->id }}" tabindex="-1"
            aria-labelledby="quartersModalLabel{{ $year->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="quartersModalLabel{{ $year->id }}">Manage Quarters</h5>
                            <small class="text-muted">{{ $year->name }}
                                ({{ \Carbon\Carbon::parse($year->start_date)->format('M d, Y') }} -
                                {{ \Carbon\Carbon::parse($year->end_date)->format('M d, Y') }})</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $quarters = $year->quarters()->orderBy('quarter')->get();
                            $quarterNames = \App\Models\SchoolYearQuarter::QUARTER_NAMES;
                        @endphp

                        {{-- Current Quarter Info --}}
                        @php $activeQuarter = $quarters->first(fn($q) => $q->isCurrent()); @endphp
                        @if ($activeQuarter)
                            <div
                                class="alert {{ $activeQuarter->is_manually_set_active ? 'alert-warning' : 'alert-info' }} py-2 d-flex align-items-center mb-3">
                                <i data-feather="{{ $activeQuarter->is_manually_set_active ? 'alert-triangle' : 'info' }}"
                                    class="feather-sm me-2"></i>
                                <small>
                                    <strong>Current:</strong> {{ $activeQuarter->name }}
                                    @if ($activeQuarter->is_manually_set_active)
                                        <span class="badge bg-warning text-dark ms-1">Manual Override</span>
                                    @endif
                                    @if ($activeQuarter->daysRemaining() >= 0)
                                        ({{ $activeQuarter->daysRemaining() }} days left)
                                    @endif
                                </small>
                            </div>
                        @endif

                        {{-- Quarter Cards --}}
                        @if ($quarters->count() > 0)
                            <div class="row g-3">
                                @foreach ($quarters as $quarter)
                                    <div class="col-md-6">
                                        <div
                                            class="card h-100 {{ $quarter->isCurrent() ? 'border-primary border-2' : '' }}">
                                            <div
                                                class="card-header py-2 d-flex justify-content-between align-items-center {{ $quarter->isCurrent() ? ($quarter->is_manually_set_active ? 'bg-primary text-white' : 'bg-success text-white') : 'bg-light' }}">
                                                <strong>{{ $quarter->name }}</strong>
                                                <span
                                                    class="badge {{ $quarter->status_badge_class }}">{{ $quarter->status }}</span>
                                            </div>
                                            <div class="card-body py-2">
                                                <div class="row small">
                                                    <div class="col-6">
                                                        <span class="text-muted">Start:</span><br>
                                                        {{ \Carbon\Carbon::parse($quarter->start_date)->format('M d, Y') }}
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted">End:</span><br>
                                                        {{ \Carbon\Carbon::parse($quarter->end_date)->format('M d, Y') }}
                                                    </div>
                                                </div>
                                                @if ($quarter->grade_submission_deadline)
                                                    <div class="small mt-2">
                                                        <span class="text-muted">Grade Deadline:</span>
                                                        <span
                                                            class="{{ $quarter->isSubmissionDeadlinePassed() ? 'text-danger' : '' }}">
                                                            {{ \Carbon\Carbon::parse($quarter->grade_submission_deadline)->format('M d, Y') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="card-footer bg-transparent py-2 d-flex gap-1">
                                                @if ($quarter->is_manually_set_active)
                                                    <form
                                                        action="{{ route('admin.school-year.quarters.unset-active', [$year, $quarter]) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('Remove manual override for {{ $quarter->name }}? The system will revert to date-based quarter detection.')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary"
                                                            title="Remove Manual Override (revert to date-based)">
                                                            <i data-feather="rotate-ccw" class="feather-sm"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form
                                                        action="{{ route('admin.school-year.quarters.set-active', [$year, $quarter]) }}"
                                                        method="POST" class="d-inline"
                                                        onsubmit="return confirm('WARNING: Setting {{ $quarter->name }} as the active quarter will:\n\n• Override automatic date-based detection\n• Work only when {{ $year->name }} is the current active school year\n• Affect grades, attendance, and enrollment system-wide\n\nAre you sure you want to proceed?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                                            title="Set Active Quarter">
                                                            <i data-feather="check-circle" class="feather-sm"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary flex-grow-1 edit-quarter-btn"
                                                    data-quarter-id="{{ $quarter->id }}"
                                                    data-school-year-id="{{ $year->id }}"
                                                    data-quarter-name="{{ $quarter->name }}"
                                                    data-start-date="{{ \Carbon\Carbon::parse($quarter->start_date)->format('Y-m-d') }}"
                                                    data-end-date="{{ \Carbon\Carbon::parse($quarter->end_date)->format('Y-m-d') }}"
                                                    data-deadline="{{ $quarter->grade_submission_deadline ? \Carbon\Carbon::parse($quarter->grade_submission_deadline)->format('Y-m-d') : '' }}"
                                                    data-is-locked="{{ $quarter->is_locked ? '1' : '0' }}"
                                                    data-school-year-active="{{ $year->is_active ? '1' : '0' }}">
                                                    <i data-feather="edit-2" class="feather-sm"></i>
                                                </button>
                                                @php
                                                    $quarterLockContext = $quarterLockContexts[$year->id] ?? null;
                                                    $quarterLockInfo = $quarterLockContext['quarterLocks'][(int) $quarter->quarter] ?? null;
                                                    $isExplicitlyLocked = $quarterLockInfo['is_explicitly_locked'] ?? ($quarter->is_locked === true);
                                                    $isExplicitlyUnlocked = $quarterLockInfo['is_explicitly_unlocked'] ?? ($quarter->is_locked === false);
                                                    $isEffectivelyLocked = $quarterLockInfo['is_locked'] ?? ($quarter->is_locked === true || ($quarter->hasEnded() && $quarter->is_locked !== false));
                                                    $lockReasonLabel = $quarterLockInfo['lock_reason_label'] ?? null;

                                                    if ($isExplicitlyLocked) {
                                                        $toggleTooltip = 'Click to unlock (Locked by Admin)';
                                                    } elseif ($isExplicitlyUnlocked) {
                                                        $toggleTooltip = 'Click to lock (Explicitly unlocked, overrides auto-lock)';
                                                    } elseif ($isEffectivelyLocked) {
                                                        $toggleTooltip = 'Click to unlock (' . ($lockReasonLabel ?? 'Quarter Ended') . ')';
                                                    } else {
                                                        $toggleTooltip = 'Click to lock';
                                                    }
                                                @endphp
                                                @if ($year->is_active)
                                                    <form
                                                        action="{{ route('admin.school-year.quarters.toggle-lock', [$year, $quarter]) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                            class="btn btn-sm {{ $isExplicitlyLocked ? 'btn-warning' : ($isExplicitlyUnlocked ? 'btn-outline-success' : ($isEffectivelyLocked ? 'btn-secondary' : 'btn-outline-secondary')) }}"
                                                            title="{{ $toggleTooltip }}">
                                                            <i data-feather="{{ $isEffectivelyLocked ? 'lock' : 'unlock' }}"
                                                                class="feather-sm"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                        title="Locking is only available for the active school year.">
                                                        <i data-feather="{{ $isEffectivelyLocked ? 'lock' : 'unlock' }}" class="feather-sm"></i>
                                                    </button>
                                                @endif
                                                <form
                                                    action="{{ route('admin.school-year.quarters.destroy', [$year, $quarter]) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Delete this quarter?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i data-feather="trash-2" class="feather-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i data-feather="calendar" style="width: 40px; height: 40px;"
                                    class="text-muted mb-2"></i>
                                <p class="text-muted mb-0">No quarters configured yet.</p>
                            </div>
                        @endif

                        {{-- Add Quarter Form (collapsible) --}}
                        @if ($quarters->count() < 4)
                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="small">Add Quarter</strong>
                                @if ($quarters->count() === 0)
                                    <form action="{{ route('admin.school-year.quarters.auto-generate', $year) }}"
                                        method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            onclick="return confirm('Auto-generate all 4 quarters with equal duration?')">
                                            <i data-feather="zap" class="feather-sm me-1"></i> Auto-Generate
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <form action="{{ route('admin.school-year.quarters.store', $year) }}" method="POST">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <select class="form-select form-select-sm" name="quarter" required>
                                            <option value="">Quarter</option>
                                            @foreach ($quarterNames as $num => $name)
                                                @if (!$quarters->contains('quarter', $num))
                                                    <option value="{{ $num }}">Q{{ $num }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-sm">
                                            <input type="date" class="form-control form-control-sm" name="start_date"
                                                id="quarterStartDate{{ $year->id }}"
                                                min="{{ \Carbon\Carbon::parse($year->start_date)->format('Y-m-d') }}"
                                                max="{{ \Carbon\Carbon::parse($year->end_date)->format('Y-m-d') }}"
                                                placeholder="Start" required>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="document.getElementById('quarterStartDate{{ $year->id }}').value = new Date().toISOString().split('T')[0]"
                                                title="Set to today">Today</button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-sm">
                                            <input type="date" class="form-control form-control-sm" name="end_date"
                                                id="quarterEndDate{{ $year->id }}"
                                                min="{{ \Carbon\Carbon::parse($year->start_date)->format('Y-m-d') }}"
                                                max="{{ \Carbon\Carbon::parse($year->end_date)->format('Y-m-d') }}"
                                                placeholder="End" required>
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                onclick="document.getElementById('quarterEndDate{{ $year->id }}').value = new Date().toISOString().split('T')[0]"
                                                title="Set to today">Today</button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                            <i data-feather="plus" class="feather-sm"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Edit Quarter Modal (shared) --}}
    <div class="modal fade" id="editQuarterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editQuarterForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit <span id="editQuarterName">Quarter</span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="start_date" id="editQuarterStartDate"
                                    required>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="document.getElementById('editQuarterStartDate').value = new Date().toISOString().split('T')[0]"
                                    title="Set to today">Today</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="end_date" id="editQuarterEndDate"
                                    required>
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="document.getElementById('editQuarterEndDate').value = new Date().toISOString().split('T')[0]"
                                    title="Set to today">Today</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Submission Deadline <small
                                    class="text-muted">(optional)</small></label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="grade_submission_deadline"
                                    id="editQuarterDeadline">
                                <button type="button" class="btn btn-outline-secondary"
                                    onclick="document.getElementById('editQuarterDeadline').value = new Date().toISOString().split('T')[0]"
                                    title="Set to today">Today</button>
                                <button class="btn btn-outline-secondary" type="button" id="editQuarterClearDeadline"
                                    title="Clear deadline">Clear</button>
                            </div>
                            <small class="text-muted">Teachers must submit grades before this date.</small>
                        </div>
                        <div id="editQuarterLockControls" class="form-check">
                            <input type="hidden" name="is_locked" id="editQuarterLockedHidden" value="0">
                            <input class="form-check-input" type="checkbox" name="is_locked" id="editQuarterLocked" value="1">
                            <label class="form-check-label" for="editQuarterLocked">
                                Lock quarter (prevents grade/attendance modifications)
                            </label>
                        </div>
                        <small id="editQuarterLockHint" class="text-muted d-none">Locking is only available for the active school year.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="schoolYearModeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">School Year Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        You selected <strong id="schoolYearModeName">this school year</strong>.
                    </p>
                    <div class="alert alert-warning mb-3">
                        Setting a school year as active affects all users (admin, teachers, and guardians).
                    </div>
                    <p class="mb-3">
                        Use <strong>View</strong> if you only want to browse this school year in your admin session
                        without changing the global active school year.
                    </p>
                    <div class="border rounded p-3 bg-light">
                        <label for="setActiveConfirmationInput" class="form-label mb-1">
                            Type <strong>CONFIRM</strong> to enable Set Active:
                        </label>
                        <input type="text" class="form-control" id="setActiveConfirmationInput"
                            placeholder="Type CONFIRM">
                        <small class="text-muted">Exact value required: CONFIRM</small>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <form id="viewSchoolYearForm" method="POST" action="" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary">
                            View Only (Admin Session)
                        </button>
                    </form>
                    <form id="setActiveSchoolYearForm" method="POST" action="" class="m-0">
                        @csrf
                        <input type="hidden" name="confirmation" id="setActiveConfirmationHidden" value="">
                        <button type="submit" class="btn btn-danger" id="confirmSetActiveBtn" disabled>
                            Set Active For All Users
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteSchoolYearModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this school year? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteSchoolYearForm" method="POST" action="" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .hover-lift {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .bg-soft-primary {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-soft-success {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-soft-warning {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-soft-info {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .feather-sm {
            width: 16px;
            height: 16px;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        document.addEventListener('DOMContentLoaded', function() {
            // Check if Chart.js is loaded
            if (typeof window.Chart === 'undefined') {
                console.error('Chart.js is not loaded. Make sure "npm run dev" is running and app.js is loaded.');
                return;
            }
            const Chart = window.Chart;
            const bootstrap = window.bootstrap;
            const schoolYearModeModalEl = document.getElementById('schoolYearModeModal');
            const schoolYearModeName = document.getElementById('schoolYearModeName');
            const viewSchoolYearForm = document.getElementById('viewSchoolYearForm');
            const setActiveSchoolYearForm = document.getElementById('setActiveSchoolYearForm');
            const setActiveConfirmationInput = document.getElementById('setActiveConfirmationInput');
            const setActiveConfirmationHidden = document.getElementById('setActiveConfirmationHidden');
            const confirmSetActiveBtn = document.getElementById('confirmSetActiveBtn');

            const prepareSchoolYearModeModal = (yearId, yearName) => {
                if (!schoolYearModeName || !viewSchoolYearForm || !setActiveSchoolYearForm) {
                    return;
                }

                schoolYearModeName.textContent = yearName;
                viewSchoolYearForm.action = `/admin/school-year/${yearId}/view`;
                setActiveSchoolYearForm.action = `/admin/school-year/${yearId}/set-active`;

                if (setActiveConfirmationInput && confirmSetActiveBtn && setActiveConfirmationHidden) {
                    setActiveConfirmationInput.value = '';
                    setActiveConfirmationHidden.value = '';
                    confirmSetActiveBtn.disabled = true;
                }
            };

            const openSchoolYearModeModal = (yearId, yearName) => {
                if (!schoolYearModeModalEl || !yearId) {
                    return;
                }

                prepareSchoolYearModeModal(yearId, yearName);
                const modeModal = new bootstrap.Modal(schoolYearModeModalEl);
                modeModal.show();
            };

            // Auto-open Add School Year modal if there are validation errors for the add form
            @if ($errors->any() && old('start_date') && !old('_method'))
                const addModal = new bootstrap.Modal(document.getElementById('addSchoolYearModal'));
                addModal.show();
            @endif

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            // Initialize DataTables for tables (uses local assets referenced in base.blade.php)
            if (typeof jQuery !== 'undefined' && typeof jQuery().DataTable === 'function') {
                try {
                    // Only initialize DataTables if table has valid data rows (not just empty message)
                    const schoolYearsTable = $('#schoolYearsTable');
                    const hasValidRows = schoolYearsTable.find('tbody tr td').length > 1 ||
                        (schoolYearsTable.find('tbody tr td').length === 1 &&
                            !schoolYearsTable.find('tbody tr td[colspan]').length);

                    if (hasValidRows && schoolYearsTable.find('tbody tr').length > 0 &&
                        !schoolYearsTable.find('tbody tr td[colspan="6"]').length) {
                        schoolYearsTable.DataTable({
                            responsive: true,
                            lengthChange: true,
                            pageLength: 10,
                            columnDefs: [{
                                orderable: false,
                                targets: -1
                            }],
                            language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search records"
                            }
                        });
                    }

                    // Only initialize DataTables if table has valid data rows (not just empty message)
                    const activitiesTable = $('#activitiesTable');
                    if (activitiesTable.find('tbody tr').length > 0 &&
                        !activitiesTable.find('tbody tr td[colspan]').length) {
                        activitiesTable.DataTable({
                            responsive: true,
                            lengthChange: false,
                            pageLength: 5,
                            ordering: true,
                            columnDefs: [{
                                orderable: false,
                                targets: 2
                            }],
                            language: {
                                search: "_INPUT_",
                                searchPlaceholder: "Search activities"
                            }
                        });
                    }
                } catch (e) {
                    console.warn('DataTables initialization failed:', e);
                }
            } else {
                console.warn('DataTables not loaded. Skipping table enhancements.');
            }

            // Activity type filter functionality
            const activityFilter = document.getElementById('activityTypeFilter');
            if (activityFilter) {
                activityFilter.addEventListener('change', function() {
                    const filterValue = this.value;
                    const activityRows = document.querySelectorAll('.activity-row');
                    let visibleCount = 0;

                    activityRows.forEach(function(row) {
                        const rowType = row.getAttribute('data-type') || 'Other';
                        if (filterValue === 'all' || rowType === filterValue) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Show message if no activities match filter
                    const tbody = document.querySelector('#activitiesTable tbody');
                    let noResultsRow = tbody.querySelector('.no-filter-results');

                    if (visibleCount === 0 && activityRows.length > 0) {
                        if (!noResultsRow) {
                            noResultsRow = document.createElement('tr');
                            noResultsRow.className = 'no-filter-results';
                            noResultsRow.innerHTML =
                                '<td colspan="3" class="text-center text-muted">No activities found for this filter.</td>';
                            tbody.appendChild(noResultsRow);
                        }
                    } else if (noResultsRow) {
                        noResultsRow.remove();
                    }
                });
            }

            // Prepare school years + quarters data for the timeline (PHP -> JSON)
            @php
                $jsSchoolYears = collect($schoolYears)
                    ->map(function ($year) use ($viewSchoolYearId) {
                        return [
                            'id' => $year->id,
                            'name' => $year->name,
                            'startDate' => \Carbon\Carbon::parse($year->start_date)->format('Y-m-d'),
                            'endDate' => \Carbon\Carbon::parse($year->end_date)->format('Y-m-d'),
                            'isCurrent' => $viewSchoolYearId ? (int) $viewSchoolYearId === (int) $year->id : (bool) $year->is_active,
                            'quarters' => $year
                                ->quarters()
                                ->orderBy('quarter')
                                ->get()
                                ->map(function ($q) {
                                    return [
                                        'quarter' => $q->quarter,
                                        'name' => $q->name,
                                        'startDate' => \Carbon\Carbon::parse($q->start_date)->format('Y-m-d'),
                                        'endDate' => \Carbon\Carbon::parse($q->end_date)->format('Y-m-d'),
                                        'isLocked' => $q->is_locked === true || ($q->hasEnded() && $q->is_locked !== false),
                                    ];
                                })
                                ->toArray(),
                        ];
                    })
                    ->toArray();
            @endphp

            const schoolYears = {!! json_encode($jsSchoolYears) !!};

            // This is the new function that creates the visual timeline.
            function initSchoolYearTimeline() {
                const timelineContainer = document.getElementById('schoolYearTimeline');
                if (!timelineContainer) return;

                // Sort years chronologically for the timeline display
                const sortedYears = [...schoolYears].sort((a, b) => new Date(a.startDate) - new Date(b
                    .startDate));

                // Use actual current date
                const currentDate = new Date();

                let timelineHTML =
                    '<div class="d-flex w-100 align-items-center position-relative" style="height: 60px;">';

                sortedYears.forEach((year, index) => {
                    const startDate = new Date(year.startDate);
                    const endDate = new Date(year.endDate);

                    let barStyle = 'background: #6c757d;'; // Default gray for past years
                    let progressIndicatorHTML = '';
                    let quartersHTML = '';

                    // Style the current year and calculate progress
                    const totalDuration = endDate.getTime() - startDate.getTime();
                    if (year.isCurrent) {
                        const elapsedDuration = currentDate.getTime() - startDate.getTime();
                        const progressPercentage = Math.max(0, Math.min(100, (elapsedDuration /
                            totalDuration) * 100));

                        barStyle =
                            `background: linear-gradient(to right, #0d6efd ${progressPercentage}%, #a9cfff ${progressPercentage}%);`;

                        // Add the glowing marker for the current date
                        progressIndicatorHTML = `
                        <div style="position: absolute; left: ${progressPercentage}%; top: 50%; transform: translate(-50%, -50%);" title="Today: ${currentDate.toLocaleDateString()}">
                            <div style="width: 18px; height: 18px; background-color: white; border: 4px solid #e63946; border-radius: 50%; box-shadow: 0 0 12px rgba(230, 57, 70, 0.8);"></div>
                        </div>
                    `;
                    } else if (startDate > currentDate) {
                        barStyle = 'background: #a9cfff;'; // Light blue for future years
                    }

                    // Render quarter segments if available
                    if (Array.isArray(year.quarters) && year.quarters.length > 0) {
                        year.quarters.forEach(q => {
                            const qStart = new Date(q.startDate);
                            const qEnd = new Date(q.endDate);
                            const leftPct = ((qStart.getTime() - startDate.getTime()) /
                                totalDuration) * 100;
                            const widthPct = ((qEnd.getTime() - qStart.getTime()) / totalDuration) *
                                100;
                            const isCurrentQuarter = (currentDate >= qStart && currentDate <= qEnd);

                            // Quarter segment styling
                            const segmentBg = isCurrentQuarter ?
                                'linear-gradient(90deg, rgba(13,110,253,0.25), rgba(13,110,253,0.15))' :
                                'rgba(255,255,255,0.06)';

                            quartersHTML += `
                                <div class="quarter-segment position-absolute" style="left: ${leftPct}%; width: ${widthPct}%; top: 0; height: 100%; border-radius: 6px; background: ${segmentBg};"></div>
                                <span class="event-dot" style="left: ${leftPct + widthPct / 2}%; top: -12px;" title="${q.name}"></span>
                            `;
                        });
                    }

                    // Build the HTML for this year segment
                    timelineHTML += `
                    <div class="flex-grow-1 position-relative px-2">
                        <div class="timeline-bar position-relative" style="height: 12px; border-radius: 6px; ${barStyle}">
                            ${progressIndicatorHTML}
                            ${quartersHTML}
                        </div>
                        <div class="text-center text-muted small mt-2">${year.name}</div>
                    </div>
                `;

                    // Add a connector line between years
                    if (index < sortedYears.length - 1) {
                        timelineHTML +=
                            `<div style="width: 30px; height: 2px; background-color: #dee2e6;"></div>`;
                    }
                });

                timelineHTML += '</div>';

                // Add styling for the event dots
                timelineHTML += `
                <style>
                    .event-dot {
                        position: absolute;
                        width: 9px;
                        height: 9px;
                        background-color: #ffc107;
                        border-radius: 50%;
                        border: 1px solid white;
                        cursor: pointer;
                        transition: transform 0.2s;
                    }
                    .event-dot:hover {
                        transform: scale(1.5);
                    }
                </style>
            `;

                // Replace the loading spinner with the generated timeline
                timelineContainer.innerHTML = timelineHTML;
            }

            // Handle Delete Button Click
            function initSchoolYearManagement() {
                const schoolYearTableBody = document.getElementById('schoolYearTableBody');
                if (!schoolYearTableBody) return;

                if (setActiveConfirmationInput && confirmSetActiveBtn && setActiveConfirmationHidden) {
                    setActiveConfirmationInput.addEventListener('input', function() {
                        const typedValue = this.value.trim();
                        setActiveConfirmationHidden.value = typedValue;
                        confirmSetActiveBtn.disabled = typedValue !== 'CONFIRM';
                    });
                }

                schoolYearTableBody.addEventListener('click', function(event) {
                    const deleteButton = event.target.closest('.delete-year-btn');
                    if (deleteButton) {
                        const button = deleteButton;
                        const yearId = button.dataset.yearId;
                        const deleteForm = document.getElementById('deleteSchoolYearForm');
                        deleteForm.action = `/admin/school-year/${yearId}`;
                    }
                });
            }

            // School Year Dropdown Automation
            function initSchoolYearDropdowns() {
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                // Helper function to get last day of month
                function getLastDayOfMonth(year, month) {
                    return new Date(year, month, 0).getDate();
                }

                // Helper function to format date for display
                function formatDateDisplay(year, month) {
                    const lastDay = getLastDayOfMonth(year, month);
                    const day = month === 6 ? 1 : lastDay; // June starts on 1st, others end on last day
                    return `${monthNames[month - 1]} ${day}, ${year}`;
                }

                // Helper function to format date for hidden input (YYYY-MM-DD)
                function formatDateValue(year, month, isStart = true) {
                    const day = isStart ? '01' : String(getLastDayOfMonth(year, month)).padStart(2, '0');
                    return `${year}-${String(month).padStart(2, '0')}-${day}`;
                }

                // ===== ADD SCHOOL YEAR MODAL =====
                const addStartYear = document.getElementById('addStartYear');
                const addEndYear = document.getElementById('addEndYear');
                const addStartMonth = document.getElementById('addStartMonth');
                const addEndMonth = document.getElementById('addEndMonth');
                const addEnableCustomDates = document.getElementById('addEnableCustomDates');
                const addSchoolYearPreview = document.getElementById('addSchoolYearPreview');
                const addDateRangePreview = document.getElementById('addDateRangePreview');
                const addStartDateInput = document.getElementById('start_date');
                const addEndDateInput = document.getElementById('end_date');

                function updateAddModalDates() {
                    if (!addStartYear || !addEndYear) return;

                    const startYear = parseInt(addStartYear.value);
                    const endYear = parseInt(addEndYear.value);
                    const startMonth = parseInt(addStartMonth.value);
                    const endMonth = parseInt(addEndMonth.value);

                    // Update preview
                    addSchoolYearPreview.textContent = `S.Y. ${startYear}-${endYear}`;

                    // Update date range preview
                    const startDisplay = `${monthNames[startMonth - 1]} 1, ${startYear}`;
                    const endDisplay =
                        `${monthNames[endMonth - 1]} ${getLastDayOfMonth(endYear, endMonth)}, ${endYear}`;
                    addDateRangePreview.textContent = `${startDisplay} — ${endDisplay}`;

                    // Update hidden inputs
                    addStartDateInput.value = formatDateValue(startYear, startMonth, true);
                    addEndDateInput.value = formatDateValue(endYear, endMonth, false);
                }

                if (addStartYear) {
                    // When start year changes, auto-update end year to +1
                    addStartYear.addEventListener('change', function() {
                        const newStartYear = parseInt(this.value);
                        addEndYear.value = newStartYear + 1;
                        updateAddModalDates();
                    });

                    // Month change listeners
                    addStartMonth.addEventListener('change', updateAddModalDates);
                    addEndMonth.addEventListener('change', updateAddModalDates);

                    // Toggle custom dates
                    addEnableCustomDates.addEventListener('change', function() {
                        addStartMonth.disabled = !this.checked;
                        addEndMonth.disabled = !this.checked;
                    });

                    // Initialize on page load
                    updateAddModalDates();
                }

                // ===== EDIT SCHOOL YEAR MODALS =====
                document.querySelectorAll('.edit-school-year-form').forEach(form => {
                    const modal = form.closest('.modal');
                    const startYearSelect = form.querySelector('.edit-start-year');
                    const endYearSelect = form.querySelector('.edit-end-year');
                    const startMonthSelect = form.querySelector('.edit-start-month');
                    const endMonthSelect = form.querySelector('.edit-end-month');
                    const enableCustomDates = form.querySelector('.edit-enable-custom-dates');
                    const schoolYearPreview = form.querySelector('.edit-school-year-preview');
                    const dateRangePreview = form.querySelector('.edit-date-range-preview');
                    const startDateInput = form.querySelector('.edit-start-date');
                    const endDateInput = form.querySelector('.edit-end-date');
                    const setAsCurrentCheckbox = form.querySelector('.edit-set-active-checkbox');

                    function updateEditModalDates() {
                        const startYear = parseInt(startYearSelect.value);
                        const endYear = parseInt(endYearSelect.value);
                        const startMonth = parseInt(startMonthSelect.value);
                        const endMonth = parseInt(endMonthSelect.value);

                        // Update preview
                        schoolYearPreview.textContent = `S.Y. ${startYear}-${endYear}`;

                        // Update date range preview
                        const startDisplay = `${monthNames[startMonth - 1]} 1, ${startYear}`;
                        const endDisplay =
                            `${monthNames[endMonth - 1]} ${getLastDayOfMonth(endYear, endMonth)}, ${endYear}`;
                        dateRangePreview.textContent = `${startDisplay} — ${endDisplay}`;

                        // Update hidden inputs
                        startDateInput.value = formatDateValue(startYear, startMonth, true);
                        endDateInput.value = formatDateValue(endYear, endMonth, false);
                    }

                    // When start year changes, auto-update end year to +1
                    startYearSelect.addEventListener('change', function() {
                        const newStartYear = parseInt(this.value);
                        endYearSelect.value = newStartYear + 1;
                        updateEditModalDates();
                    });

                    // Month change listeners
                    startMonthSelect.addEventListener('change', updateEditModalDates);
                    endMonthSelect.addEventListener('change', updateEditModalDates);

                    // Toggle custom dates
                    enableCustomDates.addEventListener('change', function() {
                        startMonthSelect.disabled = !this.checked;
                        endMonthSelect.disabled = !this.checked;
                    });

                    if (setAsCurrentCheckbox) {
                        setAsCurrentCheckbox.addEventListener('change', function() {
                            if (!this.checked) {
                                return;
                            }

                            this.checked = false;
                            const yearId = this.dataset.yearId;
                            const previewLabel = schoolYearPreview?.textContent?.trim() || '';
                            const parsedYearName = previewLabel.replace(/^S\.Y\.\s*/, '');
                            const yearName = parsedYearName || this.dataset.yearName || 'Selected School Year';
                            openSchoolYearModeModal(yearId, yearName);
                        });
                    }
                });
            }

            function initDashboardCharts() {
                const enrollmentChartEl = document.getElementById('enrollmentChart');
                const classDistributionChartEl = document.getElementById('classDistributionChart');
                const attendanceTrendChartEl = document.getElementById('attendanceTrendChart');
                if (!enrollmentChartEl || !classDistributionChartEl) return;

                let enrollmentChart;
                let classDistributionChart;
                let attendanceTrendChart;

                const fetchData = async () => {
                    // Show spinners while loading
                    enrollmentChartEl.innerHTML =
                        `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
                    classDistributionChartEl.innerHTML =
                        `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

                    try {
                        const response = await fetch(`{{ route('admin.chart-data') }}`);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();

                        updateEnrollmentChart(data.enrollmentTrends);
                        updateClassDistributionChart(data.classDistributionChart);
                        if (attendanceTrendChartEl && data.attendanceTrend) {
                            updateAttendanceTrendChart(data.attendanceTrend);
                        }
                    } catch (error) {
                        console.error('Error fetching chart data:', error);
                        enrollmentChartEl.innerHTML =
                            `<div class="text-center py-5 text-danger">Failed to load chart data. ${error.message}</div>`;
                        classDistributionChartEl.innerHTML =
                            '<div class="text-center py-5 text-danger">Failed to load chart data.</div>';
                    }
                };

                // In dashboard.blade.php

                const updateEnrollmentChart = (enrollmentTrends) => {
                    if (enrollmentChart) {
                        enrollmentChart.destroy();
                    }
                    enrollmentChartEl.innerHTML = ''; // Clear spinner

                    const canvas = document.createElement('canvas');
                    enrollmentChartEl.appendChild(canvas);

                    const ctx = canvas.getContext('2d');

                    // Get unique school years in order (using school_year_id for sorting)
                    const schoolYearMap = new Map();
                    enrollmentTrends.forEach(item => {
                        if (!schoolYearMap.has(item.school_year_id)) {
                            schoolYearMap.set(item.school_year_id, item.school_year_name);
                        }
                    });
                    const sortedSchoolYearIds = [...schoolYearMap.keys()].sort((a, b) => a - b);
                    const schoolYears = sortedSchoolYearIds.map(id => schoolYearMap.get(id));

                    const statuses = [...new Set(enrollmentTrends.map(item => item.status))].sort();

                    const datasets = statuses.map(status => {
                        const data = sortedSchoolYearIds.map(schoolYearId => {
                            const trend = enrollmentTrends.find(item => item.school_year_id ===
                                schoolYearId && item.status === status);
                            return trend ? trend.count : 0;
                        });

                        let backgroundColor, borderColor;
                        switch (status) {
                            case 'enrolled':
                                backgroundColor = 'rgba(13, 110, 253, 0.2)';
                                borderColor = 'rgba(13, 110, 253, 1)';
                                break;
                            case 'graduated':
                                backgroundColor = 'rgba(25, 135, 84, 0.2)';
                                borderColor = 'rgba(25, 135, 84, 1)';
                                break;
                            case 'transferred':
                                backgroundColor = 'rgba(255, 193, 7, 0.2)';
                                borderColor = 'rgba(255, 193, 7, 1)';
                                break;
                            case 'unenrolled':
                                backgroundColor = 'rgba(220, 53, 69, 0.2)';
                                borderColor = 'rgba(220, 53, 69, 1)';
                                break;
                            default:
                                backgroundColor = 'rgba(108, 117, 125, 0.2)';
                                borderColor = 'rgba(108, 117, 125, 1)';
                        }

                        return {
                            label: status.charAt(0).toUpperCase() + status.slice(1),
                            data: data,
                            backgroundColor: backgroundColor, // The area fill color
                            borderColor: borderColor, // The line color
                            borderWidth: 2,
                            fill: true, // **CHANGE**: Fill the area under the line
                            tension: 0.4 // **CHANGE**: Makes the line smooth/curved
                        };
                    });

                    enrollmentChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: schoolYears,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                // **REMOVED**: The 'stacked' property is removed from scales
                                x: {
                                    title: {
                                        display: true,
                                        text: 'School Year'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Students'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            interaction: { // Improves tooltip experience on line charts
                                intersect: false,
                                mode: 'index',
                            },
                        }
                    });
                };
                const updateClassDistributionChart = (chartData) => {
                    if (classDistributionChart) {
                        classDistributionChart.destroy();
                    }
                    classDistributionChartEl.innerHTML = '';

                    const canvas = document.createElement('canvas');
                    classDistributionChartEl.appendChild(canvas);

                    classDistributionChart = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Sections',
                                data: chartData.data,
                                backgroundColor: [
                                    '#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#ffc107',
                                    '#198754', '#20c997', '#0dcaf0'
                                ],
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += context.parsed + ' sections';
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                };
                const updateAttendanceTrendChart = (attendanceData) => {
                    if (attendanceTrendChart) {
                        attendanceTrendChart.destroy();
                    }
                    attendanceTrendChartEl.innerHTML = '';

                    const canvas = document.createElement('canvas');
                    attendanceTrendChartEl.appendChild(canvas);

                    const labels = attendanceData.map(item => {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric'
                        });
                    });
                    const percentages = attendanceData.map(item => item.percentage);
                    const presentCounts = attendanceData.map(item => item.present);

                    attendanceTrendChart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Attendance Rate (%)',
                                data: percentages,
                                borderColor: '#0dcaf0',
                                backgroundColor: 'rgba(13, 202, 240, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    title: {
                                        display: true,
                                        text: 'Percentage (%)'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                tooltip: {
                                    callbacks: {
                                        afterLabel: function(context) {
                                            const index = context.dataIndex;
                                            return 'Present: ' + presentCounts[index];
                                        }
                                    }
                                }
                            }
                        }
                    });
                };

                // Initial fetch
                fetchData();
            }

            // Call all initialization functions
            initSchoolYearManagement();
            initSchoolYearDropdowns();
            initDashboardCharts();
            initSchoolYearTimeline(); // Call the new timeline function

            // Edit Quarter Modal Handler
            document.querySelectorAll('.edit-quarter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const quarterId = this.dataset.quarterId;
                    const schoolYearId = this.dataset.schoolYearId;
                    const quarterName = this.dataset.quarterName;
                    const startDate = this.dataset.startDate;
                    const endDate = this.dataset.endDate;
                    const deadline = this.dataset.deadline;
                    const isLocked = this.dataset.isLocked === '1';
                    const isSchoolYearActive = this.dataset.schoolYearActive === '1';

                    // Update modal title
                    document.getElementById('editQuarterName').textContent = quarterName;

                    // Set form action
                    document.getElementById('editQuarterForm').action =
                        `/admin/school-year/${schoolYearId}/quarters/${quarterId}`;

                    // Populate fields
                    document.getElementById('editQuarterStartDate').value = startDate;
                    document.getElementById('editQuarterEndDate').value = endDate;
                    document.getElementById('editQuarterDeadline').value = deadline || '';

                    const lockCheckbox = document.getElementById('editQuarterLocked');
                    const lockHiddenInput = document.getElementById('editQuarterLockedHidden');
                    const lockControls = document.getElementById('editQuarterLockControls');
                    const lockHint = document.getElementById('editQuarterLockHint');

                    lockCheckbox.checked = isLocked;
                    lockCheckbox.disabled = !isSchoolYearActive;
                    lockHiddenInput.disabled = !isSchoolYearActive;
                    lockControls.classList.toggle('opacity-50', !isSchoolYearActive);
                    lockHint.classList.toggle('d-none', isSchoolYearActive);

                    // Show the modal
                    const modal = new bootstrap.Modal(document.getElementById('editQuarterModal'));
                    modal.show();
                });
            });

            // Clear grade submission deadline button
            const clearDeadlineBtn = document.getElementById('editQuarterClearDeadline');
            if (clearDeadlineBtn) {
                clearDeadlineBtn.addEventListener('click', function() {
                    const input = document.getElementById('editQuarterDeadline');
                    if (input) input.value = '';
                });
            }

            // Re-initialize feather icons when quarter modals are shown
            document.querySelectorAll('[id^="quartersModal"]').forEach(modal => {
                modal.addEventListener('shown.bs.modal', function() {
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                });
            });
        });
    </script>
@endpush
