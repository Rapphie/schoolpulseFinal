@extends('admin.layout')

@section('title', 'Enrollees Report')


@section('content')
    <main class="p-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Enrollment Statistics</h6>
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
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-primary h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Students</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $sections->sum('students_count') }}</div>
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
                                        Total Sections</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $sections->count() }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-layer-group fa-2x text-gray-300"></i>
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
                                        Average per Section</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $sections->count() > 0 ? round($sections->avg('students_count'), 1) : 0 }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calculator fa-2x text-gray-300"></i>
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
                                        Largest Section</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $sections->max('students_count') ?? 0 }} students
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Enrollment by Section</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-bar">
                                <canvas id="enrollmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Student Distribution by Grade Level</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4">
                                <canvas id="sectionPieChart"></canvas>
                            </div>
                            <div class="mt-4 text-center small" id="section-legend">
                                <!-- Legend will be inserted here by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detailed Enrollment by Grade Level</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="enrollmentTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Grade Level</th>
                                    <th>Number of Sections</th>
                                    <th>Total Students</th>
                                    <th>Average per Section</th>
                                    <th>Percentage</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalStudents = $sections->sum('students_count');
                                    $gradeLevels = $sections->pluck('grade_level')->unique()->sort()->values();
                                @endphp

                                @foreach ($gradeLevels as $grade)
                                    @php
                                        $gradeSections = $sections->where('grade_level', $grade);
                                        $gradeStudentCount = $gradeSections->sum('students_count');
                                        $sectionCount = $gradeSections->count();
                                        $averagePerSection = $sectionCount > 0 ? round($gradeStudentCount / $sectionCount, 1) : 0;
                                        $percentage = $totalStudents > 0 ? ($gradeStudentCount / $totalStudents) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td><strong>Grade {{ $grade }}</strong></td>
                                        <td>{{ $sectionCount }}</td>
                                        <td>{{ $gradeStudentCount }}</td>
                                        <td>{{ $averagePerSection }}</td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: {{ $percentage }}%; background-color: {{ $sectionsByGrade[$grade]['color'] ?? '#0d6efd' }}"
                                                    aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                                    {{ number_format($percentage, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary toggle-sections" data-grade="{{ $grade }}">
                                                <i data-feather="chevron-down" class="feather-sm"></i> Show Sections
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Section details for this grade level (initially hidden) -->
                                    <tr class="grade-sections grade-{{ $grade }}-sections" style="display: none;">
                                        <td colspan="6" class="p-0">
                                            <table class="table mb-0">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Section Name</th>
                                                        <th>Students</th>
                                                        <th>% of Grade</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($gradeSections->sortBy('name') as $section)
                                                        @php
                                                            $sectionPercentage = $gradeStudentCount > 0 ? ($section->students_count / $gradeStudentCount) * 100 : 0;
                                                        @endphp
                                                        <tr>
                                                            <td class="ps-4">{{ $section->name }}</td>
                                                            <td>{{ $section->students_count }}</td>
                                                            <td>{{ number_format($sectionPercentage, 1) }}%</td>
                                                            <td>
                                                                <a href="{{ route('admin.sections.show', $section->id) }}" class="btn btn-sm btn-info">
                                                                    <i data-feather="eye" class="feather-sm"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td>Total</td>
                                    <td>{{ $sections->count() }}</td>
                                    <td>{{ $totalStudents }}</td>
                                    <td>{{ $sections->count() > 0 ? round($totalStudents / $sections->count(), 1) : 0 }}</td>
                                    <td>100%</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@push('styles')
    <!-- Custom styles for this page -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <!-- Page level plugins -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables Buttons Extension -->
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            $('#enrollmentTable').DataTable({
                responsive: true,
                order: [
                    [1, 'desc']
                ],
                dom: '<"d-none"B>frtip', // Hide the default buttons but keep them accessible
                buttons: [{
                        extend: 'copy',
                        className: 'buttons-copy',
                        exportOptions: {
                            columns: [0, 1, 2] // Exclude Actions column
                        }
                    },
                    {
                        extend: 'csv',
                        className: 'buttons-csv',
                        title: 'Enrollees_Report',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    },
                    {
                        extend: 'excel',
                        className: 'buttons-excel',
                        title: 'Enrollees_Report',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    },
                    {
                        extend: 'pdf',
                        className: 'buttons-pdf',
                        title: 'Enrollees_Report',
                        exportOptions: {
                            columns: [0, 1, 2]
                        },
                        customize: function(doc) {
                            doc.content[1].table.widths = ['40%', '30%', '30%'];
                            doc.styles.tableHeader.alignment = 'left';
                            doc.content.splice(0, 1, {
                                text: 'Enrollees Report',
                                style: 'header',
                                margin: [0, 0, 0, 12]
                            });
                            doc.styles.header = {
                                fontSize: 18,
                                bold: true
                            };
                        }
                    },
                    {
                        extend: 'print',
                        className: 'buttons-print',
                        title: 'Enrollees Report',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    }
                ]
            });

            // Organize sections by grade level
            var gradeLevels = @json($sections->pluck('grade_level')->unique()->sort()->values());
            var sectionsByGrade = {};
            var colorsByGrade = {};

            // Initialize datasets structure
            gradeLevels.forEach(function(grade) {
                sectionsByGrade[grade] = {
                    sections: [],
                    counts: [],
                    color: getRandomColor()
                };
            });

            // Group sections by grade level
            @foreach ($sections as $section)
                sectionsByGrade[{{ $section->grade_level }}].sections.push("{{ $section->name }}");
                sectionsByGrade[{{ $section->grade_level }}].counts.push({{ $section->students_count }});
            @endforeach

            // Enrollment Bar Chart - grouped by grade level
            var ctx = document.getElementById('enrollmentChart').getContext('2d');

            // Prepare datasets for the chart
            var datasets = [];
            gradeLevels.forEach(function(grade) {
                datasets.push({
                    label: 'Grade ' + grade,
                    backgroundColor: sectionsByGrade[grade].color,
                    borderColor: sectionsByGrade[grade].color.replace('0.7', '1'),
                    borderWidth: 1,
                    data: sectionsByGrade[grade].counts,
                    // Add x axis categories for each data point
                    xAxisID: 'x',
                });
            });

            // Get all unique section names across all grades
            var allSectionNames = [];
            gradeLevels.forEach(function(grade) {
                sectionsByGrade[grade].sections.forEach(function(section) {
                    if (!allSectionNames.includes(section)) {
                        allSectionNames.push(section);
                    }
                });
            });

            var myBarChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: gradeLevels.map(grade => 'Grade ' + grade),
                    datasets: [{
                        label: 'Number of Students by Grade Level',
                        backgroundColor: gradeLevels.map(grade => sectionsByGrade[grade].color),
                        borderColor: gradeLevels.map(grade => sectionsByGrade[grade].color.replace(
                            '0.7', '1')),
                        borderWidth: 1,
                        data: gradeLevels.map(grade =>
                            sectionsByGrade[grade].counts.reduce((sum, count) => sum + count, 0)
                        ),
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' students';
                                }
                            }
                        }
                    }
                }
            });

            // Section Distribution Pie Chart (by Grade Level)
            var pieCtx = document.getElementById('sectionPieChart').getContext('2d');

            // Prepare data for the pie chart by grade level
            var gradeLabels = gradeLevels.map(grade => 'Grade ' + grade);
            var gradeStudentCounts = gradeLevels.map(grade =>
                sectionsByGrade[grade].counts.reduce((sum, count) => sum + count, 0)
            );
            var gradeColors = gradeLevels.map(grade => sectionsByGrade[grade].color);

            var pieChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: gradeLabels,
                    datasets: [{
                        data: gradeStudentCounts,
                        backgroundColor: gradeColors,
                        borderColor: '#fff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.raw || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} students (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Generate legend for pie chart
            var legendHtml = '';
            gradeLabels.forEach((gradeLabel, index) => {
                legendHtml += `
            <span class="me-3">
                <i class="fas fa-circle me-1" style="color:${gradeColors[index]}"></i>
                ${gradeLabel}(${gradeStudentCounts[index]})
            </span>
        `;
            });
            document.getElementById('section-legend').innerHTML = legendHtml;

            // Export button click handlers
            document.getElementById('exportPDF').addEventListener('click', function(e) {
                e.preventDefault();
                exportData('pdf');
            });

            document.getElementById('exportExcel').addEventListener('click', function(e) {
                e.preventDefault();
                // Use Laravel's export endpoint for Excel (better formatting)
                const selectedGrade = new URLSearchParams(window.location.search).get('grade') || '';
                window.location.href = "{{ route('reports.export.enrollees') }}" + (selectedGrade ?
                    '?grade=' + selectedGrade : '');
            });

            document.getElementById('exportCSV').addEventListener('click', function(e) {
                e.preventDefault();
                exportData('csv');
            });

            // Function to handle DataTables client-side exports (PDF & CSV)
            function exportData(format) {
                const table = $('#enrollmentTable').DataTable();

                switch (format) {
                    case 'pdf':
                        table.button('.buttons-pdf').trigger();
                        break;
                    case 'csv':
                        table.button('.buttons-csv').trigger();
                        break;
                }
            }
        });

        function getRandomColor() {
            var letters = '0123456789ABCDEF';
            var color = 'rgba(';
            // Generate RGB values
            for (var i = 0; i < 3; i++) {
                color += Math.floor(Math.random() * 200) + 55 + ',';
            }
            // Add alpha value
            color += '0.7)';
            return color;
        }

        function viewSectionDetails(sectionId) {
            window.location.href = `{{ url('/admin/students/section') }}/${sectionId}`;
        }

        // Initialize toggle functionality for the grade sections
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize feather icons after AJAX content is loaded
            function initFeatherIcons() {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }

            // Initialize feather icons initially
            initFeatherIcons();

            // Add event listeners to toggle section visibility
            document.querySelectorAll('.toggle-sections').forEach(button => {
                button.addEventListener('click', function() {
                    const grade = this.getAttribute('data-grade');
                    const sectionsRow = document.querySelector(`.grade-${grade}-sections`);
                    const icon = this.querySelector('svg.feather');

                    if (sectionsRow.style.display === 'none') {
                        // Show sections
                        sectionsRow.style.display = '';
                        this.innerHTML = '<i data-feather="chevron-up" class="feather-sm"></i> Hide Sections';
                    } else {
                        // Hide sections
                        sectionsRow.style.display = 'none';
                        this.innerHTML = '<i data-feather="chevron-down" class="feather-sm"></i> Show Sections';
                    }

                    // Re-initialize feather icons after changing the content
                    initFeatherIcons();
                });
            });

            // Call feather icons at the end of initialization as well
            setTimeout(initFeatherIcons, 100);
        });
    </script>
@endpush
