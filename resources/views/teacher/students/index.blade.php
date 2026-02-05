@extends('base')

@section('title', 'Student Profiles')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/students/students.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Student Profiles</h4>
                <small class="text-muted">
                    Manage student profiles and view academic history
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('teacher.students.create') }}" class="btn btn-primary btn-sm">
                    <i data-feather="user-plus" class="icon-sm me-1"></i>
                    Add New Student
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-3 g-2">
            <div class="col-md-4 col-6">
                <div class="quick-stat total">
                    <div class="stat-value">{{ $totalStudents }}</div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="quick-stat enrolled">
                    <div class="stat-value">{{ $enrolledThisYear }}</div>
                    <div class="stat-label">Enrolled This Year</div>
                </div>
            </div>
            <div class="col-md-4 col-6">
                <div class="quick-stat pending">
                    <div class="stat-value">{{ $notEnrolledThisYear }}</div>
                    <div class="stat-label">Not Yet Enrolled</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('teacher.students.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Name, LRN, or Student ID" value="{{ $search ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Enrollment Status</label>
                        <select name="enrollment_status" class="form-select form-select-sm">
                            <option value="all" {{ $enrollmentFilter === 'all' ? 'selected' : '' }}>All Students</option>
                            @if ($teacherEnrollmentEnabled ?? false)
                                <option value="pending" {{ $enrollmentFilter === 'pending' ? 'selected' : '' }}>
                                    Pending Enrollment
                                </option>
                            @endif
                            <option value="not_enrolled" {{ $enrollmentFilter === 'not_enrolled' ? 'selected' : '' }}>
                                Not Enrolled This Year
                            </option>
                            <option value="enrolled" {{ $enrollmentFilter === 'enrolled' ? 'selected' : '' }}>
                                Enrolled This Year
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Last Grade Level</label>
                        <select name="grade_level" class="form-select form-select-sm">
                            <option value="">All Grade Levels</option>
                            @foreach ($gradeLevels as $level)
                                <option value="{{ $level->id }}" {{ $gradeFilter == $level->id ? 'selected' : '' }}>
                                    {{ $level->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary me-1">
                            <i data-feather="search" class="icon-sm"></i> Filter
                        </button>
                        <a href="{{ route('teacher.students.index') }}" class="btn btn-sm btn-outline-secondary">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students List -->
        <div class="card">
            <div class="card-body p-0">
                @if ($students->isEmpty())
                    <div class="text-center py-5">
                        <i data-feather="users" class="text-muted mb-3" style="width: 48px; height: 48px;"></i>
                        <h6 class="text-muted">No Students Found</h6>
                        <p class="text-muted small mb-3">
                            @if ($search || $enrollmentFilter !== 'all' || $gradeFilter)
                                Try adjusting your filters or search terms.
                            @else
                                Start by adding a new student profile.
                            @endif
                        </p>
                        @if (!$search && $enrollmentFilter === 'all' && !$gradeFilter)
                            <a href="{{ route('teacher.students.create') }}" class="btn btn-primary btn-sm">
                                <i data-feather="user-plus" class="icon-sm me-1"></i> Add First Student
                            </a>
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>LRN</th>
                                    <th>Current Grade</th>
                                    <th>Guardian</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $student)
                                    @php
                                        // Get profile for current school year
                                        $currentProfile = $currentSchoolYear
                                            ? $student->profiles
                                                ->where('school_year_id', $currentSchoolYear->id)
                                                ->first()
                                            : $student->profiles->sortByDesc('school_year_id')->first();
                                        // Get enrollment for current school year only
                                        $currentEnrollment = $currentSchoolYear
                                            ? $student->enrollments
                                                ->where('school_year_id', $currentSchoolYear->id)
                                                ->first()
                                            : null;
                                        $isEnrolledThisYear = $currentEnrollment !== null;
                                    @endphp
                                    <tr class="student-row" data-student-id="{{ $student->id }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="student-avatar me-2">
                                                    {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-medium">
                                                        {{ $student->last_name }}, {{ $student->first_name }}
                                                    </div>
                                                    <small class="text-muted">ID: {{ $student->student_id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $student->lrn ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if ($currentProfile)
                                                <span class="badge bg-light text-dark">
                                                    {{ $currentProfile->gradeLevel->name ?? 'N/A' }}
                                                </span>
                                                <small class="text-muted d-block">
                                                    {{ $currentProfile->schoolYear->name ?? '' }}
                                                </small>
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-warning text-dark',
                                                        'active' => 'bg-success',
                                                        'enrolled' => 'bg-success',
                                                        'promoted' => 'bg-info',
                                                        'retained' => 'bg-secondary',
                                                        'transferred' => 'bg-dark',
                                                        'dropped' => 'bg-danger',
                                                        'graduated' => 'bg-primary',
                                                    ];
                                                    $statusColor =
                                                        $statusColors[$currentProfile->status] ?? 'bg-secondary';
                                                @endphp
                                                <span class="badge {{ $statusColor }} mt-1" style="font-size: 0.7em;">
                                                    {{ ucfirst($currentProfile->status ?? 'N/A') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($student->guardian && $student->guardian->user)
                                                <div class="small">
                                                    {{ $student->guardian->user->first_name }}
                                                    {{ $student->guardian->user->last_name }}
                                                </div>
                                                <small class="text-muted">{{ $student->guardian->phone }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isEnrolledThisYear)
                                                <span class="badge bg-success">
                                                    <i data-feather="check-circle" class="icon-xs me-1"></i>
                                                    Enrolled
                                                </span>
                                                <small class="text-muted d-block">
                                                    {{ $currentEnrollment->class->section->gradeLevel->name ?? '' }}
                                                    - {{ $currentEnrollment->class->section->name ?? '' }}
                                                </small>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i data-feather="clock" class="icon-xs me-1"></i>
                                                    Not Enrolled
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('teacher.students.show', $student) }}"
                                                    class="btn btn-outline-primary">
                                                    View
                                                </a>
                                                <a href="{{ route('teacher.students.edit', $student) }}"
                                                    class="btn btn-outline-secondary" title="Edit">
                                                    <i data-feather="edit-2" class="icon-sm"></i>
                                                </a>
                                                @if (!$isEnrolledThisYear && $currentSchoolYear)
                                                    @if ($teacherEnrollmentEnabled ?? false)
                                                        <a href="{{ route('teacher.enrollment.index') }}?student={{ $student->id }}"
                                                            class="btn btn-outline-success" title="Enroll Student">
                                                            <i data-feather="user-check" class="icon-sm"></i>
                                                        </a>
                                                    @else
                                                        <button type="button" class="btn btn-outline-secondary" disabled
                                                            title="Enrollment is currently disabled">
                                                            <i data-feather="user-check" class="icon-sm"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($students->hasPages())
                        <div class="card-footer border-top bg-white">
                            {{ $students->withQueryString()->links() }}
                        </div>
                    @endif
                @endif
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
