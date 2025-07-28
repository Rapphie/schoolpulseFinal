@extends('base')

@section('title', 'Attendance Report')
@section('head')
    <!-- Preconnect to external domains to speed up resource loading -->
    <link rel="preconnect" href="https://cdn.datatables.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preload" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <noscript>
        <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    </noscript>
@endsection
@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Overview</h6>
            <div>
                <div class="input-group">
                    <input type="month" class="form-control" id="monthFilter" value="{{ date('Y-m') }}">
                    <button class="btn btn-outline-primary" type="button" id="applyFilter">
                        <i data-feather="filter"></i> Apply
                    </button>
                </div>
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
                                        Present Today</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $todayPresentCount ?? 0 }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                                        This Month's Attendance</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $monthlyAttendanceRate ?? 0 }}%
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-percent fa-2x text-gray-300"></i>
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
                                        Total Absences</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalAbsences ?? 0 }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-times fa-2x text-gray-300"></i>
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
                                        Late Arrivals</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $lateArrivalsCount ?? 0 }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Trend</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="attendanceTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Attendance Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div id="attendancePieChart" style="height: 300px;">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-center small">
                                <span class="me-3">
                                    <i class="fas fa-circle text-success"></i> Present
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-circle text-danger"></i> Absent
                                </span>
                                <span class="me-3">
                                    <i class="fas fa-circle text-warning"></i> Late
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Detailed Attendance Records</h6>
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
                        <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student Name</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords as $attendance)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</td>
                                        <td>{{ $attendance->student ? $attendance->student->full_name : 'N/A' }}</td>
                                        <td>{{ $attendance->student && $attendance->student->section ? $attendance->student->section->name : 'N/A' }}
                                        </td>
                                        <td>
                                            @if ($attendance->status == 'present')
                                                <span class="badge bg-success">Present</span>
                                            @elseif($attendance->status == 'late')
                                                <span class="badge bg-warning text-dark">Late</span>
                                            @else
                                                <span class="badge bg-danger">Absent</span>
                                            @endif
                                        </td>
                                        <td>{{ $attendance->time_in ?? 'N/A' }}</td>
                                        {{-- <td>{{ $attendance->time_out ?? 'N/A' }}</td> --}}
                                        <td>
                                            <button class="btn btn-sm btn-info"
                                                onclick="viewAttendanceDetails({{ $attendance->id }})"
                                                data-bs-toggle="tooltip" title="View Details">
                                                <i data-feather="eye" class="feather-sm"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning text-white"
                                                onclick="editAttendance({{ $attendance->id }})" data-bs-toggle="tooltip"
                                                title="Edit">
                                                <i data-feather="edit-2" class="feather-sm"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No attendance records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Add Laravel's pagination links -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $attendanceRecords->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Attendance Details Modal -->
    <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" aria-labelledby="attendanceDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceDetailsModalLabel">Attendance Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="attendanceDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                    <div class="text-center my-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printAttendanceBtn">
                        <i data-feather="printer" class="feather-sm me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <!-- Defer non-critical scripts -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js" defer></script>

    <!-- DataTables export plugins (load on demand) -->
    <script>
        // Function to dynamically load scripts
        function loadScript(url, callback) {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = url;
            script.defer = true;

            if (callback) {
                if (script.readyState) { // IE
                    script.onreadystatechange = function() {
                        if (script.readyState === 'loaded' || script.readyState === 'complete') {
                            script.onreadystatechange = null;
                            callback();
                        }
                    };
                } else { // Other browsers
                    script.onload = function() {
                        callback();
                    };
                }
            }

            document.head.appendChild(script);
        }

        // Load export functionality when interacting with export dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const exportDropdown = document.getElementById('exportDropdown');
            if (exportDropdown) {
                exportDropdown.addEventListener('click', function loadExportScripts() {
                    // Remove the listener so we only load once
                    exportDropdown.removeEventListener('click', loadExportScripts);

                    const scripts = [
                        'https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js',
                        'https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js',
                        'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js',
                        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js',
                        'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js',
                        'https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js',
                        'https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js'
                    ];

                    // Load scripts sequentially
                    let i = 0;

                    function loadNextScript() {
                        if (i < scripts.length) {
                            loadScript(scripts[i], function() {
                                i++;
                                loadNextScript();
                            });
                        } else {
                            // All scripts loaded, initialize export buttons
                            initializeExportButtons();
                        }
                    }

                    loadNextScript();
                });
            }
        });

        // Inner document ready function
        document.addEventListener('DOMContentLoaded', function() {
            initPieChart();
            // Initialize DataTables lazily when user interacts with the page
            const lazyInitDataTables = () => {
                // Initialize attendance table
                if ($.fn.dataTable.isDataTable('#attendanceTable')) return;

                $('#attendanceTable').DataTable({
                    "pageLength": 10,
                    "language": {
                        "paginate": {
                            "previous": "&laquo;",
                            "next": "&raquo;"
                        }
                    },
                    // Configure server-side pagination processing
                    "processing": true,
                    "serverSide": false, // We're using Laravel's pagination already
                    // Enable the built-in pagination controls but use Laravel's pagination behind the scenes
                    "paging": false, // Disable DataTables paging since Laravel handles it
                    "ordering": true,
                    "info": true,
                    "searching": true
                });

                // Remove the event listeners after initialization
                document.removeEventListener('scroll', lazyInitDataTables);
                document.removeEventListener('mousemove', lazyInitDataTables);
            };

            // Initialize DataTables when user begins to interact with the page
            document.addEventListener('scroll', lazyInitDataTables, {
                passive: true
            });
            document.addEventListener('mousemove', lazyInitDataTables, {
                passive: true
            });

            // Initialize tooltips only when they're needed
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            if (tooltipTriggerList.length > 0) {
                const initTooltips = () => {
                    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
                    document.removeEventListener('mouseover', initTooltips);
                };
                document.addEventListener('mouseover', initTooltips, {
                    passive: true
                });

                // Lazy load charts when they're in viewport and Chart.js is loaded
                const observeElement = (elementId, callback) => {
                    const element = document.getElementById(elementId);
                    if (!element) {
                        console.error(`Element with id '${elementId}' not found`);
                        return;
                    }

                    console.log(`Setting up observer for ${elementId}`);

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                console.log(
                                    `${elementId} is visible, preparing to initialize chart`
                                );
                                // Ensure Chart.js is loaded first
                                loadChartJs().then(() => {
                                    console.log(
                                        `Chart.js is loaded, initializing ${elementId}`
                                    );
                                    callback();
                                }).catch(error => {
                                    console.error(
                                        `Failed to load Chart.js for ${elementId}:`,
                                        error);
                                });
                                observer.disconnect();
                            }
                        });
                    }, {
                        threshold: 0.1
                    });

                    observer.observe(element);
                    console.log(`Observer set for ${elementId}`);
                };

                var monthlyData = @json($monthlyData ?? []);
                var labels = Object.keys(monthlyData);
                var presentData = [];
                var absentData = [];
                var lateData = [];

                labels.forEach(function(date) {
                    presentData.push(monthlyData[date]?.present || 0);
                    absentData.push(monthlyData[date]?.absent || 0);
                    lateData.push(monthlyData[date]?.late || 0);
                });

                // Initialize trend chart only when it's visible and Chart is available
                observeElement('attendanceTrendChart', () => {
                    console.log('Initializing trend chart now that Chart.js is loaded');
                    initTrendChart();
                });

                function initTrendChart() {
                    console.log('Initializing trend chart');
                    var trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
                    var trendChart = new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Present',
                                data: presentData,
                                borderColor: '#1cc88a',
                                backgroundColor: 'rgba(28, 200, 138, 0.05)',
                                tension: 0.3,
                                fill: true
                            }, {
                                label: 'Late',
                                data: lateData,
                                borderColor: '#f6c23e',
                                backgroundColor: 'rgba(246, 194, 62, 0.05)',
                                tension: 0.3,
                                fill: true
                            }, {
                                label: 'Absent',
                                data: absentData,
                                borderColor: '#e74a3b',
                                backgroundColor: 'rgba(231, 74, 59, 0.05)',
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                }
                            },
                            scales: {
                                x: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                },
                                y: {
                                    display: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Students'
                                    },
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });

                    console.log('Trend chart initialized successfully');
                }
            });

        // Initialize pie chart only when it's visible and Chart is available
        observeElement('attendancePieChart', () => {
            console.log('Initializing pie chart now that Chart.js is loaded');
            initPieChart();
        });

        function initPieChart() {
            var pieCtx = document.getElementById('attendancePieChart').getContext('2d');
            var pieChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Present', 'Absent', 'Late'],
                    datasets: [{
                        data: [
                            {{ $presentCount ?? 0 }},
                            {{ $absentCount ?? 0 }},
                            {{ $lateCount ?? 0 }}
                        ],
                        backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e'],
                        hoverBackgroundColor: ['#17a673', '#be2617', '#dda20a'],
                        hoverBorderColor: 'rgba(234, 236, 244, 1)',
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: { // Updated for Chart.js v3+
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            padding: 15,
                            displayColors: false,
                            caretPadding: 10,
                        }
                    },
                    cutout: '80%',
                },
            });

            console.log('Pie chart initialized successfully');
        }
        });

        // Function to initialize export buttons
        function initializeExportButtons() {
            // Implementation would go here when export scripts are loaded
            console.log('Export buttons initialized');
        }

        // Attach event listeners using event delegation where possible
        document.addEventListener('click', function(e) {
            // Handle filter button
            if (e.target.closest('#applyFilter')) {
                var month = document.getElementById('monthFilter').value;
                window.location.href = '{{ route('admin.records') }}?month=' + month;
            }


        });

        // Lazy load feather icons
        if (window.feather) {
            const featherInit = () => {
                feather.replace({
                    'stroke-width': 1.5
                });
                document.removeEventListener('DOMContentLoaded', featherInit);
            };
            document.addEventListener('DOMContentLoaded', featherInit);
        }
        });

        // Optimize modal functions
        function viewAttendanceDetails(attendanceId) {
            // Only initialize modal when needed
            if (!window.attendanceModal) {
                window.attendanceModal = new bootstrap.Modal(document.getElementById('attendanceDetailsModal'));
            }

            var contentDiv = document.getElementById('attendanceDetailsContent');

            // Show loading spinner
            contentDiv.innerHTML = `
        <div class="text-center my-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>`;

            window.attendanceModal.show();

            // In a real application, this would fetch data from server
            // Simulate API delay with a short timeout
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
                            <th>Section:</th>
                            <td>Grade 10 - Section A</td>
                        </tr>
                        <tr>
                            <th>Date:</th>
                            <td>${new Date().toLocaleDateString()}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Attendance Details</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Status:</th>
                            <td><span class="badge bg-success">Present</span></td>
                        </tr>
                        <tr>
                            <th>Time In:</th>
                            <td>07:30 AM</td>
                        </tr>
                        <tr>
                            <th>Time Out:</th>
                            <td>03:15 PM</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="mt-3">
                <h6>Notes</h6>
                <p>No additional notes.</p>
            </div>`;
            }, 1000);
        }

        function editAttendance(attendanceId) {
            alert('Edit attendance with ID: ' + attendanceId);
        }
        });
        });
    </script>
@endpush
