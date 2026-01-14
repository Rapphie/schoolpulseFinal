@extends('base')

@section('title', 'Enrollees Analytics')

@section('content')
    @php
        $flatSections = collect($sectionsByGrade)->flatMap(function ($gradeGroup) {
            return collect($gradeGroup['sections'])->map(function ($section) use ($gradeGroup) {
                return array_merge($section, ['grade_label' => $gradeGroup['label']]);
            });
        });
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2 text-gray-800">Enrollees Analytics</h1>
            <p class="mb-1 text-muted">
                Active school year:
                <span class="fw-semibold text-primary" id="activeYearName">{{ $activeSchoolYear->name ?? 'Not set' }}</span>
            </p>
            <p class="mb-0 text-muted">
                Viewing data for:
                <span class="fw-semibold text-dark" id="viewingYearName">{{ $currentSchoolYear->name ?? 'Not set' }}</span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label for="schoolYearSelect" class="form-label small text-muted mb-1">School Year</label>
                <select class="form-select" id="schoolYearSelect">
                    @foreach ($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear->id }}"
                            {{ $currentSchoolYear && $schoolYear->id === $currentSchoolYear->id ? 'selected' : '' }}>
                            {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="gradeFilter" class="form-label small text-muted mb-1">Grade Level</label>
                <select class="form-select" id="gradeFilter">
                    <option value="">All Grades</option>
                    @foreach ($gradeLevels ?? [] as $gradeLevel)
                        <option value="{{ $gradeLevel->level }}"
                            {{ (string) $gradeLevel->level === (string) $selectedGrade ? 'selected' : '' }}>
                            {{ $gradeLevel->name ?? 'Grade ' . $gradeLevel->level }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="text-muted small d-none" id="analyticsLoader">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Updating...
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.enrollees.detail', ['type' => 'students', 'school_year_id' => $currentSchoolYear?->id, 'grade' => $selectedGrade]) }}"
                class="text-decoration-none card-link-wrapper" id="cardStudents" data-type="students">
                <div class="card border-left-primary shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Enrollees</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="totalStudentsCount">
                            {{ number_format($totalStudents) }}</div>
                        <small class="text-muted">Across selected filters</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-primary"><i data-feather="arrow-right" class="feather-sm"></i> View
                            Details</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.enrollees.detail', ['type' => 'sections', 'school_year_id' => $currentSchoolYear?->id, 'grade' => $selectedGrade]) }}"
                class="text-decoration-none card-link-wrapper" id="cardSections" data-type="sections">
                <div class="card border-left-success shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sections</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="totalSectionsCount">
                            {{ number_format($totalSections) }}</div>
                        <small class="text-muted">Active sections in the selected s.y.</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-success"><i data-feather="arrow-right" class="feather-sm"></i> View
                            Details</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.enrollees.detail', ['type' => 'average', 'school_year_id' => $currentSchoolYear?->id, 'grade' => $selectedGrade]) }}"
                class="text-decoration-none card-link-wrapper" id="cardAverage" data-type="average">
                <div class="card border-left-info shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average / Section</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="averagePerSection">
                            {{ number_format($averagePerSection, 1) }}</div>
                        <small class="text-muted">Students per section</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-info"><i data-feather="arrow-right" class="feather-sm"></i> View Details</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('admin.reports.enrollees.detail', ['type' => 'largest', 'school_year_id' => $currentSchoolYear?->id, 'grade' => $selectedGrade]) }}"
                class="text-decoration-none card-link-wrapper" id="cardLargest" data-type="largest">
                <div class="card border-left-warning shadow-sm h-100 card-clickable">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Largest Section</div>
                        <div class="h4 mb-0 font-weight-bold text-gray-800" id="largestSection">
                            {{ number_format($largestSection) }}</div>
                        <small class="text-muted">Peak headcount</small>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center py-2">
                        <small class="text-warning"><i data-feather="arrow-right" class="feather-sm"></i> View
                            Details</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Enrollment Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" height="130"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Grade Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="gradeDistributionChart" height="230"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Sections Comparison</h6>
                </div>
                <div class="card-body">
                    <canvas id="sectionDistributionChart" height="230"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Grade-Level Snapshot</h6>
                </div>
                <div class="card-body">
                    <div id="gradeSummaryContainer">
                        @forelse ($sectionsByGrade as $gradeGroup)
                            <div class="border rounded p-3 mb-3" data-grade="{{ $gradeGroup['grade'] }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $gradeGroup['label'] }}</h6>
                                        <small class="text-muted">Sections: {{ $gradeGroup['section_count'] }}</small>
                                    </div>
                                    <span class="badge" style="background-color: {{ $gradeGroup['color'] }};">
                                        {{ $gradeGroup['total_students'] }} students
                                    </span>
                                </div>
                                <div class="mt-2 d-flex justify-content-between small text-muted">
                                    <span>Avg / section</span>
                                    <span>{{ $gradeGroup['average_per_section'] }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted text-center py-4">No enrollment data for the selected filters.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Sections &amp; Enrollment Detail</h6>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" id="enrolleesExportLink"
                    href="{{ route('admin.reports.export.enrollees') }}">
                    <i data-feather="download"></i> Export
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 25%">Grade Level</th>
                            <th style="width: 35%">Section</th>
                            <th style="width: 20%">Students</th>
                            <th style="width: 20%">% of Grade</th>
                        </tr>
                    </thead>
                    <tbody id="sectionsTableBody">
                        @if ($flatSections->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No enrollment data for the selected
                                    filters.</td>
                            </tr>
                        @else
                            @foreach ($flatSections as $row)
                                <tr>
                                    <td>{{ $row['grade_label'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['students'] }}</td>
                                    <td>{{ $row['percentage'] }}%</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const analyticsUrl = "{{ route('admin.reports.enrollees') }}";
            const exportUrl = "{{ route('admin.reports.export.enrollees') }}";
            const schoolYearSelect = document.getElementById('schoolYearSelect');
            const gradeFilter = document.getElementById('gradeFilter');
            const loader = document.getElementById('analyticsLoader');
            const viewingYearName = document.getElementById('viewingYearName');
            const totalStudentsCount = document.getElementById('totalStudentsCount');
            const totalSectionsCount = document.getElementById('totalSectionsCount');
            const averagePerSection = document.getElementById('averagePerSection');
            const largestSection = document.getElementById('largestSection');
            const gradeSummaryContainer = document.getElementById('gradeSummaryContainer');
            const sectionsTableBody = document.getElementById('sectionsTableBody');
            const exportLink = document.getElementById('enrolleesExportLink');

            const charts = {
                monthly: buildLineChart(document.getElementById('monthlyTrendChart'),
                    @json($monthlyTrend)),
                grade: buildDoughnutChart(document.getElementById('gradeDistributionChart'),
                    @json($gradeChartData)),
                section: buildBarChart(document.getElementById('sectionDistributionChart'),
                    @json($classChartData))
            };

            const analyticsState = {
                sectionsByGrade: @json($sectionsByGrade),
                classChartData: @json($classChartData),
                gradeChartData: @json($gradeChartData),
                monthlyTrend: @json($monthlyTrend),
                totalStudents: {{ (int) $totalStudents }},
                totalSections: {{ (int) $totalSections }},
                averagePerSection: {{ (float) $averagePerSection }},
                largestSection: {{ (int) $largestSection }},
            };

            updateSummaryCards(analyticsState);
            renderGradeSummary(analyticsState.sectionsByGrade);
            renderSectionsTable(analyticsState.sectionsByGrade);
            updateExportLink();

            schoolYearSelect.addEventListener('change', handleFilterChange);
            gradeFilter.addEventListener('change', handleFilterChange);

            function handleFilterChange() {
                updateExportLink();
                updateCardLinks();
                fetchAnalytics();
            }

            async function fetchAnalytics() {
                const params = new URLSearchParams();
                if (schoolYearSelect.value) {
                    params.append('school_year_id', schoolYearSelect.value);
                }
                if (schoolYearSelect.value) {
                    params.append('school_year_id', schoolYearSelect.value);
                }
                if (gradeFilter.value) {
                    params.append('grade', gradeFilter.value);
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
                        throw new Error('Unable to fetch analytics data.');
                    }

                    const payload = await response.json();

                    analyticsState.sectionsByGrade = payload.sectionsByGrade || [];
                    analyticsState.classChartData = payload.classChartData || {
                        labels: [],
                        totals: [],
                        colors: [],
                    };
                    analyticsState.gradeChartData = payload.gradeChartData || {
                        labels: [],
                        totals: [],
                        colors: [],
                    };
                    analyticsState.monthlyTrend = payload.monthlyTrend || {
                        labels: [],
                        totals: [],
                    };
                    analyticsState.totalStudents = payload.totalStudents || 0;
                    analyticsState.totalSections = payload.totalSections || 0;
                    analyticsState.averagePerSection = payload.averagePerSection || 0;
                    analyticsState.largestSection = payload.largestSection || 0;

                    updateSummaryCards(analyticsState);
                    renderGradeSummary(analyticsState.sectionsByGrade);
                    renderSectionsTable(analyticsState.sectionsByGrade);
                    updateChart(charts.monthly, analyticsState.monthlyTrend.labels, analyticsState.monthlyTrend
                        .totals);
                    updateChart(
                        charts.grade,
                        analyticsState.gradeChartData.labels,
                        analyticsState.gradeChartData.totals,
                        analyticsState.gradeChartData.colors,
                    );
                    updateChart(
                        charts.section,
                        analyticsState.classChartData.labels,
                        analyticsState.classChartData.totals,
                        analyticsState.classChartData.colors,
                    );

                    if (payload.schoolYearLabel) {
                        viewingYearName.textContent = payload.schoolYearLabel;
                    }
                } catch (error) {
                    console.error(error);
                    alert('Unable to refresh analytics data. Please try again.');
                } finally {
                    toggleLoading(false);
                }
            }

            function toggleLoading(isLoading) {
                loader.classList.toggle('d-none', !isLoading);
                schoolYearSelect.disabled = isLoading;
                gradeFilter.disabled = isLoading;
            }

            function updateSummaryCards(state) {
                totalStudentsCount.textContent = Number(state.totalStudents).toLocaleString();
                totalSectionsCount.textContent = Number(state.totalSections).toLocaleString();
                averagePerSection.textContent = Number(state.averagePerSection).toLocaleString(undefined, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                });
                largestSection.textContent = Number(state.largestSection).toLocaleString();
            }

            function renderGradeSummary(groups) {
                if (!gradeSummaryContainer) return;

                if (!groups || groups.length === 0) {
                    gradeSummaryContainer.innerHTML =
                        '<div class="text-muted text-center py-4">No enrollment data for the selected filters.</div>';
                    return;
                }

                const cards = groups
                    .map((group) => {
                        return `
                            <div class="border rounded p-3 mb-3" data-grade="${group.grade ?? ''}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">${group.label}</h6>
                                        <small class="text-muted">Sections: ${group.section_count}</small>
                                    </div>
                                    <span class="badge" style="background-color: ${group.color};">
                                        ${group.total_students} students
                                    </span>
                                </div>
                                <div class="mt-2 d-flex justify-content-between small text-muted">
                                    <span>Avg / section</span>
                                    <span>${group.average_per_section}</span>
                                </div>
                            </div>`;
                    })
                    .join('');

                gradeSummaryContainer.innerHTML = cards;
            }

            function renderSectionsTable(groups) {
                if (!sectionsTableBody) return;
                const rows = [];

                (groups || []).forEach((group) => {
                    (group.sections || []).forEach((section) => {
                        rows.push(`
                            <tr>
                                <td>${group.label}</td>
                                <td>${section.name}</td>
                                <td>${section.students}</td>
                                <td>${section.percentage}%</td>
                            </tr>`);
                    });
                });

                sectionsTableBody.innerHTML = rows.length ?
                    rows.join('') :
                    '<tr><td colspan="4" class="text-center text-muted py-4">No enrollment data for the selected filters.</td></tr>';
            }

            function buildLineChart(canvas, dataset) {
                return new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: dataset.labels || [],
                        datasets: [{
                            label: 'Enrollees',
                            data: dataset.totals || [],
                            borderColor: 'rgba(13,110,253,0.9)',
                            backgroundColor: 'rgba(13,110,253,0.15)',
                            pointBackgroundColor: 'rgba(13,110,253,1)',
                            pointRadius: 4,
                            tension: 0.3,
                            fill: true,
                        }, ],
                    },
                    options: {
                        maintainAspectRatio: false,
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

            function buildDoughnutChart(canvas, dataset) {
                return new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: dataset.labels || [],
                        datasets: [{
                            data: dataset.totals || [],
                            backgroundColor: dataset.colors || [],
                            borderWidth: 1,
                        }, ],
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

            function buildBarChart(canvas, dataset) {
                return new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: dataset.labels || [],
                        datasets: [{
                            label: 'Students',
                            data: dataset.totals || [],
                            backgroundColor: dataset.colors || 'rgba(13,110,253,0.7)',
                            borderWidth: 0,
                        }, ],
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                            x: {
                                ticks: {
                                    autoSkip: false,
                                },
                            },
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                        },
                    },
                });
            }

            function updateChart(chart, labels, totals, colors) {
                if (!chart) return;
                chart.data.labels = labels || [];
                chart.data.datasets[0].data = totals || [];
                if (colors && chart.config.type !== 'line') {
                    chart.data.datasets[0].backgroundColor = colors;
                }
                chart.update();
            }

            function updateExportLink() {
                if (!exportLink) return;
                const params = new URLSearchParams();
                if (gradeFilter.value) {
                    params.append('grade', gradeFilter.value);
                }
                exportLink.href = params.toString() ? `${exportUrl}?${params.toString()}` : exportUrl;
            }

            function updateCardLinks() {
                const detailBaseUrl = "{{ route('admin.reports.enrollees.detail', ['type' => '__TYPE__']) }}";
                const cardLinks = document.querySelectorAll('.card-link-wrapper');

                cardLinks.forEach(link => {
                    const type = link.dataset.type;
                    if (!type) return;

                    const params = new URLSearchParams();
                    if (schoolYearSelect.value) {
                        params.append('school_year_id', schoolYearSelect.value);
                    }
                    if (gradeFilter.value) {
                        params.append('grade', gradeFilter.value);
                    }

                    const baseUrl = detailBaseUrl.replace('__TYPE__', type);
                    link.href = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
                });
            }
        });
    </script>
@endpush
