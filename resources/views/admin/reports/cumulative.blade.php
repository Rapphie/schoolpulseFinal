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
    <h2 class="mb-4">Cumulative Performance Report</h2>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card report-card">
                <div class="card-body">
                    <select class="form-select" id="academicYear">
                        <option value="2024-2025" selected>School Year 2024-2025</option>
                        <option value="2023-2024">School Year 2023-2024</option>
                        <option value="2022-2023">School Year 2022-2023</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card report-card">
                <div class="card-body">
                    <select class="form-select" id="gradeLevelFilter">
                        <option value="">All Grade Levels</option>
                        <option>Grade 1</option>
                        <option>Grade 2</option>
                        <option>Grade 3</option>
                        <option>Grade 4</option>
                        <option>Grade 5</option>
                        <option>Grade 6</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card report-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <button class="btn btn-primary" id="generateReport">
                        <i data-feather="bar-chart-2" class="me-1"></i> Generate Report
                    </button>
                    <button class="btn btn-success ms-2" id="exportReport">
                        <i data-feather="download" class="me-1"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-circle bg-primary-light me-3">
                            <i data-feather="users"></i>
                        </div>
                        <div>
                            <div class="text-xs text-uppercase mb-1 text-primary font-weight-bold">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold">856</div>
                        </div>
                    </div>
                    <div class="text-muted small">Compared to previous year: <span class="text-success">+3.2%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon-circle bg-success-light me-3">
                            <i data-feather="award"></i>
                        </div>
                        <div>
                            <div class="text-xs text-uppercase mb-1 text-success font-weight-bold">Average Grade</div>
                            <div class="h5 mb-0 font-weight-bold">85.4%</div>
                        </div>
                    </div>
                    <div class="text-muted small">Compared to previous year: <span class="text-success">+1.8%</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card summary-card h-100">
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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Performance Trend Chart
            const trendCtx = document.getElementById('performanceTrendChart').getContext('2d');
            const performanceTrendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: ['First Quarter', 'Second Quarter', 'Third Quarter', 'Fourth Quarter'],
                    datasets: [{
                        label: 'SY 2024-2025',
                        data: [82.5, 84.3, 85.8, 86.7],
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#ffffff',
                        pointHoverBackgroundColor: '#ffffff',
                        pointHoverBorderColor: '#4e73df',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        lineTension: 0.3
                    }, {
                        label: 'SY 2023-2024',
                        data: [81.2, 82.7, 83.6, 84.9],
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        pointBackgroundColor: '#1cc88a',
                        pointBorderColor: '#ffffff',
                        pointHoverBackgroundColor: '#ffffff',
                        pointHoverBorderColor: '#1cc88a',
                        pointBorderWidth: 2,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        lineTension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 75,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + '%';
                                }
                            }
                        }
                    }
                }
            });

            // Initialize Subject Performance Chart
            const subjectCtx = document.getElementById('subjectPerformanceChart').getContext('2d');
            const subjectPerformanceChart = new Chart(subjectCtx, {
                type: 'radar',
                data: {
                    labels: ['Math', 'Science', 'English', 'Filipino', 'Araling Panlipunan', 'MAPEH'],
                    datasets: [{
                        label: 'Average Grade',
                        data: [88, 83, 86, 85, 82, 89],
                        backgroundColor: 'rgba(78, 115, 223, 0.2)',
                        borderColor: '#4e73df',
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#ffffff',
                        pointHoverBackgroundColor: '#ffffff',
                        pointHoverBorderColor: '#4e73df',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            min: 70,
                            max: 100,
                            ticks: {
                                stepSize: 10,
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.raw + '%';
                                }
                            }
                        }
                    }
                }
            });

            // Handle generate report button
            document.getElementById('generateReport').addEventListener('click', function() {
                // In a real application, this would fetch data from the server
                alert('Generating cumulative report based on selected filters...');
            });

            // Handle export button
            document.getElementById('exportReport').addEventListener('click', function() {
                // In a real application, this would trigger an export
                alert('Exporting cumulative report...');
            });

            // Initialize Feather Icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>
@endpush
