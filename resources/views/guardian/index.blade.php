@extends('base')

@section('title', 'My Student Grades')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('guardian.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My Student Grades</li>
                </ol>
            </nav>
            <div>
                @if ($activeSchoolYear)
                    <span class="badge bg-primary text-white px-3 py-2">Active SY: {{ $activeSchoolYear->name }}</span>
                @else
                    <span class="badge bg-warning text-dark px-3 py-2">No active school year set</span>
                @endif
            </div>
        </div>

        @php
            $statusVariants = [
                'present' => 'bg-success',
                'absent' => 'bg-danger',
                'late' => 'bg-warning text-dark',
                'excused' => 'bg-info text-dark',
            ];
        @endphp

        @forelse ($studentsData as $index => $studentData)
            @php
                $student = $studentData['student'];
                $tabPrefix = 'student-' . $index;
            @endphp

            <div class="card shadow mb-5">
                <div class="card-header py-3 bg-primary text-white rounded-top">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h5 class="mb-1">Grades for {{ $student->full_name }}</h5>
                            <small class="text-white-50">School Year: {{ $studentData['school_year'] ?? '—' }}</small>
                        </div>
                        <span class="badge bg-light text-primary fs-6 px-3 py-2">{{ $studentData['class_section'] }}</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 text-muted mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Student Name:</strong> {{ $student->full_name }}</p>
                            <p class="mb-1"><strong>Student ID:</strong> {{ $studentData['student_identifier'] }}</p>
                            <p class="mb-0"><strong>LRN:</strong> {{ $studentData['lrn'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Grade Level:</strong> {{ $studentData['grade_level'] }}</p>
                            <p class="mb-1"><strong>Class/Section:</strong> {{ $studentData['class_section'] }}</p>
                            <p class="mb-0"><strong>Current School Year:</strong> {{ $studentData['school_year'] ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <hr class="border-light my-4">

                    <h5 class="text-gray-800 mb-3">Academic Performance</h5>

                    <ul class="nav nav-tabs mb-3" id="{{ $tabPrefix }}-gradeTabs" role="tablist">
                        @foreach ($quarterLabels as $quarterValue => $quarterLabel)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                    id="{{ $tabPrefix }}-quarter-tab-{{ $quarterValue }}" data-bs-toggle="tab"
                                    data-bs-target="#{{ $tabPrefix }}-quarter-{{ $quarterValue }}" type="button"
                                    role="tab" aria-controls="{{ $tabPrefix }}-quarter-{{ $quarterValue }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $quarterLabel }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content" id="{{ $tabPrefix }}-gradeTabsContent">
                        @foreach ($quarterLabels as $quarterValue => $quarterLabel)
                            @php
                                $quarterGrades = $studentData['grades_by_quarter']->get($quarterLabel, collect());
                            @endphp
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                id="{{ $tabPrefix }}-quarter-{{ $quarterValue }}" role="tabpanel"
                                aria-labelledby="{{ $tabPrefix }}-quarter-tab-{{ $quarterValue }}">
                                <div class="table-responsive pt-3">
                                    <table class="table table-bordered table-hover align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Teacher</th>
                                                <th class="text-center">Grade</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($quarterGrades as $grade)
                                                <tr>
                                                    <td>{{ $grade['subject'] }}</td>
                                                    <td>{{ $grade['teacher'] }}</td>
                                                    <td class="text-center fw-semibold">{{ $grade['grade'] ?? '—' }}</td>
                                                    <td>{{ $grade['remarks'] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">
                                                        No grades recorded for this quarter.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <hr class="border-light my-4">

                    <h5 class="text-gray-800 mb-3">Attendance Overview</h5>

                    <div class="row g-3 mb-4">
                        @foreach ($studentData['attendance_summary'] as $status => $count)
                            <div class="col-6 col-md-3">
                                <div class="border rounded-3 text-center p-3 h-100">
                                    <span class="text-uppercase small text-muted">{{ ucfirst($status) }}</span>
                                    <div class="fs-2 fw-bold mt-1">{{ $count }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Quarter</th>
                                    <th>Time In</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($studentData['attendance_records'] as $attendance)
                                    @php
                                        $badgeClass = $statusVariants[$attendance['status']] ?? 'bg-secondary';
                                    @endphp
                                    <tr>
                                        <td>{{ $attendance['formatted_date'] }}</td>
                                        <td>{{ $attendance['subject'] }}</td>
                                        <td><span
                                                class="badge {{ $badgeClass }}">{{ ucfirst($attendance['status']) }}</span>
                                        </td>
                                        <td>{{ $attendance['quarter'] }}</td>
                                        <td>{{ $attendance['time_in'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No attendance logs recorded
                                            for this school year.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info">
                <strong>No linked students yet.</strong> Please contact the school administrator to link your guardian
                account to a student profile.
            </div>
        @endforelse
    </div>
@endsection
