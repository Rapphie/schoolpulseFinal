@extends('admin.layout')

@section('title', 'Least Learned Competencies Report')

@section('internal-css')
    <style>
        .report-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
        }

        .competency-card {
            transition: all 0.3s ease;
        }

        .competency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0.15rem rgba(58, 59, 69, 0.2);
        }

        .progress {
            height: 10px;
        }

        .progress-bar-warning {
            background-color: #f6c23e;
        }

        .progress-bar-danger {
            background-color: #e74a3b;
        }

        .chart-container {
            height: 300px;
        }
    </style>
@endsection

@section('content')
    <main>
        <h2 class="mb-4">Least Learned Competencies Report</h2>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card report-card">
                    <div class="card-body">
                        <select class="form-select" id="gradeLevel">
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
            <div class="col-md-3">
                <div class="card report-card">
                    <div class="card-body">
                        <select class="form-select" id="subject">
                            <option value="">All Subjects</option>
                            <option>Mathematics</option>
                            <option>English</option>
                            <option>Science</option>
                            <option>Filipino</option>
                            <option>Araling Panlipunan</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card report-card">
                    <div class="card-body">
                        <select class="form-select" id="gradingPeriod">
                            <option value="first">First Grading</option>
                            <option value="second">Second Grading</option>
                            <option value="third">Third Grading</option>
                            <option value="fourth">Fourth Grading</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
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

        <div class="card report-card mb-4">
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="llcChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card report-card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Least Learned Competencies</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Competency Code</th>
                                <th>Description</th>
                                <th>Subject</th>
                                <th>Grade Level</th>
                                <th>Mastery Rate</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>MATH1Q1-a</td>
                                <td>Visualize and represent numbers from 0 to 100 using a variety of materials</td>
                                <td>Mathematics</td>
                                <td>Grade 1</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-danger" role="progressbar" style="width: 35%"
                                            aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small">35% mastery</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                </td>
                            </tr>
                            <tr>
                                <td>ENG2Q1-c</td>
                                <td>Use common expressions and appropriate gestures in conversations</td>
                                <td>English</td>
                                <td>Grade 2</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-warning" role="progressbar" style="width: 45%"
                                            aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small">45% mastery</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                </td>
                            </tr>
                            <tr>
                                <td>SCI3Q2-b</td>
                                <td>Describe the characteristics of solids, liquids, and gases</td>
                                <td>Science</td>
                                <td>Grade 3</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-danger" role="progressbar" style="width: 38%"
                                            aria-valuenow="38" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small">38% mastery</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Chart
            const ctx = document.getElementById('llcChart').getContext('2d');
            const llcChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['MATH1Q1-a', 'ENG2Q1-c', 'SCI3Q2-b', 'FIL4Q1-d', 'AP5Q2-a'],
                    datasets: [{
                        label: 'Mastery Rate (%)',
                        data: [35, 45, 38, 52, 48],
                        backgroundColor: [
                            'rgba(231, 74, 59, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(231, 74, 59, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(246, 194, 62, 0.8)'
                        ],
                        borderColor: [
                            'rgb(231, 74, 59)',
                            'rgb(246, 194, 62)',
                            'rgb(231, 74, 59)',
                            'rgb(246, 194, 62)',
                            'rgb(246, 194, 62)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Mastery Rate (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Competency Code'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.raw + '% mastery rate';
                                }
                            }
                        }
                    }
                }
            });

            // Handle generate report button
            document.getElementById('generateReport').addEventListener('click', function() {
                // In a real application, this would fetch data from the server
                alert('Generating report based on selected filters...');
            });

            // Handle export button
            document.getElementById('exportReport').addEventListener('click', function() {
                // In a real application, this would trigger an export
                alert('Exporting report...');
            });
        });
    </script>
@endpush
