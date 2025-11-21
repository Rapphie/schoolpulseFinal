@extends('base')

@section('title', 'Absenteeism Analytics')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Absenteeism Analytics</h1>
        </div>

        <div class="row">
            <!-- Monthly Attendance Trend -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Rate (%)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <div id="monthlyTrendChart"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Controls: Class selector and search -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Class & Search</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="gradeSelector" class="form-label">Select Grade Level</label>
                            <select id="gradeSelector" class="form-select">
                                <option value="">Select Grade Level</option>
                                @foreach ($gradeLevels ?? [] as $grade)
                                    <option value="{{ $grade['id'] }}">
                                        {{ $grade['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="classSelector" class="form-label">Select Class</label>
                            <select id="classSelector" class="form-select" {{ $selectedGradeLevelId ? '' : 'disabled' }}>
                                <option value="">
                                    {{ $selectedGradeLevelId ? 'All Classes in Grade' : 'Select grade level first' }}
                                </option>
                                @foreach ($classesForSelect ?? [] as $cls)
                                    <option value="{{ $cls['id'] }}"
                                        {{ isset($selectedClassId) && (int) $selectedClassId === (int) $cls['id'] ? 'selected' : '' }}>
                                        {{ $cls['label'] }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pick a grade level to load its classes.</small>
                        </div>
                        <div>
                            <label for="classSearch" class="form-label">Search Students</label>
                            <input type="text" id="classSearch" class="form-control"
                                placeholder="Search name or LRN in selected class">
                            <small class="text-muted">Filters the visible class panel below.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absences by Subject -->
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Absences by Subject</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <div id="subjectAbsenceChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($engagementSummary))
            @php
                $avgEng = $engagementSummary['average'] ?? null;
                $highCount = $engagementSummary['high_count'] ?? 0;
                $totalTracked = $engagementSummary['total_students'] ?? 0;
                $highPercent = $totalTracked > 0 ? round(($highCount / $totalTracked) * 100, 1) : null;
                $topEng = $engagementSummary['top_student'] ?? null;
            @endphp
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-left-primary h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Average Engagement</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                {{ $avgEng !== null ? number_format($avgEng, 2) . '%' : '—' }}
                            </div>
                            <p class="mb-0 text-muted">Blended attendance & performance</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-success h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Highly Engaged</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                {{ $highCount }}
                                <small class="text-muted">
                                    {{ $highPercent !== null ? '(' . $highPercent . '%)' : '' }}
                                </small>
                            </div>
                            <p class="mb-0 text-muted">Students scoring ≥ 80%</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-left-warning h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Top Engaged Student</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $topEng['name'] ?? '—' }}
                            </div>
                            <div class="text-muted">
                                {{ isset($topEng['score']) ? number_format($topEng['score'], 2) . '%' : '' }}
                                <small class="d-block">{{ $topEng['class_label'] ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- High-Risk Students (current month) -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-danger">High-Risk Students ({{ $highRiskThreshold ?? 5 }}+
                            unexcused absences this month)</h6>
                        <small class="text-muted">Window: {{ now()->startOfMonth()->format('M d') }} -
                            {{ now()->endOfMonth()->format('M d') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th class="text-center">Monthly Unexcused</th>
                                        <th class="text-center">Engagement</th>
                                        <th class="text-center">Predicted Risk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($highRiskStudents ?? []) as $risk)
                                        <tr>
                                            <td>{{ $risk['name'] ?? '—' }}</td>
                                            <td>{{ $risk['class_label'] ?? '—' }}</td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-danger">{{ $risk['monthly_unexcused_absences'] ?? 0 }}</span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $engScore = $risk['engagement_score'] ?? null;
                                                    $engClass =
                                                        $engScore >= 80
                                                            ? 'bg-success'
                                                            : ($engScore >= 60
                                                                ? 'bg-info text-dark'
                                                                : 'bg-secondary');
                                                @endphp
                                                @if ($engScore !== null)
                                                    <span
                                                        class="badge {{ $engClass }}">{{ number_format($engScore, 2) }}%</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $pred = $risk['prediction_confidence'] ?? null;
                                                    $riskClass =
                                                        $pred >= 70
                                                            ? 'bg-danger'
                                                            : ($pred >= 40
                                                                ? 'bg-warning text-dark'
                                                                : 'bg-success');
                                                @endphp
                                                @if ($pred !== null)
                                                    <span
                                                        class="badge {{ $riskClass }}">{{ number_format($pred, 2) }}%</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No students have reached the
                                                high-risk threshold this month.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engagement Ranking -->
        <div class="row">
            <div class="col-xl-6">
                <div class="card shadow mb-4 h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Top Engagement</h6>
                        <small class="text-muted">Celebrating consistent excellence</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th class="text-end">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($engagementTop ?? []) as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $row['name'] ?? '—' }}</td>
                                            <td>{{ $row['class_label'] ?? '—' }}</td>
                                            <td class="text-end">
                                                <span
                                                    class="badge bg-success">{{ number_format($row['engagement_score'], 2) }}%</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No engagement data yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card shadow mb-4 h-100">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-warning">Needs Attention</h6>
                        <small class="text-muted">Least engaged students</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th class="text-end">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($engagementBottom ?? []) as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $row['name'] ?? '—' }}</td>
                                            <td>{{ $row['class_label'] ?? '—' }}</td>
                                            <td class="text-end">
                                                <span
                                                    class="badge bg-danger">{{ number_format($row['engagement_score'], 2) }}%</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No engagement data yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Predictive Risk Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div
                        class="card-header py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h6 class="m-0 font-weight-bold text-primary">Predictive Risk Outlook</h6>
                            <small class="text-muted">Only students ≥ 70% probability appear below.</small>
                        </div>
                        <div class="mt-3 mt-md-0 d-flex flex-wrap gap-2">
                            <span class="badge bg-success">Good (&lt; 40%): {{ $riskSummary['good'] ?? 0 }}</span>
                            <span class="badge bg-warning text-dark">Medium (40-69%):
                                {{ $riskSummary['medium'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th class="text-center">Predicted Risk</th>
                                        <th class="text-center">Engagement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($predictiveHighRisk ?? []) as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $row['name'] ?? '—' }}</td>
                                            <td>{{ $row['class_label'] ?? '—' }}</td>
                                            <td class="text-center">
                                                <span
                                                    class="badge bg-danger">{{ number_format($row['prediction_confidence'], 2) }}%</span>
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $engScore = $row['engagement_score'] ?? null;
                                                    $engClass =
                                                        $engScore >= 80
                                                            ? 'bg-success'
                                                            : ($engScore >= 60
                                                                ? 'bg-info text-dark'
                                                                : 'bg-secondary');
                                                @endphp
                                                @if ($engScore !== null)
                                                    <span
                                                        class="badge {{ $engClass }}">{{ number_format($engScore, 2) }}%</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Great news! No students
                                                currently exceed the 70% predictive risk threshold.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Absentees -->
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Students with Most Absences</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student Name</th>
                                        <th>lrn</th>
                                        <th class="text-center">Total Absences</th>
                                        <th class="text-center">Predicted Risk (%)</th>
                                        <th class="text-center">Engagement Score (%)</th>
                                        <th class="text-center">Engagement Rank</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topAbsentees as $student)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                            <td>{{ $student->lrn }}</td>
                                            <td class="text-center">{{ $student->absent_count }}</td>
                                            <td class="text-center">
                                                @php
                                                    $metrics = $studentMetrics[$student->id] ?? null;
                                                    $pred = $metrics['prediction_confidence'] ?? null;
                                                    $engScore = $metrics['engagement_score'] ?? null;
                                                    $engRank = $metrics['engagement_rank'] ?? null;
                                                @endphp
                                                @if ($pred !== null)
                                                    <span class="badge bg-danger"
                                                        title="Model predicted absenteeism risk">
                                                        {{ number_format($pred, 2) }}%
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($engScore !== null)
                                                    @php
                                                        $engClass =
                                                            $engScore >= 80
                                                                ? 'bg-success'
                                                                : ($engScore >= 60
                                                                    ? 'bg-info text-dark'
                                                                    : 'bg-secondary');
                                                    @endphp
                                                    <span class="badge {{ $engClass }}"
                                                        title="Engagement score combines attendance and performance">
                                                        {{ number_format($engScore, 2) }}%
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ $engRank ? '#' . $engRank : '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No absence data available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Per-Class Prediction Panels (below) -->
        @if (isset($classPredictions) && count($classPredictions))
            <div class="row" id="classPanels">
                @foreach ($classPredictions as $classId => $bundle)
                    @php
                        $panelLabel = $bundle['label'] ?? 'Class #' . $bundle['class']->id;
                    @endphp
                    <div class="col-xl-6 col-lg-6 class-panel" data-class-id="{{ $bundle['class']->id }}"
                        data-grade-id="{{ $bundle['grade_level_id'] ?? '' }}">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">{{ $panelLabel }}</h6>
                                <small class="text-muted">ID: {{ $bundle['class']->id }}</small>
                            </div>
                            <div class="card-body">
                                @if (empty($bundle['students']))
                                    <p class="text-muted mb-0">No enrolled students.</p>
                                @else
                                    <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                                        <table class="table table-sm table-striped class-students-table">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>LRN</th>
                                                    <th class="text-center">Risk (%)</th>
                                                    <th class="text-center">Engagement (%)</th>
                                                    <th class="text-center">Rank</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($bundle['students'] as $stu)
                                                    <tr>
                                                        <td class="student-name">{{ $stu['name'] }}</td>
                                                        <td class="student-lrn">{{ $stu['lrn'] }}</td>
                                                        <td class="text-center">
                                                            @if ($stu['prediction_confidence'] !== null)
                                                                @php
                                                                    $risk = $stu['prediction_confidence'];
                                                                    $color =
                                                                        $risk >= 50
                                                                            ? 'bg-danger'
                                                                            : ($risk >= 30
                                                                                ? 'bg-warning text-dark'
                                                                                : 'bg-success');
                                                                @endphp
                                                                <span class="badge {{ $color }}"
                                                                    title="Predicted absenteeism risk">{{ number_format($risk, 2) }}%</span>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @php
                                                                $eng = $stu['engagement_score'] ?? null;
                                                            @endphp
                                                            @if ($eng !== null)
                                                                @php
                                                                    $engClass =
                                                                        $eng >= 80
                                                                            ? 'bg-success'
                                                                            : ($eng >= 60
                                                                                ? 'bg-info text-dark'
                                                                                : 'bg-secondary');
                                                                @endphp
                                                                <span class="badge {{ $engClass }}"
                                                                    title="Engagement combines attendance & scores">{{ number_format($eng, 2) }}%</span>
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            {{ isset($stu['engagement_rank']) ? '#' . $stu['engagement_rank'] : '—' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    {{-- Make sure you have ApexCharts available in your base layout or include it here --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Monthly Attendance Trend Chart
            const monthlyTrendData = @json($monthlyTrend);
            const monthlyTrendOptions = {
                series: [{
                    name: 'Attendance Rate',
                    data: Object.values(monthlyTrendData)
                }],
                chart: {
                    height: 350,
                    type: 'area',
                    toolbar: {
                        show: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth'
                },
                xaxis: {
                    type: 'category',
                    categories: Object.keys(monthlyTrendData)
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0) + "%";
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + "%"
                        }
                    }
                }
            };
            const monthlyTrendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"),
                monthlyTrendOptions);
            monthlyTrendChart.render();


            // 2. Absences by Subject Chart
            const subjectAbsenceData = @json($absencesBySubject);
            const subjectAbsenceOptions = {
                series: Object.values(subjectAbsenceData),
                chart: {
                    height: 350,
                    type: 'donut',
                },
                labels: Object.keys(subjectAbsenceData),
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };
            const subjectAbsenceChart = new ApexCharts(document.querySelector("#subjectAbsenceChart"),
                subjectAbsenceOptions);
            subjectAbsenceChart.render();

            // 3. Grade/Class selector and search filter
            const gradeSelector = document.getElementById('gradeSelector');
            const classSelector = document.getElementById('classSelector');
            const searchInput = document.getElementById('classSearch');
            const panels = document.querySelectorAll('.class-panel');
            const classesEndpoint = "{{ route('teacher.analytics.classes-by-grade') }}";
            const initialClassOptions = @json($classesForSelect ?? []);
            const initialSelectedClassId = @json($selectedClassId);

            function populateClassOptions(options, selectedId = null) {
                if (!classSelector) return;
                const safeOptions = Array.isArray(options) ? options : [];
                classSelector.innerHTML = '';
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = safeOptions.length ? 'All Classes in Grade' :
                    'Select grade level first';
                classSelector.appendChild(defaultOption);

                safeOptions.forEach(opt => {
                    const optionEl = document.createElement('option');
                    optionEl.value = String(opt.id);
                    optionEl.textContent = opt.label;
                    classSelector.appendChild(optionEl);
                });

                classSelector.value = selectedId ? String(selectedId) : '';
            }

            function updateUrlParams(gradeId, classId) {
                const url = new URL(window.location.href);
                if (gradeId) {
                    url.searchParams.set('grade_level_id', gradeId);
                } else {
                    url.searchParams.delete('grade_level_id');
                }
                if (classId) {
                    url.searchParams.set('class_id', classId);
                } else {
                    url.searchParams.delete('class_id');
                }
                window.history.replaceState({}, '', url);
            }

            function applyClassFilter() {
                const selectedGrade = gradeSelector ? gradeSelector.value : '';
                const selectedClass = classSelector ? classSelector.value : '';
                panels.forEach(panel => {
                    const panelGrade = panel.getAttribute('data-grade-id') || '';
                    const panelClass = panel.getAttribute('data-class-id') || '';
                    const gradeMatch = !selectedGrade || selectedGrade === panelGrade;
                    const classMatch = !selectedClass || selectedClass === panelClass;
                    panel.style.display = gradeMatch && classMatch ? '' : 'none';
                });
                if (searchInput) searchInput.value = '';
                applySearchFilter();
                updateUrlParams(selectedGrade, selectedClass);
            }

            function applySearchFilter() {
                const term = (searchInput ? searchInput.value : '').toLowerCase();
                panels.forEach(panel => {
                    if (panel.style.display === 'none') return;
                    panel.querySelectorAll('tbody tr').forEach(tr => {
                        const name = (tr.querySelector('.student-name')?.textContent || '')
                            .toLowerCase();
                        const lrn = (tr.querySelector('.student-lrn')?.textContent || '')
                            .toLowerCase();
                        const match = !term || name.includes(term) || lrn.includes(term);
                        tr.style.display = match ? '' : 'none';
                    });
                });
            }

            function loadClassesForGrade(gradeId) {
                if (!classSelector) return;
                classSelector.disabled = true;
                populateClassOptions([]);

                if (!gradeId) {
                    return;
                }

                fetch(`${classesEndpoint}?grade_level_id=${encodeURIComponent(gradeId)}`)
                    .then(response => response.json())
                    .then(data => {
                        const options = data.classes || [];
                        populateClassOptions(options);
                        classSelector.disabled = options.length === 0;
                    })
                    .catch(() => {
                        populateClassOptions([]);
                    });
            }

            if (gradeSelector) {
                gradeSelector.addEventListener('change', () => {
                    const gradeId = gradeSelector.value;
                    if (classSelector) {
                        classSelector.value = '';
                    }
                    updateUrlParams(gradeId, '');
                    loadClassesForGrade(gradeId);
                    applyClassFilter();
                });
            }

            if (classSelector) {
                classSelector.addEventListener('change', () => {
                    applyClassFilter();
                });
            }

            if (searchInput) {
                searchInput.addEventListener('keyup', applySearchFilter);
            }

            if (classSelector) {
                if (gradeSelector && gradeSelector.value) {
                    populateClassOptions(initialClassOptions, initialSelectedClassId);
                    classSelector.disabled = initialClassOptions.length === 0;
                } else {
                    populateClassOptions([]);
                    classSelector.disabled = true;
                }
            }

            applyClassFilter();
        });
    </script>
@endpush
