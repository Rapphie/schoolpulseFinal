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
                            <option value="all" {{ empty($selectedSubjectId) ? 'selected' : '' }}>All Subjects</option>
                            @if (isset($subjects) && count($subjects) > 0)
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}"
                                        {{ (int) ($selectedSubjectId ?? 0) === (int) $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
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
                                    <!-- Calendar is generated by JavaScript -->
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
                const dashboardUrl = "{{ route('teacher.dashboard') }}";
                const performanceFilter = document.getElementById('performanceFilter');
                const currentMonthLabel = document.getElementById('currentMonth');
                const calendarBody = document.getElementById('calendarBody');
                const calendarSchedules = @json($calendarSchedules ?? []);
                const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                const dotColors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-secondary'];
                const subjectColors = {};
                let colorIndex = 0;
                let currentDate = new Date();
                currentDate.setDate(1);

                function initializeTooltips() {
                    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(trigger) {
                        const existing = bootstrap.Tooltip.getInstance(trigger);
                        if (existing) {
                            existing.dispose();
                        }
                        new bootstrap.Tooltip(trigger);
                    });
                }

                function getSubjectColor(subject) {
                    if (!subjectColors[subject]) {
                        subjectColors[subject] = dotColors[colorIndex % dotColors.length];
                        colorIndex++;
                    }

                    return subjectColors[subject];
                }

                function isToday(date) {
                    const today = new Date();
                    return date.getDate() === today.getDate()
                        && date.getMonth() === today.getMonth()
                        && date.getFullYear() === today.getFullYear();
                }

                function buildCalendarCell(dayNumber, muted, date) {
                    const cell = document.createElement('td');

                    if (muted) {
                        cell.classList.add('text-muted');
                    }

                    if (date && isToday(date)) {
                        cell.classList.add('today');
                    }

                    cell.append(document.createTextNode(String(dayNumber)));

                    if (!muted && date) {
                        const dayName = dayNames[date.getDay()];
                        const schedulesForDay = calendarSchedules.filter(function(schedule) {
                            return Array.isArray(schedule.days) && schedule.days.includes(dayName);
                        });

                        if (schedulesForDay.length > 0) {
                            cell.classList.add('has-events');
                        }

                        schedulesForDay.forEach(function(schedule) {
                            const dot = document.createElement('div');
                            dot.className = 'event-dot ' + getSubjectColor(schedule.subject || 'Class');
                            dot.setAttribute('data-bs-toggle', 'tooltip');
                            dot.setAttribute('title', `${schedule.subject || 'Class'}${schedule.start_time ? ' - ' + schedule.start_time : ''}`);
                            cell.appendChild(dot);
                        });
                    }

                    return cell;
                }

                function renderCalendar() {
                    if (!calendarBody || !currentMonthLabel) {
                        return;
                    }

                    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                    const firstDayOfWeek = firstDay.getDay();
                    const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
                    const daysInPreviousMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();

                    currentMonthLabel.textContent = currentDate.toLocaleString('default', {
                        month: 'long',
                        year: 'numeric',
                    });

                    calendarBody.innerHTML = '';

                    let dayCounter = 1;
                    let nextMonthDayCounter = 1;

                    for (let rowIndex = 0; rowIndex < 6; rowIndex++) {
                        const row = document.createElement('tr');

                        for (let dayIndex = 0; dayIndex < 7; dayIndex++) {
                            if (rowIndex === 0 && dayIndex < firstDayOfWeek) {
                                const dayNumber = daysInPreviousMonth - firstDayOfWeek + dayIndex + 1;
                                row.appendChild(buildCalendarCell(dayNumber, true, null));
                                continue;
                            }

                            if (dayCounter > daysInMonth) {
                                row.appendChild(buildCalendarCell(nextMonthDayCounter, true, null));
                                nextMonthDayCounter++;
                                continue;
                            }

                            const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), dayCounter);
                            row.appendChild(buildCalendarCell(dayCounter, false, date));
                            dayCounter++;
                        }

                        calendarBody.appendChild(row);

                        if (dayCounter > daysInMonth) {
                            break;
                        }
                    }

                    initializeTooltips();
                }

                if (performanceFilter) {
                    performanceFilter.addEventListener('change', function() {
                        const selectedSubject = this.value;
                        const params = new URLSearchParams(window.location.search);

                        if (!selectedSubject || selectedSubject === 'all') {
                            params.delete('subject_id');
                        } else {
                            params.set('subject_id', selectedSubject);
                        }

                        const queryString = params.toString();
                        window.location.href = queryString ? `${dashboardUrl}?${queryString}` : dashboardUrl;
                    });
                }

                $('#prevMonth').on('click', function() {
                    currentDate.setMonth(currentDate.getMonth() - 1);
                    renderCalendar();
                });

                $('#nextMonth').on('click', function() {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                    renderCalendar();
                });

                renderCalendar();

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
