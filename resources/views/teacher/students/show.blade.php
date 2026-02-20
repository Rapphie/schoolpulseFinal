@extends('base')

@section('title', $student->first_name . ' ' . $student->last_name . ' - Student Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/students/students.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('teacher.students.index') }}">Student Profiles</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $student->first_name }} {{ $student->last_name }}</li>
                    </ol>
                </nav>
                <h4 class="mb-0">Student Profile</h4>
            </div>
            <div class="d-flex gap-2">
                @if ($isAdviser ?? false)
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#sectionHistoryModal">
                        <i data-feather="history" class="icon-sm me-1"></i> History
                    </button>
                @endif
                <a href="{{ route('teacher.students.edit', $student) }}" class="btn btn-outline-primary btn-sm">
                    <i data-feather="edit-2" class="icon-sm me-1"></i> Edit Profile
                </a>
                @php
                    $isEnrolledThisYear =
                        $currentSchoolYear &&
                        $student->enrollments->where('school_year_id', $currentSchoolYear->id)->isNotEmpty();
                @endphp
                @if (!$isEnrolledThisYear && $currentSchoolYear)
                    <a href="{{ route('teacher.enrollment.index') }}?student={{ $student->id }}"
                        class="btn btn-success btn-sm">
                        <i data-feather="user-check" class="icon-sm me-1"></i> Enroll Student
                    </a>
                @endif
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Student Info -->
            <div class="col-lg-4">
                <!-- Basic Info Card -->
                <div class="card mb-3">
                    <div class="card-body text-center">
                        <div class="student-avatar-large mx-auto mb-3">
                            {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                        </div>
                        <h5 class="mb-1">{{ $student->first_name }} {{ $student->last_name }}</h5>
                        <p class="text-muted mb-2">Student ID: {{ $student->student_id }}</p>

                        @if ($isEnrolledThisYear)
                            @php
                                $currentEnrollment = $student->enrollments
                                    ->where('school_year_id', $currentSchoolYear->id)
                                    ->first();
                                $adviser = $currentEnrollment->class->teacher->user ?? null;
                            @endphp
                            <div class="mb-2">
                                <span class="badge bg-success">
                                    <i data-feather="check-circle" class="icon-xs me-1"></i>
                                    Enrolled - {{ $currentEnrollment->class->section->gradeLevel->name ?? '' }}
                                    {{ $currentEnrollment->class->section->name ?? '' }}
                                </span>
                                @if ($adviser)
                                    <div class="small text-muted mt-1">
                                        Adviser: {{ $adviser->first_name }} {{ $adviser->last_name }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <span class="badge bg-warning text-dark mb-2">
                                <i data-feather="clock" class="icon-xs me-1"></i>
                                Not Enrolled This Year
                            </span>
                        @endif
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">LRN</span>
                            <span>{{ $student->lrn ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Gender</span>
                            <span>{{ ucfirst($student->gender) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Birthdate</span>
                            <span>{{ $student->birthdate ? $student->birthdate->format('M d, Y') : 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Age</span>
                            <span>{{ $student->birthdate ? $student->birthdate->age . ' years old' : 'N/A' }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="text-muted d-block mb-1">Address</span>
                            <span>{{ $student->address ?? 'N/A' }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Guardian Info Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i data-feather="users" class="icon-sm me-2"></i>
                            Guardian Information
                        </h6>
                    </div>
                    <div class="card-body">
                        @if ($student->guardian && $student->guardian->user)
                            <div class="d-flex align-items-center mb-3">
                                <div class="guardian-avatar me-3">
                                    {{ strtoupper(substr($student->guardian->user->first_name, 0, 1)) }}{{ strtoupper(substr($student->guardian->user->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <h6 class="mb-0">
                                        {{ $student->guardian->user->first_name }}
                                        {{ $student->guardian->user->last_name }}
                                    </h6>
                                    <small
                                        class="text-muted">{{ ucfirst($student->guardian->relationship ?? 'Guardian') }}</small>
                                </div>
                            </div>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i data-feather="mail" class="icon-sm text-muted me-2"></i>
                                    {{ $student->guardian->user->email }}
                                </li>
                                <li>
                                    <i data-feather="phone" class="icon-sm text-muted me-2"></i>
                                    {{ $student->guardian->phone ?? 'N/A' }}
                                </li>
                            </ul>
                        @else
                            <p class="text-muted mb-0">No guardian information available.</p>
                        @endif
                    </div>
                </div>

                <!-- Additional Info Card -->
                @if ($student->distance_km || $student->transportation || $student->family_income)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i data-feather="info" class="icon-sm me-2"></i>
                                Additional Information
                            </h6>
                        </div>
                        <ul class="list-group list-group-flush">
                            @if ($student->distance_km)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Distance from School</span>
                                    <span>{{ $student->distance_km }} km</span>
                                </li>
                            @endif
                            @if ($student->transportation)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Transportation</span>
                                    <span>{{ $student->transportation }}</span>
                                </li>
                            @endif
                            @if ($student->family_income)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Family Income</span>
                                    <span>{{ $student->family_income }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Right Column - Academic History -->
            <div class="col-lg-8">
                <!-- Summary / Short History -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card h-100 bg-light border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Academic Standing</h6>
                                <div class="h4 mb-0">
                                    @php
                                        $latestProfile =
                                            $student->profiles
                                                ->where('status', 'promoted')
                                                ->sortByDesc('school_year_id')
                                                ->first() ?? $student->profiles->sortByDesc('school_year_id')->first();
                                    @endphp
                                    {{ $latestProfile && $latestProfile->final_average ? number_format($latestProfile->final_average, 2) : 'N/A' }}
                                </div>
                                <small class="text-muted">Last recorded final average</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 bg-light border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Current Attendance</h6>
                                <div class="h4 mb-0 text-success">
                                    @php
                                        $currentAttendance = $attendanceByYear[$currentSchoolYear->id ?? 0] ?? null;
                                        $rate =
                                            $currentAttendance && $currentAttendance['total'] > 0
                                                ? (($currentAttendance['present'] + $currentAttendance['late']) /
                                                        $currentAttendance['total']) *
                                                    100
                                                : 0;
                                    @endphp
                                    {{ $rate > 0 ? number_format($rate, 1) . '%' : 'No Data' }}
                                </div>
                                <small class="text-muted">Attendance rate for current year</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Academic Performance -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i data-feather="award" class="icon-sm me-2"></i>
                            Current Academic Performance
                        </h6>
                        @if ($currentSchoolYear)
                            <span class="badge bg-primary">{{ $currentSchoolYear->name }}</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @php
                            $currentYearId = $currentSchoolYear->id ?? null;
                            $currentGrades = $student->grades->where('school_year_id', $currentYearId);
                        @endphp
                        @if ($currentGrades->isEmpty())
                            <div class="text-center py-4">
                                <i data-feather="file-text" class="text-muted mb-2"
                                    style="width: 32px; height: 32px;"></i>
                                <p class="text-muted mb-0">No grade records for the current school year.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Subject</th>
                                            <th class="text-center">1st</th>
                                            <th class="text-center">2nd</th>
                                            <th class="text-center">3rd</th>
                                            <th class="text-center">4th</th>
                                            <th class="text-center">Final</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($currentGrades->groupBy('subject_id') as $subjectId => $grades)
                                            @php
                                                $subject = $grades->first()->subject;
                                                $g1 = $grades->where('quarter', 1)->first();
                                                $g2 = $grades->where('quarter', 2)->first();
                                                $g3 = $grades->where('quarter', 3)->first();
                                                $g4 = $grades->where('quarter', 4)->first();
                                                $quarterGrades = collect([$g1, $g2, $g3, $g4])
                                                    ->filter(fn($g) => $g && $g->grade !== null)
                                                    ->pluck('grade');
                                                $finalAvg = $quarterGrades->isNotEmpty()
                                                    ? round($quarterGrades->sum() / 4, 2)
                                                    : null;
                                            @endphp
                                            <tr>
                                                <td>{{ $subject->name ?? 'Unknown' }}</td>
                                                <td class="text-center">{{ $g1->grade ?? '-' }}</td>
                                                <td class="text-center">{{ $g2->grade ?? '-' }}</td>
                                                <td class="text-center">{{ $g3->grade ?? '-' }}</td>
                                                <td class="text-center">{{ $g4->grade ?? '-' }}</td>
                                                <td class="text-center fw-bold">
                                                    {{ $finalAvg !== null ? number_format($finalAvg, 2) : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Grades History -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i data-feather="trending-up" class="icon-sm me-2"></i>
                            Grades History
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($student->profiles->isEmpty())
                            <div class="text-center py-4">
                                <i data-feather="file-text" class="text-muted mb-2"
                                    style="width: 32px; height: 32px;"></i>
                                <p class="text-muted mb-0">No grades history available.</p>
                            </div>
                        @else
                            <div class="accordion accordion-flush" id="gradesHistoryAccordion">
                                @foreach ($student->profiles as $profileIndex => $profile)
                                    @php
                                        $enrollment = $student->enrollments
                                            ->where('school_year_id', $profile->school_year_id)
                                            ->first();
                                        $attendance = $attendanceByYear[$profile->school_year_id] ?? null;
                                        $yearGrades =
                                            $student->grades->where('school_year_id', $profile->school_year_id) ??
                                            collect();
                                        $statusColors = [
                                            'pending' => 'bg-secondary',
                                            'active' => 'bg-info',
                                            'enrolled' => 'bg-info',
                                            'promoted' => 'bg-success',
                                            'retained' => 'bg-warning text-dark',
                                            'dropped' => 'bg-danger',
                                            'graduated' => 'bg-primary',
                                        ];
                                        $statusColor = $statusColors[$profile->status] ?? 'bg-secondary';
                                    @endphp
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="gradesHeading{{ $profileIndex }}">
                                            <button class="accordion-button {{ $profileIndex === 0 ? '' : 'collapsed' }}"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#gradesCollapse{{ $profileIndex }}"
                                                aria-expanded="{{ $profileIndex === 0 ? 'true' : 'false' }}"
                                                aria-controls="gradesCollapse{{ $profileIndex }}">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <strong>{{ $profile->schoolYear->name ?? 'N/A' }}</strong>
                                                        @if ($profile->is_current)
                                                            <span class="badge bg-primary">Current</span>
                                                        @endif
                                                        <span class="badge bg-light text-dark">
                                                            {{ $profile->gradeLevel->name ?? 'N/A' }}
                                                        </span>
                                                        @if ($enrollment && $enrollment->class && $enrollment->class->section)
                                                            <span
                                                                class="text-muted small">{{ $enrollment->class->section->name }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if ($profile->final_average)
                                                            <span
                                                                class="fw-medium {{ $profile->final_average >= 75 ? 'text-success' : 'text-danger' }}">
                                                                {{ number_format($profile->final_average, 2) }}
                                                            </span>
                                                        @endif
                                                        <span class="badge {{ $statusColor }}">
                                                            {{ ucfirst($profile->status ?? 'active') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="gradesCollapse{{ $profileIndex }}"
                                            class="accordion-collapse collapse {{ $profileIndex === 0 ? 'show' : '' }}"
                                            aria-labelledby="gradesHeading{{ $profileIndex }}"
                                            data-bs-parent="#gradesHistoryAccordion">
                                            <div class="accordion-body p-0">
                                                @if ($yearGrades->isEmpty())
                                                    <div class="text-center py-3">
                                                        <p class="text-muted mb-0">No grade records for this school
                                                            year.</p>
                                                    </div>
                                                @else
                                                    <div class="table-responsive">
                                                        <table class="table table-hover mb-0">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Subject</th>
                                                                    <th class="text-center">1st</th>
                                                                    <th class="text-center">2nd</th>
                                                                    <th class="text-center">3rd</th>
                                                                    <th class="text-center">4th</th>
                                                                    <th class="text-center">Final</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($yearGrades->groupBy('subject_id') as $subjectId => $subjectGrades)
                                                                    @php
                                                                        $subject = $subjectGrades->first()->subject;
                                                                        $g1 = $subjectGrades
                                                                            ->where('quarter', 1)
                                                                            ->first();
                                                                        $g2 = $subjectGrades
                                                                            ->where('quarter', 2)
                                                                            ->first();
                                                                        $g3 = $subjectGrades
                                                                            ->where('quarter', 3)
                                                                            ->first();
                                                                        $g4 = $subjectGrades
                                                                            ->where('quarter', 4)
                                                                            ->first();
                                                                        $quarterGrades = collect([$g1, $g2, $g3, $g4])
                                                                            ->filter(fn($g) => $g && $g->grade !== null)
                                                                            ->pluck('grade');
                                                                        $finalAvg = $quarterGrades->isNotEmpty()
                                                                            ? round($quarterGrades->sum() / 4, 2)
                                                                            : null;
                                                                    @endphp
                                                                    <tr>
                                                                        <td>{{ $subject->name ?? 'Unknown' }}</td>
                                                                        <td class="text-center">
                                                                            {{ $g1->grade ?? '-' }}</td>
                                                                        <td class="text-center">
                                                                            {{ $g2->grade ?? '-' }}</td>
                                                                        <td class="text-center">
                                                                            {{ $g3->grade ?? '-' }}</td>
                                                                        <td class="text-center">
                                                                            {{ $g4->grade ?? '-' }}</td>
                                                                        <td class="text-center fw-bold">
                                                                            {{ $finalAvg !== null ? number_format($finalAvg, 2) : '-' }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                                <div
                                                    class="px-3 py-2 bg-light border-top d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-3">
                                                        @if ($profile->final_average)
                                                            <span class="small">
                                                                <strong>General Average:</strong>
                                                                <span
                                                                    class="{{ $profile->final_average >= 75 ? 'text-success' : 'text-danger' }} fw-medium">
                                                                    {{ number_format($profile->final_average, 2) }}
                                                                </span>
                                                            </span>
                                                        @endif
                                                        @if ($attendance)
                                                            <span class="small">
                                                                <strong>Attendance:</strong>
                                                                <span class="text-success"
                                                                    title="Present">{{ $attendance['present'] }}</span>
                                                                <span class="text-muted">/</span>
                                                                <span class="text-danger"
                                                                    title="Absent">{{ $attendance['absent'] }}</span>
                                                                <span class="text-muted">/</span>
                                                                <span class="text-warning"
                                                                    title="Late">{{ $attendance['late'] }}</span>
                                                                <span class="text-muted small">(P/A/L)</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <a href="{{ route('teacher.students.grades', ['student' => $student->id, 'sy' => $profile->school_year_id]) }}"
                                                        class="btn btn-outline-primary btn-sm">
                                                        <i data-feather="eye" class="icon-sm me-1"></i> View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Enrollment History -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i data-feather="book-open" class="icon-sm me-2"></i>
                            Enrollment History
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($student->enrollments->isEmpty())
                            <div class="text-center py-4">
                                <i data-feather="clipboard" class="text-muted mb-2"
                                    style="width: 32px; height: 32px;"></i>
                                <p class="text-muted mb-0">No enrollment history available.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>School Year</th>
                                            <th>Grade & Section</th>
                                            <th>Enrollment Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($student->enrollments as $enrollment)
                                            <tr>
                                                <td>
                                                    <strong>{{ $enrollment->schoolYear->name ?? 'N/A' }}</strong>
                                                </td>
                                                <td>
                                                    @if ($enrollment->class && $enrollment->class->section)
                                                        {{ $enrollment->class->section->gradeLevel->name ?? '' }} -
                                                        {{ $enrollment->class->section->name }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $enrollment->enrollment_date ? $enrollment->enrollment_date->format('M d, Y') : 'N/A' }}
                                                </td>
                                                <td>
                                                    @php
                                                        $enrollStatusColors = [
                                                            'enrolled' => 'bg-success',
                                                            'transferee' => 'bg-info',
                                                            'dropped' => 'bg-danger',
                                                        ];
                                                        $enrollStatusColor =
                                                            $enrollStatusColors[$enrollment->status] ?? 'bg-secondary';
                                                    @endphp
                                                    <span class="badge {{ $enrollStatusColor }}">
                                                        {{ ucfirst($enrollment->status ?? 'enrolled') }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Attendance Trend -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i data-feather="activity" class="icon-sm me-2"></i>
                            Attendance Trend
                        </h6>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Attendance trend range">
                            <button type="button" class="btn btn-outline-primary attendance-range-btn" data-days="3">3
                                Days</button>
                            <button type="button" class="btn btn-outline-primary attendance-range-btn active"
                                data-days="7">7 Days</button>
                            <button type="button" class="btn btn-outline-primary attendance-range-btn" data-days="14">14
                                Days</button>
                            <button type="button" class="btn btn-outline-primary attendance-range-btn" data-days="30">1
                                Month</button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (empty($attendanceTrend))
                            <div class="text-center py-4">
                                <i data-feather="bar-chart-2" class="text-muted mb-2"
                                    style="width: 32px; height: 32px;"></i>
                                <p class="text-muted mb-0">No attendance data available for the current school year.</p>
                            </div>
                        @else
                            <canvas id="attendanceTrendChart" height="260"></canvas>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i data-feather="zap" class="icon-sm me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <a href="{{ route('teacher.students.edit', $student) }}"
                                    class="btn btn-outline-primary btn-sm w-100">
                                    <i data-feather="edit-2" class="icon-sm me-1"></i> Edit Profile
                                </a>
                            </div>
                            @if (!$isEnrolledThisYear && $currentSchoolYear)
                                <div class="col-md-4">
                                    <a href="{{ route('teacher.enrollment.index') }}?student={{ $student->id }}"
                                        class="btn btn-outline-success btn-sm w-100">
                                        <i data-feather="user-check" class="icon-sm me-1"></i> Enroll
                                    </a>
                                </div>
                            @endif
                            <div class="col-md-4">
                                <a href="{{ route('teacher.students.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100">
                                    <i data-feather="arrow-left" class="icon-sm me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isAdviser ?? false)
        <!-- Section History Modal -->
        <div class="modal fade" id="sectionHistoryModal" tabindex="-1" aria-labelledby="sectionHistoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sectionHistoryModalLabel">Section History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="sectionHistoryTable" width="100%">
                                <thead>
                                    <tr>
                                        <th>School Year</th>
                                        <th>Adviser</th>
                                        <th>Enrolled</th>
                                        <th>Capacity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data populated by DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($isAdviser ?? false)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sectionHistory = @json($sectionHistory ?? []);
                const table = $('#sectionHistoryTable').DataTable({
                    data: sectionHistory,
                    columns: [{
                            data: 'school_year',
                            title: 'School Year'
                        },
                        {
                            data: 'adviser',
                            title: 'Adviser'
                        },
                        {
                            data: 'enrolled',
                            title: 'Enrolled'
                        },
                        {
                            data: 'capacity',
                            title: 'Capacity'
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                if (!row.class_id) return '';
                                const url =
                                    `{{ route('teacher.classes.view', ['class' => $studentClass->id ?? 0]) }}?class_id=${row.class_id}`;
                                return `<a href="${url}" class="btn btn-sm btn-outline-primary">View</a>`;
                            }
                        }
                    ],
                    order: [
                        [0, 'desc']
                    ],
                    responsive: true,
                    destroy: true
                });

                $('#sectionHistoryModal').on('shown.bs.modal', function() {
                    table.columns.adjust().draw();
                });
            });
        </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>

    @if (!empty($attendanceTrend))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const allData = @json($attendanceTrend);
                let chartInstance = null;

                function renderChart(days) {
                    const filtered = allData.slice(-days);
                    const labels = filtered.map(d => {
                        const date = new Date(d.date);
                        return date.toLocaleDateString('en-US', {
                            month: 'short',
                            day: 'numeric'
                        });
                    });

                    const config = {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                    label: 'Present',
                                    data: filtered.map(d => d.present),
                                    borderColor: '#198754',
                                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: 'Late',
                                    data: filtered.map(d => d.late),
                                    borderColor: '#ffc107',
                                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                                    fill: true,
                                    tension: 0.3
                                },
                                {
                                    label: 'Absent',
                                    data: filtered.map(d => d.absent),
                                    borderColor: '#dc3545',
                                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                    fill: true,
                                    tension: 0.3
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        precision: 0
                                    }
                                }
                            }
                        }
                    };

                    if (chartInstance) {
                        chartInstance.destroy();
                    }

                    const ctx = document.getElementById('attendanceTrendChart');
                    if (ctx) {
                        chartInstance = new Chart(ctx, config);
                    }
                }

                renderChart(7);

                document.querySelectorAll('.attendance-range-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.attendance-range-btn').forEach(b => b
                            .classList.remove('active'));
                        this.classList.add('active');
                        renderChart(parseInt(this.dataset.days));
                    });
                });
            });
        </script>
    @endif
@endpush
