@extends('base')

@section('title', 'Cumulative Analytics')

@section('content')
    @php
        $summaryDefaults = [
            'students' => ['label' => 'Enrolled Students', 'value' => 0, 'suffix' => '', 'precision' => 0],
            'classes' => ['label' => 'Active Classes', 'value' => 0, 'suffix' => '', 'precision' => 0],
            'attendance_rate' => ['label' => 'Attendance Rate', 'value' => 0, 'suffix' => '%', 'precision' => 1],
            'average_grade' => ['label' => 'Average Grade', 'value' => 0, 'suffix' => '', 'precision' => 1],
        ];

        $summaryCardsData = array_merge($summaryDefaults, $summaryCards ?? []);

        $enrollmentSnapshotData = $enrollmentSnapshot ?? [];
        $enrollmentTotals = array_merge(
            [
                'students' => 0,
                'unique_students' => 0,
                'classes' => 0,
                'average_per_class' => 0,
            ],
            $enrollmentSnapshotData['totals'] ?? [],
        );
        $enrollmentGradeBreakdown = $enrollmentSnapshotData['gradeBreakdown'] ?? [];
        $enrollmentClassDistribution = $enrollmentSnapshotData['classDistribution'] ?? [];
        $enrollmentGenderBreakdown = $enrollmentSnapshotData['genderBreakdown'] ?? [];
        $enrollmentTrendData = array_merge(
            [
                'labels' => [],
                'totals' => [],
            ],
            $enrollmentSnapshotData['monthlyTrend'] ?? [],
        );

        $attendanceTrendData = array_merge(
            [
                'labels' => [],
                'present' => [],
                'absent' => [],
                'late' => [],
            ],
            $attendanceTrend ?? [],
        );

        $gradeDistributionData = $gradeDistribution ?? [];
        $gradeLevelBreakdownData = $gradeLevelBreakdown ?? [];
        $classLeaderboardData = $classLeaderboard ?? [];
        $attendanceSummaryData = array_merge(
            [
                'present_rate' => 0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
            ],
            $attendanceSummary ?? [],
        );
        $gradesSummaryData = array_merge(
            [
                'average' => 0,
                'highest' => 0,
                'lowest' => 0,
                'passing_rate' => 0,
            ],
            $gradesSummary ?? [],
        );
        $classOptionsMapData = $classOptionsMap ?? [];

        $summaryCardStyles = [
            'students' => 'border-left-primary',
            'classes' => 'border-left-secondary',
            'attendance_rate' => 'border-left-success',
            'average_grade' => 'border-left-warning',
        ];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Cumulative Analytics</h1>
            <p class="mb-1 text-muted">
                Active school year:
                <span class="fw-semibold text-primary">{{ $activeSchoolYear->name ?? 'Not set' }}</span>
            </p>
            <p class="mb-0 text-muted">
                Viewing data for:
                <span class="fw-semibold text-dark" id="cumulativeViewingYearName">
                    {{ $currentSchoolYear->name ?? 'Not set' }}
                </span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label for="cumulativeSchoolYearSelect" class="form-label small text-muted mb-1">School Year</label>
                <select class="form-select" id="cumulativeSchoolYearSelect">
                    @foreach ($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear->id }}"
                            {{ $currentSchoolYear && $schoolYear->id === $currentSchoolYear->id ? 'selected' : '' }}>
                            {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="cumulativeGradeSelect" class="form-label small text-muted mb-1">Grade Level</label>
                <select class="form-select" id="cumulativeGradeSelect">
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
                <label for="cumulativeClassSelect" class="form-label small text-muted mb-1">Class / Section</label>
                @php
                    $initialClassOptions = [];
                    if ($selectedGradeLevelId) {
                        $gradeKey = (string) $selectedGradeLevelId;
                        $initialClassOptions = $classOptionsMapData[$gradeKey] ?? [];
                    }
                @endphp
                <select class="form-select" id="cumulativeClassSelect" data-selected-class="{{ $selectedClassId ?? '' }}"
                    @if (!$selectedGradeLevelId) disabled @endif>
                    <option value="">All Classes</option>
                    @foreach ($initialClassOptions as $option)
                        <option value="{{ $option['id'] }}"
                            {{ $selectedClassId && (int) $option['id'] === (int) $selectedClassId ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1" id="cumulativeClassHelper">
                    {{ $selectedGradeLevelId ? 'Showing classes for the selected grade level.' : 'Select a grade level to enable the class filter.' }}
                </small>
            </div>
            <div class="text-muted small d-none" id="cumulativeLoader">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Updating...
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($summaryCardsData as $key => $card)
            <div class="col-md-3 col-sm-6">
                <div class="card shadow-sm h-100 {{ $summaryCardStyles[$key] ?? 'border-left-info' }}"
                    data-summary-card="{{ $key }}">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-uppercase mb-1 text-muted">{{ $card['label'] }}</div>
                        @php
                            $precision = $card['precision'] ?? 0;
                            $value = $card['value'] ?? 0;
                            $suffix = $card['suffix'] ?? '';
                            $formatted =
                                $precision > 0
                                    ? number_format((float) $value, $precision)
                                    : number_format((int) $value);
                        @endphp
                        <div class="h4 mb-0 font-weight-bold text-gray-800 summary-value">
                            {{ $formatted }}{{ $suffix }}
                        </div>
                        <small class="text-muted">Reflects current filters</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Enrollment Snapshot</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between text-center mb-3">
                        <div>
                            <div class="text-muted text-uppercase small">Unique Students</div>
                            <div class="h5 mb-0" id="uniqueStudentsValue">
                                {{ number_format($enrollmentTotals['unique_students']) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted text-uppercase small">Classes</div>
                            <div class="h5 mb-0" id="classCountValue">
                                {{ number_format($enrollmentTotals['classes']) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted text-uppercase small">Avg / Class</div>
                            <div class="h5 mb-0" id="avgPerClassValue">
                                {{ number_format($enrollmentTotals['average_per_class'], 1) }}
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <div class="text-muted text-uppercase small mb-2">Gender Breakdown</div>
                        <div id="genderBreakdownList" class="small">
                            @forelse ($enrollmentGenderBreakdown as $row)
                                <div class="d-flex justify-content-between mb-1">
                                    <span>{{ $row['label'] }}</span>
                                    <span>{{ number_format($row['percentage'], 1) }}%</span>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No data yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-success">Attendance Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between text-center mb-3">
                        <div>
                            <div class="text-muted text-uppercase small">Present</div>
                            <div class="h5 mb-0" id="attendancePresentValue">
                                {{ number_format($attendanceSummaryData['present']) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted text-uppercase small">Absent</div>
                            <div class="h5 mb-0 text-danger" id="attendanceAbsentValue">
                                {{ number_format($attendanceSummaryData['absent']) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted text-uppercase small">Late</div>
                            <div class="h5 mb-0 text-warning" id="attendanceLateValue">
                                {{ number_format($attendanceSummaryData['late']) }}
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted text-uppercase small">Attendance Rate</div>
                        <div class="display-6 fw-semibold text-success" id="attendanceSummaryRateValue">
                            {{ number_format($attendanceSummaryData['present_rate'], 1) }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-info">Grades Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2 text-center mb-3">
                        <div class="col">
                            <div class="text-muted text-uppercase small">Average</div>
                            <div class="h5 mb-0" id="gradeAverageValue">
                                {{ number_format($gradesSummaryData['average'], 1) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="text-muted text-uppercase small">Highest</div>
                            <div class="h5 mb-0 text-success" id="gradeHighestValue">
                                {{ number_format($gradesSummaryData['highest'], 1) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="text-muted text-uppercase small">Lowest</div>
                            <div class="h5 mb-0 text-danger" id="gradeLowestValue">
                                {{ number_format($gradesSummaryData['lowest'], 1) }}
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-muted text-uppercase small">Passing Rate</div>
                        <div class="h5 mb-0 fw-semibold" id="gradePassingValue">
                            {{ number_format($gradesSummaryData['passing_rate'], 1) }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Enrollment by Grade Level</h6>
                    <small class="text-muted">Share across scope</small>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 260px;">
                        <canvas id="gradeLevelChart"></canvas>
                    </div>
                    <div class="mt-3" id="gradeBreakdownList">
                        @forelse ($enrollmentGradeBreakdown as $row)
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ $row['label'] }}</span>
                                <span>{{ number_format($row['percentage'], 1) }}%</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No enrollment data yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Enrollment Trend</h6>
                    <small class="text-muted">Monthly total enrollees</small>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 260px;">
                        <canvas id="enrollmentTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">Attendance Trend</h6>
                    <small class="text-muted">Present vs Absent</small>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 260px;">
                        <canvas id="attendanceTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">Grade Distribution</h6>
                    <small class="text-muted">Across current filters</small>
                </div>
                <div class="card-body">
                    <div class="position-relative" style="height: 260px;">
                        <canvas id="gradeDistributionChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Grade Level</th>
                                        <th class="text-end">Average</th>
                                        <th class="text-end">Records</th>
                                    </tr>
                                </thead>
                                <tbody id="gradeLevelAverageBody">
                                    @forelse ($gradeLevelBreakdownData as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="text-end">{{ number_format($row['average'], 1) }}</td>
                                            <td class="text-end">{{ number_format($row['records']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No grade data.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Top Classes by Enrollment</h6>
                    <small class="text-muted">Top 6 classes</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th class="text-end">Students</th>
                                </tr>
                            </thead>
                            <tbody id="classDistributionBody">
                                @forelse ($enrollmentClassDistribution as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="text-end">{{ number_format($row['students']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">No classes yet.</td>
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
                    <h6 class="m-0 font-weight-bold text-success">Attendance Leaders</h6>
                    <small class="text-muted">Highest presence</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Present</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceLeaderboardBody">
                                @forelse ($classLeaderboardData as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="text-end">{{ number_format($row['attendance_rate'] ?? 0, 1) }}%</td>
                                        <td class="text-end">{{ number_format($row['present'] ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No attendance data.</td>
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
            const analyticsUrl = "{{ route('admin.reports.cumulative') }}";
            const initialState = {
                summaryCards: @json($summaryCardsData),
                enrollmentSnapshot: {
                    totals: @json($enrollmentTotals),
                    gradeBreakdown: @json($enrollmentGradeBreakdown),
                    classDistribution: @json($enrollmentClassDistribution),
                    genderBreakdown: @json($enrollmentGenderBreakdown),
                    monthlyTrend: @json($enrollmentTrendData),
                },
                attendanceTrend: @json($attendanceTrendData),
                gradeDistribution: @json($gradeDistributionData),
                gradeLevelBreakdown: @json($gradeLevelBreakdownData),
                classLeaderboard: @json($classLeaderboardData),
                attendanceSummary: @json($attendanceSummaryData),
                gradesSummary: @json($gradesSummaryData),
                classOptionsMap: @json($classOptionsMapData),
            };

            const state = typeof structuredClone === 'function' ?
                structuredClone(initialState) :
                JSON.parse(JSON.stringify(initialState));

            const schoolYearSelect = document.getElementById('cumulativeSchoolYearSelect');
            const gradeSelect = document.getElementById('cumulativeGradeSelect');
            const classSelect = document.getElementById('cumulativeClassSelect');
            const classHelper = document.getElementById('cumulativeClassHelper');
            const loader = document.getElementById('cumulativeLoader');
            const viewingYearName = document.getElementById('cumulativeViewingYearName');
            const initialClassValue = classSelect && classSelect.dataset ?
                (classSelect.dataset.selectedClass || '') :
                '';

            const uniqueStudentsValue = document.getElementById('uniqueStudentsValue');
            const classCountValue = document.getElementById('classCountValue');
            const avgPerClassValue = document.getElementById('avgPerClassValue');
            const attendancePresentValue = document.getElementById('attendancePresentValue');
            const attendanceAbsentValue = document.getElementById('attendanceAbsentValue');
            const attendanceLateValue = document.getElementById('attendanceLateValue');
            const attendanceSummaryRateValue = document.getElementById('attendanceSummaryRateValue');
            const gradeAverageValue = document.getElementById('gradeAverageValue');
            const gradeHighestValue = document.getElementById('gradeHighestValue');
            const gradeLowestValue = document.getElementById('gradeLowestValue');
            const gradePassingValue = document.getElementById('gradePassingValue');
            const gradeBreakdownList = document.getElementById('gradeBreakdownList');
            const genderBreakdownList = document.getElementById('genderBreakdownList');
            const classDistributionBody = document.getElementById('classDistributionBody');
            const attendanceLeaderboardBody = document.getElementById('attendanceLeaderboardBody');
            const gradeLevelAverageBody = document.getElementById('gradeLevelAverageBody');

            const gradeLevelCanvas = document.getElementById('gradeLevelChart');
            const enrollmentTrendCanvas = document.getElementById('enrollmentTrendChart');
            const attendanceTrendCanvas = document.getElementById('attendanceTrendChart');
            const gradeDistributionCanvas = document.getElementById('gradeDistributionChart');

            const charts = {
                gradeLevel: null,
                enrollmentTrend: null,
                attendanceTrend: null,
                gradeDistribution: null,
            };

            populateClassSelect(initialClassValue);
            updateSummaryCards();
            updateEnrollmentSnapshot();
            updateAttendanceSummary();
            updateGradesSummary();
            renderClassLeaderboard(state.classLeaderboard);
            rebuildGradeLevelChart();
            rebuildEnrollmentTrendChart();
            rebuildAttendanceTrendChart();
            rebuildGradeDistributionChart();
            renderGradeLevelAverages(state.gradeLevelBreakdown);

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
                    classHelper.textContent = 'Select a grade level to enable the class filter.';
                    return;
                }

                const options = optionsMap[selectedGrade] || [];
                if (!options.length) {
                    classSelect.disabled = true;
                    classHelper.textContent = 'No classes found for this grade in the selected school year.';
                    return;
                }

                options.forEach((option) => {
                    const opt = document.createElement('option');
                    opt.value = option.id;
                    opt.textContent = option.label;
                    classSelect.appendChild(opt);
                });

                classSelect.disabled = false;
                classHelper.textContent = 'Filtering classes within the selected grade level.';

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
                        throw new Error('Unable to refresh analytics.');
                    }

                    const payload = await response.json();

                    state.summaryCards = payload.summaryCards || initialState.summaryCards;
                    state.enrollmentSnapshot = payload.enrollmentSnapshot || initialState.enrollmentSnapshot;
                    state.attendanceTrend = payload.attendanceTrend || initialState.attendanceTrend;
                    state.attendanceSummary = payload.attendanceSummary || initialState.attendanceSummary;
                    state.gradesSummary = payload.gradesSummary || initialState.gradesSummary;
                    state.gradeDistribution = payload.gradeDistribution || initialState.gradeDistribution;
                    state.gradeLevelBreakdown = payload.gradeLevelBreakdown || initialState.gradeLevelBreakdown;
                    state.classLeaderboard = payload.classLeaderboard || initialState.classLeaderboard;
                    state.classOptionsMap = payload.classOptionsMap ?? initialState.classOptionsMap;

                    if (payload.schoolYearLabel) {
                        viewingYearName.textContent = payload.schoolYearLabel;
                    }

                    populateClassSelect();
                    updateSummaryCards();
                    updateEnrollmentSnapshot();
                    updateAttendanceSummary();
                    updateGradesSummary();
                    renderClassLeaderboard(state.classLeaderboard);
                    renderGradeLevelAverages(state.gradeLevelBreakdown);
                    rebuildGradeLevelChart();
                    rebuildEnrollmentTrendChart();
                    rebuildAttendanceTrendChart();
                    rebuildGradeDistributionChart();
                } catch (error) {
                    console.error(error);
                    alert('Unable to refresh analytics. Please try again.');
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

            function formatValue(value, precision = 0, suffix = '') {
                if (precision > 0) {
                    return `${Number(value ?? 0).toFixed(precision)}${suffix}`;
                }

                return `${Number(value ?? 0).toLocaleString()}${suffix}`;
            }

            function updateSummaryCards() {
                const cards = state.summaryCards || {};
                Object.keys(cards).forEach((key) => {
                    const card = document.querySelector(`[data-summary-card="${key}"] .summary-value`);
                    if (!card) {
                        return;
                    }
                    const data = cards[key];
                    card.textContent = formatValue(data.value, data.precision, data.suffix || '');
                });
            }

            function updateEnrollmentSnapshot() {
                const totals = (state.enrollmentSnapshot && state.enrollmentSnapshot.totals) || {};
                if (uniqueStudentsValue) {
                    uniqueStudentsValue.textContent = Number(totals.unique_students || 0).toLocaleString();
                }
                if (classCountValue) {
                    classCountValue.textContent = Number(totals.classes || 0).toLocaleString();
                }
                if (avgPerClassValue) {
                    avgPerClassValue.textContent = (Number(totals.average_per_class || 0)).toFixed(1);
                }

                const enrollmentSnapshot = state.enrollmentSnapshot || {};
                renderGenderBreakdown(enrollmentSnapshot.genderBreakdown || []);
                renderGradeBreakdown(enrollmentSnapshot.gradeBreakdown || []);
                renderClassDistribution(enrollmentSnapshot.classDistribution || []);
            }

            function updateAttendanceSummary() {
                const summary = state.attendanceSummary || {};
                if (attendancePresentValue) {
                    attendancePresentValue.textContent = Number(summary.present || 0).toLocaleString();
                }
                if (attendanceAbsentValue) {
                    attendanceAbsentValue.textContent = Number(summary.absent || 0).toLocaleString();
                }
                if (attendanceLateValue) {
                    attendanceLateValue.textContent = Number(summary.late || 0).toLocaleString();
                }
                if (attendanceSummaryRateValue) {
                    attendanceSummaryRateValue.textContent = `${Number(summary.present_rate || 0).toFixed(1)}%`;
                }
            }

            function updateGradesSummary() {
                const summary = state.gradesSummary || {};
                if (gradeAverageValue) {
                    gradeAverageValue.textContent = Number(summary.average || 0).toFixed(1);
                }
                if (gradeHighestValue) {
                    gradeHighestValue.textContent = Number(summary.highest || 0).toFixed(1);
                }
                if (gradeLowestValue) {
                    gradeLowestValue.textContent = Number(summary.lowest || 0).toFixed(1);
                }
                if (gradePassingValue) {
                    gradePassingValue.textContent = `${Number(summary.passing_rate || 0).toFixed(1)}%`;
                }
            }

            function renderGenderBreakdown(rows) {
                if (!genderBreakdownList) {
                    return;
                }

                if (!rows.length) {
                    genderBreakdownList.innerHTML = '<p class="text-muted mb-0">No data yet.</p>';
                    return;
                }

                genderBreakdownList.innerHTML = rows.map((row) => (
                    `<div class="d-flex justify-content-between mb-1">
                        <span>${row.label || 'Group'}</span>
                        <span>${Number(row.percentage || 0).toFixed(1)}%</span>
                    </div>`
                )).join('');
            }

            function renderGradeBreakdown(rows) {
                if (!gradeBreakdownList) {
                    return;
                }

                if (!rows.length) {
                    gradeBreakdownList.innerHTML = '<p class="text-muted mb-0">No enrollment data yet.</p>';
                    return;
                }

                gradeBreakdownList.innerHTML = rows.map((row) => (
                    `<div class="d-flex justify-content-between mb-1">
                        <span>${row.label || 'Grade'}</span>
                        <span>${Number(row.percentage || 0).toFixed(1)}%</span>
                    </div>`
                )).join('');
            }

            function renderClassDistribution(rows) {
                if (!classDistributionBody) {
                    return;
                }

                if (!rows.length) {
                    classDistributionBody.innerHTML =
                        '<tr><td colspan="2" class="text-center text-muted py-4">No classes yet.</td></tr>';
                    return;
                }

                classDistributionBody.innerHTML = rows.map((row) => (
                    `<tr>
                        <td>${row.label || 'Class'}</td>
                        <td class="text-end">${Number(row.students || 0).toLocaleString()}</td>
                    </tr>`
                )).join('');
            }

            function renderClassLeaderboard(rows) {
                if (!attendanceLeaderboardBody) {
                    return;
                }

                if (!rows.length) {
                    attendanceLeaderboardBody.innerHTML =
                        '<tr><td colspan="3" class="text-center text-muted py-4">No attendance data.</td></tr>';
                    return;
                }

                attendanceLeaderboardBody.innerHTML = rows.map((row) => (
                    `<tr>
                        <td>${row.label || 'Class'}</td>
                        <td class="text-end">${Number(row.attendance_rate || 0).toFixed(1)}%</td>
                        <td class="text-end">${Number(row.present || 0).toLocaleString()}</td>
                    </tr>`
                )).join('');
            }

            function renderGradeLevelAverages(rows) {
                if (!gradeLevelAverageBody) {
                    return;
                }

                if (!rows.length) {
                    gradeLevelAverageBody.innerHTML =
                        '<tr><td colspan="3" class="text-center text-muted">No grade data.</td></tr>';
                    return;
                }

                gradeLevelAverageBody.innerHTML = rows.map((row) => (
                    `<tr>
                        <td>${row.label || 'Grade Level'}</td>
                        <td class="text-end">${Number(row.average || 0).toFixed(1)}</td>
                        <td class="text-end">${Number(row.records || 0).toLocaleString()}</td>
                    </tr>`
                )).join('');
            }

            function rebuildGradeLevelChart() {
                if (!gradeLevelCanvas) {
                    return;
                }

                if (charts.gradeLevel) {
                    charts.gradeLevel.destroy();
                }

                const enrollmentSnapshot = state.enrollmentSnapshot || {};
                const breakdown = enrollmentSnapshot.gradeBreakdown || [];
                charts.gradeLevel = new Chart(gradeLevelCanvas, {
                    type: 'bar',
                    data: {
                        labels: breakdown.map((row) => row.label || 'Grade'),
                        datasets: [{
                            label: 'Students',
                            data: breakdown.map((row) => row.total || 0),
                            backgroundColor: 'rgba(13,110,253,0.7)',
                            borderRadius: 4,
                        }],
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
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            }

            function rebuildEnrollmentTrendChart() {
                if (!enrollmentTrendCanvas) {
                    return;
                }

                if (charts.enrollmentTrend) {
                    charts.enrollmentTrend.destroy();
                }

                const enrollmentSnapshot = state.enrollmentSnapshot || {};
                const dataset = enrollmentSnapshot.monthlyTrend || {
                    labels: [],
                    totals: []
                };
                charts.enrollmentTrend = new Chart(enrollmentTrendCanvas, {
                    type: 'line',
                    data: {
                        labels: dataset.labels || [],
                        datasets: [{
                            label: 'Enrollees',
                            data: dataset.totals || [],
                            borderColor: 'rgba(111,66,193,1)',
                            backgroundColor: 'rgba(111,66,193,0.2)',
                            tension: 0.35,
                            fill: true,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
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

            function rebuildAttendanceTrendChart() {
                if (!attendanceTrendCanvas) {
                    return;
                }

                if (charts.attendanceTrend) {
                    charts.attendanceTrend.destroy();
                }

                const dataset = state.attendanceTrend || {
                    labels: []
                };
                charts.attendanceTrend = new Chart(attendanceTrendCanvas, {
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
                                backgroundColor: 'rgba(255,193,7,0.15)',
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

            function rebuildGradeDistributionChart() {
                if (!gradeDistributionCanvas) {
                    return;
                }

                if (charts.gradeDistribution) {
                    charts.gradeDistribution.destroy();
                }

                const rows = state.gradeDistribution || [];
                charts.gradeDistribution = new Chart(gradeDistributionCanvas, {
                    type: 'bar',
                    data: {
                        labels: rows.map((row) => row.label || 'Range'),
                        datasets: [{
                            label: 'Records',
                            data: rows.map((row) => row.total || 0),
                            backgroundColor: 'rgba(13,202,240,0.7)',
                            borderRadius: 4,
                        }],
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
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            }
        });
    </script>
@endpush
