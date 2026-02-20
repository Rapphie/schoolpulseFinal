@extends('base')

@section('title', 'Absenteeism Analytics')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .risk-high {
            background-color: #f8d7da !important;
        }

        .risk-medium {
            background-color: #fff3cd !important;
        }

        .risk-low {
            background-color: #d1e7dd !important;
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 0.4em 0.8em;
        }

        .table-info-text {
            font-size: 0.9rem;
            color: #6c757d;
        }

        /* Progress bar styling */
        .metric-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
            min-width: 60px;
        }

        .metric-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .metric-cell {
            min-width: 100px;
        }

        .metric-value {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .metric-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        /* Color coding for metrics */
        .metric-excellent {
            color: #198754;
        }

        .metric-good {
            color: #20c997;
        }

        .metric-fair {
            color: #ffc107;
        }

        .metric-poor {
            color: #dc3545;
        }

        .bar-excellent {
            background-color: #198754;
        }

        .bar-good {
            background-color: #20c997;
        }

        .bar-fair {
            background-color: #ffc107;
        }

        .bar-poor {
            background-color: #dc3545;
        }

        /* Student insights badges */
        .insight-badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 0.375rem;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .insight-strength {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .insight-weakness {
            background-color: #f8d7da;
            color: #842029;
        }

        .insight-balanced {
            background-color: #e2e3e5;
            color: #41464b;
        }

        /* Engagement score styling */
        .engagement-score {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .engagement-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: white;
        }

        .engagement-high {
            background: linear-gradient(135deg, #198754, #20c997);
        }

        .engagement-medium {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .engagement-low {
            background: linear-gradient(135deg, #dc3545, #e35d6a);
        }

        /* Hide default DataTables search since we have custom one */
        .dataTables_filter {
            display: none;
        }

        /* DataTables styling improvements */
        .dataTables_wrapper .dataTables_length select {
            padding: 0.25rem 0.5rem;
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }

        /* Risk filter tabs styling */
        .nav-pills .nav-link {
            color: #6c757d;
            border: 1px solid #dee2e6;
            margin-right: 0.25rem;
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .nav-pills .nav-link:hover:not(.active) {
            background-color: #f8f9fa;
        }

        .nav-pills .nav-link .badge {
            font-size: 0.7rem;
            padding: 0.2em 0.5em;
        }
    </style>
@endpush

@section('content')
    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="gradeSelector" class="form-label">Grade Level</label>
                            <select id="gradeSelector" class="form-select">
                                <option value="">All Grade Levels</option>
                                @foreach ($gradeLevels as $grade)
                                    <option value="{{ $grade['id'] }}"
                                        {{ (int) $selectedGradeLevelId === (int) $grade['id'] ? 'selected' : '' }}>
                                        {{ $grade['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="classSelector" class="form-label">Class/Section</label>
                            <select id="classSelector" class="form-select" {{ empty($classesForSelect) ? 'disabled' : '' }}>
                                <option value="">
                                    {{ empty($classesForSelect) ? 'Select grade level first' : 'All Classes in Grade' }}
                                </option>
                                @foreach ($classesForSelect as $class)
                                    <option value="{{ $class['id'] }}"
                                        {{ (int) $selectedClassId === (int) $class['id'] ? 'selected' : '' }}>
                                        {{ $class['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="classSearch" class="form-label">Search Student</label>
                            <input type="text" id="classSearch" class="form-control" placeholder="Search by name...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                    Next Month Risk
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="table2-tab" data-bs-toggle="tab"
                                    data-bs-target="#table2-content" type="button" role="tab"
                                    aria-controls="table2-content" aria-selected="false">
                                    Student Engagement
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
                                    <!-- Risk Level Filter Tabs -->
                                    <ul class="nav nav-pills mb-3" id="table1RiskFilter" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active btn-sm" id="table1-all-tab"
                                                data-risk-filter="all" data-table-target="riskTable1" type="button">
                                                All <span class="badge bg-secondary ms-1" id="table1-count-all">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link btn-sm" id="table1-high-tab" data-risk-filter="high"
                                                data-table-target="riskTable1" type="button">
                                                High <span class="badge bg-danger ms-1" id="table1-count-high">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link btn-sm" id="table1-medium-tab"
                                                data-risk-filter="medium" data-table-target="riskTable1" type="button">
                                                Medium <span class="badge bg-warning ms-1"
                                                    id="table1-count-medium">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link btn-sm" id="table1-low-tab" data-risk-filter="low"
                                                data-table-target="riskTable1" type="button">
                                                Low <span class="badge bg-success ms-1" id="table1-count-low">0</span>
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle" id="riskTable1">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th class="text-center" style="width: 150px;">Risk Level</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table1']['data'] as $row)
                                                    @php
                                                        $riskLabel = $row['Risk_Label'] ?? 'N/A';
                                                        $riskPct = $row['Prob_HighRisk_pct'] ?? 0;
                                                        $riskText = $riskLabel === 'Mid' ? 'Medium' : $riskLabel;
                                                        $riskCategory = strtolower(
                                                            $riskLabel === 'Mid' ? 'medium' : $riskLabel,
                                                        );
                                                        $riskBadge = match ($riskLabel) {
                                                            'High' => 'bg-danger',
                                                            'Mid' => 'bg-warning',
                                                            default => 'bg-success',
                                                        };
                                                    @endphp
                                                    <tr data-risk-level="{{ $riskCategory }}">
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td class="text-center">
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
                                <p class="text-muted small mb-3">Forecast to plan interventions early. Trends are
                                    calculated from the last 3 months.</p>
                                @if (!empty($featureTables['table3']['data']))
                                    <!-- Risk Level Filter Tabs -->
                                    <ul class="nav nav-pills mb-3" id="table3RiskFilter" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active btn-sm" id="table3-all-tab"
                                                data-risk-filter="all" data-table-target="riskTable3" type="button">
                                                All <span class="badge bg-secondary ms-1" id="table3-count-all">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link btn-sm" id="table3-high-tab" data-risk-filter="high"
                                                data-table-target="riskTable3" type="button">
                                                High <span class="badge bg-danger ms-1" id="table3-count-high">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link btn-sm" id="table3-medium-tab"
                                                data-risk-filter="medium" data-table-target="riskTable3" type="button">
                                                Medium <span class="badge bg-warning ms-1"
                                                    id="table3-count-medium">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link btn-sm" id="table3-low-tab" data-risk-filter="low"
                                                data-table-target="riskTable3" type="button">
                                                Low <span class="badge bg-success ms-1" id="table3-count-low">0</span>
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle" id="riskTable3">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th class="text-center" style="width: 150px;">Predicted Risk</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table3']['data'] as $row)
                                                    @php
                                                        $riskLabel = $row['Risk_Label'] ?? 'N/A';
                                                        $riskPct = $row['Prob_HighRisk_pct'] ?? 0;
                                                        $riskText = $riskLabel === 'Mid' ? 'Medium' : $riskLabel;
                                                        $riskCategory = strtolower(
                                                            $riskLabel === 'Mid' ? 'medium' : $riskLabel,
                                                        );
                                                        $riskBadge = match ($riskLabel) {
                                                            'High' => 'bg-danger',
                                                            'Mid' => 'bg-warning',
                                                            default => 'bg-success',
                                                        };
                                                    @endphp
                                                    <tr data-risk-level="{{ $riskCategory }}">
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td class="text-center">
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
                                <p class="text-muted small mb-3">Student engagement overview with academic strengths and
                                    areas for improvement.</p>
                                @if (!empty($featureTables['table2']['data']))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle" id="insightsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th class="text-center" style="width: 140px;">Engagement Score</th>
                                                    <th style="width: 250px;">Strongest Subject</th>
                                                    <th style="width: 250px;">Needs Improvement</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table2']['data'] as $row)
                                                    @php
                                                        $eng = $row['EngagementScore'] ?? 0;
                                                        $engClass =
                                                            $eng >= 80 ? 'high' : ($eng >= 60 ? 'medium' : 'low');
                                                        $engLabel =
                                                            $eng >= 80 ? 'High' : ($eng >= 60 ? 'Moderate' : 'Low');

                                                        $strength = $row['Strength'] ?? '—';
                                                        $weakness = $row['Weakness'] ?? '—';

                                                        // Parse strength/weakness to extract subject and score type
                                                        $isBalancedStrength =
                                                            strtolower($strength) === 'balanced' || $strength === 'N/A';
                                                        $isBalancedWeakness =
                                                            strtolower($weakness) === 'balanced' || $weakness === 'N/A';
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td>
                                                            <div class="engagement-score justify-content-center">
                                                                <div
                                                                    class="engagement-circle engagement-{{ $engClass }}">
                                                                    {{ number_format($eng, 0) }}
                                                                </div>
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-semibold"
                                                                        style="font-size: 0.85rem;">{{ $engLabel }}</span>
                                                                    <span class="text-muted"
                                                                        style="font-size: 0.7rem;">Engagement</span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            @if ($isBalancedStrength)
                                                                <span class="insight-badge insight-balanced">
                                                                    <i class="fas fa-balance-scale me-1"></i> Balanced
                                                                    across subjects
                                                                </span>
                                                            @else
                                                                <span class="insight-badge insight-strength"
                                                                    title="{{ $strength }}">
                                                                    <i class="fas fa-star me-1"></i> {{ $strength }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($isBalancedWeakness)
                                                                <span class="insight-badge insight-balanced">
                                                                    <i class="fas fa-balance-scale me-1"></i> No
                                                                    significant gaps
                                                                </span>
                                                            @else
                                                                <span class="insight-badge insight-weakness"
                                                                    title="{{ $weakness }}">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                                    {{ $weakness }}
                                                                </span>
                                                            @endif
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
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // DataTables configuration
            const dataTableOptions = {
                paging: true,
                pageLength: 10,
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                ordering: true,
                info: true,
                autoWidth: false,
                language: {
                    emptyTable: "No student data available",
                    info: "Showing _START_ to _END_ of _TOTAL_ students",
                    infoEmpty: "No students to show",
                    infoFiltered: "(filtered from _MAX_ total students)",
                    lengthMenu: "Show _MENU_ students",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            };

            // Initialize DataTables for each table
            let table1, table2, table3;

            // Table 1: Current Month Risk - sort by risk (column 1) descending
            if (document.getElementById('riskTable1')) {
                table1 = $('#riskTable1').DataTable({
                    ...dataTableOptions,
                    order: [
                        [1, 'desc']
                    ], // Sort by Risk Level descending (now column 1)
                    columnDefs: [{
                            orderable: true,
                            targets: [0, 1]
                        },
                        {
                            // Extract numeric percent from the badge for sorting
                            type: 'num',
                            targets: [1],
                            render: function(data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    const match = $(data).text().match(/(\d+)/);
                                    return match ? parseInt(match[1]) : 0;
                                }
                                return data;
                            }
                        }
                    ]
                });
            }

            // Table 3: Next Month Forecast - sort by predicted risk descending
            if (document.getElementById('riskTable3')) {
                table3 = $('#riskTable3').DataTable({
                    ...dataTableOptions,
                    order: [
                        [1, 'desc']
                    ], // Sort by Predicted Risk descending (now column 1)
                    columnDefs: [{
                            orderable: true,
                            targets: [0, 1]
                        },
                        {
                            // Extract numeric percent from the badge for sorting
                            type: 'num',
                            targets: [1],
                            render: function(data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    const match = $(data).text().match(/(\d+)/);
                                    return match ? parseInt(match[1]) : 0;
                                }
                                return data;
                            }
                        }
                    ]
                });
            }

            // Risk level filter functionality
            function updateRiskCounts(tableId, prefix) {
                const table = tableId === 'riskTable1' ? table1 : table3;
                if (!table) return;

                let counts = {
                    all: 0,
                    high: 0,
                    medium: 0,
                    low: 0
                };

                // Count all rows (not just visible ones)
                table.rows().every(function() {
                    const row = this.node();
                    const riskLevel = $(row).data('risk-level');
                    counts.all++;
                    if (riskLevel === 'high') counts.high++;
                    else if (riskLevel === 'medium') counts.medium++;
                    else if (riskLevel === 'low') counts.low++;
                });

                // Update badge counts
                $(`#${prefix}-count-all`).text(counts.all);
                $(`#${prefix}-count-high`).text(counts.high);
                $(`#${prefix}-count-medium`).text(counts.medium);
                $(`#${prefix}-count-low`).text(counts.low);
            }

            function applyRiskFilter(tableId, riskLevel) {
                const table = tableId === 'riskTable1' ? table1 : table3;
                if (!table) return;

                // Clear any existing custom filter
                $.fn.dataTable.ext.search.pop();

                if (riskLevel !== 'all') {
                    // Add custom filter
                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                        if (settings.nTable.id !== tableId) return true;
                        const row = table.row(dataIndex).node();
                        return $(row).data('risk-level') === riskLevel;
                    });
                }

                table.draw();

                // Remove the filter after drawing (so it doesn't affect other tables)
                if (riskLevel !== 'all') {
                    $.fn.dataTable.ext.search.pop();
                }
            }

            // Initialize risk filter tabs
            function initRiskFilterTabs() {
                // Table 1 filter buttons
                $('#table1RiskFilter .nav-link').on('click', function() {
                    const riskFilter = $(this).data('risk-filter');
                    $('#table1RiskFilter .nav-link').removeClass('active');
                    $(this).addClass('active');
                    applyRiskFilter('riskTable1', riskFilter);
                });

                // Table 3 filter buttons
                $('#table3RiskFilter .nav-link').on('click', function() {
                    const riskFilter = $(this).data('risk-filter');
                    $('#table3RiskFilter .nav-link').removeClass('active');
                    $(this).addClass('active');
                    applyRiskFilter('riskTable3', riskFilter);
                });

                // Update counts after tables are initialized
                setTimeout(() => {
                    updateRiskCounts('riskTable1', 'table1');
                    updateRiskCounts('riskTable3', 'table3');
                }, 100);
            }

            // Initialize risk filter tabs after DataTables
            initRiskFilterTabs();

            // Table 2: Student Insights - sort by engagement score descending
            if (document.getElementById('insightsTable')) {
                table2 = $('#insightsTable').DataTable({
                    ...dataTableOptions,
                    order: [
                        [1, 'desc']
                    ], // Sort by Engagement Score descending
                    columnDefs: [{
                            orderable: true,
                            targets: [0, 1]
                        },
                        {
                            orderable: false,
                            targets: [2, 3]
                        }, // Don't sort by strength/weakness
                        {
                            type: 'num',
                            targets: [1],
                            render: function(data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    const match = $(data).find('.engagement-circle').text().match(
                                        /(\d+)/);
                                    return match ? parseInt(match[1]) : 0;
                                }
                                return data;
                            }
                        }
                    ]
                });
            }

            // Grade/Class selector and search filter
            const gradeSelector = document.getElementById('gradeSelector');
            const classSelector = document.getElementById('classSelector');
            const searchInput = document.getElementById('classSearch');
            const classesEndpoint = "{{ route('analytics.classes-by-grade') }}";

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

                if (selectedId) {
                    classSelector.value = String(selectedId);
                }
                classSelector.disabled = safeOptions.length === 0;
            }

            function navigateWithFilters(gradeId, classId) {
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
                window.location.href = url.toString();
            }

            // Custom search that works with DataTables
            function applySearchFilter() {
                const term = (searchInput ? searchInput.value : '').toLowerCase().trim();

                // Apply search to all DataTables
                if (table1) table1.search(term).draw();
                if (table2) table2.search(term).draw();
                if (table3) table3.search(term).draw();
            }

            function loadClassesForGrade(gradeId) {
                if (!classSelector) return;

                if (!gradeId) {
                    populateClassOptions([]);
                    return;
                }

                classSelector.disabled = true;
                classSelector.innerHTML = '<option value="">Loading...</option>';

                const url = `${classesEndpoint}?grade_level_id=${encodeURIComponent(gradeId)}`;
                console.log('Fetching classes from:', url);

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Classes data received:', data);
                        const options = data.classes || [];
                        populateClassOptions(options);
                        // Always enable if we got a valid response, even if empty
                        // (user should be able to select "All Classes in Grade")
                        if (options.length > 0) {
                            classSelector.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching classes:', error);
                        populateClassOptions([]);
                    });
            }

            if (gradeSelector) {
                gradeSelector.addEventListener('change', () => {
                    const gradeId = gradeSelector.value;
                    // Load sections for this grade (don't navigate yet - let user pick a section)
                    loadClassesForGrade(gradeId);

                    // Update URL without navigating (for bookmarking purposes)
                    const url = new URL(window.location.href);
                    if (gradeId) {
                        url.searchParams.set('grade_level_id', gradeId);
                    } else {
                        url.searchParams.delete('grade_level_id');
                    }
                    url.searchParams.delete('class_id');
                    window.history.replaceState({}, '', url.toString());
                });
            }

            if (classSelector) {
                classSelector.addEventListener('change', () => {
                    const gradeId = gradeSelector ? gradeSelector.value : '';
                    const classId = classSelector.value;
                    // Navigate to reload data with the selected class filter
                    navigateWithFilters(gradeId, classId);
                });
            }

            if (searchInput) {
                // Use debounce for better performance
                let searchTimeout;
                searchInput.addEventListener('keyup', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(applySearchFilter, 300);
                });
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(applySearchFilter, 300);
                });
            }

            // Server already rendered the correct options - no need to repopulate on load
        });
    </script>
@endpush
