@extends('base')

@section('title', 'Student Enrollment')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/enrollment/enrollment.css') }}">
@endpush

@php
    $enrollmentIndexRoute = $enrollmentIndexRoute ?? 'teacher.enrollment.index';
    $enrollmentExportAllRoute = $enrollmentExportAllRoute ?? 'teacher.enrollment.exportAll';
    $enrollmentExportMineRoute = $enrollmentExportMineRoute ?? null;
    $enrollmentStoreRoute = $enrollmentStoreRoute ?? 'teacher.enrollment.store';
    $enrollmentStorePastStudentRoute = $enrollmentStorePastStudentRoute ?? 'teacher.enrollment.storePastStudent';
    $enrollmentOwnerLabel = $enrollmentOwnerLabel ?? 'My Enrollments';
    $isEnrollmentReadOnly = $isEnrollmentReadOnly ?? false;
    $isAdminEnrollmentContext = $isAdminEnrollmentContext ?? false;
    $oldWizardInput = [
        'student_type' => old('student_type', ''),
        'student_id' => old('student_id', ''),
        'class_id' => old('class_id', ''),
        'enrollment_status' => old('enrollment_status', 'enrolled'),
    ];
    $profileWizardFields = [
        'lrn',
        'first_name',
        'last_name',
        'gender',
        'birthdate',
        'address',
        'distance_km',
        'transportation',
        'family_income',
        'guardian_first_name',
        'guardian_last_name',
        'guardian_email',
        'guardian_phone',
        'guardian_relationship',
    ];
    $profileFieldsWithErrors = collect($profileWizardFields)
        ->filter(fn(string $field): bool => $errors->has($field))
        ->values()
        ->all();
    $wizardErrorHints = [
        'hasAnyErrors' => $errors->any(),
        'hasProfileErrors' => ! empty($profileFieldsWithErrors),
        'hasClassError' => $errors->has('class_id'),
        'hasStudentSelectionError' => $errors->has('student_id'),
        'profileFieldsWithErrors' => $profileFieldsWithErrors,
    ];
@endphp

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0">Student Enrollment</h4>
                    @if (isset($allSchoolYears) && $allSchoolYears->isNotEmpty())
                        <form action="{{ route($enrollmentIndexRoute) }}" method="GET">
                            <select name="school_year_id" class="form-select form-select-sm"
                                style="width: auto; min-width: 180px; font-weight: bold;" onchange="this.form.submit()">
                                @foreach ($allSchoolYears as $sy)
                                    <option value="{{ $sy->id }}"
                                        {{ isset($currentSchoolYear) && $currentSchoolYear->id == $sy->id ? 'selected' : '' }}>
                                        {{ $sy->name }}
                                        {{ isset($activeSchoolYearId) && $sy->id == $activeSchoolYearId ? '(Current Active)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <h5 class="text-muted mb-0">{{ $currentSchoolYear->name ?? 'Current School Year' }}</h5>
                    @endif
                </div>
                @if (isset($currentSchoolYear) && $currentSchoolYear->is_enrollment_open)
                    <small class="text-success"><i class="fas fa-check-circle"></i> Enrollment Open</small>
                @endif
            </div>
            <div class="d-flex gap-2">
                @if ($isAdminEnrollmentContext && $enrollmentExportMineRoute)
                    <a href="{{ route($enrollmentExportMineRoute) }}" class="btn btn-outline-secondary btn-sm" id="myEnrollmentsDownloadButton">
                        My Enrollments
                    </a>
                @else
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="enrollmentListButton"
                        data-bs-toggle="modal" data-bs-target="#enrollmentModal"
                        data-readonly="{{ $isEnrollmentReadOnly ? 'true' : 'false' }}" {{ $isEnrollmentReadOnly ? 'disabled' : '' }}>
                        {{ $enrollmentOwnerLabel }}
                    </button>
                @endif
                <a href="{{ route($enrollmentExportAllRoute, ['school_year_id' => $currentSchoolYear?->id]) }}"
                    class="btn btn-outline-secondary btn-sm">
                    Download Enrollees
                </a>
            </div>
        </div>

        @if ($isEnrollmentReadOnly)
            <div class="alert alert-warning py-2" id="enrollmentViewModeAlert">
                Viewing a non-active school year. Enrollment actions are disabled in view mode.
            </div>
        @endif

        <!-- Quick Stats -->
        <div class="row mb-3 g-2">
            <div class="col-md-3 col-6">
                <div class="quick-stat pending h-100">
                    <div class="stat-value">{{ $students->count() }}</div>
                    <div class="stat-label">Not Yet Enrolled</div>
                    <div class="mt-2 border-top pt-2" style="max-height: 100px; overflow-y: auto;">
                        @php
                            $studentsByGrade = $students
                                ->groupBy(function ($s) {
                                    return $s->profiles->sortByDesc('school_year_id')->first()?->grade_level_id ?? 0;
                                })
                                ->map(function ($gs) {
                                    $profile = $gs->first()->profiles->sortByDesc('school_year_id')->first();
                                    return [
                                        'name' => $profile?->gradeLevel?->name ?? 'N/A',
                                        'count' => $gs->count(),
                                        'level' => $profile?->gradeLevel?->level ?? 0,
                                    ];
                                })
                                ->sortBy('level');
                        @endphp
                        @foreach ($studentsByGrade as $grade)
                            <div class="d-flex justify-content-between px-1 small text-muted">
                                <span style="font-size: 0.7rem;">{{ $grade['name'] }}</span>
                                <span style="font-size: 0.7rem;" class="fw-bold">{{ $grade['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="quick-stat classes h-100">
                    <div class="stat-value">
                        {{ $classes->filter(fn($c) => $c->enrollments->count() < $c->capacity)->count() }}</div>
                    <div class="stat-label">Classes w/ Slots</div>
                    <div class="mt-2 border-top pt-2" style="max-height: 100px; overflow-y: auto;">
                        @php
                            $classesByGrade = $classes
                                ->groupBy(fn($c) => $c->section->gradeLevel->id)
                                ->map(
                                    fn($gc) => [
                                        'name' => $gc->first()->section->gradeLevel->name,
                                        'count' => $gc
                                            ->filter(fn($c) => $c->enrollments->count() < $c->capacity)
                                            ->count(),
                                        'level' => $gc->first()->section->gradeLevel->level,
                                    ],
                                )
                                ->sortBy('level');
                        @endphp
                        @foreach ($classesByGrade as $grade)
                            @if ($grade['count'] > 0)
                                <div class="d-flex justify-content-between px-1 small text-muted">
                                    <span style="font-size: 0.7rem;">{{ $grade['name'] }}</span>
                                    <span style="font-size: 0.7rem;" class="fw-bold">{{ $grade['count'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="quick-stat enrolled h-100">
                    <div class="stat-value">{{ $teacherEnrollments->flatten()->count() }}</div>
                    <div class="stat-label">{{ $enrollmentOwnerLabel }}</div>
                    <div class="mt-2 border-top pt-2" style="max-height: 100px; overflow-y: auto;">
                        @php
                            $myEnrollmentsByGrade = $teacherEnrollments
                                ->flatten()
                                ->groupBy(fn($e) => $e->class->section->gradeLevel->id)
                                ->map(
                                    fn($ge) => [
                                        'name' => $ge->first()->class->section->gradeLevel->name,
                                        'count' => $ge->count(),
                                        'level' => $ge->first()->class->section->gradeLevel->level,
                                    ],
                                )
                                ->sortBy('level');
                        @endphp
                        @foreach ($myEnrollmentsByGrade as $grade)
                            <div class="d-flex justify-content-between px-1 small text-muted">
                                <span style="font-size: 0.7rem;">{{ $grade['name'] }}</span>
                                <span style="font-size: 0.7rem;" class="fw-bold">{{ $grade['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="quick-stat slots h-100">
                    <div class="stat-value">{{ $classes->sum(fn($c) => max(0, $c->capacity - $c->enrollments->count())) }}
                    </div>
                    <div class="stat-label">Available Slots</div>
                    <div class="mt-2 border-top pt-2" style="max-height: 100px; overflow-y: auto;">
                        @php
                            $slotsByGrade = $classes
                                ->groupBy(fn($c) => $c->section->gradeLevel->id)
                                ->map(
                                    fn($gc) => [
                                        'name' => $gc->first()->section->gradeLevel->name,
                                        'slots' => $gc->sum(fn($c) => max(0, $c->capacity - $c->enrollments->count())),
                                        'level' => $gc->first()->section->gradeLevel->level,
                                    ],
                                )
                                ->sortBy('level');
                        @endphp
                        @foreach ($slotsByGrade as $grade)
                            @if ($grade['slots'] > 0)
                                <div class="d-flex justify-content-between px-1 small text-muted">
                                    <span style="font-size: 0.7rem;">{{ $grade['name'] }}</span>
                                    <span style="font-size: 0.7rem;" class="fw-bold">{{ $grade['slots'] }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @if (isset($error))
            <div class="alert alert-warning">
                <strong>Enrollment Closed</strong><br>
                {{ $error }}
            </div>
        @else
            <!-- Enrollment Wizard -->
            <div class="card {{ $isEnrollmentReadOnly ? 'opacity-75' : '' }}" id="enrollmentWizardCard"
                data-readonly="{{ $isEnrollmentReadOnly ? 'true' : 'false' }}">
                <div class="card-body {{ $isEnrollmentReadOnly ? 'pe-none' : '' }}">
                    <!-- Stepper -->
                    <div class="wizard-stepper">
                        <div class="wizard-step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Type</div>
                        </div>
                        <div class="wizard-step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Profile</div>
                        </div>
                        <div class="wizard-step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Class</div>
                        </div>
                        <div class="wizard-step" data-step="4">
                            <div class="step-circle">4</div>
                            <div class="step-label">Confirm</div>
                        </div>
                    </div>

                    <form id="enrollmentForm" action="{{ route($enrollmentStoreRoute) }}" method="POST" novalidate>
                        @csrf
                        <input type="hidden" name="student_type" id="studentTypeInput" value="{{ old('student_type', '') }}">
                        <input type="hidden" name="student_id" id="studentIdInput" value="{{ old('student_id', '') }}">
                        <input type="hidden" name="class_id" id="classIdInput" value="{{ old('class_id', '') }}">
                        <input type="hidden" name="student_updates" id="studentUpdatesInput" value="{{ old('student_updates', '') }}">
                        <input type="hidden" name="enrollment_status" id="enrollmentStatusInput"
                            value="{{ old('enrollment_status', 'enrolled') }}">

                        <!-- Step 1: Student Type Selection -->
                        <div class="wizard-panel active" data-panel="1">
                            <h5 class="mb-3">Select Student Type</h5>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="student-type-card" data-type="new">
                                        <strong>New Student</strong>
                                        <div class="text-muted small">First time enrolling in this school</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="student-type-card" data-type="returning">
                                        <strong>Returning Student</strong>
                                        <div class="text-muted small">Previously enrolled, re-enrolling for this year</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="student-type-card" data-type="enroll">
                                        <strong>Enroll Pending Profile</strong>
                                        <div class="text-muted small">Enroll a pending student in their current grade level
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($students->isNotEmpty())
                                {{-- Quick Re-enroll Section - Only shown for "returning" type --}}
                                <div id="quickReenrollSection" class="mt-3 pt-3 border-top" style="display: none;">
                                    <h6 class="mb-3">
                                        Previously Enrolled Students ({{ $students->count() }} students not yet enrolled
                                        this year)
                                    </h6>

                                    <!-- Grade Level Filter for Returning Students -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label small text-muted">Filter by Last Grade Level</label>
                                            <select class="form-select form-select-sm" id="returningGradeFilter">
                                                <option value="">All Grade Levels</option>
                                                @php
                                                    $studentGradeLevels = $students
                                                        ->map(
                                                            fn($s) => $s->profiles
                                                                ->sortByDesc('school_year_id')
                                                                ->first()?->gradeLevel,
                                                        )
                                                        ->filter()
                                                        ->unique('id')
                                                        ->sortBy('level');
                                                @endphp
                                                @foreach ($studentGradeLevels as $level)
                                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted">Search</label>
                                            <input type="text" class="form-control form-control-sm"
                                                id="quickStudentSearch" placeholder="Search by name, LRN, or ID...">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm w-100"
                                                id="selectAllStudents">
                                                Select All
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Selected Students Counter -->
                                    <div id="selectedStudentsCounter" class="alert alert-success py-2 mb-3"
                                        style="display: none;">
                                        <strong id="selectedCount">0</strong> students selected
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-danger"
                                            id="clearSelection">
                                            Clear all
                                        </button>
                                    </div>

                                    <div id="quickStudentResults" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Students will be rendered by JavaScript -->
                                    </div>
                                </div>
                            @endif

                            <div class="wizard-navigation">
                                <div></div>
                                <button type="button" class="btn btn-primary" id="step1Next" disabled>
                                    Continue
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Profile Student (Student Information) -->
                        <div class="wizard-panel" data-panel="2">
                            <div id="newStudentForm">
                                <h5 class="mb-3">Student Profile</h5>

                                <!-- Student Basic Info Section -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        Basic Information <span class="badge bg-danger ms-2">Required</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="lrn" class="form-label">LRN <small
                                                    class="text-muted">(Optional)</small></label>
                                            <input type="text" class="form-control" id="lrn" name="lrn"
                                                value="{{ old('lrn') }}" placeholder="12-digit LRN">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="first_name" class="form-label">First Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="first_name" name="first_name"
                                                value="{{ old('first_name') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="last_name" class="form-label">Last Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                value="{{ old('last_name') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="gender" class="form-label">Gender <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="gender" name="gender" required>
                                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>
                                                    Male</option>
                                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>
                                                    Female</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="birthdate" class="form-label">Birthdate <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="birthdate" name="birthdate"
                                                value="{{ old('birthdate') }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                value="{{ old('address') }}" placeholder="Home address">
                                        </div>
                                    </div>
                                </div>

                                <!-- Guardian Info Section -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        Guardian Information <span class="badge bg-danger ms-2">Required</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="guardian_first_name" class="form-label">First Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="guardian_first_name"
                                                name="guardian_first_name" value="{{ old('guardian_first_name') }}"
                                                required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="guardian_last_name" class="form-label">Last Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="guardian_last_name"
                                                name="guardian_last_name" value="{{ old('guardian_last_name') }}"
                                                required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="guardian_email" class="form-label">Email <span
                                                    class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="guardian_email"
                                                name="guardian_email" value="{{ old('guardian_email') }}" required
                                                placeholder="parent@email.com">
                                            <small class="text-muted">Login credentials will be sent to this email</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="guardian_phone" class="form-label">Phone <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="guardian_phone"
                                                name="guardian_phone" value="{{ old('guardian_phone') }}" required
                                                placeholder="09XX XXX XXXX">
                                        </div>
                                        <div class="col-md-12">
                                            <label for="guardian_relationship" class="form-label">Relationship to Student
                                                <span class="text-danger">*</span></label>
                                            <select class="form-select" id="guardian_relationship"
                                                name="guardian_relationship" required>
                                                <option value="parent"
                                                    {{ old('guardian_relationship') == 'parent' ? 'selected' : '' }}>Parent
                                                </option>
                                                <option value="sibling"
                                                    {{ old('guardian_relationship') == 'sibling' ? 'selected' : '' }}>
                                                    Sibling</option>
                                                <option value="relative"
                                                    {{ old('guardian_relationship') == 'relative' ? 'selected' : '' }}>
                                                    Relative</option>
                                                <option value="guardian"
                                                    {{ old('guardian_relationship') == 'guardian' ? 'selected' : '' }}>
                                                    Guardian</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Info Section (Collapsible) -->
                                <div class="form-section">
                                    <div class="form-section-header collapsible-header collapsed"
                                        data-bs-toggle="collapse" data-bs-target="#additionalInfoSection"
                                        aria-expanded="false">
                                        Additional Information <span class="badge bg-secondary ms-2">Optional</span>
                                    </div>
                                    <div class="collapse" id="additionalInfoSection">
                                        <div class="row g-3 pt-3">
                                            <div class="col-md-4">
                                                <label for="distance_km" class="form-label">Distance from School
                                                    (km)</label>
                                                <input type="number" step="0.01" min="0" class="form-control"
                                                    id="distance_km" name="distance_km" value="{{ old('distance_km') }}"
                                                    placeholder="e.g., 2.5">
                                            </div>
                                            <div class="col-md-4">
                                                <label for="transportation" class="form-label">Mode of
                                                    Transportation</label>
                                                <select class="form-select" id="transportation" name="transportation">
                                                    <option value="">-- Select --</option>
                                                    <option value="Walk"
                                                        {{ old('transportation') == 'Walk' ? 'selected' : '' }}>Walk
                                                    </option>
                                                    <option value="Bicycle"
                                                        {{ old('transportation') == 'Bicycle' ? 'selected' : '' }}>Bicycle
                                                    </option>
                                                    <option value="Motorcycle"
                                                        {{ old('transportation') == 'Motorcycle' ? 'selected' : '' }}>
                                                        Motorcycle</option>
                                                    <option value="Tricycle"
                                                        {{ old('transportation') == 'Tricycle' ? 'selected' : '' }}>
                                                        Tricycle</option>
                                                    <option value="Jeepney"
                                                        {{ old('transportation') == 'Jeepney' ? 'selected' : '' }}>Jeepney
                                                    </option>
                                                    <option value="Bus"
                                                        {{ old('transportation') == 'Bus' ? 'selected' : '' }}>Bus</option>
                                                    <option value="Private Vehicle"
                                                        {{ old('transportation') == 'Private Vehicle' ? 'selected' : '' }}>
                                                        Private Vehicle</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="family_income" class="form-label">Socioeconomic Status</label>
                                                <select class="form-select" id="family_income" name="family_income">
                                                    <option value="">-- Select --</option>
                                                    <option value="Low"
                                                        {{ old('family_income') == 'Low' ? 'selected' : '' }}>Low</option>
                                                    <option value="Medium"
                                                        {{ old('family_income') == 'Medium' ? 'selected' : '' }}>Medium
                                                    </option>
                                                    <option value="High"
                                                        {{ old('family_income') == 'High' ? 'selected' : '' }}>High
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Returning Student Summary - shown when students are already selected from step 1 --}}
                            <div id="returningStudentSummary" style="display: none;">
                                <h5 class="mb-2">Selected Students</h5>
                                <p class="text-muted mb-3">Review the students you're about to re-enroll</p>

                                <div class="alert alert-info mb-3">
                                    Re-enrolling <strong id="returningStudentCount">0</strong> student(s).
                                </div>

                                <div id="nextGradeLevelBadge" class="mb-3">
                                    Promoting to: <strong id="promotingToGrade">-</strong>
                                </div>

                                <div id="selectedStudentsList" class="mb-3"
                                    style="max-height: 350px; overflow-y: auto;">
                                    <!-- Selected students will be rendered here -->
                                </div>
                            </div>

                            {{-- Existing Profile Selection - shown when "enroll" type is selected, students are loaded here --}}
                            <div id="existingProfileSelection" style="display: none;">
                                <h5 class="mb-2">Select Pending Student Profile</h5>
                                <p class="text-muted mb-3">Choose a pending student profile to enroll in their current
                                    grade level</p>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Filter by Grade Level</label>
                                        <select class="form-select form-select-sm" id="existingProfileGradeFilter">
                                            <option value="">All Grade Levels</option>
                                            @php
                                                // Filter for students with 'pending' status profile in the ACTIVE school year
                                                $activeSchoolYearIdForFilter = $currentSchoolYear
                                                    ? $currentSchoolYear->id
                                                    : null;
                                                $pendingStudentGradeLevels = $students
                                                    ->filter(function ($s) use ($activeSchoolYearIdForFilter) {
                                                        $activeYearProfile = $activeSchoolYearIdForFilter
                                                            ? $s->profiles->firstWhere(
                                                                'school_year_id',
                                                                $activeSchoolYearIdForFilter,
                                                            )
                                                            : null;
                                                        return $activeYearProfile &&
                                                            $activeYearProfile->status === 'pending';
                                                    })
                                                    ->map(function ($s) use ($activeSchoolYearIdForFilter) {
                                                        $activeYearProfile = $activeSchoolYearIdForFilter
                                                            ? $s->profiles->firstWhere(
                                                                'school_year_id',
                                                                $activeSchoolYearIdForFilter,
                                                            )
                                                            : null;
                                                        return $activeYearProfile?->gradeLevel;
                                                    })
                                                    ->filter()
                                                    ->unique('id')
                                                    ->sortBy('level');
                                            @endphp
                                            @foreach ($pendingStudentGradeLevels as $level)
                                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted">Search</label>
                                        <input type="text" class="form-control form-control-sm"
                                            id="existingProfileSearch" placeholder="Search by name, LRN, or ID...">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100"
                                            id="selectAllExistingProfiles">
                                            Select All
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected Students Counter -->
                                <div id="existingProfileCounter" class="alert alert-success py-2 mb-3"
                                    style="display: none;">
                                    <strong id="existingProfileCount">0</strong> students selected
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-danger"
                                        id="clearExistingProfileSelection">
                                        Clear all
                                    </button>
                                </div>

                                <div id="existingProfileResults" style="max-height: 350px; overflow-y: auto;">
                                    <!-- Students will be rendered by JavaScript -->
                                </div>
                            </div>

                            <div class="wizard-navigation">
                                <button type="button" class="btn btn-outline-secondary" id="step2Back">
                                    Back
                                </button>
                                <button type="button" class="btn btn-primary" id="step2Next">
                                    Continue
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Enroll to Class -->
                        <div class="wizard-panel" data-panel="3">
                            <h5 class="mb-3">Select Class</h5>

                            <div id="nextGradeLevelNotice" class="alert alert-info mb-3" style="display: none;">
                                <span id="nextGradeLevelText">Showing classes for the next grade level.</span>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Filter by Grade Level</label>
                                    <select class="form-select" id="gradeLevelFilter">
                                        <option value="">All Grade Levels</option>
                                        @php
                                            $gradeLevels = $classes
                                                ->map(fn($c) => $c->section->gradeLevel)
                                                ->unique('id')
                                                ->sortBy('id');
                                        @endphp
                                        @foreach ($gradeLevels as $level)
                                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Search Class</label>
                                    <input type="text" class="form-control" id="classSearch"
                                        placeholder="Search by section name...">
                                </div>
                            </div>

                            <div id="classesContainer" style="max-height: 400px; overflow-y: auto;">
                                @foreach ($classes->sortBy(fn($c) => [$c->section->gradeLevel->id, $c->section->name]) as $class)
                                    @php
                                        $enrolled = $class->enrollments->count();
                                        $capacity = $class->capacity;
                                        $percentage = $capacity > 0 ? ($enrolled / $capacity) * 100 : 100;
                                        $isFull = $enrolled >= $capacity;

                                        if ($percentage >= 100) {
                                            $capacityClass = 'capacity-full';
                                        } elseif ($percentage >= 80) {
                                            $capacityClass = 'capacity-high';
                                        } elseif ($percentage >= 50) {
                                            $capacityClass = 'capacity-medium';
                                        } else {
                                            $capacityClass = 'capacity-low';
                                        }
                                    @endphp
                                    <div class="class-card {{ $isFull ? 'full' : '' }}"
                                        data-class-id="{{ $class->id }}"
                                        data-grade-level="{{ $class->section->gradeLevel->id }}"
                                        data-section-name="{{ strtolower($class->section->name) }}"
                                        data-full="{{ $isFull ? '1' : '0' }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <span
                                                        class="badge bg-primary me-2">{{ $class->section->gradeLevel->name }}</span>
                                                    {{ $class->section->name }}
                                                </h6>
                                                <small class="text-muted">
                                                    School Year: {{ $currentSchoolYear->name ?? 'Current' }}
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge {{ $isFull ? 'bg-danger' : 'bg-success' }}">
                                                    {{ $enrolled }}/{{ $capacity }}
                                                </span>
                                                @if ($isFull)
                                                    <div class="small text-danger mt-1">Full</div>
                                                @else
                                                    <div class="small text-success mt-1">{{ $capacity - $enrolled }} slots
                                                        left</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="class-capacity-bar">
                                            <div class="fill {{ $capacityClass }}"
                                                style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            @empty(!$classes)
                                <span class="p-3 mb-2 rounded text-white">No classes added in this SY. Contact School
                                    Admin</span>
                            @endempty
                        </div>

                        <div class="wizard-navigation">
                            <button type="button" class="btn btn-outline-secondary" id="step3Back">
                                Back
                            </button>
                            <button type="button" class="btn btn-primary" id="step3Next" disabled>
                                Review
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Review & Submit -->
                    <div class="wizard-panel" data-panel="4">
                        <h5 class="mb-3">Review Enrollment</h5>

                        <!-- Single Student Review (for new students or single returning student) -->
                        <div id="singleStudentReview">
                            <!-- Edit Mode Toggle for Returning Students -->
                            <div id="singleEditModeToggle" class="edit-mode-toggle" style="display: none;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="singleEditModeSwitch">
                                    <label class="form-check-label" for="singleEditModeSwitch">
                                        Enable Editing
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            Student Information
                                        </div>
                                        <div class="card-body" id="summaryStudentInfo">
                                            <!-- Static view for new students -->
                                            <div id="staticStudentInfo">
                                                <div class="summary-item">
                                                    <span class="summary-label">Name</span>
                                                    <span class="summary-value" id="summaryStudentName">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="summary-label">LRN</span>
                                                    <span class="summary-value" id="summaryStudentLrn">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="summary-label">Gender</span>
                                                    <span class="summary-value" id="summaryStudentGender">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="summary-label">Birthdate</span>
                                                    <span class="summary-value" id="summaryStudentBirthdate">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="summary-label">Address</span>
                                                    <span class="summary-value" id="summaryStudentAddress">-</span>
                                                </div>
                                            </div>
                                            <!-- Editable view for returning students -->
                                            <div id="editableStudentInfo" style="display: none;">
                                                <div class="editable-row">
                                                    <span class="editable-label">First Name</span>
                                                    <div class="editable-value">
                                                        <input type="text"
                                                            class="review-editable-field single-edit-field"
                                                            name="single_student[first_name]" id="editFirstName"
                                                            disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Last Name</span>
                                                    <div class="editable-value">
                                                        <input type="text"
                                                            class="review-editable-field single-edit-field"
                                                            name="single_student[last_name]" id="editLastName"
                                                            disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">LRN</span>
                                                    <div class="editable-value">
                                                        <input type="text"
                                                            class="review-editable-field single-edit-field"
                                                            name="single_student[lrn]" id="editLrn" disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Gender</span>
                                                    <div class="editable-value">
                                                        <select class="review-editable-field single-edit-field"
                                                            name="single_student[gender]" id="editGender" disabled>
                                                            <option value="male">Male</option>
                                                            <option value="female">Female</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Birthdate</span>
                                                    <div class="editable-value">
                                                        <input type="date"
                                                            class="review-editable-field single-edit-field"
                                                            name="single_student[birthdate]" id="editBirthdate"
                                                            disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Address</span>
                                                    <div class="editable-value">
                                                        <input type="text"
                                                            class="review-editable-field single-edit-field"
                                                            name="single_student[address]" id="editAddress" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            Class Information
                                        </div>
                                        <div class="card-body">
                                            <div class="summary-item">
                                                <span class="summary-label">Grade Level</span>
                                                <span class="summary-value" id="summaryGradeLevel">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label">Section</span>
                                                <span class="summary-value" id="summarySectionName">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label">Capacity</span>
                                                <span class="summary-value" id="summaryCapacity">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enrollment Status Selection -->
                            <div class="row g-3 mt-2">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            Enrollment Status
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <!-- For new students -->
                                                <div id="newStudentStatusOptions">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input enrollment-status-radio"
                                                            type="radio" name="enrollment_status_radio"
                                                            id="statusEnrolled" value="enrolled" checked>
                                                        <label class="form-check-label" for="statusEnrolled">
                                                            <span class="badge bg-success me-1">Regular</span>
                                                            New student enrolling for the first time
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-inline mt-2">
                                                        <input class="form-check-input enrollment-status-radio"
                                                            type="radio" name="enrollment_status_radio"
                                                            id="statusTransferred" value="transferred">
                                                        <label class="form-check-label" for="statusTransferred">
                                                            <span class="badge bg-info me-1">Transferee</span>
                                                            Student transferring from another school
                                                        </label>
                                                    </div>
                                                </div>
                                                <!-- For returning students -->
                                                <div id="returningStudentStatusOptions" style="display: none;">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input enrollment-status-radio"
                                                            type="radio" name="enrollment_status_radio"
                                                            id="statusReturning" value="enrolled" checked>
                                                        <label class="form-check-label" for="statusReturning">
                                                            <span class="badge bg-success me-1">Regular</span>
                                                            Continuing from previous school year
                                                        </label>
                                                    </div>
                                                    <div id="droppedStudentInfo" class="mt-2"
                                                        style="display: none;">
                                                        <div class="alert alert-info py-2 mb-0">
                                                            <small>This student previously dropped. They are being
                                                                re-enrolled.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="guardianSummaryCard" class="mt-3">
                                <div class="card">
                                    <div class="card-header">
                                        Guardian Information
                                    </div>
                                    <div class="card-body">
                                        <!-- Static view for new students -->
                                        <div id="staticGuardianInfo">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="summary-item">
                                                        <span class="summary-label">Name</span>
                                                        <span class="summary-value" id="summaryGuardianName">-</span>
                                                    </div>
                                                    <div class="summary-item">
                                                        <span class="summary-label">Relationship</span>
                                                        <span class="summary-value"
                                                            id="summaryGuardianRelationship">-</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="summary-item">
                                                        <span class="summary-label">Email</span>
                                                        <span class="summary-value" id="summaryGuardianEmail">-</span>
                                                    </div>
                                                    <div class="summary-item">
                                                        <span class="summary-label">Phone</span>
                                                        <span class="summary-value" id="summaryGuardianPhone">-</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Editable view for returning students -->
                                        <div id="editableGuardianInfo" style="display: none;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="editable-row">
                                                        <span class="editable-label">First Name</span>
                                                        <div class="editable-value">
                                                            <input type="text"
                                                                class="review-editable-field single-edit-field"
                                                                name="single_student[guardian_first_name]"
                                                                id="editGuardianFirstName" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="editable-row">
                                                        <span class="editable-label">Last Name</span>
                                                        <div class="editable-value">
                                                            <input type="text"
                                                                class="review-editable-field single-edit-field"
                                                                name="single_student[guardian_last_name]"
                                                                id="editGuardianLastName" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="editable-row">
                                                        <span class="editable-label">Relationship</span>
                                                        <div class="editable-value">
                                                            <select class="review-editable-field single-edit-field"
                                                                name="single_student[guardian_relationship]"
                                                                id="editGuardianRelationship" disabled>
                                                                <option value="">-- Select --</option>
                                                                <option value="parent">Parent</option>
                                                                <option value="sibling">Sibling</option>
                                                                <option value="relative">Relative</option>
                                                                <option value="guardian">Guardian</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="editable-row">
                                                        <span class="editable-label">Email</span>
                                                        <div class="editable-value">
                                                            <input type="email"
                                                                class="review-editable-field single-edit-field"
                                                                name="single_student[guardian_email]"
                                                                id="editGuardianEmail" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="editable-row">
                                                        <span class="editable-label">Phone</span>
                                                        <div class="editable-value">
                                                            <input type="text"
                                                                class="review-editable-field single-edit-field"
                                                                name="single_student[guardian_phone]"
                                                                id="editGuardianPhone" disabled>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Class History Section for returning students -->
                            <div id="classHistoryCard" class="mt-3" style="display: none;">
                                <div class="card">
                                    <div class="card-header">
                                        Class History & Attendance
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="class-history-table" id="singleClassHistoryTable">
                                                <thead>
                                                    <tr>
                                                        <th>School Year</th>
                                                        <th>Grade</th>
                                                        <th>Section</th>
                                                        <th>Attendance</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="singleClassHistoryBody">
                                                    <!-- Will be populated dynamically -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3">
                                <strong>Note:</strong> The student will be enrolled in the selected class.
                                <span id="guardianEmailNote">An email with login credentials will be sent to the
                                    guardian.</span>
                            </div>
                        </div>

                        <!-- Multi-Student Carousel Review (for multiple returning students) -->
                        <div id="multiStudentReview" style="display: none;">
                            <div class="student-carousel">
                                <!-- Carousel Header -->
                                <div class="carousel-header">
                                    <div>
                                        <div class="carousel-counter">
                                            <span id="currentStudentIndex">1</span> of <span
                                                id="totalStudentsCount">0</span> Students
                                        </div>
                                    </div>
                                    <div class="carousel-nav-buttons">
                                        <button type="button" class="carousel-nav-btn" id="carouselPrev" disabled>
                                            ←
                                        </button>
                                        <button type="button" class="carousel-nav-btn" id="carouselNext">
                                            →
                                        </button>
                                    </div>
                                </div>

                                <!-- Class Info (Shared for all students) -->
                                <div class="card mb-3">
                                    <div class="card-header py-2">
                                        Enrolling to Class
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong id="carouselGradeLevel">-</strong> -
                                                <span id="carouselSectionName">-</span>
                                            </div>
                                            <span class="badge bg-secondary" id="carouselCapacity">-</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Carousel Slides Container -->
                                <div class="carousel-slides-container" id="carouselSlidesContainer">
                                    <!-- Slides will be dynamically inserted here -->
                                </div>

                                <!-- Progress Dots -->
                                <div class="carousel-progress" id="carouselProgress">
                                    <!-- Dots will be dynamically inserted here -->
                                </div>

                                <!-- Confirmation Summary -->
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted">Confirmed Students:</span>
                                        <span class="badge bg-success fs-6" id="confirmedCountBadge">0 / 0</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            id="confirmProgressBar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <!-- All Confirmed Message -->
                                <div class="all-confirmed-message mt-3" id="allConfirmedMessage">
                                    <strong>All Students Reviewed!</strong>
                                    <p class="mb-0">You can now submit the enrollment.</p>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <strong>Bulk Enrollment:</strong> All confirmed students will be enrolled to <strong
                                    id="bulkClassName">-</strong>.
                            </div>
                        </div>

                        <!-- Sticky Submit Container for Step 4 -->
                        <div id="stickySubmitContainer" class="sticky-submit-container">
                            <div class="wizard-navigation">
                                <button type="button" class="btn btn-outline-secondary" id="step4Back">
                                    Back
                                </button>
                                <button type="submit" class="btn btn-success" id="submitEnrollment">
                                    Confirm Enrollment
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<!-- My Enrollments Modal -->
<div class="modal fade" id="enrollmentModal" tabindex="-1" aria-labelledby="enrollmentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrollmentModalLabel">
                    {{ $enrollmentOwnerLabel }} This Year
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($teacherEnrollments->isEmpty())
                    <div class="text-center py-4">
                        <h6 class="text-muted">No Enrollments Yet</h6>
                        <p class="text-muted small">Students you enroll will appear here.</p>
                    </div>
                @else
                    <div class="accordion" id="enrollmentAccordion">
                        @foreach ($teacherEnrollments as $classId => $enrollments)
                            @php $class = $enrollments->first()->class; @endphp
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#class{{ $classId }}">
                                        <span
                                            class="badge bg-primary me-2">{{ $class->section->gradeLevel->name }}</span>
                                        {{ $class->section->name }}
                                        <span class="badge bg-secondary ms-2">{{ $enrollments->count() }}
                                            students</span>
                                    </button>
                                </h2>
                                <div id="class{{ $classId }}"
                                    class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                    data-bs-parent="#enrollmentAccordion">
                                    <div class="accordion-body p-0">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>LRN</th>
                                                    <th>Student Name</th>
                                                    <th>Enrolled On</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($enrollments as $enrollment)
                                                    <tr>
                                                        <td>{{ $enrollment->student->lrn ?? 'N/A' }}</td>
                                                        <td>{{ $enrollment->student->first_name }}
                                                            {{ $enrollment->student->last_name }}</td>
                                                        <td>{{ $enrollment->created_at->format('M d, Y h:i A') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // State management
        const state = {
            currentStep: 1,
            studentType: null, // 'new', 'returning', or 'enroll'
            selectedStudentId: null, // For single new student
            selectedStudentData: null,
            selectedStudentIds: [], // For multi-select returning students
            selectedStudentsData: [], // Array of selected student objects
            selectedClassId: null,
            selectedClassData: null
        };

        // All returning students data for search
        @php
            $allGradeLevels = \App\Models\GradeLevel::orderBy('level')->get();
            $activeSchoolYearId = $currentSchoolYear ? $currentSchoolYear->id : null;
            $studentsJson = $students
                ->map(function ($s) use ($allGradeLevels, $activeSchoolYearId) {
                    $lastProfile = $s->profiles->sortByDesc('school_year_id')->first();
                    // Get the profile for the active school year (for pending status check)
                    $activeYearProfile = $activeSchoolYearId ? $s->profiles->firstWhere('school_year_id', $activeSchoolYearId) : null;
                    $lastGradeLevel = $lastProfile ? $lastProfile->gradeLevel : null;
                    $nextGradeLevel = null;

                    if ($lastGradeLevel) {
                        // Find the next grade level by level order
                        $nextGradeLevel = $allGradeLevels->first(fn($gl) => $gl->level > $lastGradeLevel->level);
                    }

                    // Get guardian info
                    $guardian = $s->guardian;
                    $guardianUser = $guardian ? $guardian->user : null;

                    // Get enrollment history with class and attendance data per school year
                    $enrollmentHistory = $s
                        ->enrollments()
                        ->with(['class.section.gradeLevel', 'schoolYear'])
                        ->orderBy('school_year_id', 'desc')
                        ->get()
                        ->map(function ($enrollment) use ($s) {
                            // Get attendance stats for this enrollment
                            $attendanceQuery = \App\Models\Attendance::where('student_id', $s->id)->where('school_year_id', $enrollment->school_year_id);

                            $present = (clone $attendanceQuery)->where('status', 'present')->count();
                            $absent = (clone $attendanceQuery)->where('status', 'absent')->count();
                            $late = (clone $attendanceQuery)->where('status', 'late')->count();
                            $total = $present + $absent + $late;

                            return [
                                'school_year' => $enrollment->schoolYear ? $enrollment->schoolYear->name : 'N/A',
                                'school_year_id' => $enrollment->school_year_id,
                                'grade_level' => $enrollment->class && $enrollment->class->section && $enrollment->class->section->gradeLevel ? $enrollment->class->section->gradeLevel->name : 'N/A',
                                'section' => $enrollment->class && $enrollment->class->section ? $enrollment->class->section->name : 'N/A',
                                'status' => $enrollment->status,
                                'attendance' => [
                                    'present' => $present,
                                    'absent' => $absent,
                                    'late' => $late,
                                    'total' => $total,
                                    'total_late_absent' => $late + $absent,
                                ],
                            ];
                        });

                    return [
                        'id' => $s->id,
                        'first_name' => $s->first_name,
                        'last_name' => $s->last_name,
                        'lrn' => $s->lrn,
                        'gender' => $s->gender,
                        'birthdate' => $s->birthdate ? $s->birthdate->format('Y-m-d') : null,
                        'birthdate_display' => $s->birthdate ? $s->birthdate->format('M d, Y') : 'N/A',
                        'address' => $s->address,
                        'distance_km' => $s->distance_km,
                        'transportation' => $s->transportation,
                        'family_income' => $s->family_income,
                        'last_grade' => $lastGradeLevel ? $lastGradeLevel->name : 'N/A',
                        'last_grade_level_id' => $lastGradeLevel ? $lastGradeLevel->id : null,
                        'next_grade_level_id' => $nextGradeLevel ? $nextGradeLevel->id : null,
                        'next_grade_level_name' => $nextGradeLevel ? $nextGradeLevel->name : null,
                        // Use active school year profile status for 'pending' check, otherwise use last profile status
                        'status' => $activeYearProfile ? $activeYearProfile->status : ($lastProfile ? $lastProfile->status : null),
                        'active_year_profile_status' => $activeYearProfile ? $activeYearProfile->status : null,
                        'created_by_teacher_id' => $activeYearProfile ? $activeYearProfile->created_by_teacher_id : ($lastProfile ? $lastProfile->created_by_teacher_id : null),
                        'guardian' => [
                            'id' => $guardian ? $guardian->id : null,
                            'first_name' => $guardianUser ? $guardianUser->first_name : null,
                            'last_name' => $guardianUser ? $guardianUser->last_name : null,
                            'email' => $guardianUser ? $guardianUser->email : null,
                            'phone' => $guardian ? $guardian->phone : null,
                            'relationship' => $guardian ? $guardian->relationship : null,
                        ],
                        'enrollment_history' => $enrollmentHistory,
                    ];
                })
                ->values();

            $classesJson = $classes
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'grade_level' => $c->section->gradeLevel->name,
                        'grade_level_id' => $c->section->gradeLevel->id,
                        'section_name' => $c->section->name,
                        'enrolled' => $c->enrollments->count(),
                        'capacity' => $c->capacity,
                    ];
                })
                ->values();
        @endphp
        const allStudents = @json($studentsJson);
        const isEnrollmentReadOnly = @json($isEnrollmentReadOnly);

        // All classes data
        const allClasses = @json($classesJson);

        // Current teacher ID for filtering pending profiles
        const currentTeacherId = @json($currentTeacherId);

        // Previous school year ID for filtering returning students
        const previousSchoolYearId = @json($previousSchoolYear?->id);
        const oldWizardInput = @json($oldWizardInput);
        const wizardErrorHints = @json($wizardErrorHints);

        // DOM elements
        const wizardSteps = document.querySelectorAll('.wizard-step');
        const wizardPanels = document.querySelectorAll('.wizard-panel');
        const studentTypeCards = document.querySelectorAll('.student-type-card');
        const studentResultCards = document.querySelectorAll('.student-result-card');
        const classCards = document.querySelectorAll('.class-card');

        // Navigation buttons
        const step1Next = document.getElementById('step1Next');
        const step2Back = document.getElementById('step2Back');
        const step2Next = document.getElementById('step2Next');
        const step3Back = document.getElementById('step3Back');
        const step3Next = document.getElementById('step3Next');
        const step4Back = document.getElementById('step4Back');

        // Form inputs
        const studentTypeInput = document.getElementById('studentTypeInput');
        const studentIdInput = document.getElementById('studentIdInput');
        const classIdInput = document.getElementById('classIdInput');
        const enrollmentForm = document.getElementById('enrollmentForm');

        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        if (isEnrollmentReadOnly) {
            document.querySelectorAll('#enrollmentWizardCard button').forEach((button) => {
                button.disabled = true;
                button.classList.add('disabled');
            });
        }

        // Helper function to enable/disable required fields in new student form
        function toggleNewStudentFormRequired(enable) {
            const newStudentForm = document.getElementById('newStudentForm');
            if (!newStudentForm) return;

            const requiredFields = newStudentForm.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (enable) {
                    field.disabled = false;
                    field.removeAttribute('data-was-required');
                } else {
                    field.disabled = true;
                    field.setAttribute('data-was-required', 'true');
                }
            });
        }

        // Step navigation functions
        function goToStep(step) {
            state.currentStep = step;

            // Update stepper
            wizardSteps.forEach((ws, index) => {
                ws.classList.remove('active', 'completed');
                if (index + 1 < step) ws.classList.add('completed');
                if (index + 1 === step) ws.classList.add('active');
            });

            // Update panels
            wizardPanels.forEach(panel => {
                panel.classList.remove('active');
                if (parseInt(panel.dataset.panel) === step) {
                    panel.classList.add('active');
                }
            });

            // Re-initialize feather icons for new panel
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            // Special handling for step 1 - sync selection state with student cards
            if (step === 1) {
                // Re-render student results to reflect current selection state
                filterAndRenderStudents();
            }

            // Special handling for step 2 (Profile Student)
            if (step === 2) {
                const newStudentForm = document.getElementById('newStudentForm');
                const returningSummary = document.getElementById('returningStudentSummary');
                const existingProfileSelection = document.getElementById('existingProfileSelection');

                // Hide all sections first
                if (newStudentForm) newStudentForm.style.display = 'none';
                if (returningSummary) returningSummary.style.display = 'none';
                if (existingProfileSelection) existingProfileSelection.style.display = 'none';

                if (state.studentType === 'returning') {
                    // Returning students were selected in step 1 - show summary
                    returningSummary.style.display = 'block';
                    updateReturningSummary();
                    toggleNewStudentFormRequired(false);
                    // Enable step2Next since students are already selected
                    step2Next.disabled = false;
                } else if (state.studentType === 'enroll') {
                    // Existing profile - load students here in step 2
                    existingProfileSelection.style.display = 'block';
                    filterAndRenderExistingProfiles();
                    toggleNewStudentFormRequired(false);
                    // Disable step2Next until students are selected
                    step2Next.disabled = state.selectedStudentIds.length === 0;
                } else {
                    // New student - show form
                    newStudentForm.style.display = 'block';
                    toggleNewStudentFormRequired(true);
                    step2Next.disabled = false;
                }
            }

            // Special handling for step 3 (Enroll to Class) - filter by next grade level for returning students
            if (step === 3) {
                updateGradeLevelFilterForReturning();
            }

            // Update form action for returning/enroll students and toggle required fields
            if (state.studentType === 'returning' || state.studentType === 'enroll') {
                enrollmentForm.action = '{{ route($enrollmentStorePastStudentRoute) }}';
                // Ensure new student form fields are disabled for returning/enroll students
                toggleNewStudentFormRequired(false);
            } else {
                enrollmentForm.action = '{{ route($enrollmentStoreRoute) }}';
            }

            // Step 4: Update summary
            if (step === 4) {
                updateFinalSummary();
                updateEnrollmentStatusOptions();
            }
        }

        function updateReturningSummary() {
            const listContainer = document.getElementById('selectedStudentsList');
            const countEl = document.getElementById('returningStudentCount');
            const promotingToEl = document.getElementById('promotingToGrade');

            if (state.selectedStudentsData.length > 0) {
                countEl.textContent = state.selectedStudentsData.length;

                // Get next grade level from first student (all should be same if filtered)
                const nextGrade = state.selectedStudentsData[0]?.next_grade_level_name || 'Next Grade';
                promotingToEl.textContent = nextGrade;

                // Render all selected students
                let html = '';
                state.selectedStudentsData.forEach((s, index) => {
                    html += `
                            <div class="card mb-2">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">${s.first_name} ${s.last_name}</div>
                                            <div class="small text-muted">
                                                LRN: ${s.lrn || 'N/A'} • From: ${s.last_grade} → ${s.next_grade_level_name || 'N/A'}
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-student-btn" data-student-id="${s.id}" title="Remove">
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                });

                listContainer.innerHTML = html;

                // Add remove handlers
                listContainer.querySelectorAll('.remove-student-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const studentId = parseInt(this.dataset.studentId);
                        state.selectedStudentIds = state.selectedStudentIds.filter(id => id !==
                            studentId);
                        state.selectedStudentsData = state.selectedStudentsData.filter(s => s
                            .id !== studentId);
                        studentIdInput.value = state.selectedStudentIds.join(',');

                        if (state.selectedStudentIds.length === 0) {
                            // Go back to step 1 if no students left
                            goToStep(1);
                        } else {
                            state.selectedStudentId = state.selectedStudentIds[0];
                            state.selectedStudentData = state.selectedStudentsData[0];
                            updateReturningSummary();
                        }
                        updateSelectedCounter();
                    });
                });

                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        }

        // Carousel state for multi-student review
        const carouselState = {
            currentIndex: 0,
            confirmedStudents: new Set(),
        };

        function updateFinalSummary() {
            const isMultiStudent = (state.studentType === 'returning' || state.studentType === 'enroll') &&
                state.selectedStudentsData.length > 1;

            // Show/hide appropriate review sections
            document.getElementById('singleStudentReview').style.display = isMultiStudent ? 'none' : 'block';
            document.getElementById('multiStudentReview').style.display = isMultiStudent ? 'block' : 'none';

            if (isMultiStudent) {
                // Multi-student carousel
                initializeCarousel();
            } else {
                // Single student review
                if ((state.studentType === 'returning' || state.studentType === 'enroll') && state
                    .selectedStudentData) {
                    const s = state.selectedStudentData;
                    const guardian = s.guardian || {};

                    // Show edit mode toggle and editable fields for returning students
                    document.getElementById('singleEditModeToggle').style.display = 'flex';
                    document.getElementById('staticStudentInfo').style.display = 'none';
                    document.getElementById('editableStudentInfo').style.display = 'block';
                    document.getElementById('staticGuardianInfo').style.display = 'none';
                    document.getElementById('editableGuardianInfo').style.display = 'block';

                    // Populate editable student fields
                    document.getElementById('editFirstName').value = s.first_name || '';
                    document.getElementById('editLastName').value = s.last_name || '';
                    document.getElementById('editLrn').value = s.lrn || '';
                    document.getElementById('editGender').value = s.gender || 'male';
                    document.getElementById('editBirthdate').value = s.birthdate || '';
                    document.getElementById('editAddress').value = s.address || '';

                    // Populate editable guardian fields
                    document.getElementById('editGuardianFirstName').value = guardian.first_name || '';
                    document.getElementById('editGuardianLastName').value = guardian.last_name || '';
                    document.getElementById('editGuardianRelationship').value = guardian.relationship || '';
                    document.getElementById('editGuardianEmail').value = guardian.email || '';
                    document.getElementById('editGuardianPhone').value = guardian.phone || '';

                    // Show guardian card with editable view
                    document.getElementById('guardianSummaryCard').style.display = 'block';
                    document.getElementById('guardianEmailNote').textContent =
                        'Guardian information will be retained.';

                    // Show and populate class history
                    const classHistoryCard = document.getElementById('classHistoryCard');
                    const historyBody = document.getElementById('singleClassHistoryBody');

                    if (s.enrollment_history && s.enrollment_history.length > 0) {
                        classHistoryCard.style.display = 'block';
                        let historyHtml = '';
                        s.enrollment_history.forEach(record => {
                            const att = record.attendance || {};
                            historyHtml += `
                                    <tr>
                                        <td><strong>${record.school_year || 'N/A'}</strong></td>
                                        <td>${record.grade_level || 'N/A'}</td>
                                        <td>${record.section || 'N/A'}</td>
                                        <td>
                                            <div class="attendance-stats">
                                                <span class="attendance-stat present" title="Present">P: ${att.present || 0}</span>
                                                <span class="attendance-stat absent" title="Absent">A: ${att.absent || 0}</span>
                                                <span class="attendance-stat late" title="Late">L: ${att.late || 0}</span>
                                                <span class="attendance-stat total-issues" title="Late + Absent">L+A: ${att.total_late_absent || 0}</span>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                        });
                        historyBody.innerHTML = historyHtml;
                    } else {
                        classHistoryCard.style.display = 'none';
                    }

                    // Setup single edit mode toggle listener
                    const editSwitch = document.getElementById('singleEditModeSwitch');
                    editSwitch.checked = false;
                    editSwitch.onchange = function() {
                        const fields = document.querySelectorAll('.single-edit-field');
                        fields.forEach(field => {
                            field.disabled = !this.checked;
                        });
                    };

                    // Ensure the student_id hidden input is set for single returning student
                    studentIdInput.value = state.selectedStudentIds.join(',');
                } else {
                    // New student - show static view
                    document.getElementById('singleEditModeToggle').style.display = 'none';
                    document.getElementById('staticStudentInfo').style.display = 'block';
                    document.getElementById('editableStudentInfo').style.display = 'none';
                    document.getElementById('staticGuardianInfo').style.display = 'block';
                    document.getElementById('editableGuardianInfo').style.display = 'none';
                    document.getElementById('classHistoryCard').style.display = 'none';

                    const firstName = document.getElementById('first_name').value;
                    const lastName = document.getElementById('last_name').value;
                    document.getElementById('summaryStudentName').textContent = firstName + ' ' + lastName;
                    document.getElementById('summaryStudentLrn').textContent = document.getElementById('lrn')
                        .value || 'N/A';
                    document.getElementById('summaryStudentGender').textContent = document.getElementById(
                            'gender')
                        .value;
                    document.getElementById('summaryStudentBirthdate').textContent = document.getElementById(
                        'birthdate').value;
                    document.getElementById('summaryStudentAddress').textContent = document.getElementById(
                        'address').value || 'N/A';

                    // Guardian info
                    document.getElementById('guardianSummaryCard').style.display = 'block';
                    document.getElementById('summaryGuardianName').textContent =
                        document.getElementById('guardian_first_name').value + ' ' +
                        document.getElementById('guardian_last_name').value;
                    document.getElementById('summaryGuardianRelationship').textContent =
                        document.getElementById('guardian_relationship').value;
                    document.getElementById('summaryGuardianEmail').textContent =
                        document.getElementById('guardian_email').value;
                    document.getElementById('summaryGuardianPhone').textContent =
                        document.getElementById('guardian_phone').value;
                    document.getElementById('guardianEmailNote').textContent =
                        'An email with login credentials will be sent to the guardian.';
                }

                // Class info for single student
                if (state.selectedClassData) {
                    const c = state.selectedClassData;
                    document.getElementById('summaryGradeLevel').textContent = c.grade_level;
                    document.getElementById('summarySectionName').textContent = c.section_name;
                    document.getElementById('summaryCapacity').textContent = c.enrolled + '/' + c.capacity;

                    // Ensure class_id hidden input is set
                    classIdInput.value = state.selectedClassId;
                }
            }

            // Re-initialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        function initializeCarousel() {
            const container = document.getElementById('carouselSlidesContainer');
            const progressContainer = document.getElementById('carouselProgress');
            const students = state.selectedStudentsData;

            // Reset carousel state
            carouselState.currentIndex = 0;
            carouselState.confirmedStudents = new Set();

            // Update total count
            document.getElementById('totalStudentsCount').textContent = students.length;

            // Update class info
            if (state.selectedClassData) {
                const c = state.selectedClassData;
                document.getElementById('carouselGradeLevel').textContent = c.grade_level;
                document.getElementById('carouselSectionName').textContent = c.section_name;
                document.getElementById('carouselCapacity').textContent =
                    `${c.enrolled + students.length}/${c.capacity}`;
                document.getElementById('bulkClassName').textContent = `${c.grade_level} - ${c.section_name}`;
            }

            // Helper function to generate class history HTML
            function generateClassHistoryHtml(enrollmentHistory) {
                if (!enrollmentHistory || enrollmentHistory.length === 0) {
                    return '<p class="text-muted small mb-0">No enrollment history available</p>';
                }

                let html = '<table class="class-history-table"><thead><tr>';
                html += '<th>School Year</th><th>Grade</th><th>Section</th><th>Attendance</th>';
                html += '</tr></thead><tbody>';

                enrollmentHistory.forEach(record => {
                    const att = record.attendance || {};
                    html += `<tr>
                            <td><strong>${record.school_year || 'N/A'}</strong></td>
                            <td>${record.grade_level || 'N/A'}</td>
                            <td>${record.section || 'N/A'}</td>
                            <td>
                                <div class="attendance-stats">
                                    <span class="attendance-stat present" title="Present">P: ${att.present || 0}</span>
                                    <span class="attendance-stat absent" title="Absent">A: ${att.absent || 0}</span>
                                    <span class="attendance-stat late" title="Late">L: ${att.late || 0}</span>
                                    <span class="attendance-stat total-issues" title="Late + Absent">L+A: ${att.total_late_absent || 0}</span>
                                </div>
                            </td>
                        </tr>`;
                });

                html += '</tbody></table>';
                return html;
            }

            // Build slides
            let slidesHtml = '';
            students.forEach((s, index) => {
                const guardian = s.guardian || {};

                slidesHtml += `
                        <div class="carousel-slide ${index === 0 ? 'active' : ''}" data-slide-index="${index}" data-student-id="${s.id}">
                            <div class="student-review-card">
                                <!-- Student Header -->
                                <div class="student-header">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">${s.first_name} ${s.last_name}</h5>
                                        <div class="text-muted">
                                            <span class="badge bg-secondary me-1">LRN: ${s.lrn || 'N/A'}</span>
                                            <span class="badge bg-info">${s.last_grade} → ${s.next_grade_level_name || 'N/A'}</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark fs-6">#${index + 1}</span>
                                    </div>
                                </div>

                                <!-- Edit Mode Toggle -->
                                <div class="edit-mode-toggle">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input edit-mode-switch" type="checkbox" id="editMode_${s.id}" data-student-id="${s.id}">
                                        <label class="form-check-label" for="editMode_${s.id}">
                                            Enable Editing
                                        </label>
                                    </div>
                                </div>

                                <!-- Student Information Section -->
                                <div class="review-section">
                                    <div class="review-section-header" data-toggle-section="student_${s.id}">
                                        <span>Student Information</span>
                                        <span class="toggle-indicator">▼</span>
                                    </div>
                                    <div class="review-section-body" id="section_student_${s.id}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="editable-row">
                                                    <span class="editable-label">First Name</span>
                                                    <div class="editable-value">
                                                        <input type="text" class="review-editable-field"
                                                            name="students[${s.id}][first_name]"
                                                            value="${s.first_name}" disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Last Name</span>
                                                    <div class="editable-value">
                                                        <input type="text" class="review-editable-field"
                                                            name="students[${s.id}][last_name]"
                                                            value="${s.last_name}" disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">LRN</span>
                                                    <div class="editable-value">
                                                        <input type="text" class="review-editable-field"
                                                            name="students[${s.id}][lrn]"
                                                            value="${s.lrn || ''}" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="editable-row">
                                                    <span class="editable-label">Gender</span>
                                                    <div class="editable-value">
                                                        <select class="review-editable-field"
                                                            name="students[${s.id}][gender]" disabled>
                                                            <option value="male" ${s.gender === 'male' ? 'selected' : ''}>Male</option>
                                                            <option value="female" ${s.gender === 'female' ? 'selected' : ''}>Female</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Birthdate</span>
                                                    <div class="editable-value">
                                                        <input type="date" class="review-editable-field"
                                                            name="students[${s.id}][birthdate]"
                                                            value="${s.birthdate || ''}" disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Address</span>
                                                    <div class="editable-value">
                                                        <input type="text" class="review-editable-field"
                                                            name="students[${s.id}][address]"
                                                            value="${s.address || ''}" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guardian Information Section -->
                                <div class="review-section">
                                    <div class="review-section-header" data-toggle-section="guardian_${s.id}">
                                        <span>Guardian Information</span>
                                        <span class="toggle-indicator">▼</span>
                                    </div>
                                    <div class="review-section-body" id="section_guardian_${s.id}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="editable-row">
                                                    <span class="editable-label">First Name</span>
                                                    <div class="editable-value">
                                                        <input type="text" class="review-editable-field"
                                                            name="students[${s.id}][guardian_first_name]"
                                                            value="${guardian.first_name || ''}" disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Last Name</span>
                                                    <div class="editable-value">
                                                        <input type="text" class="review-editable-field"
                                                            name="students[${s.id}][guardian_last_name]"
                                                            value="${guardian.last_name || ''}" disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Relationship</span>
                                                    <div class="editable-value">
                                                        <select class="review-editable-field"
                                                            name="students[${s.id}][guardian_relationship]" disabled>
                                                            <option value="">-- Select --</option>
                                                            <option value="parent" ${guardian.relationship === 'parent' ? 'selected' : ''}>Parent</option>
                                                            <option value="sibling" ${guardian.relationship === 'sibling' ? 'selected' : ''}>Sibling</option>
                                                            <option value="relative" ${guardian.relationship === 'relative' ? 'selected' : ''}>Relative</option>
                                                            <option value="guardian" ${guardian.relationship === 'guardian' ? 'selected' : ''}>Guardian</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="editable-row">
                                                    <span class="editable-label">Email</span>
                                                    <div class="editable-value">
                                                        <input type="email" class="review-editable-field"
                                                            name="students[${s.id}][guardian_email]"
                                                            value="${guardian.email || ''}" disabled>
                                                    </div>
                                                </div>
                                                <div class="editable-row">
                                                    <span class="editable-label">Phone</span>
                                                    <div class="editable-value">
                                                        <input type="text" class="review-editable-field"
                                                            name="students[${s.id}][guardian_phone]"
                                                            value="${guardian.phone || ''}" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Class History Section -->
                                <div class="review-section">
                                    <div class="review-section-header" data-toggle-section="history_${s.id}">
                                        <span>Class History & Attendance</span>
                                        <span class="toggle-indicator">▼</span>
                                    </div>
                                    <div class="review-section-body" id="section_history_${s.id}">
                                        ${generateClassHistoryHtml(s.enrollment_history)}
                                    </div>
                                </div>

                                <!-- Confirm Button -->
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-outline-success confirm-student-btn" data-student-id="${s.id}" data-index="${index}">
                                        <span class="btn-text">Confirm This Student</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
            });
            container.innerHTML = slidesHtml;

            // Build progress dots
            let dotsHtml = '';
            students.forEach((s, index) => {
                dotsHtml +=
                    `<div class="progress-dot ${index === 0 ? 'active' : ''}" data-dot-index="${index}"></div>`;
            });
            progressContainer.innerHTML = dotsHtml;

            // Setup event listeners
            setupCarouselEvents();
            setupEditModeListeners();
            setupSectionToggleListeners();
            updateCarouselUI();
            updateConfirmationProgress(); // Initialize the confirmation state

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        function setupCarouselEvents() {
            // Carousel navigation - remove old listeners by cloning elements
            const prevBtn = document.getElementById('carouselPrev');
            const nextBtn = document.getElementById('carouselNext');

            if (prevBtn) {
                const newPrevBtn = prevBtn.cloneNode(true);
                prevBtn.parentNode.replaceChild(newPrevBtn, prevBtn);
                newPrevBtn.addEventListener('click', () => navigateCarousel(-1));
            }

            if (nextBtn) {
                const newNextBtn = nextBtn.cloneNode(true);
                nextBtn.parentNode.replaceChild(newNextBtn, nextBtn);
                newNextBtn.addEventListener('click', () => navigateCarousel(1));
            }

            // Progress dots (dynamically created, so no duplicate listener issue)
            document.querySelectorAll('#carouselProgress .progress-dot').forEach(dot => {
                dot.addEventListener('click', function() {
                    const index = parseInt(this.dataset.dotIndex);
                    goToSlide(index);
                });
            });

            // Confirm buttons (dynamically created, so no duplicate listener issue)
            document.querySelectorAll('.confirm-student-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const studentId = parseInt(this.dataset.studentId);
                    const index = parseInt(this.dataset.index);
                    toggleStudentConfirmation(studentId, index, this);
                });
            });
        }

        // Setup edit mode toggle listeners
        function setupEditModeListeners() {
            document.querySelectorAll('.edit-mode-switch').forEach(switchEl => {
                switchEl.addEventListener('change', function() {
                    const studentId = this.dataset.studentId;
                    const slide = this.closest('.carousel-slide');
                    const fields = slide.querySelectorAll('.review-editable-field');

                    if (this.checked) {
                        // Enable editing
                        fields.forEach(field => {
                            field.disabled = false;
                        });
                        // Mark student as having edits
                        if (!state.editedStudents) state.editedStudents = new Set();
                        state.editedStudents.add(parseInt(studentId));
                    } else {
                        // Disable editing
                        fields.forEach(field => {
                            field.disabled = true;
                        });
                    }
                });
            });
        }

        // Setup collapsible section toggle listeners
        function setupSectionToggleListeners() {
            document.querySelectorAll('.review-section-header[data-toggle-section]').forEach(header => {
                header.addEventListener('click', function() {
                    const sectionId = this.dataset.toggleSection;
                    const body = document.getElementById('section_' + sectionId);
                    const indicator = this.querySelector('.toggle-indicator');

                    if (body) {
                        body.classList.toggle('collapsed');
                        if (indicator) {
                            indicator.textContent = body.classList.contains('collapsed') ? '▶' :
                                '▼';
                        }
                    }
                });
            });
        }

        // Collect edited student data for form submission
        function collectEditedStudentData() {
            const editedData = {};

            document.querySelectorAll('.carousel-slide').forEach(slide => {
                const studentId = slide.dataset.studentId;
                if (!studentId) return;

                const fields = slide.querySelectorAll('.review-editable-field:not(:disabled)');
                if (fields.length === 0) return; // No edits for this student

                editedData[studentId] = {};
                fields.forEach(field => {
                    const name = field.name;
                    // Extract field name from students[id][field_name]
                    const match = name.match(/students\[\d+\]\[(\w+)\]/);
                    if (match) {
                        editedData[studentId][match[1]] = field.value;
                    }
                });
            });

            return editedData;
        }

        function navigateCarousel(direction) {
            const newIndex = carouselState.currentIndex + direction;
            if (newIndex >= 0 && newIndex < state.selectedStudentsData.length) {
                goToSlide(newIndex);
            }
        }

        function goToSlide(index) {
            carouselState.currentIndex = index;

            // Update slides
            document.querySelectorAll('.carousel-slide').forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });

            // Update dots
            document.querySelectorAll('#carouselProgress .progress-dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });

            updateCarouselUI();
        }

        function toggleStudentConfirmation(studentId, index, btn) {
            console.log('toggleStudentConfirmation called:', {
                studentId,
                index,
                confirmedStudents: Array.from(carouselState.confirmedStudents)
            });

            if (carouselState.confirmedStudents.has(studentId)) {
                carouselState.confirmedStudents.delete(studentId);
                btn.classList.remove('confirmed');
                btn.querySelector('.btn-text').textContent = 'Confirm This Student';
                document.querySelectorAll('#carouselProgress .progress-dot')[index]?.classList.remove(
                    'confirmed');
            } else {
                carouselState.confirmedStudents.add(studentId);
                btn.classList.add('confirmed');
                btn.querySelector('.btn-text').textContent = 'Confirmed ✓';
                document.querySelectorAll('#carouselProgress .progress-dot')[index]?.classList.add('confirmed');

                // Auto-advance to next unconfirmed student
                const nextUnconfirmed = findNextUnconfirmedIndex(index);
                if (nextUnconfirmed !== -1 && nextUnconfirmed !== index) {
                    setTimeout(() => goToSlide(nextUnconfirmed), 300);
                }
            }

            updateConfirmationProgress();
            console.log('After toggle:', {
                confirmedStudents: Array.from(carouselState.confirmedStudents)
            });
        }

        function findNextUnconfirmedIndex(currentIndex) {
            const students = state.selectedStudentsData;
            // First try to find next unconfirmed after current
            for (let i = currentIndex + 1; i < students.length; i++) {
                if (!carouselState.confirmedStudents.has(students[i].id)) {
                    return i;
                }
            }
            // Then try from beginning
            for (let i = 0; i < currentIndex; i++) {
                if (!carouselState.confirmedStudents.has(students[i].id)) {
                    return i;
                }
            }
            return -1; // All confirmed
        }

        function updateCarouselUI() {
            const current = carouselState.currentIndex;
            const total = state.selectedStudentsData.length;

            document.getElementById('currentStudentIndex').textContent = current + 1;
            document.getElementById('carouselPrev').disabled = current === 0;
            document.getElementById('carouselNext').disabled = current === total - 1;
        }

        function updateConfirmationProgress() {
            const confirmed = carouselState.confirmedStudents.size;
            const total = state.selectedStudentsData.length;
            const percentage = (confirmed / total) * 100;

            document.getElementById('confirmedCountBadge').textContent = `${confirmed} / ${total}`;
            document.getElementById('confirmProgressBar').style.width = `${percentage}%`;

            const allConfirmedMsg = document.getElementById('allConfirmedMessage');
            const submitBtn = document.getElementById('submitEnrollment');
            const stickyContainer = document.getElementById('stickySubmitContainer');

            // Update all individual confirm buttons to show correct state
            document.querySelectorAll('.confirm-student-btn').forEach(btn => {
                const studentId = parseInt(btn.dataset.studentId);
                if (carouselState.confirmedStudents.has(studentId)) {
                    btn.classList.add('confirmed');
                    btn.querySelector('.btn-text').textContent = 'Confirmed ✓';
                } else {
                    btn.classList.remove('confirmed');
                    btn.querySelector('.btn-text').textContent = 'Confirm This Student';
                }
            });

            if (confirmed === total && total > 0) {
                allConfirmedMsg.classList.add('show');
                stickyContainer?.classList.add('all-confirmed');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-feather="check-circle" class="me-2"></i>Enroll All ' + total +
                    ' Students';
            } else {
                allConfirmedMsg.classList.remove('show');
                stickyContainer?.classList.remove('all-confirmed');
                if (confirmed > 0) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `Enroll ${confirmed} of ${total} Students`;
                } else {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Confirm Students to Continue';
                }
            }

            // Update hidden input to only include confirmed students
            if (confirmed > 0) {
                const confirmedIds = Array.from(carouselState.confirmedStudents);
                studentIdInput.value = confirmedIds.join(',');
            } else {
                studentIdInput.value = '';
            }

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        // Student type selection
        studentTypeCards.forEach(card => {
            card.addEventListener('click', function() {
                studentTypeCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                state.studentType = this.dataset.type;
                state.selectedStudentId = null;
                state.selectedStudentData = null;
                state.selectedStudentIds = [];
                state.selectedStudentsData = [];
                studentTypeInput.value = state.studentType;
                studentIdInput.value = '';

                // For "returning" type, require student selection before enabling continue
                // For "new" and "enroll" types, enable continue immediately
                if (state.studentType === 'returning') {
                    step1Next.disabled = true; // Will be enabled when a student is selected
                } else {
                    step1Next.disabled = false;
                }

                // Deselect any student in all sections
                document.querySelectorAll('.student-result-card').forEach(c => c.classList
                    .remove('selected'));
                updateSelectedCounter();
                updateExistingProfileCounter();

                // Show/hide Quick Re-enroll section based on student type
                // Only show for "returning" type, NOT for "enroll" (existing profile loads in step 2)
                const quickReenrollSection = document.getElementById('quickReenrollSection');
                if (quickReenrollSection) {
                    if (state.studentType === 'returning') {
                        quickReenrollSection.style.display = 'block';
                    } else {
                        quickReenrollSection.style.display = 'none';
                    }
                }
            });
        });

        // Returning student multi-select - use event delegation
        document.getElementById('quickStudentResults')?.addEventListener('click', function(e) {
            const card = e.target.closest('.student-result-card');
            if (!card) return;

            const studentId = parseInt(card.dataset.studentId);
            const studentData = allStudents.find(s => s.id === studentId);

            const gradeFilterVal = returningGradeFilter?.value || '';

            // If grade filter is empty (All Grade Levels), enforce single-selection behavior
            if (!gradeFilterVal) {
                if (card.classList.contains('selected')) {
                    // If already selected, deselect it (resulting in zero selection)
                    state.selectedStudentIds = [];
                    state.selectedStudentsData = [];
                    document.querySelectorAll('#quickStudentResults .student-result-card.selected')
                        .forEach(c => c.classList.remove('selected'));
                    studentIdInput.value = '';
                    state.selectedStudentId = null;
                    state.selectedStudentData = null;
                    step1Next.disabled = true;
                    updateSelectedCounter();
                    return;
                }

                // Deselect any other selected students and select only this one
                state.selectedStudentIds = [studentId];
                state.selectedStudentsData = [studentData];
                document.querySelectorAll('#quickStudentResults .student-result-card.selected').forEach(
                    c => c.classList.remove('selected'));
                card.classList.add('selected');

                state.studentType = 'returning';
                studentTypeInput.value = 'returning';
                studentIdInput.value = state.selectedStudentIds.join(',');
                step1Next.disabled = false;

                // Single-selection compatibility
                state.selectedStudentId = state.selectedStudentIds[0];
                state.selectedStudentData = state.selectedStudentsData[0];

                // Select returning card visually
                document.querySelector('.student-type-card[data-type="returning"]')?.classList.add(
                    'selected');
                studentTypeCards.forEach(c => {
                    if (c.dataset.type === 'new') c.classList.remove('selected');
                });

                updateSelectedCounter();
                return;
            }

            // Multi-select behavior when a specific grade is chosen
            const index = state.selectedStudentIds.indexOf(studentId);
            if (index > -1) {
                // Deselect
                state.selectedStudentIds.splice(index, 1);
                state.selectedStudentsData = state.selectedStudentsData.filter(s => s.id !== studentId);
                card.classList.remove('selected');
            } else {
                // Select
                state.selectedStudentIds.push(studentId);
                state.selectedStudentsData.push(studentData);
                card.classList.add('selected');
            }

            // Update state
            if (state.selectedStudentIds.length > 0) {
                state.studentType = 'returning';
                studentTypeInput.value = 'returning';
                studentIdInput.value = state.selectedStudentIds.join(',');
                step1Next.disabled = false;

                // For single selection compatibility
                state.selectedStudentId = state.selectedStudentIds[0];
                state.selectedStudentData = state.selectedStudentsData[0];

                // Select returning card visually
                document.querySelector('.student-type-card[data-type="returning"]')?.classList.add(
                    'selected');
                studentTypeCards.forEach(c => {
                    if (c.dataset.type === 'new') c.classList.remove('selected');
                });
            } else {
                studentIdInput.value = '';
                state.selectedStudentId = null;
                state.selectedStudentData = null;
                // If no students selected and returning type is active, disable continue
                if (state.studentType === 'returning') {
                    step1Next.disabled = true;
                } else if (!state.studentType) {
                    step1Next.disabled = true;
                }
            }

            updateSelectedCounter();
        });

        // Update selected students counter
        function updateSelectedCounter() {
            const counter = document.getElementById('selectedStudentsCounter');
            const countEl = document.getElementById('selectedCount');
            if (counter && countEl) {
                if (state.selectedStudentIds.length > 0) {
                    counter.style.display = 'flex';
                    countEl.textContent = state.selectedStudentIds.length;
                } else {
                    counter.style.display = 'none';
                }
            }
        }

        // Clear all selections
        document.getElementById('clearSelection')?.addEventListener('click', function() {
            state.selectedStudentIds = [];
            state.selectedStudentsData = [];
            state.selectedStudentId = null;
            state.selectedStudentData = null;
            studentIdInput.value = '';
            document.querySelectorAll('.student-result-card').forEach(c => {
                c.classList.remove('selected');
            });
            updateSelectedCounter();
        });

        // Select all visible students
        document.getElementById('selectAllStudents')?.addEventListener('click', function() {
            // Prevent multi-select across all grade levels: require a specific grade filter
            const gradeFilterVal = returningGradeFilter?.value || '';
            if (!gradeFilterVal) {
                alert('Please filter by a specific grade level to select multiple students.');
                return;
            }

            const visibleCards = document.querySelectorAll(
                '#quickStudentResults .student-result-card:not([style*="display: none"])');
            visibleCards.forEach(card => {
                const studentId = parseInt(card.dataset.studentId);
                if (!state.selectedStudentIds.includes(studentId)) {
                    const studentData = allStudents.find(s => s.id === studentId);
                    state.selectedStudentIds.push(studentId);
                    state.selectedStudentsData.push(studentData);
                    card.classList.add('selected');
                    setCardChecked(card, true);
                }
            });

            if (state.selectedStudentIds.length > 0) {
                state.studentType = 'returning';
                studentTypeInput.value = 'returning';
                studentIdInput.value = state.selectedStudentIds.join(',');
                step1Next.disabled = false;
                state.selectedStudentId = state.selectedStudentIds[0];
                state.selectedStudentData = state.selectedStudentsData[0];
                document.querySelector('.student-type-card[data-type="returning"]')?.classList.add(
                    'selected');
            }

            updateSelectedCounter();
        });

        // Quick search and grade filter functionality
        const quickSearch = document.getElementById('quickStudentSearch');
        const quickResults = document.getElementById('quickStudentResults');
        const returningGradeFilter = document.getElementById('returningGradeFilter');

        function filterAndRenderStudents() {
            const query = quickSearch?.value.toLowerCase().trim() || '';
            const gradeFilter = returningGradeFilter?.value || '';

            // Show only students who were enrolled in the previous school year (not pending)
            let filtered = allStudents.filter(s => {
                if (!s.enrollment_history || s.enrollment_history.length === 0) return false;
                // Check if student was enrolled in the previous school year
                const wasEnrolledLastYear = s.enrollment_history.some(
                    h => h.school_year_id === previousSchoolYearId
                );
                return wasEnrolledLastYear;
            });

            // Filter by grade level
            if (gradeFilter) {
                filtered = filtered.filter(s => s.last_grade_level_id == gradeFilter);
            }

            // Filter by search query
            if (query.length > 0) {
                filtered = filtered.filter(s => {
                    const fullName = (s.first_name + ' ' + s.last_name).toLowerCase();
                    const lrn = (s.lrn || '').toLowerCase();
                    const studentId = String(s.id);
                    return fullName.includes(query) || lrn.includes(query) || studentId.includes(query);
                });
            }

            renderStudentResults(filtered, false);
        }

        if (quickSearch) {
            quickSearch.addEventListener('input', filterAndRenderStudents);
        }

        if (returningGradeFilter) {
            returningGradeFilter.addEventListener('change', filterAndRenderStudents);
        }

        // Initial render
        if (quickResults) {
            filterAndRenderStudents();
        }

        function renderStudentResults(students, showMore) {
            let html = '';
            students.forEach(s => {
                // Check if this student is in the multi-select array
                const selected = state.selectedStudentIds.includes(s.id) ? 'selected' : '';

                // Use last enrollment to show which school year they were last enrolled
                const lastEnrollment = s.enrollment_history && s.enrollment_history.length > 0 ? s
                    .enrollment_history[0] : null;
                const statusBadge = lastEnrollment ?
                    `<span class="badge bg-secondary ms-1">Enrolled S.Y. ${lastEnrollment.school_year}</span>` :
                    '';

                const nextGrade = s.next_grade_level_name ? `→ ${s.next_grade_level_name}` : '';

                html += `
                        <div class="student-result-card ${selected}" data-student-id="${s.id}"
                            data-name="${s.first_name} ${s.last_name}" data-lrn="${s.lrn || ''}"
                            data-next-grade-level-id="${s.next_grade_level_id || ''}">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">${s.first_name} ${s.last_name}${statusBadge}</div>
                                <div class="small text-muted">
                                    LRN: ${s.lrn || 'N/A'} • Last Grade: ${s.last_grade} ${nextGrade}
                                </div>
                            </div>
                        </div>
                    `;
            });

            if (showMore) {
                html += `<div class="text-center py-2 text-muted small">Type to search more students...</div>`;
            }

            if (students.length === 0) {
                html =
                    `<div class="text-center py-3 text-muted">No students found matching your criteria</div>`;
            }

            quickResults.innerHTML = html;

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        // =====================================
        // EXISTING PROFILE SELECTION (Step 2 for 'enroll' type)
        // =====================================
        const existingProfileSearch = document.getElementById('existingProfileSearch');
        const existingProfileResults = document.getElementById('existingProfileResults');
        const existingProfileGradeFilter = document.getElementById('existingProfileGradeFilter');

        function filterAndRenderExistingProfiles() {
            const query = existingProfileSearch?.value.toLowerCase().trim() || '';
            const gradeFilter = existingProfileGradeFilter?.value || '';

            // For existing profile, show only students with 'pending' status in the ACTIVE school year
            let filtered = allStudents.filter(s => s.active_year_profile_status === 'pending');

            // Filter by grade level (use last_grade_level_id since we're not promoting)
            if (gradeFilter) {
                filtered = filtered.filter(s => s.last_grade_level_id == gradeFilter);
            }

            // Filter by search query
            if (query.length > 0) {
                filtered = filtered.filter(s => {
                    const fullName = (s.first_name + ' ' + s.last_name).toLowerCase();
                    const lrn = (s.lrn || '').toLowerCase();
                    const studentId = String(s.id);
                    return fullName.includes(query) || lrn.includes(query) || studentId.includes(query);
                });
            }

            renderExistingProfileResults(filtered);
        }

        function renderExistingProfileResults(students) {
            if (!existingProfileResults) return;

            let html = '';
            students.forEach(s => {
                // Check if this student is in the multi-select array
                const selected = state.selectedStudentIds.includes(s.id) ? 'selected' : '';
                // Show pending badge for pending students
                const statusBadge = `<span class="badge bg-secondary ms-1">Pending</span>`;

                html += `
                    <div class="student-result-card ${selected}" data-student-id="${s.id}"
                        data-name="${s.first_name} ${s.last_name}" data-lrn="${s.lrn || ''}"
                        data-last-grade-level-id="${s.last_grade_level_id || ''}">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${s.first_name} ${s.last_name}${statusBadge}</div>
                            <div class="small text-muted">
                                LRN: ${s.lrn || 'N/A'} • Grade: ${s.last_grade}
                            </div>
                        </div>
                    </div>
                `;
            });

            if (students.length === 0) {
                html =
                    `<div class="text-center py-3 text-muted">No students found matching your criteria</div>`;
            }

            existingProfileResults.innerHTML = html;

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        // Event listeners for existing profile selection
        if (existingProfileSearch) {
            existingProfileSearch.addEventListener('input', filterAndRenderExistingProfiles);
        }

        if (existingProfileGradeFilter) {
            existingProfileGradeFilter.addEventListener('change', filterAndRenderExistingProfiles);
        }

        // Existing profile click handler - use event delegation
        document.getElementById('existingProfileResults')?.addEventListener('click', function(e) {
            const card = e.target.closest('.student-result-card');
            if (!card) return;

            const studentId = parseInt(card.dataset.studentId);
            const studentData = allStudents.find(s => s.id === studentId);

            const gradeFilterVal = existingProfileGradeFilter?.value || '';

            // If grade filter is empty (All Grade Levels), enforce single-selection behavior
            if (!gradeFilterVal) {
                if (card.classList.contains('selected')) {
                    // Deselect
                    state.selectedStudentIds = [];
                    state.selectedStudentsData = [];
                    document.querySelectorAll('#existingProfileResults .student-result-card.selected')
                        .forEach(c => c.classList.remove('selected'));
                    studentIdInput.value = '';
                    state.selectedStudentId = null;
                    state.selectedStudentData = null;
                    step2Next.disabled = true;
                    updateExistingProfileCounter();
                    return;
                }

                // Single selection
                state.selectedStudentIds = [studentId];
                state.selectedStudentsData = [studentData];
                document.querySelectorAll('#existingProfileResults .student-result-card.selected')
                    .forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
            } else {
                // Multi-select when grade is filtered
                const index = state.selectedStudentIds.indexOf(studentId);
                if (index > -1) {
                    // Deselect
                    state.selectedStudentIds.splice(index, 1);
                    state.selectedStudentsData = state.selectedStudentsData.filter(s => s.id !==
                        studentId);
                    card.classList.remove('selected');
                } else {
                    // Select
                    state.selectedStudentIds.push(studentId);
                    state.selectedStudentsData.push(studentData);
                    card.classList.add('selected');
                }
            }

            // Update state
            if (state.selectedStudentIds.length > 0) {
                studentIdInput.value = state.selectedStudentIds.join(',');
                step2Next.disabled = false;
                state.selectedStudentId = state.selectedStudentIds[0];
                state.selectedStudentData = state.selectedStudentsData[0];
            } else {
                studentIdInput.value = '';
                step2Next.disabled = true;
                state.selectedStudentId = null;
                state.selectedStudentData = null;
            }

            updateExistingProfileCounter();
        });

        // Update existing profile counter
        function updateExistingProfileCounter() {
            const counter = document.getElementById('existingProfileCounter');
            const countEl = document.getElementById('existingProfileCount');
            if (counter && countEl) {
                if (state.selectedStudentIds.length > 0) {
                    counter.style.display = 'flex';
                    countEl.textContent = state.selectedStudentIds.length;
                } else {
                    counter.style.display = 'none';
                }
            }
        }

        // Clear existing profile selection
        document.getElementById('clearExistingProfileSelection')?.addEventListener('click', function() {
            state.selectedStudentIds = [];
            state.selectedStudentsData = [];
            state.selectedStudentId = null;
            state.selectedStudentData = null;
            studentIdInput.value = '';
            step2Next.disabled = true;
            document.querySelectorAll('#existingProfileResults .student-result-card').forEach(c => {
                c.classList.remove('selected');
            });
            updateExistingProfileCounter();
        });

        // Select all existing profiles
        document.getElementById('selectAllExistingProfiles')?.addEventListener('click', function() {
            const gradeFilterVal = existingProfileGradeFilter?.value || '';
            if (!gradeFilterVal) {
                alert('Please filter by a specific grade level to select multiple students.');
                return;
            }

            const visibleCards = document.querySelectorAll(
                '#existingProfileResults .student-result-card:not([style*="display: none"])');
            visibleCards.forEach(card => {
                const studentId = parseInt(card.dataset.studentId);
                if (!state.selectedStudentIds.includes(studentId)) {
                    const studentData = allStudents.find(s => s.id === studentId);
                    state.selectedStudentIds.push(studentId);
                    state.selectedStudentsData.push(studentData);
                    card.classList.add('selected');
                }
            });

            if (state.selectedStudentIds.length > 0) {
                studentIdInput.value = state.selectedStudentIds.join(',');
                step2Next.disabled = false;
                state.selectedStudentId = state.selectedStudentIds[0];
                state.selectedStudentData = state.selectedStudentsData[0];
            }

            updateExistingProfileCounter();
        });

        // Class selection
        classCards.forEach(card => {
            card.addEventListener('click', function() {
                if (this.dataset.full === '1') return;

                classCards.forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');

                state.selectedClassId = parseInt(this.dataset.classId);
                state.selectedClassData = allClasses.find(c => c.id === state.selectedClassId);
                classIdInput.value = state.selectedClassId;
                step3Next.disabled = false;
            });
        });

        // Class filtering
        const gradeLevelFilter = document.getElementById('gradeLevelFilter');
        const classSearch = document.getElementById('classSearch');
        const nextGradeLevelNotice = document.getElementById('nextGradeLevelNotice');
        const nextGradeLevelText = document.getElementById('nextGradeLevelText');

        function filterClasses() {
            const gradeFilter = gradeLevelFilter?.value || '';
            const searchFilter = classSearch?.value.toLowerCase().trim() || '';

            // For returning/enroll students, enforce grade level filter based on selected students
            let enforceGradeLevel = null;
            if ((state.studentType === 'returning' || state.studentType === 'enroll') && state
                .selectedStudentsData.length > 0) {
                // For 'enroll' type (pending students), use current grade level (no promotion)
                // For 'returning' type, use next grade level (promotion)
                if (state.studentType === 'enroll') {
                    const lastGradeLevelIds = [...new Set(state.selectedStudentsData.map(s => s
                            .last_grade_level_id)
                        .filter(Boolean))];
                    if (lastGradeLevelIds.length === 1) {
                        enforceGradeLevel = lastGradeLevelIds[0].toString();
                    }
                } else {
                    const nextGradeLevelIds = [...new Set(state.selectedStudentsData.map(s => s
                            .next_grade_level_id)
                        .filter(Boolean))];
                    if (nextGradeLevelIds.length === 1) {
                        enforceGradeLevel = nextGradeLevelIds[0].toString();
                    }
                }
            }

            classCards.forEach(card => {
                let gradeMatch;
                if (enforceGradeLevel) {
                    // For returning/enroll students, only show next grade level
                    gradeMatch = card.dataset.gradeLevel === enforceGradeLevel;
                } else {
                    gradeMatch = !gradeFilter || card.dataset.gradeLevel === gradeFilter;
                }
                const searchMatch = !searchFilter || card.dataset.sectionName.includes(searchFilter);
                card.style.display = (gradeMatch && searchMatch) ? 'block' : 'none';
            });
        }

        function updateGradeLevelFilterForReturning() {
            if ((state.studentType === 'returning' || state.studentType === 'enroll') && state
                .selectedStudentsData.length > 0) {

                // For 'enroll' type (pending students), use current grade level (no promotion)
                if (state.studentType === 'enroll') {
                    const lastGradeLevelIds = [...new Set(state.selectedStudentsData.map(s => s
                            .last_grade_level_id)
                        .filter(Boolean))];

                    if (lastGradeLevelIds.length === 1) {
                        const currentGradeName = state.selectedStudentsData[0].last_grade;
                        nextGradeLevelText.textContent =
                            `${state.selectedStudentsData.length} pending student(s) will be enrolled in ${currentGradeName}. Only ${currentGradeName} sections are shown.`;
                        nextGradeLevelNotice.style.display = 'flex';
                        gradeLevelFilter.value = lastGradeLevelIds[0];
                        gradeLevelFilter.disabled = true;
                    } else if (lastGradeLevelIds.length > 1) {
                        nextGradeLevelText.innerHTML =
                            `<strong>Warning:</strong> Selected students are from different grade levels. Please go back and select students from the same grade level.`;
                        nextGradeLevelNotice.classList.remove('alert-info');
                        nextGradeLevelNotice.classList.add('alert-warning');
                        nextGradeLevelNotice.style.display = 'flex';
                        gradeLevelFilter.value = '';
                        gradeLevelFilter.disabled = false;
                    } else {
                        nextGradeLevelNotice.style.display = 'none';
                        gradeLevelFilter.value = '';
                        gradeLevelFilter.disabled = false;
                    }
                } else {
                    // For 'returning' type, use next grade level (promotion)
                    const nextGradeLevelIds = [...new Set(state.selectedStudentsData.map(s => s
                            .next_grade_level_id)
                        .filter(Boolean))];

                    if (nextGradeLevelIds.length === 1) {
                        const nextGradeName = state.selectedStudentsData[0].next_grade_level_name;
                        nextGradeLevelText.textContent =
                            `${state.selectedStudentsData.length} student(s) will be promoted to ${nextGradeName}. Only ${nextGradeName} sections are shown.`;
                        nextGradeLevelNotice.style.display = 'flex';
                        gradeLevelFilter.value = nextGradeLevelIds[0];
                        gradeLevelFilter.disabled = true;
                    } else if (nextGradeLevelIds.length > 1) {
                        nextGradeLevelText.innerHTML =
                            `<strong>Warning:</strong> Selected students are from different grade levels and will be promoted to different grades. Please go back and select students from the same grade level.`;
                        nextGradeLevelNotice.classList.remove('alert-info');
                        nextGradeLevelNotice.classList.add('alert-warning');
                        nextGradeLevelNotice.style.display = 'flex';
                        gradeLevelFilter.value = '';
                        gradeLevelFilter.disabled = false;
                    } else {
                        nextGradeLevelNotice.style.display = 'none';
                        gradeLevelFilter.value = '';
                        gradeLevelFilter.disabled = false;
                    }
                }
            } else {
                // Allow all grade levels for new students
                nextGradeLevelNotice.style.display = 'none';
                nextGradeLevelNotice.classList.remove('alert-warning');
                nextGradeLevelNotice.classList.add('alert-info');
                gradeLevelFilter.value = '';
                gradeLevelFilter.disabled = false;
            }
            filterClasses();
        }

        gradeLevelFilter?.addEventListener('change', filterClasses);
        classSearch?.addEventListener('input', filterClasses);

        // Navigation handlers
        step1Next?.addEventListener('click', () => goToStep(2));
        step2Back?.addEventListener('click', () => goToStep(1));
        step2Next?.addEventListener('click', () => {
            // Validate new student form (Step 2 = Profile Student)
            if (state.studentType === 'new') {
                const form = document.getElementById('newStudentForm');
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        valid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (!valid) {
                    alert('Please fill in all required fields');
                    return;
                }
            }

            // Validate student selection for 'enroll' type
            if (state.studentType === 'enroll' && state.selectedStudentIds.length === 0) {
                alert('Please select at least one student to enroll');
                return;
            }

            goToStep(3);
        });
        step3Back?.addEventListener('click', () => goToStep(2));
        step3Next?.addEventListener('click', () => {
            // Validate class selection (Step 3 = Enroll to Class)
            if (!state.selectedClassId) {
                alert('Please select a class to enroll the student');
                return;
            }
            goToStep(4);
        });
        step4Back?.addEventListener('click', () => goToStep(3));

        // Enrollment status radio button handlers
        const enrollmentStatusInput = document.getElementById('enrollmentStatusInput');
        const enrollmentStatusRadios = document.querySelectorAll('.enrollment-status-radio');
        const newStudentStatusOptions = document.getElementById('newStudentStatusOptions');
        const returningStudentStatusOptions = document.getElementById('returningStudentStatusOptions');
        const droppedStudentInfo = document.getElementById('droppedStudentInfo');

        enrollmentStatusRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                enrollmentStatusInput.value = this.value;
            });
        });

        // Function to update enrollment status options based on student type
        function updateEnrollmentStatusOptions() {
            if (state.studentType === 'new') {
                newStudentStatusOptions.style.display = 'block';
                returningStudentStatusOptions.style.display = 'none';
                // Default to 'enrolled' for new students
                document.getElementById('statusEnrolled').checked = true;
                enrollmentStatusInput.value = 'enrolled';
            } else if (state.studentType === 'returning' || state.studentType === 'enroll') {
                newStudentStatusOptions.style.display = 'none';
                returningStudentStatusOptions.style.display = 'block';
                // Check if selected student(s) were previously dropped
                const hasDroppedStudent = state.selectedStudentsData.some(s => s.status === 'dropped');
                if (hasDroppedStudent && droppedStudentInfo) {
                    droppedStudentInfo.style.display = 'block';
                } else if (droppedStudentInfo) {
                    droppedStudentInfo.style.display = 'none';
                }
                // Default to 'enrolled' for returning students
                document.getElementById('statusReturning').checked = true;
                enrollmentStatusInput.value = 'enrolled';
            }

            // Re-initialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        function restoreWizardFromOldInput() {
            const oldStudentType = (oldWizardInput.student_type || '').trim();
            const oldStudentIds = String(oldWizardInput.student_id || '')
                .split(',')
                .map((value) => parseInt(value, 10))
                .filter((value) => !Number.isNaN(value));
            const oldClassId = parseInt(oldWizardInput.class_id || '', 10);
            const oldEnrollmentStatus = (oldWizardInput.enrollment_status || '').trim();
            const hasWizardState = oldStudentType || oldStudentIds.length > 0 || !Number.isNaN(oldClassId);

            if (!hasWizardState && !wizardErrorHints.hasAnyErrors) {
                return;
            }

            if (oldStudentType) {
                const typeCard = document.querySelector(`.student-type-card[data-type="${oldStudentType}"]`);
                if (typeCard) {
                    typeCard.click();
                }
            } else if (wizardErrorHints.hasProfileErrors) {
                const newStudentTypeCard = document.querySelector('.student-type-card[data-type="new"]');
                if (newStudentTypeCard) {
                    newStudentTypeCard.click();
                }
            }

            if (oldStudentIds.length > 0) {
                const oldSelectedStudents = allStudents.filter((student) => oldStudentIds.includes(student.id));
                state.selectedStudentIds = oldStudentIds;
                state.selectedStudentsData = oldSelectedStudents;
                state.selectedStudentId = oldStudentIds[0] ?? null;
                state.selectedStudentData = oldSelectedStudents[0] ?? null;
                studentIdInput.value = oldStudentIds.join(',');
                step1Next.disabled = false;
                step2Next.disabled = false;
                updateSelectedCounter();
                updateExistingProfileCounter();
                filterAndRenderStudents();
                filterAndRenderExistingProfiles();
            }

            if (!Number.isNaN(oldClassId)) {
                const oldClassData = allClasses.find((schoolClass) => schoolClass.id === oldClassId);
                if (oldClassData) {
                    state.selectedClassId = oldClassId;
                    state.selectedClassData = oldClassData;
                    classIdInput.value = String(oldClassId);
                    const selectedClassCard = document.querySelector(`.class-card[data-class-id="${oldClassId}"]`);
                    if (selectedClassCard && selectedClassCard.dataset.full !== '1') {
                        classCards.forEach((card) => card.classList.remove('selected'));
                        selectedClassCard.classList.add('selected');
                        step3Next.disabled = false;
                    }
                }
            }

            let targetStep = 1;
            if (wizardErrorHints.hasProfileErrors) {
                targetStep = 2;
            } else if (wizardErrorHints.hasClassError) {
                targetStep = 3;
            } else if (wizardErrorHints.hasStudentSelectionError) {
                targetStep = state.studentType === 'enroll' ? 2 : 1;
            } else if (state.studentType === 'new') {
                targetStep = state.selectedClassId ? 4 : 2;
            } else if ((state.studentType === 'returning' || state.studentType === 'enroll') && state
                .selectedStudentIds.length > 0) {
                targetStep = state.selectedClassId ? 4 : 3;
            } else if (state.studentType) {
                targetStep = 2;
            }

            goToStep(targetStep);
            highlightProfileValidationErrors();

            if (oldEnrollmentStatus) {
                enrollmentStatusInput.value = oldEnrollmentStatus;
                const statusRadio = document.querySelector(`.enrollment-status-radio[value="${oldEnrollmentStatus}"]`);
                if (statusRadio) {
                    statusRadio.checked = true;
                }
            }
        }

        function highlightProfileValidationErrors() {
            if (!wizardErrorHints.hasProfileErrors || !Array.isArray(wizardErrorHints.profileFieldsWithErrors)) {
                return;
            }

            wizardErrorHints.profileFieldsWithErrors.forEach((fieldName) => {
                const field = document.querySelector(`#newStudentForm [name="${fieldName}"]`);
                if (field) {
                    field.classList.add('is-invalid');
                }
            });
        }

        // Form submission
        enrollmentForm?.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitEnrollment');
            const studentUpdatesInput = document.getElementById('studentUpdatesInput');

            // Debug logging
            console.log('Form submission:', {
                studentType: state.studentType,
                studentIdInput: studentIdInput.value,
                classIdInput: classIdInput.value,
                formAction: enrollmentForm.action,
                selectedStudentsData: state.selectedStudentsData,
                selectedStudentIds: state.selectedStudentIds
            });

            // Validate that required hidden inputs are set
            // For returning/enroll students, we need a student_id
            // For new students, the student is created during enrollment (no student_id required)
            if ((state.studentType === 'returning' || state.studentType === 'enroll') && !studentIdInput
                .value) {
                e.preventDefault();
                alert('Error: No student selected. Please go back and select a student.');
                return;
            }

            if (!classIdInput.value) {
                e.preventDefault();
                alert('Error: No class selected. Please go back and select a class.');
                return;
            }

            // Collect edited student data for returning/enroll students
            if (state.studentType === 'returning' || state.studentType === 'enroll') {
                const editedData = {};

                // For multi-student carousel
                if (state.selectedStudentsData.length > 1) {
                    document.querySelectorAll('.carousel-slide').forEach(slide => {
                        const studentId = slide.dataset.studentId;
                        if (!studentId) return;

                        const editSwitch = slide.querySelector('.edit-mode-switch');
                        if (editSwitch && editSwitch.checked) {
                            editedData[studentId] = {};
                            const fields = slide.querySelectorAll('.review-editable-field');
                            fields.forEach(field => {
                                const name = field.name;
                                const match = name.match(/students\[\d+\]\[(\w+)\]/);
                                if (match) {
                                    editedData[studentId][match[1]] = field.value;
                                }
                            });
                        }
                    });
                } else {
                    // For single returning student
                    const editSwitch = document.getElementById('singleEditModeSwitch');
                    if (editSwitch && editSwitch.checked && state.selectedStudentId) {
                        editedData[state.selectedStudentId] = {};
                        document.querySelectorAll('.single-edit-field').forEach(field => {
                            const name = field.name;
                            const match = name.match(/single_student\[(\w+)\]/);
                            if (match) {
                                editedData[state.selectedStudentId][match[1]] = field.value;
                            }
                        });
                    }
                }

                // Set the student updates hidden input
                if (Object.keys(editedData).length > 0) {
                    studentUpdatesInput.value = JSON.stringify(editedData);
                    console.log('Student updates:', editedData);
                }
            }

            // For multi-student enrollment, check if any students are confirmed
            if ((state.studentType === 'returning' || state.studentType === 'enroll') && state
                .selectedStudentsData.length > 1) {
                if (carouselState.confirmedStudents.size === 0) {
                    e.preventDefault();
                    alert('Please confirm at least one student before submitting.');
                    return;
                }

                // Update the student_id input with only confirmed students
                const confirmedIds = Array.from(carouselState.confirmedStudents);
                studentIdInput.value = confirmedIds.join(',');

                // Show confirmation dialog
                const confirmMsg =
                    `You are about to enroll ${confirmedIds.length} student(s) to ${state.selectedClassData.grade_level} - ${state.selectedClassData.section_name}. Continue?`;
                if (!confirm(confirmMsg)) {
                    e.preventDefault();
                    return;
                }
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Processing...';
        });

        restoreWizardFromOldInput();

        // Handle collapsible toggle
        document.querySelectorAll('.collapsible-header').forEach(header => {
            const targetId = header.dataset.bsTarget;
            const target = document.querySelector(targetId);
            if (target) {
                target.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
                target.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
            }
        });
    });
</script>
@endpush
