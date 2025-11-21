@extends('base')

@section('title', 'Cumulative Performance Report')

@section('internal-css')
    <style>
        .report-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            transition: all 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0.15rem rgba(58, 59, 69, 0.2);
        }

        .chart-container {
            height: 300px;
        }

        .icon-circle {
            height: 3rem;
            width: 3rem;
            border-radius: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .bg-primary-light {
            background-color: rgba(78, 115, 223, 0.2);
            color: #4e73df;
        }

        .bg-success-light {
            background-color: rgba(28, 200, 138, 0.2);
            color: #1cc88a;
        }

        .bg-info-light {
            background-color: rgba(54, 185, 204, 0.2);
            color: #36b9cc;
        }

        .bg-warning-light {
            background-color: rgba(246, 194, 62, 0.2);
            color: #f6c23e;
        }

        .text-xs {
            font-size: .7rem;
        }

        .text-primary {
            color: #4e73df !important;
        }

        .text-success {
            color: #1cc88a !important;
        }

        .text-info {
            color: #36b9cc !important;
        }

        .text-warning {
            color: #f6c23e !important;
        }
    </style>
@endsection

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Cumulative Performance Report</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.cumulative') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section" required>
                            <option value="">Select Section</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}"
                                    {{ $selectedSection == $section->id ? 'selected' : '' }}>
                                    {{ $section->name }}
                                    ({{ $section->gradeLevel ? 'Grade ' . $section->gradeLevel->level : 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="">Select Subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}"
                                    {{ $selectedSubject == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="school_year" class="form-label">School Year</label>
                        <select class="form-select" id="school_year" name="school_year" required>
                            <option value="">Select School Year</option>
                            @foreach ($schoolYears as $sy)
                                <option value="{{ $sy->id }}" {{ $selectedSchoolYear == $sy->id ? 'selected' : '' }}>
                                    {{ $sy->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-feather="filter"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>

            @if (isset($cumulativeData) && $studentPerformance->count() > 0)
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Students</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $cumulativeData->totalStudents }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-success h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Average Grade</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ number_format($cumulativeData->averageGrade, 2) }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-info h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Passing Students</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $cumulativeData->passingCount }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-warning h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Failing Students</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $cumulativeData->failingCount }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card report-card">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Student Performance Across Quarters</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="cumulativeTable">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>LRN</th>
                                        <th>1st Quarter</th>
                                        <th>2nd Quarter</th>
                                        <th>3rd Quarter</th>
                                        <th>4th Quarter</th>
                                        <th>Average</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($studentPerformance as $student)
                                        @php
                                            $rowClass = '';
                                            if ($student->average !== null) {
                                                $rowClass = $student->average >= 75 ? 'table-success' : 'table-danger';
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>{{ $student->name }}</td>
                                            <td>{{ $student->lrn }}</td>
                                            <td class="text-center">
                                                {{ $student->quarters['1st Quarter'] !== null ? number_format($student->quarters['1st Quarter'], 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $student->quarters['2nd Quarter'] !== null ? number_format($student->quarters['2nd Quarter'], 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $student->quarters['3rd Quarter'] !== null ? number_format($student->quarters['3rd Quarter'], 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                {{ $student->quarters['4th Quarter'] !== null ? number_format($student->quarters['4th Quarter'], 2) : '-' }}
                                            </td>
                                            <td class="text-center fw-bold">
                                                {{ $student->average !== null ? number_format($student->average, 2) : 'N/A' }}
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-{{ $student->status == 'Passing' ? 'success' : ($student->status == 'Failing' ? 'danger' : 'secondary') }}">
                                                    {{ $student->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    Please select a section, subject, and school year to view the cumulative performance report.
                </div>
            @endif
        </div>
    </div>
    <div class="h5 mb-0 font-weight-bold">85.4%</div>
    </div>
    </div>
    <div class="text-muted small">Compared to previous year: <span class="text-success">+1.8%</span>
    </div>
    </div>
    </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-circle bg-info-light me-3">
                        <i data-feather="check-circle"></i>
                    </div>
                    <div>
                        <div class="text-xs text-uppercase mb-1 text-info font-weight-bold">Attendance Rate</div>
                        <div class="h5 mb-0 font-weight-bold">94.7%</div>
                    </div>
                </div>
                <div class="text-muted small">Compared to previous year: <span class="text-danger">-0.5%</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card summary-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-circle bg-warning-light me-3">
                        <i data-feather="trending-up"></i>
                    </div>
                    <div>
                        <div class="text-xs text-uppercase mb-1 text-warning font-weight-bold">Promotion Rate</div>
                        <div class="h5 mb-0 font-weight-bold">98.2%</div>
                    </div>
                </div>
                <div class="text-muted small">Compared to previous year: <span class="text-success">+0.7%</span>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card report-card h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Academic Performance Trend</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            By Quarter
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="#">By Quarter</a></li>
                            <li><a class="dropdown-item" href="#">By Subject</a></li>
                            <li><a class="dropdown-item" href="#">By Grade Level</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="performanceTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card report-card h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">Subject Performance</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="subjectPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card report-card">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Performance by Section</h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="sectionFilterDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    All Grades
                </button>
                <ul class="dropdown-menu" aria-labelledby="sectionFilterDropdown">
                    <li><a class="dropdown-item" href="#">All Grades</a></li>
                    <li><a class="dropdown-item" href="#">Grade 1</a></li>
                    <li><a class="dropdown-item" href="#">Grade 2</a></li>
                    <li><a class="dropdown-item" href="#">Grade 3</a></li>
                    <li><a class="dropdown-item" href="#">Grade 4</a></li>
                    <li><a class="dropdown-item" href="#">Grade 5</a></li>
                    <li><a class="dropdown-item" href="#">Grade 6</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Grade Level</th>
                            <th>Adviser</th>
                            <th>Students</th>
                            <th>Average Grade</th>
                            <th>Attendance Rate</th>
                            <th>Promotion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sampaguita</td>
                            <td>Grade 1</td>
                            <td>Maria Clara</td>
                            <td>32</td>
                            <td>87.5%</td>
                            <td>95.2%</td>
                            <td>100%</td>
                        </tr>
                        <tr>
                            <td>Rosas</td>
                            <td>Grade 2</td>
                            <td>Juan Dela Cruz</td>
                            <td>35</td>
                            <td>86.2%</td>
                            <td>93.8%</td>
                            <td>97.1%</td>
                        </tr>
                        <tr>
                            <td>Orchids</td>
                            <td>Grade 3</td>
                            <td>Maria Makiling</td>
                            <td>33</td>
                            <td>84.9%</td>
                            <td>94.3%</td>
                            <td>100%</td>
                        </tr>
                        <tr>
                            <td>Daisy</td>
                            <td>Grade 4</td>
                            <td>Pedro Penduko</td>
                            <td>36</td>
                            <td>85.6%</td>
                            <td>95.7%</td>
                            <td>97.2%</td>
                        </tr>
                        <tr>
                            <td>Rosal</td>
                            <td>Grade 5</td>
                            <td>Juana Delos Reyes</td>
                            <td>34</td>
                            <td>83.7%</td>
                            <td>94.1%</td>
                            <td>97.1%</td>
                        </tr>
                        <tr>
                            <td>Lily</td>
                            <td>Grade 6</td>
                            <td>Jose Rizal</td>
                            <td>30</td>
                            <td>88.3%</td>
                            <td>96.2%</td>
                            <td>100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();

            @if (isset($studentPerformance) && $studentPerformance->count() > 0)
                // Initialize DataTable for cumulative data
                $('#cumulativeTable').DataTable({
                    responsive: true,
                    order: [
                        [0, 'asc']
                    ]
                });
            @endif

        });
    </script>
@endpush
