@extends('base')

@section('title', 'Teacher Dashboard')

@section('content')
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                My Classes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $classCount ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="layers" class="text-gray-300" style="width: 32px; height: 32px;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light p-2">
                    <a href="{{ route('teacher.classes') }}" class="btn btn-sm btn-primary w-100">View All Classes</a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $studentCount ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="users" class="text-gray-300" style="width: 32px; height: 32px;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light p-2">
                    <a href="{{ route('teacher.students.index') }}" class="btn btn-sm btn-success w-100">View All
                        Students</a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Today's Attendance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $todayAttendanceCount ?? 0 }} <span
                                    class="small text-muted">students</span></div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="check-circle" class="text-gray-300" style="width: 32px; height: 32px;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light p-2">
                    <a href="{{ route('teacher.attendance.take') }}" class="btn btn-sm btn-info w-100">Take
                        Attendance</a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Upcoming Schedules</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $scheduleCount ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i data-feather="calendar" class="text-gray-300" style="width: 32px; height: 32px;"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light p-2">
                    <a href="#calendar" class="btn btn-sm btn-warning w-100">View Calendar</a>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- Upcoming Classes -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Upcoming Classes Today</h6>
                    <span class="badge bg-primary">{{ now()->format('l, F j, Y') }}</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Grade</th>
                                    <th>Section</th>
                                    <th>Subject</th>
                                    <th>Time</th>
                                    <th>Room</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($upcomingSchedules) && $upcomingSchedules->count() > 0)
                                    @foreach ($upcomingSchedules as $class)
                                        <tr>
                                            <td>{{ $class->section->grade_level_id ?? 'N/A' }}</td>
                                            <td>{{ $class->section->name ?? 'N/A' }}</td>
                                            <td>{{ $class->subject->name ?? 'N/A' }}</td>
                                            <td>{{ $class->start_time->format('H:i') ?? 'N/A' }} -
                                                {{ $class->end_time->format('H:i') ?? 'N/A' }}</td>
                                            <td>{{ $class->room ?? 'N/A' }}</td>

                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('teacher.attendance.take', ['class_id' => $class->id ?? 0]) }}"
                                                        class="btn btn-primary" data-bs-toggle="tooltip"
                                                        title="Take Attendance">
                                                        <i data-feather="check-circle" class="feather-sm"></i>
                                                    </a>
                                                    <a href="{{ route('teacher.classes') }}" class="btn btn-info"
                                                        data-bs-toggle="tooltip" title="View Class">
                                                        <i data-feather="eye" class="feather-sm"></i>
                                                    </a>
                                                </div>
                                            </td>

                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center">No upcoming classes today.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Recent Activities -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Student Performance</h6>
                    <div>
                        <select id="performanceFilter" class="form-select form-select-sm">
                            <option value="all">All Subjects</option>
                            @if (isset($subjects) && count($subjects) > 0)
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="performance-stat bg-success-light rounded p-3 h-100">
                                <h3 class="text-success">{{ $highPerformers ?? 0 }}</h3>
                                <p class="mb-0">High Performers</p>
                                <small class="text-muted">(Above 90%)</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="performance-stat bg-warning-light rounded p-3 h-100">
                                <h3 class="text-warning">{{ $averagePerformers ?? 0 }}</h3>
                                <p class="mb-0">Average Performers</p>
                                <small class="text-muted">(75-89%)</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="performance-stat bg-danger-light rounded p-3 h-100">
                                <h3 class="text-danger">{{ $lowPerformers ?? 0 }}</h3>
                                <p class="mb-0">At Risk</p>
                                <small class="text-muted">(Below 75%)</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="font-weight-bold mb-0">Recent Activities</h6>
                            <select class="form-select form-select-sm" id="activityFilter" style="width: auto;">
                                <option value="all">All Activities</option>
                                <option value="grade">Grades</option>
                                <option value="attendance">Attendance</option>
                                <option value="enrollment">Enrollment</option>
                            </select>
                        </div>
                        <div class="list-group list-group-flush" id="activitiesList">
                            @if (isset($recentActivities) && count($recentActivities) > 0)
                                @foreach ($recentActivities as $activity)
                                    <div class="list-group-item list-group-item-action border-0 px-0 activity-item"
                                        data-type="{{ $activity->type ?? 'other' }}">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">{{ $activity->title ?? 'Activity' }}</h6>
                                            <small>{{ isset($activity->created_at) ? $activity->created_at->diffForHumans() : 'Recently' }}</small>
                                        </div>
                                        <p class="mb-1 small">
                                            {{ $activity->description ?? 'No description available' }}</p>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-center text-muted my-3">No recent activities found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Academic Calendar</h6>
                        <div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="prevMonth">
                                <i data-feather="chevron-left"></i>
                            </button>
                            <span class="mx-2" id="currentMonth">{{ now()->format('F Y') }}</span>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="nextMonth">
                                <i data-feather="chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="calendar" style="min-height: 350px;">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="legend-container d-flex">
                                            <div class="legend-item me-3">
                                                <span class="badge rounded-circle bg-primary">&nbsp;</span>
                                                <span class="small">Regular Class</span>
                                            </div>
                                            <div class="legend-item">
                                                <span class="badge rounded-circle bg-danger">&nbsp;</span>
                                                <span class="small">Holiday</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-bordered calendar-table">
                                <thead>
                                    <tr>
                                        <th>Sunday</th>
                                        <th>Monday</th>
                                        <th>Tuesday</th>
                                        <th>Wednesday</th>
                                        <th>Thursday</th>
                                        <th>Friday</th>
                                        <th>Saturday</th>
                                    </tr>
                                </thead>
                                <tbody id="calendarBody">
                                    <!-- Calendar will be generated by JavaScript -->
                                    <tr>
                                        <td class="text-muted">25</td>
                                        <td class="text-muted">26</td>
                                        <td class="text-muted">27</td>
                                        <td class="text-muted">28</td>
                                        <td class="text-muted">29</td>
                                        <td class="text-muted">30</td>
                                        <td>1</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td class="has-events">
                                            3
                                            <div class="event-dot bg-primary" data-bs-toggle="tooltip"
                                                title="Math Class - 8:30 AM"></div>
                                        </td>
                                        <td class="has-events">
                                            4
                                            <div class="event-dot bg-primary" data-bs-toggle="tooltip"
                                                title="Science Class - 10:00 AM"></div>
                                        </td>
                                        <td class="has-events">
                                            5
                                            <div class="event-dot bg-success" data-bs-toggle="tooltip"
                                                title="Math Quiz - 9:30 AM"></div>
                                        </td>
                                        <td>6</td>
                                        <td>7</td>
                                        <td>8</td>
                                    </tr>
                                    <tr>
                                        <td>9</td>
                                        <td>10</td>
                                        <td>11</td>
                                        <td>12</td>
                                        <td class="has-events">
                                            13
                                            <div class="event-dot bg-warning" data-bs-toggle="tooltip"
                                                title="Midterm Exam - 8:00 AM"></div>
                                        </td>
                                        <td>14</td>
                                        <td>15</td>
                                    </tr>
                                    <tr>
                                        <td>16</td>
                                        <td>17</td>
                                        <td>18</td>
                                        <td>19</td>
                                        <td>20</td>
                                        <td class="has-events">
                                            21
                                            <div class="event-dot bg-primary" data-bs-toggle="tooltip"
                                                title="Science Class - 10:00 AM"></div>
                                        </td>
                                        <td>22</td>
                                    </tr>
                                    <tr>
                                        <td>23</td>
                                        <td>24</td>
                                        <td>25</td>
                                        <td class="has-events">
                                            26
                                            <div class="event-dot bg-danger" data-bs-toggle="tooltip"
                                                title="School Holiday"></div>
                                        </td>
                                        <td>27</td>
                                        <td>28</td>
                                        <td>29</td>
                                    </tr>
                                    <tr>
                                        <td>30</td>
                                        <td class="text-muted">1</td>
                                        <td class="text-muted">2</td>
                                        <td class="text-muted">3</td>
                                        <td class="text-muted">4</td>
                                        <td class="text-muted">5</td>
                                        <td class="text-muted">6</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('styles')
        <style>
            .bg-success-light {
                background-color: rgba(40, 167, 69, 0.15);
            }

            .bg-warning-light {
                background-color: rgba(255, 193, 7, 0.15);
            }

            .bg-danger-light {
                background-color: rgba(220, 53, 69, 0.15);
            }

            .performance-stat {
                transition: all 0.3s;
            }

            .performance-stat:hover {
                transform: translateY(-5px);
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            .calendar-table {
                table-layout: fixed;
            }

            .calendar-table th,
            .calendar-table td {
                height: 60px;
                vertical-align: top;
                padding: 5px 8px;
                position: relative;
            }

            .has-events {
                position: relative;
            }

            .event-dot {
                display: block;
                height: 8px;
                width: 8px;
                border-radius: 50%;
                margin-top: 5px;
                cursor: pointer;
            }

            td.today {
                background-color: #f8f9fa;
                font-weight: bold;
            }

            td.has-events {
                background-color: rgba(0, 123, 255, 0.05);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl)
                });

                // Performance filter
                $('#performanceFilter').on('change', function() {
                    // In a real application, this would make an AJAX call to filter data by subject
                    // For demo, just show a loading indicator
                    const selectedSubject = $(this).val();
                    alert('In a real application, this would filter data for subject ID: ' + selectedSubject);
                });

                // Calendar navigation
                let currentDate = new Date();

                $('#prevMonth').on('click', function() {
                    currentDate.setMonth(currentDate.getMonth() - 1);
                    updateCalendar();
                });

                $('#nextMonth').on('click', function() {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                    updateCalendar();
                });

                function updateCalendar() {
                    // In a real application, this would update the calendar with actual events
                    // For demo, just update the current month display
                    const month = currentDate.toLocaleString('default', {
                        month: 'long'
                    });
                    const year = currentDate.getFullYear();
                    $('#currentMonth').text(month + ' ' + year);
                }

                // Highlight today
                const today = new Date();
                $('td').each(function() {
                    const cellDay = $(this).text().trim();
                    if (cellDay == today.getDate() &&
                        !$(this).hasClass('text-muted') &&
                        currentDate.getMonth() === today.getMonth() &&
                        currentDate.getFullYear() === today.getFullYear()) {
                        $(this).addClass('today');
                    }
                });

                // Activity filter functionality
                const activityFilter = document.getElementById('activityFilter');
                if (activityFilter) {
                    activityFilter.addEventListener('change', function() {
                        const filterValue = this.value;
                        const activityItems = document.querySelectorAll('.activity-item');

                        activityItems.forEach(function(item) {
                            const itemType = item.getAttribute('data-type') || 'other';
                            if (filterValue === 'all' || itemType === filterValue) {
                                item.style.display = 'block';
                            } else {
                                item.style.display = 'none';
                            }
                        });

                        // Show message if no activities match filter
                        const visibleItems = document.querySelectorAll(
                            '.activity-item[style="display: block"]');
                        const activitiesList = document.getElementById('activitiesList');
                        let noResultsMsg = activitiesList.querySelector('.no-filter-results');

                        if (visibleItems.length === 0 && activityItems.length > 0) {
                            if (!noResultsMsg) {
                                noResultsMsg = document.createElement('p');
                                noResultsMsg.className = 'text-center text-muted my-3 no-filter-results';
                                noResultsMsg.textContent = 'No activities found for this filter.';
                                activitiesList.appendChild(noResultsMsg);
                            }
                        } else if (noResultsMsg) {
                            noResultsMsg.remove();
                        }
                    });
                }
            });
        </script>
    @endpush
