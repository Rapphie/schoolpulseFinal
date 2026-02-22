@extends('base')

@section('title', 'Student Profile')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Classes</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Student Profile</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-dark">{{ $student->last_name }}, {{ $student->first_name }}</h1>
                <p class="mb-0 text-muted">LRN: {{ $student->lrn ?? 'N/A' }} • Student ID:
                    {{ $student->student_id ?? 'N/A' }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-outline-primary">
                    <i data-feather="edit-2" class="me-1"></i> Edit
                </a>

                @if ($activeEnrollment && $activeEnrollment->class && $activeEnrollment->class->section)
                    <form
                        action="{{ route('admin.sections.students.destroy', [$activeEnrollment->class->section, $student]) }}"
                        method="POST"
                        onsubmit="return confirm('Remove this student from the current class (active school year)?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">Delete</button>
                    </form>
                @endif

                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Admin Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="text-xs font-weight-bold text-uppercase text-muted">Active School Year</div>
                                <div class="h6 mb-0">{{ $activeSchoolYear->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="text-xs font-weight-bold text-uppercase text-muted">Current Grade Level</div>
                                <div class="h6 mb-0">
                                    @if ($activeProfile && $activeProfile->gradeLevel)
                                        {{ $activeProfile->gradeLevel->name }}
                                        <span
                                            class="badge bg-{{ $activeProfile->status === 'active' ? 'success' : 'secondary' }} ms-1">
                                            {{ ucfirst($activeProfile->status) }}
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="text-xs font-weight-bold text-uppercase text-muted">Current Class (Section)
                                </div>
                                <div class="h6 mb-0">
                                    @if ($activeEnrollment && $activeEnrollment->class)
                                        {{ $activeEnrollment->class->section->name ?? 'N/A' }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    Adviser:
                                    @if ($activeEnrollment && $activeEnrollment->class && $activeEnrollment->class->teacher)
                                        {{ $activeEnrollment->class->teacher->user->first_name ?? '' }}
                                        {{ $activeEnrollment->class->teacher->user->last_name ?? '' }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-xs font-weight-bold text-uppercase text-muted">Attendance (Active SY)
                                </div>
                                <div class="h6 mb-0">
                                    Present: {{ $attendanceSummary['present'] ?? 0 }} • Absent:
                                    {{ $attendanceSummary['absent'] ?? 0 }}
                                </div>
                                <div class="small text-muted">
                                    Late: {{ $attendanceSummary['late'] ?? 0 }} • Excused:
                                    {{ $attendanceSummary['excused'] ?? 0 }}
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-xs font-weight-bold text-uppercase text-muted">Enrollment Date (Student)
                                </div>
                                <div class="h6 mb-0">{{ $student->enrollment_date?->format('Y-m-d') ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-xs font-weight-bold text-uppercase text-muted">Grades (Active SY)</div>
                                <div class="h6 mb-0">
                                    Avg:
                                    {{ isset($gradeSummary?->avg_grade) ? number_format((float) $gradeSummary->avg_grade, 2) : 'N/A' }}
                                </div>
                                <div class="small text-muted">Records: {{ $gradeSummary?->grade_count ?? 0 }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-xs font-weight-bold text-uppercase text-muted">Record Info</div>
                                <div class="small text-muted">DB ID: {{ $student->id }}</div>
                                <div class="small text-muted">Created:
                                    {{ $student->created_at?->format('Y-m-d g:i A') ?? 'N/A' }}</div>
                                <div class="small text-muted">Updated:
                                    {{ $student->updated_at?->format('Y-m-d g:i A') ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">{{ $student->first_name }} {{ $student->last_name }}</dd>

                            <dt class="col-sm-4">Gender</dt>
                            <dd class="col-sm-8">{{ ucfirst($student->gender) }}</dd>

                            <dt class="col-sm-4">Birthdate</dt>
                            <dd class="col-sm-8">{{ $student->birthdate?->format('Y-m-d') ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Address</dt>
                            <dd class="col-sm-8">{{ $student->address ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Distance (km)</dt>
                            <dd class="col-sm-8">{{ $student->distance_km ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Transportation</dt>
                            <dd class="col-sm-8">{{ $student->transportation ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Socioeconomic</dt>
                            <dd class="col-sm-8">{{ $student->family_income ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Parent Education</dt>
                            <dd class="col-sm-8">{{ $student->parent_education ?? 'N/A' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Guardian Information</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Name</dt>
                            <dd class="col-sm-8">
                                {{ $student->guardian->user->first_name ?? 'N/A' }}
                                {{ $student->guardian->user->last_name ?? '' }}
                            </dd>

                            <dt class="col-sm-4">Guardian User ID</dt>
                            <dd class="col-sm-8">{{ $student->guardian->user->id ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8">{{ $student->guardian->user->email ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Phone</dt>
                            <dd class="col-sm-8">{{ $student->guardian->phone ?? 'N/A' }}</dd>

                            <dt class="col-sm-4">Relationship</dt>
                            <dd class="col-sm-8">{{ ucfirst($student->guardian->relationship ?? 'N/A') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Grades by Quarter (Active SY)</h6>
                </div>
                <div class="card-body">
                    @if ($quarters->isNotEmpty())
                        <div class="row">
                            @foreach ($quarters as $quarter)
                                <div class="col-md-3 mb-2">
                                    <div class="text-xs font-weight-bold text-uppercase text-muted">
                                        {{ $quarter->name }}</div>
                                    <div class="h6 mb-0">
                                        {{ isset($gradesByQuarter[$quarter->name]) ? number_format($gradesByQuarter[$quarter->name]->average_grade, 2) : 'N/A' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No quarters found for the active school year.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Grades by Subject (Active SY)</h6>
                </div>
                <div class="card-body">
                    @if ($gradesBySubject->isNotEmpty())
                        <ul class="list-group list-group-flush">
                            @foreach ($gradesBySubject as $grade)
                                <li class="list-group-item d-flex justify-content-between align-items-center ps-0 pe-0">
                                    {{ $grade->subject_name }}
                                    <span
                                        class="badge bg-primary rounded-pill">{{ number_format($grade->average_grade, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No subject grades found for the active school year.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-graduation-cap me-2"></i>Grade Level History
                </h6>
                <span class="badge bg-secondary">{{ $gradeHistory->count() }} School Year(s)</span>
            </div>
            <div class="card-body">
                @if ($gradeHistory->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>School Year</th>
                                    <th>Grade Level</th>
                                    <th>Section</th>
                                    <th>Final Average</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gradeHistory as $profile)
                                    <tr @if ($profile->school_year_id === ($activeSchoolYear->id ?? null)) class="table-primary" @endif>
                                        <td>
                                            {{ $profile->schoolYear->name ?? 'N/A' }}
                                            @if ($profile->school_year_id === ($activeSchoolYear->id ?? null))
                                                <span class="badge bg-success ms-1">Current</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $profile->gradeLevel->name ?? 'N/A' }}</strong></td>
                                        <td>{{ $student->enrollments->firstWhere('school_year_id', $profile->school_year_id)?->class?->section?->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            @if ($profile->final_average)
                                                <span
                                                    class="fw-bold {{ $profile->final_average >= 75 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($profile->final_average, 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'active' => 'primary',
                                                    'enrolled' => 'primary',
                                                    'promoted' => 'success',
                                                    'retained' => 'warning',
                                                    'transferred' => 'info',
                                                    'dropped' => 'danger',
                                                    'graduated' => 'success',
                                                ];
                                                $color = $statusColors[$profile->status] ?? 'secondary';
                                            @endphp
                                            <span
                                                class="badge bg-{{ $color }}">{{ ucfirst($profile->status) }}</span>
                                        </td>
                                        <td>{{ $profile->remarks ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No grade level history found. Profile will be created upon enrollment.</p>
                @endif
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Enrollments</h6>
            </div>
            <div class="card-body">
                @if ($student->enrollments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th>School Year</th>
                                    <th>Grade Level</th>
                                    <th>Section</th>
                                    <th>Adviser</th>
                                    <th>Enrollment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($student->enrollments as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->schoolYear->name ?? 'N/A' }}</td>
                                        <td>{{ $enrollment->class->section->gradeLevel->name ?? 'N/A' }}</td>
                                        <td>{{ $enrollment->class->section->name ?? 'N/A' }}</td>
                                        <td>
                                            {{ $enrollment->class->teacher->user->first_name ?? 'N/A' }}
                                            {{ $enrollment->class->teacher->user->last_name ?? '' }}
                                        </td>
                                        <td>{{ $enrollment->enrollment_date?->format('Y-m-d') ?? 'N/A' }}</td>
                                        <td>{{ ucfirst($enrollment->status ?? 'N/A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No enrollments found.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
