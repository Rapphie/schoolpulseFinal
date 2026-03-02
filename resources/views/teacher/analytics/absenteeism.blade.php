@extends('base')

@section('title', 'Absenteeism Analytics')
@section('head')
    <link rel="stylesheet" href="{{ asset('css/absenteeism/absenteeism.css') }}">
@endsection
@section('content')
    @php
        $selectedGradeLabel = 'All Grade Levels';
        foreach ($gradeLevels as $gradeOption) {
            if ((int) $selectedGradeLevelId === (int) ($gradeOption['id'] ?? 0)) {
                $selectedGradeLabel = $gradeOption['label'] ?? $selectedGradeLabel;
                break;
            }
        }

        $selectedClassLabel = 'All Handled Classes';
        foreach ($classesForSelect as $classOption) {
            if ((int) $selectedClassId === (int) ($classOption['id'] ?? 0)) {
                $selectedClassLabel = $classOption['label'] ?? $selectedClassLabel;
                break;
            }
        }
    @endphp

    <div class="card shadow-sm analytics-hero mb-4">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <p class="text-muted small mb-1">Teacher Analytics</p>
                <h5 class="analytics-hero-title mb-2">Absenteeism Analytics Overview</h5>
                <p class="text-muted mb-0">
                    Review attendance risk, engagement, and intervention priorities using your current class filters.
                </p>
            </div>
            <div class="d-flex flex-column justify-content-center">
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill text-bg-primary scope-pill">
                        <i class="fas fa-layer-group me-1" aria-hidden="true"></i> {{ $selectedGradeLabel }}
                    </span>
                    <span class="badge rounded-pill text-bg-light border text-dark scope-pill">
                        <i class="fas fa-users me-1" aria-hidden="true"></i> {{ $selectedClassLabel }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm filter-card">
                <div
                    class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="m-0 fw-bold">Filters</h6>
                        <p class="text-muted small mb-0">Apply grade/class scope and search once across all analytics
                            tables.</p>
                    </div>
                    <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-rotate-left me-1" aria-hidden="true"></i> Reset Filters
                    </button>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
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
                        <div class="col-lg-4 col-md-6">
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
                        <div class="col-lg-4 col-md-12">
                            <label for="classSearch" class="form-label">Search Student</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search" aria-hidden="true"></i>
                                </span>
                                <input type="text" id="classSearch" class="form-control"
                                    placeholder="Search by name across visible tables...">
                                <button type="button" class="btn btn-outline-secondary" id="clearSearchBtn">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($analyticsServiceWarning))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-warning mb-0" role="alert">
                    {{ $analyticsServiceWarning }}
                </div>
            </div>
        </div>
    @endif

    @if (!empty($analyticsAccessNotice) && ($analyticsScopeMode ?? null) !== 'none')
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info mb-0" role="alert">
                    {{ $analyticsAccessNotice }}
                </div>
            </div>
        </div>
    @endif

    <!-- ML Feature Tables Section -->
    @if (isset($featureTables) && $featureTables)
        @php
            $honorsSummary ?? [];
            $recognitionTop5 ?? [];
            $interventionQueue ?? [];
            $decliningTrendRows ?? [];
            $riskCalibrationMeta ?? [];
            $showHonors = (bool) ($canViewHonors ?? false);
            $calibrationNote =
                $riskCalibrationMeta['note'] ?? 'Risk labels and percentages came from model prediction';
            $currentRiskRows = $featureTables['table1']['data'] ?? [];
            $highRiskCount = 0;
            $mediumRiskCount = 0;
            $lowRiskCount = 0;

            foreach ($currentRiskRows as $riskRow) {
                $riskCategory = strtolower(
                    (string) ($riskRow['Display_Risk_Category'] ??
                        ($riskRow['Display_Risk_Label'] ?? ($riskRow['Risk_Label'] ?? 'low'))),
                );
                $riskCategory = $riskCategory === 'mid' ? 'medium' : $riskCategory;

                if ($riskCategory === 'high') {
                    $highRiskCount++;
                } elseif ($riskCategory === 'medium') {
                    $mediumRiskCount++;
                } else {
                    $lowRiskCount++;
                }
            }

            $trackedStudentsCount = count($currentRiskRows);
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm summary-card h-100">
                    <div class="card-body">
                        <p class="summary-caption">Tracked Students</p>
                        <p class="summary-value text-primary mb-0">{{ $trackedStudentsCount }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm summary-card h-100">
                    <div class="card-body">
                        <p class="summary-caption">High Risk (Current)</p>
                        <p class="summary-value text-danger mb-0">{{ $highRiskCount }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm summary-card h-100">
                    <div class="card-body">
                        <p class="summary-caption">Intervention Queue</p>
                        <p class="summary-value text-warning mb-0">{{ count($interventionQueue) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm summary-card h-100">
                    <div class="card-body">
                        <p class="summary-caption">Top Recognition</p>
                        <p class="summary-value text-success mb-0">{{ count($recognitionTop5) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <p class="text-muted small mb-0">
                    Current month risk distribution:
                    <span class="fw-semibold text-danger">{{ $highRiskCount }} high</span>,
                    <span class="fw-semibold text-warning">{{ $mediumRiskCount }} medium</span>,
                    <span class="fw-semibold text-success">{{ $lowRiskCount }} low</span>.
                </p>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header honors-toggle-header section-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold">{{ $showHonors ? 'Honors & Recognition' : 'Top Performing Students' }}</h6>
                <button class="btn btn-sm btn-outline-primary honors-toggle-btn" type="button" data-bs-toggle="collapse"
                    data-bs-target="#honorsRecognitionCollapse" aria-expanded="true"
                    aria-controls="honorsRecognitionCollapse">
                    <span>Show/Hide</span>
                    <i class="fas fa-chevron-down toggle-chevron" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="collapse show" id="honorsRecognitionCollapse">
            @if ($showHonors)
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm recognition-card h-100">
                            <div class="card-body">
                                <p class="text-muted mb-1 small">With High Honors</p>
                                <p class="recognition-value text-primary mb-0">
                                    {{ (int) ($honorsSummary['with_high_honors_count'] ?? 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm recognition-card h-100">
                            <div class="card-body">
                                <p class="text-muted mb-1 small">With Honors</p>
                                <p class="recognition-value text-info mb-0">
                                    {{ (int) ($honorsSummary['with_honors_count'] ?? 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm recognition-card h-100">
                            <div class="card-body">
                                <p class="text-muted mb-1 small">Top 5 Recognition</p>
                                <p class="recognition-value text-success mb-0">{{ count($recognitionTop5) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header py-3 section-header">
                            <h6 class="m-0 fw-bold">Top Performing Students</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Ranked by engagement score, then performance percentage.
                            </p>
                            @if (!empty($recognitionTop5))
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle" id="recognitionTopTable"
                                        data-honors-enabled="{{ $showHonors ? '1' : '0' }}">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 70px;">Rank</th>
                                                <th>Student Name</th>
                                                <th class="text-center" style="width: 130px;">Engagement</th>
                                                @if ($showHonors)
                                                    <th style="width: 170px;">Honors</th>
                                                @endif
                                                <th>Strongest Subject</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($recognitionTop5 as $index => $row)
                                                @if ($showHonors)
                                                    @php
                                                        $honors = $row['HonorsClassification'] ?? 'Regular';
                                                        $honorsBadge = match ($honors) {
                                                            'With High Honors' => 'bg-primary',
                                                            'With Honors' => 'bg-info text-dark',
                                                            default => 'bg-secondary',
                                                        };
                                                    @endphp
                                                @endif
                                                <tr>
                                                    <td><span class="badge bg-dark">#{{ $index + 1 }}</span></td>
                                                    <td><strong>{{ $row['Name'] ?? '—' }}</strong></td>
                                                    <td class="text-center">
                                                        {{ number_format((float) ($row['EngagementScore'] ?? 0), 1) }}
                                                    </td>
                                                    @if ($showHonors)
                                                        <td>
                                                            <span
                                                                class="badge {{ $honorsBadge }}">{{ $honors }}</span>
                                                        </td>
                                                    @endif
                                                    <td>{{ $row['Strength'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-award fa-2x mb-2"></i>
                                    <p class="mb-0">No students available for recognition.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header py-3 section-header">
                        <h6 class="m-0 fw-bold">Early Intervention Queue</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Students flagged for immediate intervention based on declining trends.
                        </p>
                        @if (!empty($interventionQueue))
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle" id="interventionTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th style="width: 115px;">Severity</th>
                                            <th>Reason</th>
                                            <th style="width: 140px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($interventionQueue as $item)
                                            @php
                                                $severity = $item['severity'] ?? 'warning';
                                                $severityClass =
                                                    $severity === 'critical'
                                                        ? 'severity-badge-critical'
                                                        : 'severity-badge-warning';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $item['name'] ?? '—' }}</strong>
                                                    <div class="text-muted small">{{ $item['class_label'] ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $severityClass }}">{{ ucfirst($severity) }}</span>
                                                </td>
                                                <td>
                                                    <div class="small mb-1">
                                                        {{ implode('; ', $item['reason_tags'] ?? []) }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{ $item['recommended_action'] ?? '' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    @if (!empty($item['student_id']))
                                                        <div class="d-flex flex-column gap-1">
                                                            <a href="{{ route('teacher.students.show', $item['student_id']) }}"
                                                                class="btn btn-outline-primary btn-sm">Profile</a>
                                                            <a href="{{ route('teacher.attendance.records', ['student_id' => $item['student_id']]) }}"
                                                                class="btn btn-outline-secondary btn-sm">Attendance</a>
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">No action</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-shield-check fa-2x mb-2"></i>
                                <p class="mb-0">No immediate intervention required.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header py-3 section-header">
                        <h6 class="m-0 fw-bold">Declining Attendance Trends</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Warning at 5-point drop and critical at 10-point drop from previous month.
                        </p>
                        @if (!empty($decliningTrendRows))
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle" id="decliningTrendTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th class="text-center" style="width: 140px;">Previous Month</th>
                                            <th class="text-center" style="width: 140px;">Current Month</th>
                                            <th class="text-center" style="width: 120px;">Drop</th>
                                            <th class="text-center" style="width: 120px;">Severity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($decliningTrendRows as $item)
                                            @php
                                                $severity = $item['severity'] ?? 'warning';
                                                $severityClass =
                                                    $severity === 'critical'
                                                        ? 'severity-badge-critical'
                                                        : 'severity-badge-warning';
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $item['name'] ?? '—' }}</strong></td>
                                                <td>{{ $item['class_label'] ?? 'N/A' }}</td>
                                                <td class="text-center">
                                                    {{ number_format((float) ($item['att_past1'] ?? 0), 1) }}%
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format((float) ($item['att_current'] ?? 0), 1) }}%
                                                </td>
                                                <td class="text-center text-danger fw-semibold">
                                                    {{ number_format((float) ($item['attendance_drop'] ?? 0), 1) }}
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge {{ $severityClass }}">{{ ucfirst($severity) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-chart-line fa-2x mb-2"></i>
                                <p class="mb-0">No declining attendance trends detected.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 section-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">
                            Student Risk Monitoring
                        </h6>
                        <span class="badge bg-light text-dark border">
                            Risk label + probability
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            {{ $calibrationNote }}
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
                                <p class="text-muted small mb-3">
                                    Focus students marked as high risk then review trend and action
                                    queue.
                                </p>
                                @if (!empty($featureTables['table1']['data']))
                                    <!-- Risk Level Filter Tabs -->
                                    <ul class="nav nav-pills risk-filter-tabs mb-3 flex-wrap" id="table1RiskFilter"
                                        role="tablist">
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
                                                    <th class="text-center" style="width: 220px;">Risk Level</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table1']['data'] as $row)
                                                    @php
                                                        $displayRiskLabel =
                                                            $row['Display_Risk_Label'] ?? ($row['Risk_Label'] ?? 'Low');
                                                        $displayRiskLabel =
                                                            $displayRiskLabel === 'Mid' ? 'Medium' : $displayRiskLabel;
                                                        $displayRiskPct =
                                                            (float) ($row['display_prob_highrisk_pct'] ??
                                                                ($row['Prob_HighRisk_pct'] ?? 0));
                                                        $riskCategory = strtolower(
                                                            $row['Display_Risk_Category'] ?? $displayRiskLabel,
                                                        );
                                                        $riskCategory =
                                                            $riskCategory === 'mid' ? 'medium' : $riskCategory;
                                                        $riskBadge = match ($displayRiskLabel) {
                                                            'High' => 'bg-danger',
                                                            'Medium' => 'bg-warning text-dark',
                                                            default => 'bg-success',
                                                        };
                                                    @endphp
                                                    <tr data-risk-level="{{ $riskCategory }}">
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge {{ $riskBadge }}">
                                                                {{ $displayRiskLabel }}
                                                                ({{ number_format($displayRiskPct, 1) }}%)
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
                                <p class="text-muted small mb-3">
                                    Next-month forecast to support counseling and attendance follow-up before month-end.
                                </p>
                                @if (!empty($featureTables['table3']['data']))
                                    <!-- Risk Level Filter Tabs -->
                                    <ul class="nav nav-pills risk-filter-tabs mb-3 flex-wrap" id="table3RiskFilter"
                                        role="tablist">
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
                                                    <th class="text-center" style="width: 220px;">Predicted Risk</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table3']['data'] as $row)
                                                    @php
                                                        $displayRiskLabel =
                                                            $row['Display_Risk_Label'] ?? ($row['Risk_Label'] ?? 'Low');
                                                        $displayRiskLabel =
                                                            $displayRiskLabel === 'Mid' ? 'Medium' : $displayRiskLabel;
                                                        $displayRiskPct =
                                                            (float) ($row['display_prob_highrisk_pct'] ??
                                                                ($row['Prob_HighRisk_pct'] ?? 0));
                                                        $riskCategory = strtolower(
                                                            $row['Display_Risk_Category'] ?? $displayRiskLabel,
                                                        );
                                                        $riskCategory =
                                                            $riskCategory === 'mid' ? 'medium' : $riskCategory;
                                                        $riskBadge = match ($displayRiskLabel) {
                                                            'High' => 'bg-danger',
                                                            'Medium' => 'bg-warning text-dark',
                                                            default => 'bg-success',
                                                        };
                                                    @endphp
                                                    <tr data-risk-level="{{ $riskCategory }}">
                                                        <td>
                                                            <strong>{{ $row['Name'] ?? '—' }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge {{ $riskBadge }}">
                                                                {{ $displayRiskLabel }}
                                                                ({{ number_format($displayRiskPct, 1) }}%)
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
                                <p class="text-muted small mb-3">
                                    Engagement score{{ $showHonors ? ', honors classification,' : '' }} and subject-level strengths/weaknesses.
                                </p>
                                @if (!empty($featureTables['table2']['data']))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle" id="insightsTable"
                                            data-honors-enabled="{{ $showHonors ? '1' : '0' }}">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Student Name</th>
                                                    <th class="text-center" style="width: 140px;">Engagement Score</th>
                                                    @if ($showHonors)
                                                        <th style="width: 170px;">Honors</th>
                                                    @endif
                                                    <th style="width: 250px;">Strongest Subject</th>
                                                    <th style="width: 250px;">Needs Improvement</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($featureTables['table2']['data'] as $row)
                                                    @php
                                                        $eng = (float) ($row['EngagementScore'] ?? 0);
                                                        $engClass =
                                                            $eng >= 80 ? 'high' : ($eng >= 60 ? 'medium' : 'low');
                                                        $engLabel =
                                                            $eng >= 80 ? 'High' : ($eng >= 60 ? 'Moderate' : 'Low');

                                                        $strength = $row['Strength'] ?? '—';
                                                        $weakness = $row['Weakness'] ?? '—';

                                                        $isBalancedStrength =
                                                            strtolower((string) $strength) === 'balanced' ||
                                                            $strength === 'N/A';
                                                        $isBalancedWeakness =
                                                            strtolower((string) $weakness) === 'balanced' ||
                                                            $weakness === 'N/A';
                                                        $honors = $row['HonorsClassification'] ?? 'Regular';
                                                        $honorsBadge = match ($honors) {
                                                            'With High Honors' => 'bg-primary',
                                                            'With Honors' => 'bg-info text-dark',
                                                            default => 'bg-secondary',
                                                        };
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
                                                        @if ($showHonors)
                                                            <td>
                                                                <span
                                                                    class="badge {{ $honorsBadge }}">{{ $honors }}</span>
                                                            </td>
                                                        @endif
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
                @php
                    $fallbackTitle = 'Data Unavailable:';
                    $fallbackMessage =
                        'Unable to load student risk data. Please ensure the prediction service is running.';

                    if (($analyticsScopeMode ?? null) === 'none') {
                        $fallbackTitle = 'No Analytics Available:';
                        $fallbackMessage =
                            $analyticsAccessNotice ??
                            'No advisory class handled and no scheduled subjects handled for the current school year.';
                    } elseif (!empty($analyticsServiceWarning)) {
                        $fallbackMessage = $analyticsServiceWarning;
                    }
                @endphp
                <div class="border rounded p-3">
                    <strong>{{ $fallbackTitle }}</strong> {{ $fallbackMessage }}
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
            let table1, table2, table3, recognitionTable, interventionTable, decliningTrendTable;

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

            if (document.getElementById('recognitionTopTable')) {
                const recognitionHasHonorsColumn = document.getElementById('recognitionTopTable').dataset
                    .honorsEnabled === '1';
                recognitionTable = $('#recognitionTopTable').DataTable({
                    ...dataTableOptions,
                    order: [
                        [2, 'desc']
                    ],
                    columnDefs: recognitionHasHonorsColumn ? [{
                            orderable: true,
                            targets: [0, 1, 2]
                        },
                        {
                            orderable: false,
                            targets: [3, 4]
                        }
                    ] : [{
                            orderable: true,
                            targets: [0, 1, 2]
                        },
                        {
                            orderable: false,
                            targets: [3]
                        }
                    ]
                });
            }

            const honorsCollapse = document.getElementById('honorsRecognitionCollapse');
            if (honorsCollapse) {
                honorsCollapse.addEventListener('shown.bs.collapse', function() {
                    if (recognitionTable) {
                        recognitionTable.columns.adjust().draw(false);
                    }
                });
            }

            if (document.getElementById('interventionTable')) {
                interventionTable = $('#interventionTable').DataTable({
                    ...dataTableOptions,
                    order: [
                        [1, 'asc']
                    ],
                    columnDefs: [{
                            orderable: true,
                            targets: [0, 1, 2]
                        },
                        {
                            orderable: false,
                            targets: [3]
                        }
                    ]
                });
            }

            if (document.getElementById('decliningTrendTable')) {
                decliningTrendTable = $('#decliningTrendTable').DataTable({
                    ...dataTableOptions,
                    order: [
                        [4, 'desc']
                    ],
                    columnDefs: [{
                            orderable: true,
                            targets: [0, 1, 2, 3, 4, 5]
                        },
                        {
                            type: 'num',
                            targets: [2, 3, 4],
                            render: function(data, type) {
                                if (type === 'sort' || type === 'type') {
                                    const match = String(data).match(/([\d.]+)/);
                                    return match ? parseFloat(match[1]) : 0;
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
                const insightsHasHonorsColumn = document.getElementById('insightsTable').dataset
                    .honorsEnabled === '1';
                table2 = $('#insightsTable').DataTable({
                    ...dataTableOptions,
                    order: [
                        [1, 'desc']
                    ], // Sort by Engagement Score descending
                    columnDefs: insightsHasHonorsColumn ? [{
                                orderable: true,
                                targets: [0, 1]
                            },
                            {
                                orderable: false,
                                targets: [3, 4]
                            },
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
                        ] : [{
                                orderable: true,
                                targets: [0, 1]
                            },
                            {
                                orderable: false,
                                targets: [2, 3]
                            },
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
            const clearSearchButton = document.getElementById('clearSearchBtn');
            const resetFiltersButton = document.getElementById('resetFiltersBtn');
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
                if (recognitionTable) recognitionTable.search(term).draw();
                if (interventionTable) interventionTable.search(term).draw();
                if (decliningTrendTable) decliningTrendTable.search(term).draw();
            }

            function clearSearchTerm() {
                if (!searchInput) {
                    return;
                }

                searchInput.value = '';
                applySearchFilter();
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
                searchInput.addEventListener('keydown', event => {
                    if (event.key === 'Escape') {
                        clearSearchTerm();
                    }
                });
            }

            if (clearSearchButton) {
                clearSearchButton.addEventListener('click', () => {
                    clearSearchTerm();
                    if (searchInput) {
                        searchInput.focus();
                    }
                });
            }

            if (resetFiltersButton) {
                resetFiltersButton.addEventListener('click', () => {
                    clearSearchTerm();
                    const url = new URL(window.location.href);
                    url.searchParams.delete('grade_level_id');
                    url.searchParams.delete('class_id');
                    window.location.href = url.toString();
                });
            }

            // Server already rendered the correct options - no need to repopulate on load
        });
    </script>
@endpush
