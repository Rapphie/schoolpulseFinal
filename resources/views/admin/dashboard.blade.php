@extends('base')
@section('title', 'Admin - Dashboard')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1 fw-bold text-dark">Welcome back, {{ Auth::user()->first_name }}</h2>
            <p class="text-muted mb-0">Here's what's happening with your school today</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-primary rounded p-2">
                            <i data-feather="users" class="text-primary"></i>
                        </div>
                    </div>
                    <h3 class="mb-1">{{ $enrolledStudents }}</h3>
                    <p class="text-muted mb-0">Enrolled Students</p>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-success rounded p-2">
                            <i data-feather="user-check" class="text-success"></i>
                        </div>
                    </div>
                    <h3 class="mb-1">{{ $teacherCount }}</h3>
                    <p class="text-muted mb-0">Teaching Staff</p>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-warning rounded p-2">
                            <i data-feather="book-open" class="text-warning"></i>
                        </div>
                    </div>
                    <h3 class="mb-1">{{ $sectionCount }}</h3>
                    <p class="text-muted mb-0">Active Sections</p>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-soft-info rounded p-2">
                            <i data-feather="check-circle" class="text-info"></i>
                        </div>
                    </div>
                    <h3 class="mb-1">{{ $todaysAttendance }}</h3>
                    <p class="text-muted mb-0">Attendance Today</p>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 100%" aria-valuenow="100"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Student Enrollment Overview</h5>
                    <div class="d-flex align-items-center">
                        <label class="me-2 mb-0 small text-muted">School Year:</label>
                        <select class="form-select form-select-sm" id="enrollmentYearSelect" style="width: 150px;" disabled>
                            <option value="current" selected>2025-2026 (Current)</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-3">Enrolled Students</h6>
                        <div class="badge bg-soft-primary text-primary">Total: <span
                                id="totalEnrollment">{{ $enrolledStudents }}</span>
                            students</div>
                    </div>
                    <div id="enrollmentChart" style="height: 275px;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div class="small text-muted">Showing enrollment data for <span
                                id="currentSelectedYear">2025-2026</span> school year</div>
                        <div class="d-flex gap-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Enrolled Students</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-success rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Continuing</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-warning rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Transfers</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">Grade-Level Distribution</h5>
                </div>
                <div class="card-body">
                    <div id="classDistributionChart" style="height: 300px;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">School Year Management</h5>
                    <button class="btn btn-sm btn-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#addSchoolYearModal">
                        <i data-feather="plus" class="feather-sm me-1"></i> Add School Year
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-soft-primary">
                                <div class="card-body">
                                    @if ($activeSchoolYear)
                                        <h6 class="text-primary fw-bold">Current School Year</h6>
                                        <h3 id="currentSchoolYear">{{ $activeSchoolYear->name }}</h3>
                                        <p class="mb-2">Status: <span class="badge bg-success">Active</span></p>
                                        <p class="mb-0">Duration:
                                            {{ \Carbon\Carbon::parse($activeSchoolYear->start_date)->format('F j, Y') }}
                                            '' -
                                            {{ \Carbon\Carbon::parse($activeSchoolYear->end_date)->format('F j, Y') }}
                                        </p>
                                    @else
                                        <h6>No Active School Year</h6>
                                    @endif

                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-3">School Year Timeline</h6>
                            <div class="position-relative" id="schoolYearTimeline" style="height: 100px;">
                                <div class="text-center py-2 d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>School Year</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th width="200">Action</th>
                                </tr>
                            </thead>
                            <tbody id="schoolYearTableBody">
                                @forelse ($schoolYears as $year)
                                    {{-- The loop here ONLY contains the table row <tr> --}}
                                    <tr>
                                        <td>{{ $year->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($year->start_date)->format('F j, Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($year->end_date)->format('F j, Y') }}</td>
                                        <td>
                                            @if ($year->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="#" data-bs-toggle="modal"
                                                data-bs-target="#editSchoolYearModal{{ $year->id }}"
                                                class="btn btn-sm btn-primary">
                                                Edit
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-year-btn" data-bs-toggle="modal"
                                                data-bs-target="#deleteSchoolYearModal"
                                                data-year-id="{{ $year->id }}">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No school years found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSchoolYearModal" tabindex="-1" aria-labelledby="addSchoolYearModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSchoolYearModalLabel">Add School Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="schoolYearForm" method="POST" action="{{ route('admin.school-year.store') }}">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isCurrentYear" name="is_active">
                            <label class="form-check-label" for="isCurrentYear">
                                Set as current school year
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="schoolYearForm" class="btn btn-primary"
                        id="saveSchoolYear">Save</button>
                </div>
            </div>
        </div>
    </div>

    @foreach ($schoolYears as $year)
        <div class="modal fade text-left" id="editSchoolYearModal{{ $year->id }}" tabindex="-1" role="dialog"
            aria-labelledby="editSchoolYearModalLabel{{ $year->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editSchoolYearModalLabel{{ $year->id }}">Edit School Year</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('admin.school-year.update', $year->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="editStartDate{{ $year->id }}" class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control"
                                        value="{{ $year->start_date }}" id="editStartDate{{ $year->id }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="editEndDate{{ $year->id }}" class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control"
                                        value="{{ $year->end_date }}" id="editEndDate{{ $year->id }}" required>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox"
                                    id="editIsCurrentYear{{ $year->id }}" name="is_active"
                                    {{ $year->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="editIsCurrentYear{{ $year->id }}">
                                    Set as current school year
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach


    <div class="modal fade" id="deleteSchoolYearModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this school year? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteSchoolYearForm" method="POST" action="">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sample school year data (from your existing code)
            const schoolYears = [{
                id: 1,
                name: '2025-2026',
                startDate: '2025-06-15',
                endDate: '2026-03-31',
                description: 'Regular school year',
                isCurrent: true
            }, ];

            // This is the new function that creates the visual timeline.
            function initSchoolYearTimeline() {
                const timelineContainer = document.getElementById('schoolYearTimeline');
                if (!timelineContainer) return;

                // Sort years chronologically for the timeline display
                const sortedYears = [...schoolYears].sort((a, b) => new Date(a.startDate) - new Date(b
                    .startDate));

                // Set the current date to match the context
                const currentDate = new Date('2025-07-24T02:52:35');

                let timelineHTML =
                    '<div class="d-flex w-100 align-items-center position-relative" style="height: 60px;">';

                sortedYears.forEach((year, index) => {
                    const startDate = new Date(year.startDate);
                    const endDate = new Date(year.endDate);

                    let barStyle = 'background: #6c757d;'; // Default gray for past years
                    let progressIndicatorHTML = '';
                    let eventDotsHTML = '';

                    // Style the current year and calculate progress
                    if (year.isCurrent) {
                        const totalDuration = endDate.getTime() - startDate.getTime();
                        const elapsedDuration = currentDate.getTime() - startDate.getTime();
                        const progressPercentage = Math.max(0, Math.min(100, (elapsedDuration /
                            totalDuration) * 100));

                        barStyle =
                            `background: linear-gradient(to right, #0d6efd ${progressPercentage}%, #a9cfff ${progressPercentage}%);`;

                        // Add the glowing marker for the current date
                        progressIndicatorHTML = `
                        <div style="position: absolute; left: ${progressPercentage}%; top: 50%; transform: translate(-50%, -50%);" title="Today: ${currentDate.toLocaleDateString()}">
                            <div style="width: 18px; height: 18px; background-color: white; border: 4px solid #e63946; border-radius: 50%; box-shadow: 0 0 12px rgba(230, 57, 70, 0.8);"></div>
                        </div>
                    `;

                        // Add sample event dots for the current year
                        eventDotsHTML = `
                        <span class="event-dot" style="left: 80%; top: -15px;" title="Midterm Exams"></span>
                        <span class="event-dot" style="left: 88%; top: -15px;" title="School Fair"></span>
                        <span class="event-dot" style="left: 95%; top: -15px;" title="Final Exams"></span>
                    `;
                    } else if (startDate > currentDate) {
                        barStyle = 'background: #a9cfff;'; // Light blue for future years
                    }

                    // Add placeholder dots for past years to match the image
                    if (year.name === '2023-2024') {
                        eventDotsHTML =
                            `
                        <span class="event-dot" style="left: 20%; bottom: -15px;"></span> <span class="event-dot" style="left: 45%; bottom: -15px;"></span>
                        <span class="event-dot" style="left: 55%; bottom: -15px;"></span> <span class="event-dot" style="left: 85%; bottom: -15px;"></span>`;
                    } else if (year.name === '2024-2025') {
                        eventDotsHTML =
                            `
                        <span class="event-dot" style="left: 40%; bottom: -15px;"></span> <span class="event-dot" style="left: 65%; bottom: -15px;"></span>
                        <span class="event-dot" style="left: 75%; bottom: -15px;"></span> <span class="event-dot" style="left: 90%; bottom: -15px;"></span>`;
                    }

                    // Build the HTML for this year segment
                    timelineHTML += `
                    <div class="flex-grow-1 position-relative px-2">
                        <div class="timeline-bar position-relative" style="height: 12px; border-radius: 6px; ${barStyle}">
                            ${progressIndicatorHTML}
                            ${eventDotsHTML}
                        </div>
                        <div class="text-center text-muted small mt-2">${year.name}</div>
                    </div>
                `;

                    // Add a connector line between years
                    if (index < sortedYears.length - 1) {
                        timelineHTML +=
                            `<div style="width: 30px; height: 2px; background-color: #dee2e6;"></div>`;
                    }
                });

                timelineHTML += '</div>';

                // Add styling for the event dots
                timelineHTML += `
                <style>
                    .event-dot {
                        position: absolute;
                        width: 9px;
                        height: 9px;
                        background-color: #ffc107;
                        border-radius: 50%;
                        border: 1px solid white;
                        cursor: pointer;
                        transition: transform 0.2s;
                    }
                    .event-dot:hover {
                        transform: scale(1.5);
                    }
                </style>
            `;

                // Replace the loading spinner with the generated timeline
                timelineContainer.innerHTML = timelineHTML;
            }

            // Keep your other functions as they are
            function initSchoolYearManagement() {
                // This function is from your original code
                const schoolYearTableBody = document.getElementById('schoolYearTableBody');
                if (!schoolYearTableBody) return;


                // Handle Delete Button Click
                schoolYearTableBody.addEventListener('click', function(event) {
                    if (event.target.classList.contains('delete-year-btn')) {
                        const button = event.target;
                        document.getElementById('deleteYearId').value = button.dataset.yearId;
                    }
                });
            }

            function initDashboardCharts() {
                const enrollmentChartEl = document.getElementById('enrollmentChart');
                const classDistributionChartEl = document.getElementById('classDistributionChart');
                if (!enrollmentChartEl || !classDistributionChartEl) return;

                let enrollmentChart;
                let classDistributionChart;

                const fetchData = async () => {
                    // Show spinners while loading
                    enrollmentChartEl.innerHTML =
                        `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
                    classDistributionChartEl.innerHTML =
                        `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

                    try {
                        const response = await fetch(
                            `/admin/dashboard/chart-data`);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        const data = await response.json();
                        updateEnrollmentChart(data.enrollmentTrends);
                        updateClassDistributionChart(data.classDistributionChart);
                    } catch (error) {
                        console.error('Error fetching chart data:', error);
                        enrollmentChartEl.innerHTML =
                            `<div class="text-center py-5 text-danger">Failed to load chart data. ${error.message}</div>`;
                        classDistributionChartEl.innerHTML =
                            '<div class="text-center py-5 text-danger">Failed to load chart data.</div>';
                    }
                };

                // In dashboard.blade.php

                const updateEnrollmentChart = (enrollmentTrends) => {
                    if (enrollmentChart) {
                        enrollmentChart.destroy();
                    }
                    enrollmentChartEl.innerHTML = ''; // Clear spinner

                    const canvas = document.createElement('canvas');
                    enrollmentChartEl.appendChild(canvas);

                    const ctx = canvas.getContext('2d');

                    const years = [...new Set(enrollmentTrends.map(item => item.enrollment_year))].sort();
                    const statuses = [...new Set(enrollmentTrends.map(item => item.status))].sort();

                    const datasets = statuses.map(status => {
                        const data = years.map(year => {
                            const trend = enrollmentTrends.find(item => item.enrollment_year ===
                                year && item.status === status);
                            return trend ? trend.count : 0;
                        });

                        let backgroundColor, borderColor;
                        switch (status) {
                            case 'active':
                                backgroundColor =
                                    'rgba(13, 110, 253, 0.1)'; // Lighter fill for line chart
                                borderColor = 'rgba(13, 110, 253, 1)';
                                break;
                            case 'alumni':
                                backgroundColor = 'rgba(25, 135, 84, 0.1)';
                                borderColor = 'rgba(25, 135, 84, 1)';
                                break;
                            case 'transferee':
                                backgroundColor = 'rgba(255, 193, 7, 0.1)';
                                borderColor = 'rgba(255, 193, 7, 1)';
                                break;
                            case 'inactive':
                                backgroundColor = 'rgba(220, 53, 69, 0.1)';
                                borderColor = 'rgba(220, 53, 69, 1)';
                                break;
                            default:
                                backgroundColor = 'rgba(108, 117, 125, 0.1)';
                                borderColor = 'rgba(108, 117, 125, 1)';
                        }

                        return {
                            label: status.charAt(0).toUpperCase() + status.slice(1),
                            data: data,
                            backgroundColor: backgroundColor, // The area fill color
                            borderColor: borderColor, // The line color
                            borderWidth: 2,
                            fill: true, // **CHANGE**: Fill the area under the line
                            tension: 0.4 // **CHANGE**: Makes the line smooth/curved
                        };
                    });

                    enrollmentChart = new Chart(ctx, {
                        type: 'line', // **CHANGE**: The chart type is now 'line'
                        data: {
                            labels: years,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                // **REMOVED**: The 'stacked' property is removed from scales
                                x: {
                                    title: {
                                        display: true,
                                        text: 'School Year'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Students'
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            interaction: { // Improves tooltip experience on line charts
                                intersect: false,
                                mode: 'index',
                            },
                        }
                    });
                };
                const updateClassDistributionChart = (chartData) => {
                    if (classDistributionChart) {
                        classDistributionChart.destroy();
                    }
                    // Clear spinner
                    classDistributionChartEl.innerHTML = '';

                    classDistributionChart = new Chart(classDistributionChartEl, {
                        type: 'doughnut',
                        data: {
                            labels: chartData.labels,
                            datasets: [{
                                label: 'Sections',
                                data: chartData.data,
                                backgroundColor: [
                                    '#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#ffc107',
                                    '#198754', '#20c997', '#0dcaf0'
                                ],
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                }
                            }
                        }
                    });
                };

                const enrollmentYearSelect = document.getElementById('enrollmentYearSelect');
                enrollmentYearSelect.addEventListener('change', (e) => {
                    const selectedYear = e.target.value;
                    document.getElementById('currentSelectedYear').textContent = e.target.options[e.target
                        .selectedIndex].text;
                    fetchData(selectedYear);
                });

                // Initial fetch
                fetchData(enrollmentYearSelect.value);
            }

            // Call all initialization functions
            initSchoolYearManagement();
            initDashboardCharts();
            initSchoolYearTimeline(); // Call the new timeline function
        });
    </script>
@endpush
