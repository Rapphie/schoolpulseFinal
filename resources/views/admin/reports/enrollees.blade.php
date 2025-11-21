@extends('base')

@section('title', 'Enrollees Report')


@section('content')
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
                                    {{ $totalStudents }}</div>
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
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSections }}</div>
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
                                    {{ $averagePerSection }}
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
                                    {{ $largestSection }} students
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
                        <div class="chart-bar chart-wrapper">
                            <canvas id="enrollmentChart"></canvas>
                            <div class="chart-empty" id="enrollmentChartEmpty">No data yet</div>
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
                        <div class="chart-pie chart-wrapper pt-4">
                            <canvas id="sectionPieChart"></canvas>
                            <div class="chart-empty" id="sectionPieChartEmpty">No data yet</div>
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
                                $grandTotalStudents = $totalStudents;
                            @endphp

                            @forelse ($sectionsByGrade as $gradeData)
                                @php
                                    $gradeKey = $gradeData['grade'] ?? 'unassigned';
                                    $gradePercentage =
                                        $grandTotalStudents > 0
                                            ? ($gradeData['total_students'] / $grandTotalStudents) * 100
                                            : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ $gradeData['label'] }}</strong></td>
                                    <td>{{ $gradeData['section_count'] }}</td>
                                    <td>{{ $gradeData['total_students'] }}</td>
                                    <td>{{ $gradeData['average_per_section'] }}</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: {{ $gradePercentage }}%; background-color: {{ $gradeData['color'] ?? '#0d6efd' }}"
                                                aria-valuenow="{{ $gradePercentage }}" aria-valuemin="0"
                                                aria-valuemax="100">
                                                {{ number_format($gradePercentage, 1) }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary toggle-sections"
                                            data-grade="{{ $gradeKey }}">
                                            <div class="d-flex align-items-center"><i data-feather="chevron-down"
                                                    class="feather-sm me-1"></i> Show Sections</div>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Section details for this grade level (initially hidden) -->
                                <tr class="grade-sections grade-{{ $gradeKey }}-sections" style="display: none;">
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
                                                @forelse ($gradeData['sections'] as $section)
                                                    <tr>
                                                        <td class="ps-4">{{ $section['name'] }}</td>
                                                        <td>{{ $section['students'] }}</td>
                                                        <td>{{ number_format($section['percentage'], 1) }}%</td>
                                                        <td>
                                                            <a href="{{ route('admin.sections.show', ['section' => $section['id']]) }}"
                                                                class="btn btn-sm btn-info">
                                                                <div class="d-flex align-items-center text-white"><i
                                                                        data-feather="eye" class="feather-sm me-2"></i>
                                                                    View
                                                                </div>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No sections yet.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No enrollment data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td>Total</td>
                                <td>{{ $totalSections }}</td>
                                <td>{{ $totalStudents }}</td>
                                <td>{{ $totalSections > 0 ? round($totalStudents / $totalSections, 1) : 0 }}
                                </td>
                                <td>100%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <!-- Custom styles for this page -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        .chart-wrapper {
            position: relative;
            min-height: 300px;
        }

        .chart-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .chart-empty {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            display: none;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6c757d;
            background: rgba(248, 249, 250, 0.85);
            border-radius: 0.35rem;
        }

        /* Prevent DataTables from processing nested rows */
        .datatable-ignore {
            display: none !important;
        }

        .grade-sections {
            display: none;
        }

        .grade-sections.show {
            display: table-row !important;
        }
    </style>
@endpush

@push('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Page level plugins -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons Extension -->
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

    <script>
        // Ensure jQuery is loaded before running DataTables
        (function() {
            if (typeof jQuery === 'undefined') {
                console.error('jQuery is not loaded!');
                return;
            }

            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }

            console.log('Chart.js version:', Chart.version);

            $(document).ready(function() {
                // Hide nested section rows before DataTables initialization
                $('#enrollmentTable tbody .grade-sections').addClass('datatable-ignore');

                // Initialize DataTable - exclude nested section rows
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
                                columns: [0, 1, 2, 3] // Exclude Actions and Details columns
                            }
                        },
                        {
                            extend: 'csv',
                            className: 'buttons-csv',
                            title: 'Enrollees_Report',
                            exportOptions: {
                                columns: [0, 1, 2, 3],
                                format: {
                                    body: function(data, row, column, node) {
                                        // Remove HTML tags and clean up percentage values
                                        return data.replace(/<[^>]*>/g, '').trim();
                                    }
                                }
                            }
                        },
                        {
                            extend: 'excel',
                            className: 'buttons-excel',
                            title: 'Enrollees_Report',
                            exportOptions: {
                                columns: [0, 1, 2, 3]
                            }
                        },
                        {
                            extend: 'pdf',
                            className: 'buttons-pdf',
                            title: 'Enrollees_Report',
                            exportOptions: {
                                columns: [0, 1, 2, 3]
                            },
                            customize: function(doc) {
                                doc.content[1].table.widths = ['25%', '20%', '20%', '35%'];
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
                                columns: [0, 1, 2, 3]
                            }
                        }
                    ]
                });

                // Temporary sample data for development/testing
                const sampleClassChart = {
                    labels: ['Grade 7-A', 'Grade 7-B', 'Grade 8-A', 'Grade 8-B', 'Grade 9-A', 'Grade 9-B',
                        'Grade 10-A', 'Grade 10-B'
                    ],
                    totals: [35, 32, 38, 30, 33, 36, 34, 31],
                    colors: ['rgba(54, 162, 235, 0.7)', 'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 206, 86, 0.7)', 'rgba(255, 159, 64, 0.7)',
                        'rgba(153, 102, 255, 0.7)', 'rgba(255, 99, 132, 0.7)',
                        'rgba(201, 203, 207, 0.7)', 'rgba(255, 205, 86, 0.7)'
                    ]
                };

                const sampleGradeChart = {
                    labels: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
                    totals: [67, 68, 69, 65],
                    colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                };

                const rawClassChart = @json($classChartData ?? ['labels' => [], 'totals' => [], 'colors' => []]);
                const rawGradeChart = @json($gradeChartData ?? ['labels' => [], 'totals' => [], 'colors' => []]);
                const rawGradeBreakdown = @json($sectionsByGrade ?? []);

                const normalizeArray = (value) => {
                    if (Array.isArray(value)) {
                        return value;
                    }

                    if (value && typeof value === 'object') {
                        return Object.values(value);
                    }

                    return [];
                };

                // Use sample data if backend data is empty
                const hasBackendClassData = rawClassChart && rawClassChart.labels && rawClassChart.labels
                    .length > 0;
                const hasBackendGradeData = rawGradeChart && rawGradeChart.labels && rawGradeChart.labels
                    .length > 0;

                const classChart = {
                    labels: hasBackendClassData ? normalizeArray(rawClassChart.labels) : sampleClassChart
                        .labels,
                    totals: hasBackendClassData ? normalizeArray(rawClassChart.totals).map((value) =>
                        Number(value) || 0) : sampleClassChart.totals,
                    colors: hasBackendClassData ? normalizeArray(rawClassChart.colors) : sampleClassChart
                        .colors,
                };

                const gradeChart = {
                    labels: hasBackendGradeData ? normalizeArray(rawGradeChart.labels) : sampleGradeChart
                        .labels,
                    totals: hasBackendGradeData ? normalizeArray(rawGradeChart.totals).map((value) =>
                        Number(value) || 0) : sampleGradeChart.totals,
                    colors: hasBackendGradeData ? normalizeArray(rawGradeChart.colors) : sampleGradeChart
                        .colors,
                };

                const gradeBreakdown = normalizeArray(rawGradeBreakdown).map((gradeEntry) => {
                    const safeEntry = gradeEntry || {};
                    return {
                        grade: safeEntry.grade ?? null,
                        label: safeEntry.label || 'Unspecified Grade',
                        total_students: Number(safeEntry.total_students) || 0,
                        section_count: Number(safeEntry.section_count) || 0,
                        average_per_section: safeEntry.average_per_section || 0,
                        color: safeEntry.color || '#0d6efd',
                        sections: normalizeArray(safeEntry.sections).map((section) => ({
                            id: section.id,
                            name: section.name,
                            students: Number(section.students) || 0,
                            percentage: Number(section.percentage) || 0,
                        })),
                    };
                });

                const enrollmentChartCanvas = document.getElementById('enrollmentChart');
                const enrollmentChartEmpty = document.getElementById('enrollmentChartEmpty');
                const pieChartCanvas = document.getElementById('sectionPieChart');
                const pieChartEmpty = document.getElementById('sectionPieChartEmpty');
                const legendContainer = document.getElementById('section-legend');

                const classLabels = classChart.labels;
                const classTotals = classChart.totals;
                const classColors = classChart.colors;

                const classTotalsHaveValue = classTotals.some(value => value > 0);
                const hasClassLabels = classLabels.length > 0;
                const classChartHasData = hasClassLabels && classTotalsHaveValue;

                function toOpaqueColor(color) {
                    if (typeof color !== 'string') {
                        return '#0d6efd';
                    }

                    if (color.includes('rgba')) {
                        return color.replace(/0\.7(?=\))/g, '1');
                    }

                    return color;
                }

                function toggleChartState(canvas, emptyState, isActive) {
                    if (!canvas || !emptyState) {
                        return;
                    }

                    canvas.style.display = isActive ? 'block' : 'none';
                    emptyState.style.display = isActive ? 'none' : 'flex';
                }

                function renderEnrollmentChart() {
                    toggleChartState(enrollmentChartCanvas, enrollmentChartEmpty, classChartHasData);

                    if (!classChartHasData || !enrollmentChartCanvas) {
                        return;
                    }

                    new Chart(enrollmentChartCanvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: classLabels,
                            datasets: [{
                                label: 'Number of Students per Class',
                                backgroundColor: classColors.length ? classColors : '#0d6efd',
                                borderColor: classColors.length ? classColors.map(
                                    toOpaqueColor) : '#0d6efd',
                                borderWidth: 1,
                                data: classTotals,
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
                }

                function renderLegend(totals, colors, labels) {
                    if (!legendContainer) {
                        return;
                    }

                    const hasLegendData = totals.some(value => Number(value) > 0);

                    if (!hasLegendData) {
                        legendContainer.innerHTML = '<span class="text-muted">No data yet.</span>';
                        return;
                    }

                    const legendHtml = labels.map(function(label, index) {
                        const color = colors[index] || '#0d6efd';
                        const total = totals[index] || 0;
                        return (
                            '<span class="me-3">' +
                            '<i class="fas fa-circle me-1" style="color:' + color + '"></i>' +
                            label + ' (' + total + ')' +
                            '</span>'
                        );
                    }).join('');

                    legendContainer.innerHTML = legendHtml;
                }

                function renderPieChart() {
                    const pieLabels = gradeChart.labels;
                    const pieTotals = gradeChart.totals;
                    const pieColors = gradeChart.colors;
                    const pieHasData = pieLabels.length > 0 && pieTotals.some(value => Number(value) > 0);

                    toggleChartState(pieChartCanvas, pieChartEmpty, pieHasData);

                    if (!pieHasData || !pieChartCanvas) {
                        renderLegend([], [], []);
                        return;
                    }

                    new Chart(pieChartCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: pieLabels,
                            datasets: [{
                                data: pieTotals,
                                backgroundColor: pieColors,
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
                                            var total = context.dataset.data.reduce((a, b) => a + b,
                                                    0) ||
                                                1;
                                            var percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} students (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    renderLegend(pieTotals, pieColors, pieLabels);
                }

                renderEnrollmentChart();
                renderPieChart();

                // Export button click handlers
                const exportPdfButton = document.getElementById('exportPDF');
                if (exportPdfButton) {
                    exportPdfButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        exportData('pdf');
                    });
                }

                const exportExcelButton = document.getElementById('exportExcel');
                if (exportExcelButton) {
                    exportExcelButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        const selectedGrade = new URLSearchParams(window.location.search).get(
                            'grade') || '';
                        const excelUrl = "{{ route('admin.reports.export.enrollees') }}" +
                            (selectedGrade ? '?grade=' + selectedGrade : '');
                        window.location.href = excelUrl;
                    });
                }

                const exportCsvButton = document.getElementById('exportCSV');
                if (exportCsvButton) {
                    exportCsvButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        const selectedGrade = new URLSearchParams(window.location.search).get(
                            'grade') || '';
                        const baseUrl = "{{ route('admin.reports.export.enrollees') }}";
                        const params = new URLSearchParams();
                        if (selectedGrade) params.append('grade', selectedGrade);
                        params.append('format', 'csv');
                        window.location.href = baseUrl + '?' + params.toString();
                    });
                }

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
        })();

        // Initialize toggle functionality for the grade sections
        $(document).ready(function() {
            // Initialize feather icons after AJAX content is loaded
            function initFeatherIcons() {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }

            // Initialize feather icons initially
            initFeatherIcons();

            function toggleButtonTemplate(isExpanded) {
                var icon = isExpanded ? 'chevron-up' : 'chevron-down';
                var text = isExpanded ? 'Hide Sections' : 'Show Sections';
                return '<div class="d-flex align-items-center"><i data-feather="' + icon +
                    '" class="feather-sm me-1"></i> ' + text + '</div>';
            }

            // Add event listeners to toggle section visibility
            document.querySelectorAll('.toggle-sections').forEach(button => {
                button.addEventListener('click', function() {
                    const grade = this.getAttribute('data-grade');
                    const sectionsRow = document.querySelector(`.grade-${grade}-sections`);
                    if (!sectionsRow) {
                        return;
                    }

                    const isHidden = !sectionsRow.classList.contains('show');
                    sectionsRow.classList.toggle('show');
                    this.innerHTML = toggleButtonTemplate(isHidden);

                    // Re-initialize feather icons after changing the content
                    initFeatherIcons();
                });
            });

            // Call feather icons at the end of initialization as well
            setTimeout(initFeatherIcons, 100);
        });
    </script>
@endpush
