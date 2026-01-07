@extends('base')

@section('title', 'Absenteeism Analytics')

@section('content')

    <!-- ML Feature Tables Section -->
    @if (isset($featureTables) && $featureTables)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">
                            Student Risk Monitoring
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Monitor students at risk of absenteeism. Students are sorted by risk level (highest first).
                        </p>

                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs nav-fill" id="featureTablesTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="table1-tab" data-bs-toggle="tab"
                                    data-bs-target="#table1-content" type="button" role="tab"
                                    aria-controls="table1-content" aria-selected="true">
                                    Current Month Risk
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="table3-tab" data-bs-toggle="tab"
                                    data-bs-target="#table3-content" type="button" role="tab"
                                    aria-controls="table3-content" aria-selected="false">
                                    Next Month Forecast
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="table2-tab" data-bs-toggle="tab"
                                    data-bs-target="#table2-content" type="button" role="tab"
                                    aria-controls="table2-content" aria-selected="false">
                                    Student Insights
                                </button>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content pt-3" id="featureTablesContent">
                            <!-- Table 1: Current Month Risk -->
                            <div class="tab-pane fade show active" id="table1-content" role="tabpanel"
                                aria-labelledby="table1-tab">
                                <p class="text-muted small mb-3">Focus first on students marked as High risk.</p>
                                @if (!empty($featureTables['table1']['data']))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th class="text-center" style="width: 120px;">Attendance</th>
                                                    <th class="text-center" style="width: 120px;">Performance</th>
                                                    <th class="text-center" style="width: 150px;">Risk Level</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table1']['data'] as $row)
                                                    @php
                                                        $riskLabel = $row['Risk_Label'] ?? 'N/A';
                                                        $riskPct = $row['Prob_HighRisk_pct'] ?? 0;
                                                        $att = $row['Att_Current'] ?? 0;
                                                        $perf = $row['Perf_Current'] ?? 0;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            {{ number_format($att, 0) }}%
                                                        </td>
                                                        <td class="text-center">
                                                            {{ number_format($perf, 0) }}%
                                                        </td>
                                                        <td class="text-center">
                                                            @php
                                                                $riskText =
                                                                    $riskLabel === 'Mid' ? 'Medium' : $riskLabel;
                                                                $riskBadge = match ($riskLabel) {
                                                                    'High' => 'bg-danger',
                                                                    'Mid' => 'bg-warning',
                                                                    default => 'bg-success',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $riskBadge }} text-white">
                                                                {{ $riskText }} ({{ number_format($riskPct, 0) }}%)
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-users fa-2x mb-2"></i>
                                        <p>No student data available.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Table 3: Next Month Forecast -->
                            <div class="tab-pane fade" id="table3-content" role="tabpanel" aria-labelledby="table3-tab">
                                <p class="text-muted small mb-3">Forecast to plan interventions early.</p>
                                @if (!empty($featureTables['table3']['data']))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th class="text-center" style="width: 100px;">Attendance Trend</th>
                                                    <th class="text-center" style="width: 100px;">Performance Trend</th>
                                                    <th class="text-center" style="width: 150px;">Next Month Risk</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table3']['data'] as $row)
                                                    @php
                                                        $riskLabel = $row['Risk_Label'] ?? 'N/A';
                                                        $riskPct = $row['Prob_HighRisk_pct'] ?? 0;
                                                        $wAtt = $row['Weighted_Attendance'] ?? 0;
                                                        $wPerf = $row['Weighted_Performance'] ?? 0;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            {{ number_format($wAtt, 0) }}%
                                                        </td>
                                                        <td class="text-center">
                                                            {{ number_format($wPerf, 0) }}%
                                                        </td>
                                                        <td class="text-center">
                                                            @php
                                                                $riskText =
                                                                    $riskLabel === 'Mid' ? 'Medium' : $riskLabel;
                                                                $riskBadge = match ($riskLabel) {
                                                                    'High' => 'bg-danger',
                                                                    'Mid' => 'bg-warning',
                                                                    default => 'bg-success',
                                                                };
                                                            @endphp
                                                            <span class="badge {{ $riskBadge }} text-white">
                                                                {{ $riskText }} ({{ number_format($riskPct, 0) }}%)
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-calendar fa-2x mb-2"></i>
                                        <p>No forecast data available.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Table 2: Student Insights -->
                            <div class="tab-pane fade" id="table2-content" role="tabpanel" aria-labelledby="table2-tab">
                                <p class="text-muted small mb-3">Quick notes to guide student support.</p>
                                @if (!empty($featureTables['table2']['data']))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th class="text-center" style="width: 120px;">Engagement</th>
                                                    <th>Strength</th>
                                                    <th>Needs Improvement</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table2']['data'] as $row)
                                                    @php $eng = $row['EngagementScore'] ?? 0; @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            {{ number_format($eng, 0) }}%
                                                        </td>
                                                        <td>
                                                            {{ $row['Strength'] ?? '—' }}
                                                        </td>
                                                        <td>
                                                            {{ $row['Weakness'] ?? '—' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-chart-pie fa-2x mb-2"></i>
                                        <p>No engagement data available.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row mt-4">
            <div class="col-12">
                <div class="border rounded p-3">
                    <strong>Data Unavailable:</strong> Unable to load student risk data. Please ensure the prediction
                    service is running.
                </div>
            </div>
        </div>
    @endif
    </div>
@endsection

@push('scripts')
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
