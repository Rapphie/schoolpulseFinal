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
                            @php $currentEnrollment = $student->enrollments->where('school_year_id', $currentSchoolYear->id)->first(); @endphp
                            <span class="badge bg-success mb-2">
                                <i data-feather="check-circle" class="icon-xs me-1"></i>
                                Enrolled - {{ $currentEnrollment->class->section->gradeLevel->name ?? '' }}
                                {{ $currentEnrollment->class->section->name ?? '' }}
                            </span>
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
                <!-- Grade Level History -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i data-feather="trending-up" class="icon-sm me-2"></i>
                            Grade Level History
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @if ($student->profiles->isEmpty())
                            <div class="text-center py-4">
                                <i data-feather="file-text" class="text-muted mb-2" style="width: 32px; height: 32px;"></i>
                                <p class="text-muted mb-0">No grade level history available.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>School Year</th>
                                            <th>Grade Level</th>
                                            <th>Section</th>
                                            <th>Final Average</th>
                                            <th>Status</th>
                                            <th>Attendance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($student->profiles as $profile)
                                            @php
                                                $enrollment = $student->enrollments
                                                    ->where('school_year_id', $profile->school_year_id)
                                                    ->first();
                                                $attendance = $attendanceByYear[$profile->school_year_id] ?? null;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $profile->schoolYear->name ?? 'N/A' }}</strong>
                                                    @if ($profile->is_current)
                                                        <span class="badge bg-primary ms-1">Current</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        {{ $profile->gradeLevel->name ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($enrollment && $enrollment->class && $enrollment->class->section)
                                                        {{ $enrollment->class->section->name }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($profile->final_average)
                                                        <span
                                                            class="fw-medium {{ $profile->final_average >= 75 ? 'text-success' : 'text-danger' }}">
                                                            {{ number_format($profile->final_average, 2) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $statusColors = [
                                                            'pending' => 'bg-secondary',
                                                            'active' => 'bg-info',
                                                            'promoted' => 'bg-success',
                                                            'retained' => 'bg-warning text-dark',
                                                            'dropped' => 'bg-danger',
                                                            'graduated' => 'bg-primary',
                                                        ];
                                                        $statusColor =
                                                            $statusColors[$profile->status] ?? 'bg-secondary';
                                                    @endphp
                                                    <span class="badge {{ $statusColor }}">
                                                        {{ ucfirst($profile->status ?? 'active') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($attendance)
                                                        <div class="attendance-mini">
                                                            <span class="text-success"
                                                                title="Present">{{ $attendance['present'] }}</span>
                                                            <span class="text-muted">/</span>
                                                            <span class="text-danger"
                                                                title="Absent">{{ $attendance['absent'] }}</span>
                                                            <span class="text-muted">/</span>
                                                            <span class="text-warning"
                                                                title="Late">{{ $attendance['late'] }}</span>
                                                        </div>
                                                        <small class="text-muted">P/A/L</small>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>
@endpush
