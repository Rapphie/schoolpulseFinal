@extends('base')

@section('title', $title)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item">
                        <a
                            href="{{ route('admin.reports.attendance', ['school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}">
                            Attendance Analytics
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-2 text-gray-800">{{ $title }}</h1>
            <p class="mb-1 text-muted">{{ $description }}</p>
            <p class="mb-0 text-muted">
                Viewing data for:
                <span class="fw-semibold text-dark">{{ $currentSchoolYear->name ?? 'Not set' }}</span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label for="schoolYearSelect" class="form-label small text-muted mb-1">School Year</label>
                <select class="form-select" id="schoolYearSelect">
                    @foreach ($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear->id }}"
                            {{ $currentSchoolYear && $schoolYear->id === $currentSchoolYear->id ? 'selected' : '' }}>
                            {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="gradeFilter" class="form-label small text-muted mb-1">Grade Level</label>
                <select class="form-select" id="gradeFilter">
                    <option value="">All Grades</option>
                    @foreach ($gradeLevels ?? [] as $gradeLevel)
                        <option value="{{ $gradeLevel->id }}"
                            {{ $selectedGradeLevelId && $gradeLevel->id === (int) $selectedGradeLevelId ? 'selected' : '' }}>
                            {{ $gradeLevel->name ?? 'Grade ' . $gradeLevel->level }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.reports.export.attendance', ['school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId, 'status' => $type !== 'records' ? $type : null]) }}"
                    class="btn btn-outline-primary" id="exportBtn">
                    <i data-feather="download" class="me-1"></i> Export
                </a>
                <a href="{{ route('admin.reports.attendance', ['school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                    class="btn btn-outline-secondary">
                    <i data-feather="arrow-left" class="me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-left-primary shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Records</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($data['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-left-success shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Present</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($data['present']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-left-danger shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absent</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($data['absent']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-left-warning shadow-sm h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($data['late']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Records Table --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i data-feather="calendar" class="me-2"></i>
                {{ $title }}
                <span class="badge bg-primary ms-2">{{ number_format($data['filtered_count']) }} shown</span>
                @if ($data['filtered_count'] < $data['total'] && $type === 'records')
                    <small class="text-muted ms-2">(showing most recent 500)</small>
                @endif
            </h6>
        </div>
        <div class="card-body">
            @if (count($data['records']) > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle js-datatable" width="100%">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>LRN</th>
                                <th>Student Name</th>
                                <th>Grade</th>
                                <th>Section</th>
                                <th>Subject</th>
                                <th class="text-center">Status</th>
                                <th>Time In</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['records'] as $record)
                                @php
                                    $statusClass = match ($record['status_raw']) {
                                        'present' => 'bg-success',
                                        'absent' => 'bg-danger',
                                        'late' => 'bg-warning text-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $record['date'] }}</td>
                                    <td>{{ $record['lrn'] }}</td>
                                    <td class="fw-semibold">{{ $record['full_name'] }}</td>
                                    <td>{{ $record['grade'] }}</td>
                                    <td>{{ $record['section'] }}</td>
                                    <td>{{ $record['subject'] }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $statusClass }}">{{ $record['status'] }}</span>
                                    </td>
                                    <td>{{ $record['time_in'] }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.students.show', $record['student_id']) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i data-feather="eye" class="feather-sm"></i> View Student
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted py-5">
                    <i data-feather="calendar" style="width: 48px; height: 48px;" class="mb-3 text-muted"></i>
                    <p class="mb-0">No attendance records found for the selected filters.</p>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const schoolYearSelect = document.getElementById('schoolYearSelect');
            const gradeFilter = document.getElementById('gradeFilter');
            const exportBtn = document.getElementById('exportBtn');
            const currentType = @json($type);

            function updateFilters() {
                const params = new URLSearchParams();
                if (schoolYearSelect.value) {
                    params.append('school_year_id', schoolYearSelect.value);
                }
                if (gradeFilter.value) {
                    params.append('grade_level_id', gradeFilter.value);
                }

                const baseUrl = "{{ route('admin.reports.attendance.detail', ['type' => '__TYPE__']) }}".replace(
                    '__TYPE__', currentType);
                window.location.href = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
            }

            function updateExportLink() {
                if (!exportBtn) return;
                const params = new URLSearchParams();
                if (schoolYearSelect.value) {
                    params.append('school_year_id', schoolYearSelect.value);
                }
                if (gradeFilter.value) {
                    params.append('grade_level_id', gradeFilter.value);
                }
                if (currentType !== 'records') {
                    params.append('status', currentType);
                }

                const exportBaseUrl = "{{ route('admin.reports.export.attendance') }}";
                exportBtn.href = params.toString() ? `${exportBaseUrl}?${params.toString()}` : exportBaseUrl;
            }

            schoolYearSelect.addEventListener('change', updateFilters);
            gradeFilter.addEventListener('change', updateFilters);

            // Initial update
            updateExportLink();
        });
    </script>
@endpush
