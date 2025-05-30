@extends('admin.layout')

@section('title', 'Grades Report')

@section('header', 'Student Grades Report')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Grade Summary</h6>
            <div class="d-flex">
                <select class="form-select form-select-sm me-2" id="sectionFilter">
                    <option value="">All Sections</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                            {{ $section->name }}
                        </option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm me-2" id="subjectFilter">
                    <option value="">All Subjects</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm" id="applyFilter">
                    <i data-feather="filter"></i> Apply
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-primary h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Average Grade</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ number_format($averageGrade, 2) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                        Highest Grade</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ number_format($highestGrade, 2) }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
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
                                        Lowest Grade</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($lowestGrade, 2) }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
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
                                        Passing Rate</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $passingRate }}%</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-percent fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Grade Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="gradeDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Performance Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4">
                                <canvas id="performancePieChart"></canvas>
                            </div>
                            <div class="mt-4 text-center small">
                                <span class="me-3">
                                    <i class="fas fa-circle text-success"></i> Excellent (90-100)
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-circle text-info"></i> Good (80-89)
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-circle text-primary"></i> Average (70-79)
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-circle text-warning"></i> Needs Improvement (60-69)
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-circle text-danger"></i> Failing (Below 60)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Detailed Grade Report</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="exportDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="download"></i> Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" id="exportPDF">PDF</a></li>
                            <li><a class="dropdown-item" href="#" id="exportExcel">Excel</a></li>
                            <li><a class="dropdown-item" href="#" id="exportCSV">CSV</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="gradesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Section</th>
                                    <th>Subject</th>
                                    <th>1st Quarter</th>
                                    <th>2nd Quarter</th>
                                    <th>3rd Quarter</th>
                                    <th>4th Quarter</th>
                                    <th>Final Grade</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grades as $grade)
                                    @php
                                        $finalGrade =
                                            ($grade->first_quarter +
                                                $grade->second_quarter +
                                                $grade->third_quarter +
                                                $grade->fourth_quarter) /
                                            4;
                                        $remarks = $finalGrade >= 75 ? 'Passed' : 'Failed';
                                        $rowClass =
                                            $finalGrade >= 90
                                                ? 'table-success'
                                                : ($finalGrade >= 80
                                                    ? 'table-info'
                                                    : ($finalGrade >= 70
                                                        ? ''
                                                        : ($finalGrade >= 60
                                                            ? 'table-warning'
                                                            : 'table-danger')));
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td>{{ $grade->student->name ?? 'N/A' }}</td>
                                        <td>{{ $grade->student->section->name ?? 'N/A' }}</td>
                                        <td>{{ $grade->subject->name ?? 'N/A' }}</td>
                                        <td class="text-center">{{ number_format($grade->first_quarter, 2) }}</td>
                                        <td class="text-center">{{ number_format($grade->second_quarter, 2) }}</td>
                                        <td class="text-center">{{ number_format($grade->third_quarter, 2) }}</td>
                                        <td class="text-center">{{ number_format($grade->fourth_quarter, 2) }}</td>
                                        <td class="text-center fw-bold">{{ number_format($finalGrade, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $remarks == 'Passed' ? 'success' : 'danger' }}">
                                                {{ $remarks }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info"
                                                onclick="viewGradeDetails({{ $grade->id }})" data-bs-toggle="tooltip"
                                                title="View Details">
                                                <i data-feather="eye" class="feather-sm"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning text-white"
                                                onclick="editGrade({{ $grade->id }})" data-bs-toggle="tooltip"
                                                title="Edit Grade">
                                                <i data-feather="edit-2" class="feather-sm"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No grade records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

    <!-- Grade Details Modal -->
    <div class="modal fade" id="gradeDetailsModal" tabindex="-1" aria-labelledby="gradeDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gradeDetailsModalLabel">Grade Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="gradeDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                    <div class="text-center my-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printGradeBtn">
                        <i data-feather="printer" class="feather-sm me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <!-- Custom styles for this page -->
        <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
        <!-- Page level plugins -->
        <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize DataTable with export buttons
                var table = $('#gradesTable').DataTable({
                    responsive: true,
                    order: [
                        [0, 'asc']
                    ],
                    dom: 'Bfrtip',
                    buttons: [
                        'copy', 'csv', 'excel', 'pdf', 'print'
                    ]
                });

                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                var gradeDistributionData = @json($gradeDistribution);
                var performanceSummaryData = @json($performanceSummary);

                var gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');
                var gradeChart = new Chart(gradeCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(gradeDistributionData),
                        datasets: [{
                            label: 'Number of Students',
                            data: Object.values(gradeDistributionData),
                            backgroundColor: [
                                'rgba(28, 200, 138, 0.7)',
                                'rgba(54, 185, 204, 0.7)',
                                'rgba(78, 115, 223, 0.7)',
                                'rgba(246, 194, 62, 0.7)',
                                'rgba(231, 74, 59, 0.7)'
                            ],
                            borderColor: [
                                'rgba(28, 200, 138, 1)',
                                'rgba(54, 185, 204, 1)',
                                'rgba(78, 115, 223, 1)',
                                'rgba(246, 194, 62, 1)',
                                'rgba(231, 74, 59, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                var pieCtx = document.getElementById('performancePieChart').getContext('2d');
                var pieChart = new Chart(pieCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Excellent (90-100)', 'Good (80-89)', 'Average (70-79)',
                            'Needs Improvement (60-69)', 'Failing (Below 60)'
                        ],
                        datasets: [{
                            data: Object.values(performanceSummaryData),
                            backgroundColor: ['#1cc88a', '#36b9cc', '#4e73df', '#f6c23e', '#e74a3b'],
                            hoverBackgroundColor: ['#17a673', '#2c9faf', '#2e59d9', '#dda20a',
                                '#be2617'
                            ]
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        tooltips: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyFontColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            xPadding: 15,
                            yPadding: 15,
                            displayColors: false,
                            caretPadding: 10,
                            callbacks: {
                                label: function(tooltipItem, data) {
                                    var dataset = data.datasets[tooltipItem.datasetIndex];
                                    var total = dataset.data.reduce(function(sum, value) {
                                        return sum + value;
                                    }, 0);
                                    var currentValue = dataset.data[tooltipItem.index];
                                    var percentage = Math.floor((currentValue / total) * 100 + 0.5);
                                    return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' +
                                        percentage + '%)';
                                }
                            }
                        },
                        legend: {
                            display: false
                        },
                        cutout: '80%',
                    },
                });

                document.getElementById('applyFilter').addEventListener('click', function() {
                    var sectionId = document.getElementById('sectionFilter').value;
                    var subjectId = document.getElementById('subjectFilter').value;

                    var url = new URL(window.location.href);

                    if (sectionId) {
                        url.searchParams.set('section_id', sectionId);
                    } else {
                        url.searchParams.delete('section_id');
                    }

                    if (subjectId) {
                        url.searchParams.set('subject_id', subjectId);
                    } else {
                        url.searchParams.delete('subject_id');
                    }

                    window.location.href = url.toString();
                });

                document.getElementById('printGradeBtn').addEventListener('click', function() {
                    window.print();
                });
            });

            function viewGradeDetails(gradeId) {
                var modal = new bootstrap.Modal(document.getElementById('gradeDetailsModal'));
                var contentDiv = document.getElementById('gradeDetailsContent');

                contentDiv.innerHTML = `
        <div class="text-center my-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;

                modal.show();

                setTimeout(() => {
                    contentDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Student Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Name:</th>
                            <td>John Doe</td>
                        </tr>
                        <tr>
                            <th>Student ID:</th>
                            <td>STU-001</td>
                        </tr>
                        <tr>
                            <th>Section:</th>
                            <td>Grade 10 - Section A</td>
                        </tr>
                        <tr>
                            <th>Subject:</th>
                            <td>Mathematics</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Grade Details</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>1st Quarter:</th>
                            <td class="text-end">92.50</td>
                        </tr>
                        <tr>
                            <th>2nd Quarter:</th>
                            <td class="text-end">88.75</td>
                        </tr>
                        <tr>
                            <th>3rd Quarter:</th>
                            <td class="text-end">95.25</td>
                        </tr>
                        <tr>
                            <th>4th Quarter:</th>
                            <td class="text-end">90.00</td>
                        </tr>
                        <tr class="table-active">
                            <th>Final Grade:</th>
                            <td class="text-end fw-bold">91.63</td>
                        </tr>
                        <tr>
                            <th>Remarks:</th>
                            <td class="text-end"><span class="badge bg-success">Passed</span></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Performance Summary</h6>
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">1st Q</div>
                        <div class="progress-bar bg-info" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">2nd Q</div>
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">3rd Q</div>
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">4th Q</div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <h6>Teacher's Comments</h6>
                <div class="alert alert-light">
                    John has shown excellent performance in Mathematics this school year.
                    He consistently demonstrates a strong understanding of the concepts
                    and actively participates in class discussions.
                </div>
            </div>`;
                }, 1000);
            }

            function editGrade(gradeId) {
                alert('Edit grade with ID: ' + gradeId);
            }
        </script>
    @endpush
