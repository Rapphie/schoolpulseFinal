@extends('base')

@section('title', 'Attendance Analytics')

@section('content')
    @php
        $summary = array_merge(
            [
                'total_records' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'present_rate' => 0,
                'absence_rate' => 0,
                'late_rate' => 0,
            ],
            $attendanceSummary ?? [],
        );

        $todaySnapshotData = array_merge(
            [
                'date' => now()->format('M d, Y'),
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'total' => 0,
            ],
            $todaySnapshot ?? [],
        );

        $statusDistributionData = $statusDistribution ?? [];
        $monthlyTrendData = array_merge(
            [
                'labels' => [],
                'present' => [],
                'absent' => [],
                'late' => [],
            ],
            $monthlyTrend ?? [],
        );

        $dailySparklineData = array_merge(
            [
                'labels' => [],
                'present' => [],
                'absent' => [],
                'attendance_rate' => [],
            ],
            $dailySparkline ?? [],
        );

        $classLeaderboardData = $classLeaderboard ?? [];
        $classOptionsMapData = $classOptionsMap ?? [];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2 text-dark">Attendance Analytics</h1>
            <p class="mb-1 text-muted">
                Active school year:
                <span class="fw-semibold text-primary">{{ $activeSchoolYear->name ?? 'Not set' }}</span>
            </p>
            <p class="mb-0 text-muted">
                Viewing data for:
                <span class="fw-semibold text-dark"
                    id="attendanceViewingYearName">{{ $currentSchoolYear->name ?? 'Not set' }}</span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label for="attendanceSchoolYearSelect" class="form-label small text-muted mb-1">School Year</label>
                <select class="form-select" id="attendanceSchoolYearSelect">
                    @foreach ($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear->id }}"
                            {{ $currentSchoolYear && $schoolYear->id === $currentSchoolYear->id ? 'selected' : '' }}>
                            {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="attendanceGradeSelect" class="form-label small text-muted mb-1">Grade Level</label>
                <select class="form-select" id="attendanceGradeSelect">
                    <option value="">All Grade Levels</option>
                    @foreach ($gradeLevels as $gradeLevel)
                        <option value="{{ $gradeLevel->id }}"
                            {{ $selectedGradeLevelId && $gradeLevel->id === (int) $selectedGradeLevelId ? 'selected' : '' }}>
                            {{ $gradeLevel->name ?? 'Grade ' . $gradeLevel->level }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="attendanceClassSelect" class="form-label small text-muted mb-1">Class / Section</label>
                <select class="form-select" id="attendanceClassSelect" data-selected-class="{{ $selectedClassId ?? '' }}"
                    @if (!$selectedGradeLevelId) disabled @endif>
                    <option value="">All Classes</option>
                </select>
                <small class="text-muted d-block mt-1" id="classSelectHelper">
                    {{ $selectedGradeLevelId ? 'Showing classes for the selected grade level.' : 'Select a grade level to enable the class filter.' }}
                </small>
            </div>
            <div>
                <label class="form-label small text-muted mb-1">&nbsp;</label>
                <a class="btn btn-outline-primary d-block" id="attendanceExportLink"
                    href="{{ route('admin.reports.export.attendance', ['school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}">
                    <i data-feather="download"></i> Export
                </a>
            </div>
            <div class="text-muted small d-none" id="attendanceLoader">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Updating...
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.attendance.detail', ['type' => 'present', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-link-wrapper" id="cardAttendanceRate" data-type="present">
                <div class="card border-left-success shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Attendance Rate</div>
                        <div class="h4 mb-0 font-weight-bold text-dark" id="attendanceRateValue">
                            {{ number_format($summary['present_rate'], 1) }}%
                        </div>
                        <small class="text-muted">Present ÷ total sessions</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-success"><i data-feather="arrow-right" class="feather-sm"></i> View
                            Details</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.attendance.detail', ['type' => 'records', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-link-wrapper" id="cardSessions" data-type="records">
                <div class="card border-left-primary shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Sessions Logged</div>
                        <div class="h4 mb-0 font-weight-bold text-dark" id="attendanceSessionsValue">
                            {{ number_format($summary['total_records']) }}
                        </div>
                        <small class="text-muted">Across current filters</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-primary"><i data-feather="arrow-right" class="feather-sm"></i> View
                            Details</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.attendance.detail', ['type' => 'present', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-link-wrapper" id="cardPresent" data-type="present">
                <div class="card border-left-info shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Present</div>
                        <div class="h4 mb-0 font-weight-bold text-dark" id="attendancePresentValue">
                            {{ number_format($summary['present']) }}
                        </div>
                        <small class="text-muted">{{ number_format($summary['present_rate'], 1) }}% of sessions</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-info"><i data-feather="arrow-right" class="feather-sm"></i> View Details</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.attendance.detail', ['type' => 'absent', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-link-wrapper" id="cardAbsent" data-type="absent">
                <div class="card border-left-danger shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Absences</div>
                        <div class="h4 mb-0 font-weight-bold text-dark" id="attendanceAbsentValue">
                            {{ number_format($summary['absent']) }}
                        </div>
                        <small class="text-muted">{{ number_format($summary['absence_rate'], 1) }}% of sessions</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-danger"><i data-feather="arrow-right" class="feather-sm"></i> View
                            Details</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <a href="{{ route('admin.reports.attendance.detail', ['type' => 'late', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-link-wrapper" id="cardLate" data-type="late">
                <div class="card shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Late Arrivals</div>
                                <div class="h4 mb-0 font-weight-bold text-dark" id="attendanceLateValue">
                                    {{ number_format($summary['late']) }}
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark" id="attendanceLateRate">
                                {{ number_format($summary['late_rate'], 1) }}%
                            </span>
                        </div>
                        <p class="mb-0 text-muted small">Share of sessions tagged as late across the selected scope.</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-warning"><i data-feather="arrow-right" class="feather-sm"></i> View
                            Details</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Snapshot</div>
                            <div class="h4 mb-0 font-weight-bold text-dark" id="todaySnapshotDate">
                                {{ $todaySnapshotData['date'] }}
                            </div>
                        </div>
                        <span class="badge bg-light text-muted">Live</span>
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        <div>
                            <div class="text-muted text-uppercase small">Present</div>
                            <div class="h5 mb-0" id="todayPresentValue">
                                {{ number_format($todaySnapshotData['present']) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted text-uppercase small">Absent</div>
                            <div class="h5 mb-0 text-danger" id="todayAbsentValue">
                                {{ number_format($todaySnapshotData['absent']) }}</div>
                        </div>
                        <div>
                            <div class="text-muted text-uppercase small">Late</div>
                            <div class="h5 mb-0 text-warning" id="todayLateValue">
                                {{ number_format($todaySnapshotData['late']) }}</div>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Captured from records logged today.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Trend</h6>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 260px;">
                        <canvas id="monthlyAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Status Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 220px;">
                        <canvas id="statusDistributionChart"></canvas>
                    </div>
                    <div class="mt-3" id="statusDistributionList">
                        @if (empty($statusDistributionData))
                            <p class="text-muted text-center mb-0">No attendance data for the selected filters.</p>
                        @else
                            @foreach ($statusDistributionData as $item)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>{{ ucfirst($item['label'] ?? $item['status']) }}</span>
                                    <span class="fw-semibold">{{ number_format($item['percentage'] ?? 0, 1) }}%</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Presence Rate</h6>
                    <small class="text-muted">Last 14 school days</small>
                </div>
                <div class="card-body">
                    <div id="dailyChartEmpty"
                        class="text-muted text-center py-4 {{ empty($dailySparklineData['labels']) ? '' : 'd-none' }}">
                        No attendance data for the selected filters.
                    </div>
                    <div class="position-relative" style="height: 220px;">
                        <canvas id="dailyAttendanceChart"
                            class="{{ empty($dailySparklineData['labels']) ? 'd-none' : '' }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Class Attendance Leaders</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 45%">Class</th>
                                    <th style="width: 20%">Attend. Rate</th>
                                    <th style="width: 15%">Present</th>
                                    <th style="width: 20%">Absent</th>
                                </tr>
                            </thead>
                            <tbody id="classLeaderboardBody">
                                @forelse ($classLeaderboardData as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>{{ number_format($row['attendance_rate'], 1) }}%</td>
                                        <td>{{ number_format($row['present']) }}</td>
                                        <td>{{ number_format($row['absent']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No class-level data yet.
                                        </td>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const analyticsUrl = "{{ route('admin.reports.attendance') }}";
            const initialState = {
                summary: @json($summary),
                statusDistribution: @json($statusDistributionData),
                monthlyTrend: @json($monthlyTrendData),
                dailySparkline: @json($dailySparklineData),
                classLeaderboard: @json($classLeaderboardData),
                todaySnapshot: @json($todaySnapshotData),
                classOptionsMap: @json($classOptionsMapData),
            };

            const state = typeof structuredClone === 'function' ?
                structuredClone(initialState) :
                JSON.parse(JSON.stringify(initialState));

            const schoolYearSelect = document.getElementById('attendanceSchoolYearSelect');
            const gradeSelect = document.getElementById('attendanceGradeSelect');
            const classSelect = document.getElementById('attendanceClassSelect');
            const classSelectHelper = document.getElementById('classSelectHelper');
            const loader = document.getElementById('attendanceLoader');
            const viewingYearName = document.getElementById('attendanceViewingYearName');
            const rateValue = document.getElementById('attendanceRateValue');
            const sessionsValue = document.getElementById('attendanceSessionsValue');
            const presentValue = document.getElementById('attendancePresentValue');
            const absentValue = document.getElementById('attendanceAbsentValue');
            const lateValue = document.getElementById('attendanceLateValue');
            const lateRateBadge = document.getElementById('attendanceLateRate');
            const todayDate = document.getElementById('todaySnapshotDate');
            const todayPresent = document.getElementById('todayPresentValue');
            const todayAbsent = document.getElementById('todayAbsentValue');
            const todayLate = document.getElementById('todayLateValue');
            const statusListContainer = document.getElementById('statusDistributionList');
            const classLeaderboardBody = document.getElementById('classLeaderboardBody');
            const dailyChartEmpty = document.getElementById('dailyChartEmpty');
            const initialClassSelection = classSelect?.dataset?.selectedClass || '';

            const monthlyCanvas = document.getElementById('monthlyAttendanceChart');
            const statusCanvas = document.getElementById('statusDistributionChart');
            const dailyCanvas = document.getElementById('dailyAttendanceChart');

            const charts = {
                monthly: null,
                status: null,
                daily: null,
            };

            rebuildMonthlyChart();
            rebuildStatusChart();
            rebuildDailyChart();

            populateClassSelect(initialClassSelection);
            updateSummaryCards();
            renderStatusList(state.statusDistribution);
            renderClassLeaderboard(state.classLeaderboard);
            toggleDailyChart(state.dailySparkline);

            schoolYearSelect.addEventListener('change', handleFilterChange);
            gradeSelect.addEventListener('change', () => {
                populateClassSelect();
                handleFilterChange();
            });
            classSelect.addEventListener('change', handleFilterChange);

            function populateClassSelect(preservedValue = '') {
                const selectedGrade = gradeSelect.value;
                const optionsMap = state.classOptionsMap || {};
                const previousValue = preservedValue || classSelect.value;

                classSelect.innerHTML = '<option value="">All Classes</option>';

                if (!selectedGrade) {
                    classSelect.value = '';
                    classSelect.disabled = true;
                    classSelectHelper.textContent = 'Select a grade level to enable the class filter.';
                    return;
                }

                const options = optionsMap[selectedGrade] || [];
                if (!options.length) {
                    classSelect.disabled = true;
                    classSelectHelper.textContent = 'No classes found for this grade in the selected school year.';
                    return;
                }

                options.forEach((option) => {
                    const opt = document.createElement('option');
                    opt.value = option.id;
                    opt.textContent = option.label;
                    classSelect.appendChild(opt);
                });

                classSelect.disabled = false;
                classSelectHelper.textContent = 'Filtering classes within the selected grade level.';

                if (previousValue && options.some((option) => String(option.id) === String(previousValue))) {
                    classSelect.value = previousValue;
                } else {
                    classSelect.value = '';
                }
            }

            async function handleFilterChange() {
                const params = new URLSearchParams();
                if (schoolYearSelect.value) {
                    params.append('school_year_id', schoolYearSelect.value);
                }
                if (gradeSelect.value) {
                    params.append('grade_level_id', gradeSelect.value);
                }
                if (gradeSelect.value && classSelect.value) {
                    params.append('class_id', classSelect.value);
                }
                params.append('_', Date.now());

                toggleLoading(true);

                try {
                    const response = await fetch(`${analyticsUrl}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to refresh attendance analytics.');
                    }

                    const payload = await response.json();

                    state.summary = payload.attendanceSummary || initialState.summary;
                    state.statusDistribution = payload.statusDistribution || [];
                    state.monthlyTrend = payload.monthlyTrend || initialState.monthlyTrend;
                    state.dailySparkline = payload.dailySparkline || initialState.dailySparkline;
                    state.classLeaderboard = payload.classLeaderboard || [];
                    state.todaySnapshot = payload.todaySnapshot || initialState.todaySnapshot;
                    state.classOptionsMap = payload.classOptionsMap ?? state.classOptionsMap;

                    if (payload.schoolYearLabel) {
                        viewingYearName.textContent = payload.schoolYearLabel;
                    }

                    populateClassSelect();
                    updateSummaryCards();
                    renderStatusList(state.statusDistribution);
                    renderClassLeaderboard(state.classLeaderboard);
                    rebuildMonthlyChart();
                    rebuildStatusChart();
                    rebuildDailyChart();
                    toggleDailyChart(state.dailySparkline);
                    updateCardLinks();
                    updateExportLink();
                } catch (error) {
                    console.error(error);
                    alert('Unable to refresh attendance analytics. Please try again.');
                } finally {
                    toggleLoading(false);
                }
            }

            function toggleLoading(isLoading) {
                loader.classList.toggle('d-none', !isLoading);
                schoolYearSelect.disabled = isLoading;
                gradeSelect.disabled = isLoading;
                classSelect.disabled = isLoading || !gradeSelect.value || classSelect.options.length <= 1;
            }

            function updateSummaryCards() {
                rateValue.textContent = `${(state.summary.present_rate ?? 0).toFixed(1)}%`;
                sessionsValue.textContent = Number(state.summary.total_records || 0).toLocaleString();
                presentValue.textContent = Number(state.summary.present || 0).toLocaleString();
                absentValue.textContent = Number(state.summary.absent || 0).toLocaleString();
                lateValue.textContent = Number(state.summary.late || 0).toLocaleString();
                lateRateBadge.textContent = `${(state.summary.late_rate ?? 0).toFixed(1)}%`;

                todayDate.textContent = state.todaySnapshot.date || '';
                todayPresent.textContent = Number(state.todaySnapshot.present || 0).toLocaleString();
                todayAbsent.textContent = Number(state.todaySnapshot.absent || 0).toLocaleString();
                todayLate.textContent = Number(state.todaySnapshot.late || 0).toLocaleString();
            }

            function renderStatusList(distribution) {
                if (!statusListContainer) return;
                if (!distribution || !distribution.length) {
                    statusListContainer.innerHTML =
                        '<p class="text-muted text-center mb-0">No attendance data for the selected filters.</p>';
                    return;
                }

                const rows = distribution.map((item) => {
                    const label = item.label || item.status || 'Status';
                    const percentage = (item.percentage ?? 0).toFixed(1);
                    return `<div class="d-flex justify-content-between align-items-center mb-2">
                                <span>${label}</span>
                                <span class="fw-semibold">${percentage}%</span>
                            </div>`;
                });

                statusListContainer.innerHTML = rows.join('');
            }

            function renderClassLeaderboard(data) {
                if (!classLeaderboardBody) return;
                if (!data || !data.length) {
                    classLeaderboardBody.innerHTML =
                        '<tr><td colspan="4" class="text-center text-muted py-4">No class-level data yet.</td></tr>';
                    return;
                }

                classLeaderboardBody.innerHTML = data.map((row) => {
                    const rate = (row.attendance_rate ?? 0).toFixed(1);
                    return `<tr>
                                <td>${row.label || 'Class'}</td>
                                <td>${rate}%</td>
                                <td>${Number(row.present || 0).toLocaleString()}</td>
                                <td>${Number(row.absent || 0).toLocaleString()}</td>
                            </tr>`;
                }).join('');
            }

            function toggleDailyChart(dataset) {
                if (!dailyChartEmpty || !dailyCanvas) return;
                const hasData = dataset.labels && dataset.labels.length;
                dailyChartEmpty.classList.toggle('d-none', hasData);
                dailyCanvas.classList.toggle('d-none', !hasData);
            }

            function rebuildMonthlyChart() {
                if (!monthlyCanvas) return;
                if (charts.monthly) {
                    charts.monthly.destroy();
                }
                charts.monthly = buildMonthlyTrendChart(monthlyCanvas, state.monthlyTrend);
            }

            function rebuildStatusChart() {
                if (!statusCanvas) return;
                if (charts.status) {
                    charts.status.destroy();
                }
                charts.status = buildStatusChart(statusCanvas, state.statusDistribution);
            }

            function rebuildDailyChart() {
                if (!dailyCanvas) return;
                if (charts.daily) {
                    charts.daily.destroy();
                }
                charts.daily = buildDailyChart(dailyCanvas, state.dailySparkline);
            }

            function buildMonthlyTrendChart(canvas, dataset) {
                if (!canvas) return null;
                return new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: dataset.labels || [],
                        datasets: [{
                                label: 'Present',
                                data: dataset.present || [],
                                borderColor: 'rgba(25,135,84,1)',
                                backgroundColor: 'rgba(25,135,84,0.15)',
                                tension: 0.35,
                                fill: true,
                            },
                            {
                                label: 'Absent',
                                data: dataset.absent || [],
                                borderColor: 'rgba(220,53,69,1)',
                                backgroundColor: 'rgba(220,53,69,0.1)',
                                tension: 0.35,
                                fill: true,
                            },
                            {
                                label: 'Late',
                                data: dataset.late || [],
                                borderColor: 'rgba(255,193,7,1)',
                                backgroundColor: 'rgba(255,193,7,0.2)',
                                tension: 0.35,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        stacked: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            }

            function getStatusChartDataset(distribution) {
                const palette = {
                    present: 'rgba(25,135,84,0.85)',
                    absent: 'rgba(220,53,69,0.85)',
                    late: 'rgba(255,193,7,0.85)',
                    default: 'rgba(108,117,125,0.65)'
                };

                const labels = [];
                const values = [];
                const colors = [];

                (distribution || []).forEach((item) => {
                    const statusKey = (item.status || '').toLowerCase();
                    labels.push(item.label || item.status || 'Status');
                    values.push(item.total || 0);
                    colors.push(palette[statusKey] || palette.default);
                });

                return {
                    labels,
                    values,
                    colors,
                };
            }

            function buildStatusChart(canvas, distribution) {
                if (!canvas) return null;
                const dataset = getStatusChartDataset(distribution);
                return new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: dataset.labels,
                        datasets: [{
                            data: dataset.values,
                            backgroundColor: dataset.colors,
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            },
                        },
                    },
                });
            }

            function buildDailyChart(canvas, dataset) {
                if (!canvas) return null;
                return new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: dataset.labels || [],
                        datasets: [{
                            label: 'Presence Rate %',
                            data: dataset.attendance_rate || [],
                            backgroundColor: 'rgba(13,110,253,0.7)',
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: (value) => `${value}%`,
                                },
                            },
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                    },
                });
            }

            function updateCardLinks() {
                const detailBaseUrl = "{{ route('admin.reports.attendance.detail', ['type' => '__TYPE__']) }}";
                const cardLinks = document.querySelectorAll('.card-link-wrapper');

                cardLinks.forEach(link => {
                    const type = link.dataset.type;
                    if (!type) return;

                    const params = new URLSearchParams();
                    if (schoolYearSelect.value) {
                        params.append('school_year_id', schoolYearSelect.value);
                    }
                    if (gradeSelect.value) {
                        params.append('grade_level_id', gradeSelect.value);
                    }
                    if (gradeSelect.value && classSelect.value) {
                        params.append('class_id', classSelect.value);
                    }

                    const baseUrl = detailBaseUrl.replace('__TYPE__', type);
                    link.href = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
                });
            }

            function updateExportLink() {
                const exportLink = document.getElementById('attendanceExportLink');
                if (!exportLink) return;

                const exportBaseUrl = "{{ route('admin.reports.export.attendance') }}";
                const params = new URLSearchParams();
                if (schoolYearSelect.value) {
                    params.append('school_year_id', schoolYearSelect.value);
                }
                if (gradeSelect.value) {
                    params.append('grade_level_id', gradeSelect.value);
                }
                if (gradeSelect.value && classSelect.value) {
                    params.append('class_id', classSelect.value);
                }

                exportLink.href = params.toString() ? `${exportBaseUrl}?${params.toString()}` : exportBaseUrl;
            }

            // Initial updates
            updateCardLinks();
            updateExportLink();
        });
    </script>
@endpush

@push('styles')
    <style>
        .card-clickable {
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .card-clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .card-link-wrapper:hover .card-footer small {
            text-decoration: underline;
        }

        .card-clickable .card-footer {
            opacity: 0.7;
            transition: opacity 0.2s ease-in-out;
        }

        .card-clickable:hover .card-footer {
            opacity: 1;
        }
    </style>
@endpush
