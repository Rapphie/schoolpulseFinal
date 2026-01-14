@extends('base')

@section('title', $title)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item">
                        <a
                            href="{{ route('admin.reports.enrollees', ['school_year_id' => $currentSchoolYear?->id, 'grade' => $selectedGrade]) }}">
                            Enrollees Analytics
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
                @if ($selectedGrade)
                    <span class="ms-2 badge bg-primary">Grade {{ $selectedGrade }}</span>
                @endif
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
                        <option value="{{ $gradeLevel->level }}"
                            {{ (string) $gradeLevel->level === (string) $selectedGrade ? 'selected' : '' }}>
                            {{ $gradeLevel->name ?? 'Grade ' . $gradeLevel->level }}
                        </option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.reports.enrollees', ['school_year_id' => $currentSchoolYear?->id, 'grade' => $selectedGrade]) }}"
                class="btn btn-outline-secondary">
                <i data-feather="arrow-left" class="me-1"></i> Back to Analytics
            </a>
        </div>
    </div>

    @if ($type === 'students')
        {{-- Students Table --}}
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i data-feather="users" class="me-2"></i>
                    Enrolled Students
                    <span class="badge bg-primary ms-2">{{ number_format($data['total']) }}</span>
                </h6>
            </div>
            <div class="card-body">
                @if (count($data['students']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle js-datatable" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Grade Level</th>
                                    <th>Section</th>
                                    <th>Enrollment Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['students'] as $student)
                                    <tr>
                                        <td>{{ $student['lrn'] }}</td>
                                        <td class="fw-semibold">{{ $student['full_name'] }}</td>
                                        <td>{{ $student['gender'] }}</td>
                                        <td>{{ $student['grade'] }}</td>
                                        <td>{{ $student['section'] }}</td>
                                        <td>{{ $student['enrollment_date'] }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.students.show', $student['student_id']) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i data-feather="eye" class="feather-sm"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i data-feather="users" style="width: 48px; height: 48px;" class="mb-3 text-muted"></i>
                        <p class="mb-0">No enrolled students found for the selected filters.</p>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Sections Table (for sections, average, largest) --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Sections</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($data['total_sections']) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Students</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($data['total_students']) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average / Section</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">
                            {{ number_format($data['average_per_section'], 1) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Largest Section</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($data['largest_count']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i data-feather="grid" class="me-2"></i>
                    @if ($type === 'largest')
                        Largest Section(s)
                    @elseif ($type === 'average')
                        Sections by Enrollment Size
                    @else
                        All Sections
                    @endif
                    <span class="badge bg-primary ms-2">{{ count($data['sections']) }}</span>
                </h6>
            </div>
            <div class="card-body">
                @if (count($data['sections']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle js-datatable" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Grade Level</th>
                                    <th>Section Name</th>
                                    <th>Adviser</th>
                                    <th class="text-center">Students</th>
                                    <th class="text-center">Capacity</th>
                                    <th class="text-center">Utilization</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data['sections'] as $section)
                                    @php
                                        $utilization =
                                            is_numeric($section['capacity']) && $section['capacity'] > 0
                                                ? round(($section['students_count'] / $section['capacity']) * 100, 1)
                                                : null;
                                        $utilizationClass = $utilization
                                            ? ($utilization >= 90
                                                ? 'bg-danger'
                                                : ($utilization >= 70
                                                    ? 'bg-warning'
                                                    : 'bg-success'))
                                            : 'bg-secondary';
                                    @endphp
                                    <tr>
                                        <td>{{ $section['grade'] }}</td>
                                        <td class="fw-semibold">{{ $section['section_name'] }}</td>
                                        <td>{{ $section['adviser'] }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $section['students_count'] }}</span>
                                        </td>
                                        <td class="text-center">{{ $section['capacity'] }}</td>
                                        <td class="text-center">
                                            @if ($utilization !== null)
                                                <span class="badge {{ $utilizationClass }}">{{ $utilization }}%</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.sections.manage', $section['section_id']) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i data-feather="settings" class="feather-sm"></i> Manage
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i data-feather="grid" style="width: 48px; height: 48px;" class="mb-3 text-muted"></i>
                        <p class="mb-0">No sections found for the selected filters.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const schoolYearSelect = document.getElementById('schoolYearSelect');
            const gradeFilter = document.getElementById('gradeFilter');
            const currentType = @json($type);

            function updateFilters() {
                const params = new URLSearchParams();
                if (schoolYearSelect.value) {
                    params.append('school_year_id', schoolYearSelect.value);
                }
                if (gradeFilter.value) {
                    params.append('grade', gradeFilter.value);
                }

                const baseUrl = "{{ route('admin.reports.enrollees.detail', ['type' => '__TYPE__']) }}".replace(
                    '__TYPE__', currentType);
                window.location.href = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
            }

            schoolYearSelect.addEventListener('change', updateFilters);
            gradeFilter.addEventListener('change', updateFilters);
        });
    </script>
@endpush
