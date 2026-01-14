@extends('base')

@section('title', 'Grades Analytics')

@push('styles')
    <style>
        .card-clickable {
            display: block;
            transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .card-clickable:hover {
            transform: translateY(-3px);
        }

        .card-clickable:hover .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .card-clickable .card-footer-link {
            opacity: 0.7;
            transition: opacity 0.15s ease-in-out;
        }

        .card-clickable:hover .card-footer-link {
            opacity: 1;
        }
    </style>
@endpush

@section('content')
    @php
        $summaryData = array_merge(
            [
                'average' => 0,
                'passing_rate' => 0,
                'highest' => 0,
                'lowest' => 0,
                'records' => 0,
            ],
            $summary ?? [],
        );

        $gradeDistributionData = $gradeDistribution ?? [];
        $quarterTrendData = array_merge(
            [
                'labels' => [],
                'averages' => [],
            ],
            $quarterTrend ?? [],
        );

        $gradeLevelBreakdownData = $gradeLevelBreakdown ?? [];
        $subjectLeaderboardData = $subjectLeaderboard ?? [];
        $studentLeaderboardData = $studentLeaderboard ?? [];
        $classLeaderboardData = $classLeaderboard ?? [];
        $classOptionsMapData = $classOptionsMap ?? [];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Grades Analytics</h1>
            <p class="mb-1 text-muted">
                Active school year:
                <span class="fw-semibold text-primary">{{ $activeSchoolYear->name ?? 'Not set' }}</span>
            </p>
            <p class="mb-0 text-muted">
                Viewing data for:
                <span class="fw-semibold text-dark"
                    id="gradesViewingYearName">{{ $currentSchoolYear->name ?? 'Not set' }}</span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label for="gradesSchoolYearSelect" class="form-label small text-muted mb-1">School Year</label>
                <select class="form-select" id="gradesSchoolYearSelect">
                    @foreach ($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear->id }}"
                            {{ $currentSchoolYear && $schoolYear->id === $currentSchoolYear->id ? 'selected' : '' }}>
                            {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="gradesGradeSelect" class="form-label small text-muted mb-1">Grade Level</label>
                <select class="form-select" id="gradesGradeSelect">
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
                <label for="gradesClassSelect" class="form-label small text-muted mb-1">Class / Section</label>
                <select class="form-select" id="gradesClassSelect" data-selected-class="{{ $selectedClassId ?? '' }}"
                    @if (!$selectedGradeLevelId) disabled @endif>
                    <option value="">All Classes</option>
                </select>
                <small class="text-muted d-block mt-1" id="gradesClassSelectHelper">
                    {{ $selectedGradeLevelId ? 'Showing classes for the selected grade level.' : 'Select a grade level to enable the class filter.' }}
                </small>
            </div>
            <div class="text-muted small d-none" id="gradesLoader">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Updating...
            </div>
            <div>
                <a href="{{ route('admin.reports.export.grades', ['school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                    class="btn btn-outline-primary" id="gradesExportBtn">
                    <i data-feather="download" class="me-1"></i> Export
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.grades.detail', ['type' => 'average', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-clickable" id="cardAverageLink">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Average Grade</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="gradesAverageValue">
                            {{ number_format($summaryData['average'], 1) }}
                        </div>
                        <small class="text-muted">Across selected scope</small>
                        <div class="card-footer-link mt-2">
                            <span class="text-primary small">View Subject Analysis →</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.grades.detail', ['type' => 'passing', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-clickable" id="cardPassingLink">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Passing Rate</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="gradesPassingRate">
                            {{ number_format($summaryData['passing_rate'], 1) }}%
                        </div>
                        <small class="text-muted">Grades ≥ 75</small>
                        <div class="card-footer-link mt-2">
                            <span class="text-success small">View Passing Records →</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.grades.detail', ['type' => 'highest', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-clickable" id="cardHighestLink">
                <div class="card border-left-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Highest Grade</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="gradesHighestValue">
                            {{ number_format($summaryData['highest'], 1) }}
                        </div>
                        <small class="text-muted">Best score recorded</small>
                        <div class="card-footer-link mt-2">
                            <span class="text-info small">View Top Performers →</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.grades.detail', ['type' => 'records', 'school_year_id' => $currentSchoolYear?->id, 'grade_level_id' => $selectedGradeLevelId, 'class_id' => $selectedClassId]) }}"
                class="text-decoration-none card-clickable" id="cardRecordsLink">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Records Evaluated</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="gradesRecordsValue">
                            {{ number_format($summaryData['records']) }}
                        </div>
                        <small class="text-muted">Total grade entries</small>
                        <div class="card-footer-link mt-2">
                            <span class="text-warning small">View All Records →</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Quarterly Trend</h6>
                    <small class="text-muted">Average per grading period</small>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 260px;">
                        <canvas id="quarterTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Grade Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 220px;">
                        <canvas id="gradeDistributionChart"></canvas>
                    </div>
                    <div class="mt-3" id="gradeDistributionList">
                        @if (empty($gradeDistributionData))
                            <p class="text-muted text-center mb-0">No grade data for the selected filters.</p>
                        @else
                            @foreach ($gradeDistributionData as $item)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="fw-semibold">{{ number_format($item['percentage'] ?? 0, 1) }}%</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Grade Level Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 260px;">
                        <canvas id="gradeLevelChart"></canvas>
                    </div>
                    <div class="mt-3" id="gradeLevelList">
                        @forelse ($gradeLevelBreakdownData as $row)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ $row['label'] }}</span>
                                <span class="fw-semibold">{{ number_format($row['average'], 1) }}</span>
                            </div>
                        @empty
                            <p class="text-muted text-center mb-0">No grade level data yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top Subjects</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th class="text-end">Avg Grade</th>
                                </tr>
                            </thead>
                            <tbody id="subjectLeaderboardBody">
                                @forelse ($subjectLeaderboardData as $subject)
                                    <tr>
                                        <td>{{ $subject['label'] }}</td>
                                        <td class="text-end">{{ number_format($subject['average'], 1) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No subject data yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top Students</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th class="text-end">Avg Grade</th>
                                </tr>
                            </thead>
                            <tbody id="studentLeaderboardBody">
                                @forelse ($studentLeaderboardData as $student)
                                    <tr>
                                        <td>{{ $student['label'] }}</td>
                                        <td class="text-end">{{ number_format($student['average'], 1) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No student data yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top Classes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th class="text-end">Avg Grade</th>
                                </tr>
                            </thead>
                            <tbody id="classLeaderboardBody">
                                @forelse ($classLeaderboardData as $class)
                                    <tr>
                                        <td>{{ $class['label'] }}</td>
                                        <td class="text-end">{{ number_format($class['average'], 1) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No class data yet.</td>
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
            const analyticsUrl = "{{ route('admin.reports.grades') }}";
            const initialState = {
                summary: @json($summaryData),
                gradeDistribution: @json($gradeDistributionData),
                quarterTrend: @json($quarterTrendData),
                gradeLevelBreakdown: @json($gradeLevelBreakdownData),
                subjectLeaderboard: @json($subjectLeaderboardData),
                studentLeaderboard: @json($studentLeaderboardData),
                classLeaderboard: @json($classLeaderboardData),
                classOptionsMap: @json($classOptionsMapData),
            };

            const state = typeof structuredClone === 'function' ?
                structuredClone(initialState) :
                JSON.parse(JSON.stringify(initialState));

            const schoolYearSelect = document.getElementById('gradesSchoolYearSelect');
            const gradeSelect = document.getElementById('gradesGradeSelect');
            const classSelect = document.getElementById('gradesClassSelect');
            const classSelectHelper = document.getElementById('gradesClassSelectHelper');
            const loader = document.getElementById('gradesLoader');
            const viewingYearName = document.getElementById('gradesViewingYearName');

            const averageValue = document.getElementById('gradesAverageValue');
            const passingRateValue = document.getElementById('gradesPassingRate');
            const highestValue = document.getElementById('gradesHighestValue');
            const recordsValue = document.getElementById('gradesRecordsValue');
            const distributionList = document.getElementById('gradeDistributionList');
            const gradeLevelList = document.getElementById('gradeLevelList');
            const subjectLeaderboardBody = document.getElementById('subjectLeaderboardBody');
            const studentLeaderboardBody = document.getElementById('studentLeaderboardBody');
            const classLeaderboardBody = document.getElementById('classLeaderboardBody');

            // Card links and export button
            const cardAverageLink = document.getElementById('cardAverageLink');
            const cardPassingLink = document.getElementById('cardPassingLink');
            const cardHighestLink = document.getElementById('cardHighestLink');
            const cardRecordsLink = document.getElementById('cardRecordsLink');
            const exportBtn = document.getElementById('gradesExportBtn');

            const initialClassSelection = classSelect?.dataset?.selectedClass || '';

            const charts = {
                quarter: null,
                distribution: null,
                gradeLevel: null,
            };

            rebuildQuarterChart();
            rebuildDistributionChart();
            rebuildGradeLevelChart();
            populateClassSelect(initialClassSelection);
            updateSummaryCards();
            updateCardLinks();
            renderDistributionList(state.gradeDistribution);
            renderGradeLevelList(state.gradeLevelBreakdown);
            renderSubjectLeaderboard(state.subjectLeaderboard);
            renderStudentLeaderboard(state.studentLeaderboard);
            renderClassLeaderboard(state.classLeaderboard);

            schoolYearSelect.addEventListener('change', handleFilterChange);
            gradeSelect.addEventListener('change', () => {
                populateClassSelect();
                handleFilterChange();
            });
            classSelect.addEventListener('change', handleFilterChange);

            function handleFilterChange() {
                const params = new URLSearchParams();

                if (schoolYearSelect.value) {
                    params.set('school_year_id', schoolYearSelect.value);
                }

                if (gradeSelect.value) {
                    params.set('grade_level_id', gradeSelect.value);
                }

                if (classSelect.value) {
                    params.set('class_id', classSelect.value);
                }

                loader.classList.remove('d-none');

                fetch(`${analyticsUrl}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    })
                    .then((response) => response.json())
                    .then((data) => {
                        state.summary = data.summary || initialState.summary;
                        state.gradeDistribution = data.gradeDistribution || [];
                        state.quarterTrend = data.quarterTrend || {
                            labels: [],
                            averages: [],
                        };
                        state.gradeLevelBreakdown = data.gradeLevelBreakdown || [];
                        state.subjectLeaderboard = data.subjectLeaderboard || [];
                        state.studentLeaderboard = data.studentLeaderboard || [];
                        state.classLeaderboard = data.classLeaderboard || [];
                        state.classOptionsMap = data.classOptionsMap || state.classOptionsMap;

                        viewingYearName.textContent = data.schoolYearLabel || 'N/A';

                        populateClassSelect(classSelect.value);
                        updateSummaryCards();
                        updateCardLinks();
                        renderDistributionList(state.gradeDistribution);
                        renderGradeLevelList(state.gradeLevelBreakdown);
                        renderSubjectLeaderboard(state.subjectLeaderboard);
                        renderStudentLeaderboard(state.studentLeaderboard);
                        renderClassLeaderboard(state.classLeaderboard);
                        rebuildQuarterChart();
                        rebuildDistributionChart();
                        rebuildGradeLevelChart();
                    })
                    .catch(() => {
                        // Swallow errors but keep UI responsive
                    })
                    .finally(() => {
                        loader.classList.add('d-none');
                    });
            }

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
                classSelectHelper.textContent = 'Showing classes for the selected grade level.';

                if (previousValue && options.some((option) => String(option.id) === String(previousValue))) {
                    classSelect.value = previousValue;
                } else {
                    classSelect.value = '';
                }
            }

            function updateSummaryCards() {
                averageValue.textContent = Number(state.summary.average || 0).toFixed(1);
                passingRateValue.textContent = `${Number(state.summary.passing_rate || 0).toFixed(1)}%`;
                highestValue.textContent = Number(state.summary.highest || 0).toFixed(1);
                recordsValue.textContent = new Intl.NumberFormat().format(state.summary.records || 0);
            }

            function updateCardLinks() {
                const params = new URLSearchParams();
                if (schoolYearSelect.value) {
                    params.set('school_year_id', schoolYearSelect.value);
                }
                if (gradeSelect.value) {
                    params.set('grade_level_id', gradeSelect.value);
                }
                if (classSelect.value) {
                    params.set('class_id', classSelect.value);
                }
                const queryString = params.toString();

                const detailBaseUrl = "{{ route('admin.reports.grades.detail', ['type' => '__TYPE__']) }}";
                const exportBaseUrl = "{{ route('admin.reports.export.grades') }}";

                if (cardAverageLink) {
                    const url = detailBaseUrl.replace('__TYPE__', 'average');
                    cardAverageLink.href = queryString ? `${url}?${queryString}` : url;
                }
                if (cardPassingLink) {
                    const url = detailBaseUrl.replace('__TYPE__', 'passing');
                    cardPassingLink.href = queryString ? `${url}?${queryString}` : url;
                }
                if (cardHighestLink) {
                    const url = detailBaseUrl.replace('__TYPE__', 'highest');
                    cardHighestLink.href = queryString ? `${url}?${queryString}` : url;
                }
                if (cardRecordsLink) {
                    const url = detailBaseUrl.replace('__TYPE__', 'records');
                    cardRecordsLink.href = queryString ? `${url}?${queryString}` : url;
                }
                if (exportBtn) {
                    exportBtn.href = queryString ? `${exportBaseUrl}?${queryString}` : exportBaseUrl;
                }
            }

            function renderDistributionList(list) {
                if (!list.length) {
                    distributionList.innerHTML =
                        '<p class="text-muted text-center mb-0">No grade data for the selected filters.</p>';
                    return;
                }

                distributionList.innerHTML = list.map((item) => (
                    `<div class="d-flex justify-content-between align-items-center mb-2">
                        <span>${item.label}</span>
                        <span class="fw-semibold">${Number(item.percentage || 0).toFixed(1)}%</span>
                    </div>`
                )).join('');
            }

            function renderGradeLevelList(list) {
                if (!list.length) {
                    gradeLevelList.innerHTML =
                        '<p class="text-muted text-center mb-0">No grade level data yet.</p>';
                    return;
                }

                gradeLevelList.innerHTML = list.map((item) => (
                    `<div class="d-flex justify-content-between align-items-center mb-2">
                        <span>${item.label}</span>
                        <span class="fw-semibold">${Number(item.average || 0).toFixed(1)}</span>
                    </div>`
                )).join('');
            }

            function renderSubjectLeaderboard(rows) {
                if (!rows.length) {
                    subjectLeaderboardBody.innerHTML =
                        '<tr><td colspan="2" class="text-center text-muted py-4">No subject data yet.</td></tr>';
                    return;
                }

                subjectLeaderboardBody.innerHTML = rows.map((row) => (
                    `<tr>
                        <td>${row.label}</td>
                        <td class="text-end">${Number(row.average || 0).toFixed(1)}</td>
                    </tr>`
                )).join('');
            }

            function renderStudentLeaderboard(rows) {
                if (!rows.length) {
                    studentLeaderboardBody.innerHTML =
                        '<tr><td colspan="2" class="text-center text-muted py-4">No student data yet.</td></tr>';
                    return;
                }

                studentLeaderboardBody.innerHTML = rows.map((row) => (
                    `<tr>
                        <td>${row.label}</td>
                        <td class="text-end">${Number(row.average || 0).toFixed(1)}</td>
                    </tr>`
                )).join('');
            }

            function renderClassLeaderboard(rows) {
                if (!rows.length) {
                    classLeaderboardBody.innerHTML =
                        '<tr><td colspan="2" class="text-center text-muted py-4">No class data yet.</td></tr>';
                    return;
                }

                classLeaderboardBody.innerHTML = rows.map((row) => (
                    `<tr>
                        <td>${row.label}</td>
                        <td class="text-end">${Number(row.average || 0).toFixed(1)}</td>
                    </tr>`
                )).join('');
            }

            function rebuildQuarterChart() {
                const canvas = document.getElementById('quarterTrendChart');
                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                if (charts.quarter) {
                    charts.quarter.destroy();
                }

                const labels = state.quarterTrend.labels?.length ? state.quarterTrend.labels : ['No data'];
                const dataPoints = state.quarterTrend.averages?.length ? state.quarterTrend.averages : [0];

                charts.quarter = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Average Grade',
                            data: dataPoints,
                            borderColor: 'rgba(13, 110, 253, 1)',
                            backgroundColor: 'rgba(13, 110, 253, 0.15)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                        }, ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: 100,
                            },
                        },
                    },
                });
            }

            function rebuildDistributionChart() {
                const canvas = document.getElementById('gradeDistributionChart');
                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                if (charts.distribution) {
                    charts.distribution.destroy();
                }

                const labels = state.gradeDistribution.length ?
                    state.gradeDistribution.map((item) => item.label) : ['No data'];
                const dataPoints = state.gradeDistribution.length ?
                    state.gradeDistribution.map((item) => item.total || 0) : [0];

                charts.distribution = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: dataPoints,
                            backgroundColor: [
                                'rgba(13,110,253,0.8)',
                                'rgba(25,135,84,0.8)',
                                'rgba(255,193,7,0.8)',
                                'rgba(220,53,69,0.8)',
                                'rgba(111,66,193,0.8)',
                            ],
                            borderWidth: 0,
                        }, ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                    },
                });
            }

            function rebuildGradeLevelChart() {
                const canvas = document.getElementById('gradeLevelChart');
                if (!canvas || typeof Chart === 'undefined') {
                    return;
                }

                if (charts.gradeLevel) {
                    charts.gradeLevel.destroy();
                }

                const labels = state.gradeLevelBreakdown.length ?
                    state.gradeLevelBreakdown.map((item) => item.label) : ['No data'];
                const dataPoints = state.gradeLevelBreakdown.length ?
                    state.gradeLevelBreakdown.map((item) => item.average || 0) : [0];

                charts.gradeLevel = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Average Grade',
                            data: dataPoints,
                            backgroundColor: 'rgba(32, 201, 151, 0.7)',
                            borderRadius: 6,
                        }, ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: 100,
                            },
                        },
                    },
                });
            }
        });
    </script>
@endpush
