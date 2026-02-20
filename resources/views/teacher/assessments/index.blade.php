@extends('base')

@section('title', 'Grade Management - ' . $class->section->name)

@push('styles')
    <style>
        .grade-table {
            font-size: 12px;
        }

        .grade-table th,
        .grade-table td {
            padding: 2px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            min-width: 26px;
        }

        .grade-input {
            width: 36px;
            height: 22px;
            text-align: center;
            border: none;
            background: transparent;
            font-size: 9px;
            border-radius: 0;
            font-weight: 600;
        }

        .grade-input:focus {
            outline: 2px solid #007bff;
            border-radius: 2px;
        }

        .grade-input::-webkit-outer-spin-button,
        .grade-input::-webkit-inner-spin-button,
        .max-score-input::-webkit-outer-spin-button,
        .max-score-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .grade-input[type='number'],
        .max-score-input[type='number'] {
            -moz-appearance: textfield;
        }

        .calculated-field {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }

        .student-name {
            text-align: left;
            font-weight: bold;
            min-width: 220px;
            background-color: #f8f9fa;
            position: sticky;
            left: 0;
            z-index: 10;
        }

        .grade-table thead .student-name {
            z-index: 12;
        }

        .gender-group-row td {
            background-color: #e9ecef !important;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: not-allowed;
        }

        .gender-group-label {
            text-align: left !important;
            position: sticky;
            left: 0;
            z-index: 11;
        }

        .student-search-toolbar {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .grade-save-status {
            min-width: 170px;
            text-align: center;
            cursor: default;
        }

        .grade-save-status.retry {
            cursor: pointer;
        }

        .grade-input-dirty {
            background-color: #fff3cd;
            box-shadow: inset 0 0 0 1px #ffc107;
        }

        .grade-input.is-invalid {
            background-color: #f8d7da;
            box-shadow: inset 0 0 0 1px #dc3545;
        }

        .grade-input.grade-input-selected {
            background-color: #cfe2ff;
            box-shadow: inset 0 0 0 1px #0d6efd;
        }

        .grade-cell-selected {
            background-color: #cfe2ff !important;
            box-shadow: inset 0 0 0 1px #0d6efd;
        }

        /* Disable card hover transform to keep fixed save button position stable */
        .grade-management-card {
            transform: none !important;
            cursor: default !important;
        }

        .grade-management-card:hover {
            transform: none !important;
        }

        .assessment-header {
            background-color: #e9ecef;
            font-weight: bold;
            font-size: 9px;
            min-width: 26px;
        }

        .quarter-header {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        .type-header {
            background-color: #6c757d;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .percentage-col {
            background-color: #fff3cd;
        }

        .grade-col {
            background-color: #d1ecf1;
        }

        .save-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .grade-management-card:fullscreen,
        .grade-management-card:-webkit-full-screen,
        .grade-management-card.grade-card-fullscreen {
            height: 100vh;
            background: #fff;
            overflow: hidden;
        }

        .grade-management-card.grade-card-fullscreen {
            position: fixed;
            inset: 0;
            z-index: 1055;
            border-radius: 0;
        }

        .grade-management-card:fullscreen .table-responsive,
        .grade-management-card:-webkit-full-screen .table-responsive,
        .grade-management-card.grade-card-fullscreen .table-responsive {
            max-height: calc(100vh - 170px);
        }

        .grade-management-card:fullscreen .save-btn,
        .grade-management-card:-webkit-full-screen .save-btn,
        .grade-management-card.grade-card-fullscreen .save-btn {
            z-index: 1060;
        }

        .highest-score-row {
            background-color: #e9ecef;
            font-weight: bold;
            font-size: 10px;
        }

        .nav-tabs-lg .nav-link {
            padding: 12px 20px;
            font-weight: 500;
            font-size: 14px;
        }

        .nav-tabs-lg .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .tab-content {
            min-height: 400px;
        }

        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }

        .max-score-input {
            font-size: 10px;
            color: #495057;
        }

        .max-score-input:focus {
            outline: 2px solid #007bff;
            background-color: white;
        }

        .oral-participation-cell {
            background-color: #d4edda !important;
            cursor: not-allowed;
        }

        .oral-participation-cell input {
            background-color: #d4edda !important;
            cursor: not-allowed;
            color: #155724;
        }

        .oral-participation-header {
            background-color: #28a745 !important;
            color: white !important;
        }

        .oral-participation-link {
            font-size: 8px;
            display: block;
            color: #155724;
        }

        .student-highlight {
            animation: studentHighlightFade 0.8s ease-in-out alternate;
            animation-iteration-count: 6;
            background-color: #fff3cd !important;
        }

        @keyframes studentHighlightFade {
            from {
                background-color: #fff3cd;
            }

            to {
                background-color: #ffe8a1;
            }
        }

        body.selecting-range {
            user-select: none;
            -webkit-user-select: none;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.list') }}">Grades</a></li>
                    <li class="breadcrumb-item">My Classes</li>
                    <li class="breadcrumb-item active" aria-current="page">Grade Management</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4>Grade Management - {{ $class->section->gradeLevel->name }} {{ $class->section->name }}</h4>
                    @if ($selectedSubject)
                        <span class="badge bg-info">{{ $selectedSubject->name }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    Subject:
                    @if ($subjects->count() > 1)
                        <select id="subjectFilter" class="form-select form-select-sm" style="width: 200px;">
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}"
                                    {{ $selectedSubject && $selectedSubject->id == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($studentsData->isEmpty())
        <div class="alert alert-info">
            <h5>No Students Found</h5>
            <p>There are no students enrolled in this class yet.</p>
        </div>
    @else
        <!-- Oral Participation Info Banner -->
        <div class="alert alert-info py-2 px-3 d-flex align-items-center mb-3 shadow-sm" role="alert">
            <i class="fas fa-comments me-2 text-primary"></i>
            <div class="flex-grow-1 small">
                <strong>Oral Participation:</strong> The "OP" column is linked to Oral Participation.
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#recitationModeModal">
                    <i class="fas fa-chalkboard-teacher me-1"></i> Recitation Mode
                </button>
                <a href="{{ route('teacher.oral-participation.index', $class) }}@if ($selectedSubject) ?subject_id={{ $selectedSubject->id }} @endif"
                    id="manageOralParticipationLink" class="btn btn-outline-primary btn-sm"
                    title="Manage Oral Participation">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </div>

        <!-- Recitation Mode Modal -->
        <div class="modal fade" id="recitationModeModal" tabindex="-1" aria-labelledby="recitationModeModalLabel"
            aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <div class="d-flex align-items-center w-100 justify-content-between">
                            <h5 class="modal-title mb-0" id="recitationModeModalLabel">
                                <i class="fas fa-chalkboard-teacher me-2"></i> Recitation Mode
                            </h5>
                            <div class="d-flex gap-3 align-items-center">
                                <div class="d-flex align-items-center">
                                    <label for="recitationQuarter" class="me-2 fw-bold text-white-50">Quarter:</label>
                                    <select id="recitationQuarter" class="form-select form-select-sm text-dark"
                                        style="width: 120px;">
                                        @for ($q = 1; $q <= 4; $q++)
                                            <option value="{{ $q }}">Quarter {{ $q }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="d-flex align-items-center">
                                    <label for="recitationMaxScore" class="me-2 fw-bold text-white-50">Activity Max:</label>
                                    <input type="number" id="recitationMaxScore"
                                        class="form-control form-control-sm text-center fw-bold" value="10"
                                        min="1" max="100" style="width: 70px;">
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="recitationTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <th class="text-center" style="width: 200px;">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($studentsData as $studentData)
                                        <tr class="student-card-wrapper"
                                            data-student-id="{{ $studentData['student']->id }}">
                                            <td class="align-middle fw-bold">
                                                {{ $studentData['student']->last_name }},
                                                {{ $studentData['student']->first_name }}
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="input-group justify-content-center"
                                                    style="max-width: 160px; margin: 0 auto;">
                                                    <button class="btn btn-outline-danger minus-btn" type="button">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number"
                                                        class="form-control text-center recitation-score-input fw-bold"
                                                        data-student-id="{{ $studentData['student']->id }}" value="0"
                                                        min="0">
                                                    <button class="btn btn-outline-success plus-btn" type="button">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetRecitation">
                                <i class="fas fa-undo me-1"></i> Reset All
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm ms-2" id="fillPerfectRecitation">
                                <i class="fas fa-star me-1"></i> All Perfect
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="applyRecitationScores">
                                <i class="fas fa-check-circle me-2"></i> Apply & Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quarter Tabs -->
        <div class="card shadow grade-management-card" id="gradeManagementCard">
            <div class="card-header p-0">
                <ul class="nav nav-tabs nav-tabs-lg" id="quarterTabs" role="tablist">
                    @for ($quarter = 1; $quarter <= 4; $quarter++)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $quarter === 1 ? 'active' : '' }}"
                                id="quarter{{ $quarter }}-tab" data-bs-toggle="tab"
                                data-bs-target="#quarter{{ $quarter }}" type="button" role="tab"
                                aria-controls="quarter{{ $quarter }}"
                                aria-selected="{{ $quarter === 1 ? 'true' : 'false' }}">
                                <i class="fas fa-calendar-alt me-2"></i>Quarter {{ $quarter }}
                            </button>
                        </li>
                    @endfor
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="finalGrade-tab" data-bs-toggle="tab" data-bs-target="#finalGrade"
                            type="button" role="tab" aria-controls="finalGrade" aria-selected="false">
                            <i class="fas fa-chart-line me-2"></i>Final Grade
                        </button>
                    </li>
                    <li class="nav-item ms-auto d-flex align-items-center px-2" role="presentation">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleFullscreen"
                            title="Enter fullscreen" aria-label="Enter fullscreen">
                            <i data-feather="maximize"></i>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-0">
                <div class="student-search-toolbar p-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <label for="studentSearchInput" class="mb-0 fw-semibold">Search:</label>
                            <input type="text" id="studentSearchInput" class="form-control form-control-sm"
                                style="width: 260px;" placeholder="Male/Female, last name, or first name">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                id="clearStudentSearch">Clear</button>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge text-bg-success grade-save-status" id="gradeSaveState"
                                title="Saved">Saved</span>
                        </div>
                    </div>
                </div>

                @php
                    $maleStudents = $studentsData->filter(
                        fn($studentData) => strtolower((string) ($studentData['student']->gender ?? '')) === 'male',
                    );
                    $femaleStudents = $studentsData->filter(
                        fn($studentData) => strtolower((string) ($studentData['student']->gender ?? '')) === 'female',
                    );
                    $unspecifiedStudents = $studentsData->filter(
                        fn($studentData) => !in_array(
                            strtolower((string) ($studentData['student']->gender ?? '')),
                            ['male', 'female'],
                            true,
                        ),
                    );
                    $studentsGroupedByGender = collect([
                        'male' => $maleStudents,
                        'female' => $femaleStudents,
                    ]);

                    if ($unspecifiedStudents->isNotEmpty()) {
                        $studentsGroupedByGender->put('unspecified', $unspecifiedStudents);
                    }

                    $genderGroupLabels = [
                        'male' => 'MALE',
                        'female' => 'FEMALE',
                        'unspecified' => 'UNSPECIFIED',
                    ];
                @endphp
                <div class="tab-content" id="quarterTabContent">
                    @for ($quarter = 1; $quarter <= 4; $quarter++)
                        <div class="tab-pane fade {{ $quarter === 1 ? 'show active' : '' }}"
                            id="quarter{{ $quarter }}" role="tabpanel"
                            aria-labelledby="quarter{{ $quarter }}-tab">

                            <div class="table-responsive">
                                <table class="table table-bordered grade-table mb-0" data-quarter="{{ $quarter }}">
                                    <thead class="sticky-top">
                                        @php
                                            $quarterAssessments = $assessments->get($quarter, collect());
                                            // Build types array with labels and percentages from controller data
                                            $types = [];
                                            foreach ($assessmentTypeLabels as $typeKey => $label) {
                                                $weight = ($assessmentTypeWeights[$typeKey] ?? 0) * 100;
                                                $types[$typeKey] = $label . ' (' . number_format($weight, 0) . '%)';
                                            }
                                            $limitedAssessments = [];
                                        @endphp

                                        <tr>
                                            <th rowspan="3" class="student-name">LEARNERS' NAMES</th>
                                            @foreach ($types as $type => $label)
                                                @php
                                                    $availableAssessments = $quarterAssessments->get($type, collect());

                                                    if ($type === 'performance_tasks') {
                                                        $opAssessment = $availableAssessments->firstWhere(
                                                            'type',
                                                            'oral_participation',
                                                        );
                                                        $otherAssessments = $availableAssessments->where(
                                                            'type',
                                                            '!=',
                                                            'oral_participation',
                                                        );

                                                        if (!$opAssessment) {
                                                            // If no OP assessment exists for this quarter, create a placeholder.
                                                            $opAssessment = (object) [
                                                                'id' => -999, // Virtual ID
                                                                'name' => 'Oral Participation',
                                                                'type' => 'oral_participation',
                                                                'max_score' => null, // View will display "--" until OP sessions are added
                                                            ];
                                                        }
                                                        // Re-assemble the collection with OP guaranteed to be first.
                                                        $finalAssessments = collect([$opAssessment])->merge(
                                                            $otherAssessments,
                                                        );
                                                    } else {
                                                        $finalAssessments = $availableAssessments;
                                                    }

                                                    $baseCount =
                                                        $fixedAssessmentCounts[$type] ?? $finalAssessments->count();
                                                    $limitedAssessments[$type] = $finalAssessments->take($baseCount);

                                                    // +3 for Total, PS, and Weighted Score columns
                                                    $colCount = $limitedAssessments[$type]->count() + 3;
                                                @endphp
                                                <th colspan="{{ $colCount }}" class="type-header">
                                                    {{ $label }}
                                                </th>
                                            @endforeach
                                            <th rowspan="2" class="grade-col">Initial Grade</th>
                                            <th rowspan="2" class="grade-col">FINAL</th>
                                        </tr>

                                        <tr>
                                            @foreach ($types as $type => $label)
                                                @php
                                                    $typeAssessments = $limitedAssessments[$type] ?? collect();
                                                    $ptCounter = 1;
                                                @endphp
                                                @foreach ($typeAssessments as $index => $assessment)
                                                    @php
                                                        // Check if assessment is oral participation by type
                                                        $isOralParticipation =
                                                            $assessment->type === 'oral_participation';
                                                    @endphp
                                                    <th class="assessment-header position-relative {{ $isOralParticipation ? 'oral-participation-header' : '' }}"
                                                        title="{{ $isOralParticipation ? 'Oral Participation (linked)' : $assessment->name }}">
                                                        @if ($isOralParticipation)
                                                            <span
                                                                title="Oral Participation - Edit in Oral Participation page">OP</span>
                                                        @else
                                                            {{ $ptCounter++ }}
                                                        @endif
                                                    </th>
                                                @endforeach
                                                <th class="assessment-header">Total</th>
                                                <th class="assessment-header">PS</th>
                                                <th class="assessment-header">WS</th>
                                            @endforeach
                                        </tr>

                                        <tr>
                                            @foreach ($types as $type => $label)
                                                @php
                                                    $typeAssessments = $limitedAssessments[$type] ?? collect();
                                                    $typeWeight = ($assessmentTypeWeights[$type] ?? 0) * 100;
                                                @endphp
                                                @foreach ($typeAssessments as $index => $assessment)
                                                    @php
                                                        if ($assessment->max_score !== null) {
                                                            $maxScoreDisplay =
                                                                fmod($assessment->max_score, 1) === 0.0
                                                                    ? number_format($assessment->max_score, 0)
                                                                    : rtrim(
                                                                        rtrim(
                                                                            number_format(
                                                                                $assessment->max_score,
                                                                                2,
                                                                                '.',
                                                                                '',
                                                                            ),
                                                                            '0',
                                                                        ),
                                                                        '.',
                                                                    );
                                                        } else {
                                                            $maxScoreDisplay = '--';
                                                        }
                                                        // Check if assessment is oral participation by type
                                                        $isOralParticipation =
                                                            $assessment->type === 'oral_participation';
                                                    @endphp
                                                    <th
                                                        class="highest-score-row {{ $isOralParticipation ? 'oral-participation-cell' : '' }}">
                                                        @if ($isOralParticipation)
                                                            <span style="font-size: 10px;"
                                                                title="Edit in Oral Participation page">{{ $maxScoreDisplay }}</span>
                                                        @else
                                                            <input type="number" class="max-score-input"
                                                                data-assessment-id="{{ $assessment->id }}"
                                                                value="{{ $assessment->max_score !== null ? $maxScoreDisplay : '' }}"
                                                                max="1000"
                                                                style="width: 30px; border: none; background: transparent; text-align: center; font-weight: bold;"
                                                                placeholder="--">
                                                        @endif
                                                    </th>
                                                @endforeach
                                                <th class="highest-score-row">Total</th>
                                                <th class="highest-score-row">100.00</th>
                                                <th class="highest-score-row">{{ number_format($typeWeight) }}%</th>
                                            @endforeach
                                            <th class="highest-score-row">100.00</th>
                                            <th class="highest-score-row">100</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $quarterTotalColumns = 3;
                                            foreach ($types as $type => $label) {
                                                $quarterTotalColumns +=
                                                    ($limitedAssessments[$type] ?? collect())->count() + 3;
                                            }
                                        @endphp
                                        @foreach ($studentsGroupedByGender as $genderKey => $groupedStudents)
                                            <tr class="gender-group-row" data-gender-group="{{ $genderKey }}"
                                                aria-readonly="true">
                                                <td class="gender-group-label" colspan="{{ $quarterTotalColumns }}">
                                                    {{ $genderGroupLabels[$genderKey] ?? strtoupper($genderKey) }}
                                                </td>
                                            </tr>
                                            @foreach ($groupedStudents as $studentData)
                                                @php
                                                    $studentGender = strtolower(
                                                        (string) ($studentData['student']->gender ?? 'unspecified'),
                                                    );
                                                    $searchGender =
                                                        $studentGender !== '' ? $studentGender : 'unspecified';
                                                @endphp
                                                <tr data-student-id="{{ $studentData['student']->id }}"
                                                    data-gender-group="{{ $genderKey }}"
                                                    data-student-search="{{ strtolower(trim($searchGender . ' ' . $studentData['student']->last_name . ' ' . $studentData['student']->first_name)) }}">
                                                    <td class="student-name">{{ $studentData['student']->last_name }},
                                                        {{ $studentData['student']->first_name }}</td>

                                                    @php
                                                        $quarterData = $studentData['quarters'][$quarter] ?? [];
                                                    @endphp

                                                    @foreach ($types as $type => $label)
                                                        @php
                                                            $typeAssessments = $limitedAssessments[$type] ?? collect();
                                                            $studentTypeData = $quarterData[$type] ?? [];
                                                        @endphp

                                                        {{-- Individual Assessment Columns --}}
                                                        @foreach ($typeAssessments as $index => $assessment)
                                                            @php
                                                                $studentScore = collect($studentTypeData)->first(
                                                                    function ($item) use ($assessment) {
                                                                        return isset($item['assessment']) &&
                                                                            $item['assessment']->id === $assessment->id;
                                                                    },
                                                                );
                                                                $scoreValue = $studentScore['score'] ?? '';
                                                                if (
                                                                    $scoreValue !== '' &&
                                                                    fmod((float) $scoreValue, 1) === 0.0
                                                                ) {
                                                                    $scoreValue = number_format($scoreValue, 0);
                                                                }
                                                                // Check if assessment is oral participation by type
                                                                $isOralParticipation =
                                                                    $assessment->type === 'oral_participation';
                                                            @endphp
                                                            <td
                                                                class="{{ $isOralParticipation ? 'oral-participation-cell' : '' }}">
                                                                <input type="number" min="0"
                                                                    class="grade-input {{ str_replace('_', '-', $type) }}-score"
                                                                    data-quarter="{{ $quarter }}"
                                                                    data-type="{{ $type }}"
                                                                    data-index="{{ $index }}"
                                                                    data-assessment-id="{{ $assessment->id }}"
                                                                    data-max-score="{{ $assessment->max_score }}"
                                                                    data-is-oral-participation="{{ $isOralParticipation ? '1' : '0' }}"
                                                                    value="{{ $scoreValue }}"
                                                                    {{ $isOralParticipation ? 'readonly' : '' }}
                                                                    title="{{ $isOralParticipation ? 'Oral Participation - Edit in Oral Participation page' : '' }}">
                                                            </td>
                                                        @endforeach

                                                        {{-- Total Column --}}
                                                        <td class="calculated-field {{ str_replace('_', '-', $type) }}-total"
                                                            data-quarter="{{ $quarter }}">0</td>

                                                        {{-- PS Column --}}
                                                        <td class="calculated-field {{ str_replace('_', '-', $type) }}-ps percentage-col"
                                                            data-quarter="{{ $quarter }}">0.00</td>

                                                        {{-- Weighted Score Column --}}
                                                        <td class="calculated-field {{ str_replace('_', '-', $type) }}-weighted grade-col"
                                                            data-quarter="{{ $quarter }}">0.00</td>
                                                    @endforeach

                                                    {{-- Quarter Grade --}}
                                                    <td class="calculated-field initial-grade grade-col"
                                                        data-quarter="{{ $quarter }}">--</td>
                                                    <td class="calculated-field quarter-grade grade-col"
                                                        data-quarter="{{ $quarter }}" data-has-grade="0">--</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endfor
                    <div class="tab-pane fade" id="finalGrade" role="tabpanel" aria-labelledby="finalGrade-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered grade-table mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th class="student-name">LEARNERS' NAMES</th>
                                        @for ($quarter = 1; $quarter <= 4; $quarter++)
                                            <th class="grade-col">Quarter {{ $quarter }}</th>
                                        @endfor
                                        <th class="grade-col">Final Grade</th>
                                        <th class="grade-col">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $finalTotalColumns = 7;
                                    @endphp
                                    @foreach ($studentsGroupedByGender as $genderKey => $groupedStudents)
                                        <tr class="gender-group-row" data-gender-group="{{ $genderKey }}"
                                            aria-readonly="true">
                                            <td class="gender-group-label" colspan="{{ $finalTotalColumns }}">
                                                {{ $genderGroupLabels[$genderKey] ?? strtoupper($genderKey) }}
                                            </td>
                                        </tr>
                                        @foreach ($groupedStudents as $studentData)
                                            @php
                                                $studentGender = strtolower(
                                                    (string) ($studentData['student']->gender ?? 'unspecified'),
                                                );
                                                $searchGender = $studentGender !== '' ? $studentGender : 'unspecified';
                                            @endphp
                                            <tr data-student-id="{{ $studentData['student']->id }}"
                                                data-gender-group="{{ $genderKey }}"
                                                data-student-search="{{ strtolower(trim($searchGender . ' ' . $studentData['student']->last_name . ' ' . $studentData['student']->first_name)) }}">
                                                <td class="student-name">{{ $studentData['student']->last_name }},
                                                    {{ $studentData['student']->first_name }}</td>
                                                @for ($quarter = 1; $quarter <= 4; $quarter++)
                                                    <td class="calculated-field final-quarter-grade grade-col"
                                                        data-quarter="{{ $quarter }}">--</td>
                                                @endfor
                                                <td class="calculated-field final-grade grade-col">--</td>
                                                <td class="calculated-field final-remarks grade-col">--</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-success btn-lg save-btn" id="saveAllGrades">
                <i class="fas fa-save"></i> Save Grades
            </button>
        </div>
    @endif

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            const highlightStudentId = @json($highlightStudentId);
            const classId = {{ $class->id }};
            const selectedSubjectId = {{ $selectedSubject?->id ?? 'null' }};
            const saveGradesEndpoint = "{{ route('teacher.assessments.saveGrades', $class) }}";
            const autoSaveDelayMs = 30000;
            const dirtyGrades = new Map();
            const invalidCells = new Map();
            let autoSaveTimeout = null;
            let isSaving = false;
            let pendingManualSave = false;
            // Convert PHP weights (with underscores) to JS format (with dashes)
            const typeWeights = {
                @foreach ($assessmentTypeWeights as $type => $weight)
                    '{{ str_replace('_', '-', $type) }}': {{ $weight }},
                @endforeach
            };
            const $gradeManagementCard = $('#gradeManagementCard');
            const gradeManagementCard = $gradeManagementCard.get(0);
            const $toggleFullscreenButton = $('#toggleFullscreen');
            const $saveButton = $('#saveAllGrades');
            const $saveStateBadge = $('#gradeSaveState');
            let isFallbackFullscreen = false;

            initializeAssessmentInputsState();

            function getGradeKey(studentId, assessmentId) {
                return `${studentId}:${assessmentId}`;
            }

            function getGradeInputByCoordinates(studentId, assessmentId) {
                return $(
                    `tr[data-student-id="${studentId}"] .grade-input[data-assessment-id="${assessmentId}"]`
                ).first();
            }

            function normalizeScoreForPayload(value) {
                const raw = String(value ?? '').trim();
                if (raw === '') {
                    return null;
                }

                const numericValue = parseFloat(raw);

                return Number.isNaN(numericValue) ? null : numericValue;
            }

            function setSaveButtonState(isBusy) {
                if (isBusy) {
                    $saveButton.prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

                    return;
                }

                $saveButton.prop('disabled', false).html('<i class="fas fa-save"></i> Save Grades');
            }

            function updateSaveState(state, message = null) {
                $saveStateBadge.removeClass('text-bg-success text-bg-warning text-bg-primary text-bg-danger retry');

                if (state === 'dirty') {
                    $saveStateBadge.addClass('text-bg-warning');
                    $saveStateBadge.text(message ?? 'Unsaved changes');
                    $saveStateBadge.attr('title', message ?? 'Unsaved changes');

                    return;
                }

                if (state === 'saving') {
                    $saveStateBadge.addClass('text-bg-primary');
                    $saveStateBadge.text(message ?? 'Saving...');
                    $saveStateBadge.attr('title', message ?? 'Saving...');

                    return;
                }

                if (state === 'invalid') {
                    $saveStateBadge.addClass('text-bg-warning');
                    $saveStateBadge.text(message ?? 'Fix invalid scores');
                    $saveStateBadge.attr('title', message ?? 'Fix invalid scores');

                    return;
                }

                if (state === 'error') {
                    $saveStateBadge.addClass('text-bg-danger retry');
                    $saveStateBadge.text(message ?? 'Save failed - Retry');
                    $saveStateBadge.attr('title', message ?? 'Save failed - Retry');

                    return;
                }

                $saveStateBadge.addClass('text-bg-success');
                $saveStateBadge.text(message ?? 'Saved');
                $saveStateBadge.attr('title', message ?? 'Saved');
            }

            function invalidCountMessage(count) {
                const suffix = count === 1 ? '' : 's';

                return `Fix ${count} invalid score${suffix}`;
            }

            function syncSaveStateBadge() {
                if (invalidCells.size > 0) {
                    updateSaveState('invalid', invalidCountMessage(invalidCells.size));

                    return;
                }

                if (dirtyGrades.size > 0) {
                    updateSaveState('dirty');

                    return;
                }

                updateSaveState('saved', 'Saved');
            }

            function clearInputInvalidState($input) {
                $input.removeClass('is-invalid');
                $input.removeAttr('title');
            }

            function setInputInvalidState($input, reason) {
                $input.addClass('is-invalid');
                $input.attr('title', reason);
            }

            function getInputContext($input) {
                const assessmentId = parseInt($input.data('assessment-id'), 10);
                const studentId = parseInt($input.closest('tr').data('student-id'), 10);
                const isOralParticipation = String($input.data('is-oral-participation')) === '1';
                const trackable = Boolean(
                    assessmentId &&
                    assessmentId !== -999 &&
                    !isOralParticipation &&
                    studentId &&
                    !$input.prop('disabled')
                );

                return {
                    assessmentId,
                    studentId,
                    trackable,
                };
            }

            function validateGradeInput($input) {
                const context = getInputContext($input);
                if (!context.trackable) {
                    return {
                        ...context,
                        isValid: true,
                        normalizedScore: null,
                        reason: null,
                    };
                }

                const rawValue = String($input.val() ?? '').trim();
                if (rawValue === '') {
                    return {
                        ...context,
                        isValid: true,
                        normalizedScore: null,
                        reason: null,
                    };
                }

                const numericValue = parseFloat(rawValue);
                if (Number.isNaN(numericValue)) {
                    return {
                        ...context,
                        isValid: false,
                        normalizedScore: null,
                        reason: 'Enter a valid numeric score.',
                    };
                }

                if (numericValue < 0) {
                    return {
                        ...context,
                        isValid: false,
                        normalizedScore: null,
                        reason: 'Score cannot be negative.',
                    };
                }

                const maxScoreRaw = $input.data('max-score');
                const maxScoreText = maxScoreRaw === undefined || maxScoreRaw === null ? '' : String(maxScoreRaw)
                    .trim();
                if (maxScoreText !== '') {
                    const maxScore = parseFloat(maxScoreText);
                    if (!Number.isNaN(maxScore) && numericValue > maxScore) {
                        return {
                            ...context,
                            isValid: false,
                            normalizedScore: null,
                            reason: `Score cannot exceed ${maxScore}.`,
                        };
                    }
                }

                return {
                    ...context,
                    isValid: true,
                    normalizedScore: numericValue,
                    reason: null,
                };
            }

            function applySavedStateToInputs(gradePayload) {
                gradePayload.forEach((gradeEntry) => {
                    const key = getGradeKey(gradeEntry.student_id, gradeEntry.assessment_id);
                    const currentDraft = dirtyGrades.get(key);
                    const sentScore = normalizeScoreForPayload(gradeEntry.score);

                    if (currentDraft && currentDraft.score === sentScore) {
                        dirtyGrades.delete(key);
                        invalidCells.delete(key);
                        const $input = getGradeInputByCoordinates(gradeEntry.student_id, gradeEntry
                            .assessment_id);
                        $input.removeClass('grade-input-dirty');
                        clearInputInvalidState($input);
                    }
                });
            }

            function applyRejectedStateToInputs(rejectedCells) {
                rejectedCells.forEach((rejectedCell) => {
                    const studentId = parseInt(rejectedCell.student_id, 10);
                    const assessmentId = parseInt(rejectedCell.assessment_id, 10);
                    if (!studentId || !assessmentId) {
                        return;
                    }

                    const key = getGradeKey(studentId, assessmentId);
                    const reason = rejectedCell.reason ?? 'Invalid score.';

                    dirtyGrades.delete(key);
                    invalidCells.set(key, {
                        student_id: studentId,
                        assessment_id: assessmentId,
                        reason,
                    });

                    const $input = getGradeInputByCoordinates(studentId, assessmentId);
                    $input.removeClass('grade-input-dirty');
                    setInputInvalidState($input, reason);
                });
            }

            function scheduleAutoSave() {
                if (autoSaveTimeout) {
                    clearTimeout(autoSaveTimeout);
                }

                if (dirtyGrades.size === 0) {
                    autoSaveTimeout = null;

                    return;
                }

                autoSaveTimeout = setTimeout(() => {
                    if (dirtyGrades.size > 0) {
                        void saveDirtyGrades(false);
                    }
                }, autoSaveDelayMs);
            }

            function collectDirtyGradePayload() {
                const payload = [];
                dirtyGrades.forEach((gradePayload, key) => {
                    if (!invalidCells.has(key)) {
                        payload.push(gradePayload);
                    }
                });

                return payload;
            }

            function markInputDirty($input) {
                const validation = validateGradeInput($input);
                if (!validation.trackable) {
                    return;
                }

                const key = getGradeKey(validation.studentId, validation.assessmentId);

                if (!validation.isValid) {
                    dirtyGrades.delete(key);
                    invalidCells.set(key, {
                        student_id: validation.studentId,
                        assessment_id: validation.assessmentId,
                        reason: validation.reason,
                    });
                    $input.removeClass('grade-input-dirty');
                    setInputInvalidState($input, validation.reason);
                    syncSaveStateBadge();
                    scheduleAutoSave();
                    return;
                }

                invalidCells.delete(key);
                clearInputInvalidState($input);

                const payload = {
                    student_id: validation.studentId,
                    assessment_id: validation.assessmentId,
                    score: validation.normalizedScore,
                };

                dirtyGrades.set(key, payload);
                $input.addClass('grade-input-dirty');
                syncSaveStateBadge();
                scheduleAutoSave();
            }

            function refreshInputValidationState($input) {
                const validation = validateGradeInput($input);
                if (!validation.trackable) {
                    return;
                }

                const key = getGradeKey(validation.studentId, validation.assessmentId);

                if (!validation.isValid) {
                    dirtyGrades.delete(key);
                    invalidCells.set(key, {
                        student_id: validation.studentId,
                        assessment_id: validation.assessmentId,
                        reason: validation.reason,
                    });
                    $input.removeClass('grade-input-dirty');
                    setInputInvalidState($input, validation.reason);

                    return;
                }

                invalidCells.delete(key);
                clearInputInvalidState($input);
            }

            async function saveDirtyGrades(isManualSave) {
                const gradesPayload = collectDirtyGradePayload();

                if (gradesPayload.length === 0) {
                    if (invalidCells.size > 0) {
                        syncSaveStateBadge();
                        if (isManualSave) {
                            alert(`${invalidCountMessage(invalidCells.size)} before saving.`);
                        }

                        return;
                    }

                    if (isManualSave) {
                        syncSaveStateBadge();
                        alert('No unsaved changes to save.');
                    }

                    return;
                }

                if (isSaving) {
                    if (isManualSave) {
                        pendingManualSave = true;
                    }

                    return;
                }

                isSaving = true;
                if (isManualSave) {
                    pendingManualSave = false;
                }
                if (autoSaveTimeout) {
                    clearTimeout(autoSaveTimeout);
                    autoSaveTimeout = null;
                }

                setSaveButtonState(true);
                updateSaveState('saving');
                let requestFailed = false;

                try {
                    const response = await $.ajax({
                        url: saveGradesEndpoint,
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            _token: '{{ csrf_token() }}',
                            grades: gradesPayload,
                        }),
                    });

                    const rejectedCells = Array.isArray(response?.rejected_cells) ? response.rejected_cells :
                [];
                    if (rejectedCells.length > 0) {
                        applyRejectedStateToInputs(rejectedCells);
                    }
                    applySavedStateToInputs(gradesPayload);
                    syncSaveStateBadge();

                    if (isManualSave) {
                        const responseMessage = response?.message || 'Grades saved successfully.';
                        if (rejectedCells.length > 0) {
                            const suffix = rejectedCells.length === 1 ? '' : 's';
                            alert(
                                `${responseMessage} ${rejectedCells.length} invalid score${suffix} still need correction.`
                            );
                        } else {
                            alert(responseMessage);
                        }
                    }
                } catch (xhr) {
                    requestFailed = true;
                    let errorMessage = xhr?.responseJSON?.message || 'Validation failed.';

                    if (xhr?.status === 419) {
                        errorMessage = 'Session expired. Please refresh this page.';
                    } else if (xhr?.status === 413) {
                        errorMessage = 'Request is too large. Save in smaller changes.';
                    }

                    updateSaveState('error', 'Save failed - Retry');

                    if (isManualSave) {
                        alert(`Error saving grades: ${errorMessage}`);
                    }
                } finally {
                    isSaving = false;
                    setSaveButtonState(false);

                    if (pendingManualSave) {
                        pendingManualSave = false;
                        if (collectDirtyGradePayload().length > 0) {
                            void saveDirtyGrades(true);
                            return;
                        }
                    }

                    if (!requestFailed && dirtyGrades.size > 0) {
                        scheduleAutoSave();
                    }
                }
            }

            function getCurrentFullscreenElement() {
                return document.fullscreenElement || document.webkitFullscreenElement ||
                    document.msFullscreenElement || null;
            }

            function isNativeFullscreenActive() {
                return getCurrentFullscreenElement() === gradeManagementCard;
            }

            function updateFullscreenToggleIcon() {
                const isFullscreen = isNativeFullscreenActive() || isFallbackFullscreen;
                const iconName = isFullscreen ? 'minimize' : 'maximize';
                const buttonLabel = isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen';

                $toggleFullscreenButton.html(`<i data-feather="${iconName}"></i>`);
                $toggleFullscreenButton.attr('title', buttonLabel);
                $toggleFullscreenButton.attr('aria-label', buttonLabel);

                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }

            function adjustVisibleDataTables() {
                if (typeof $.fn.dataTable === 'undefined') {
                    return;
                }

                const visibleTables = $.fn.dataTable.tables({
                    visible: true,
                    api: true
                });

                if (visibleTables && typeof visibleTables.columns === 'function') {
                    visibleTables.columns.adjust();
                }
            }

            function enterFallbackFullscreen() {
                isFallbackFullscreen = true;
                $gradeManagementCard.addClass('grade-card-fullscreen');
                $('body').css('overflow', 'hidden');
                updateFullscreenToggleIcon();
                adjustVisibleDataTables();
            }

            function exitFallbackFullscreen() {
                isFallbackFullscreen = false;
                $gradeManagementCard.removeClass('grade-card-fullscreen');
                $('body').css('overflow', '');
                updateFullscreenToggleIcon();
                adjustVisibleDataTables();
            }

            function requestNativeFullscreen() {
                if (!gradeManagementCard) {
                    return Promise.reject(new Error('Grade card not found.'));
                }

                if (gradeManagementCard.requestFullscreen) {
                    return gradeManagementCard.requestFullscreen();
                }

                if (gradeManagementCard.webkitRequestFullscreen) {
                    gradeManagementCard.webkitRequestFullscreen();
                    return Promise.resolve();
                }

                if (gradeManagementCard.msRequestFullscreen) {
                    gradeManagementCard.msRequestFullscreen();
                    return Promise.resolve();
                }

                return Promise.reject(new Error('Fullscreen API not supported.'));
            }

            function exitNativeFullscreen() {
                if (document.exitFullscreen) {
                    return document.exitFullscreen();
                }

                if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                    return Promise.resolve();
                }

                if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                    return Promise.resolve();
                }

                return Promise.reject(new Error('Fullscreen API exit not supported.'));
            }

            function supportsNativeFullscreen() {
                if (!gradeManagementCard) {
                    return false;
                }

                const canRequest = gradeManagementCard.requestFullscreen || gradeManagementCard
                    .webkitRequestFullscreen ||
                    gradeManagementCard.msRequestFullscreen;
                const canExit = document.exitFullscreen || document.webkitExitFullscreen || document
                    .msExitFullscreen;

                return Boolean(canRequest && canExit);
            }

            $toggleFullscreenButton.on('click', function() {
                if (isFallbackFullscreen) {
                    exitFallbackFullscreen();
                    return;
                }

                if (supportsNativeFullscreen()) {
                    if (isNativeFullscreenActive()) {
                        exitNativeFullscreen().catch(() => {});
                    } else {
                        requestNativeFullscreen().catch(() => {
                            enterFallbackFullscreen();
                        });
                    }

                    return;
                }

                enterFallbackFullscreen();
            });

            $(document).on('fullscreenchange webkitfullscreenchange MSFullscreenChange', function() {
                if (!isNativeFullscreenActive() && !isFallbackFullscreen) {
                    $('body').css('overflow', '');
                }

                updateFullscreenToggleIcon();
                adjustVisibleDataTables();
            });

            $(document).on('keydown', function(event) {
                if (event.key === 'Escape' && isFallbackFullscreen && !isNativeFullscreenActive()) {
                    event.preventDefault();
                    exitFallbackFullscreen();
                }
            });

            updateFullscreenToggleIcon();

            function updateManageOralParticipationLink(quarter) {
                const baseUrl = "{{ route('teacher.oral-participation.index', $class) }}";
                const params = new URLSearchParams();

                if (selectedSubjectId) {
                    params.append('subject_id', selectedSubjectId);
                }

                const quarterNum = parseInt(quarter);
                if (!isNaN(quarterNum) && quarterNum >= 1 && quarterNum <= 4) {
                    params.append('quarter', quarterNum);
                }

                const queryString = params.toString();
                const newHref = queryString ? `${baseUrl}?${queryString}` : baseUrl;

                $('#manageOralParticipationLink').attr('href', newHref);
            }

            function getActiveQuarter() {
                const activeTabLink = $('#quarterTabs .nav-link.active');
                const activeTabId = activeTabLink.attr('id');
                if (activeTabId) {
                    const match = activeTabId.match(/quarter(\d+)/);
                    if (match && match[1]) {
                        return parseInt(match[1]);
                    }
                }
                return 1; // Default to 1
            }

            function normalizeSearchValue(value) {
                return String(value ?? '').trim().toLowerCase();
            }

            function applyStudentSearchFilter() {
                const searchTerm = normalizeSearchValue($('#studentSearchInput').val());

                $('#quarterTabContent .tab-pane').each(function() {
                    const $tabPane = $(this);
                    const $studentRows = $tabPane.find('tbody tr[data-student-id]');

                    $studentRows.each(function() {
                        const $row = $(this);
                        const searchText = normalizeSearchValue($row.data('student-search'));
                        const isMatch = searchTerm === '' || searchText.includes(searchTerm);
                        $row.toggle(isMatch);
                    });

                    $tabPane.find('tbody tr.gender-group-row').each(function() {
                        const $groupRow = $(this);
                        const genderGroup = normalizeSearchValue($groupRow.data('gender-group'));
                        const visibleStudentsInGroup = $tabPane.find(
                            `tbody tr[data-student-id][data-gender-group="${genderGroup}"]:visible`
                        ).length;
                        const shouldShowGroup = searchTerm === '' || visibleStudentsInGroup > 0;
                        $groupRow.toggle(shouldShowGroup);
                    });
                });
            }

            // Set initial link on page load
            updateManageOralParticipationLink(getActiveQuarter());
            applyStudentSearchFilter();

            $('#studentSearchInput').on('input', function() {
                applyStudentSearchFilter();
            });

            $('#clearStudentSearch').on('click', function() {
                $('#studentSearchInput').val('');
                applyStudentSearchFilter();
                $('#studentSearchInput').trigger('focus');
            });

            if (highlightStudentId) {
                const $rows = $(`tr[data-student-id="${highlightStudentId}"]`);
                if ($rows.length) {
                    const $firstRow = $rows.first();
                    const $nameCells = $rows.find('.student-name');
                    $nameCells.addClass('student-highlight');
                    const $scrollContainer = $firstRow.closest('.table-responsive');
                    if ($scrollContainer.length) {
                        const targetScroll = $firstRow.position().top + $scrollContainer.scrollTop() - 60;
                        $scrollContainer.animate({
                            scrollTop: targetScroll
                        }, 600);
                    } else {
                        $('html, body').animate({
                            scrollTop: $firstRow.offset().top - 120
                        }, 600);
                    }
                    setTimeout(() => $nameCells.removeClass('student-highlight'), 6000);
                }
            }

            // Subject filter functionality
            $('#subjectFilter').change(function() {
                const subjectId = $(this).val();
                window.location.href = `/teacher/classes/assessments/${classId}?subject_id=${subjectId}`;
            });

            // Calculate grades when input changes
            $(document).on('input change', '.grade-input', function(event) {
                const $input = $(this);

                if (event.originalEvent) {
                    markInputDirty($input);
                } else {
                    refreshInputValidationState($input);
                    syncSaveStateBadge();
                }

                const $row = $(this).closest('tr');
                const quarter = $(this).closest('.tab-pane').attr('id').replace('quarter', '');
                calculateQuarterGrades($row, parseInt(quarter));
            });

            // Handle max score updates
            $(document).on('change', '.max-score-input', function() {
                const $input = $(this);
                const assessmentId = $input.data('assessment-id');
                const maxScoreRaw = ($input.val() ?? '').trim();
                const isBlank = maxScoreRaw === '';
                let maxScore = null;

                if (!isBlank) {
                    maxScore = parseFloat(maxScoreRaw);

                    if (Number.isNaN(maxScore) || maxScore <= 0) {
                        alert('Please enter a valid maximum score greater than zero.');
                        return;
                    }
                }

                $.ajax({
                    url: '{{ route('teacher.assessments.updateMaxScore', $class) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        assessment_id: assessmentId,
                        max_score: maxScore
                    },
                    success: function(response) {
                        const serverMaxScore = response?.max_score ?? maxScore;
                        const formattedMax = formatScoreValue(serverMaxScore);
                        $input.val(formattedMax);
                        updateAssessmentInputsState(assessmentId, serverMaxScore);
                        // Recalculate grades
                        $('.grade-input').trigger('change');
                    },
                    error: function(xhr) {
                        let msg = 'Error updating max score: ' + (xhr.responseJSON?.message ||
                            'Unknown error');
                        if (xhr.responseJSON?.errors) {
                            msg += '\n' + Object.values(xhr.responseJSON.errors).map(e => e
                                .join('\n')).join('\n');
                        }
                        alert(msg);
                    }
                });
            });


            // Initialize calculations when tab is shown
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const targetId = $(e.target).attr('aria-controls');
                if (targetId && targetId.startsWith('quarter')) {
                    const quarter = targetId.replace('quarter', '');
                    initializeQuarterCalculations(parseInt(quarter));
                    updateManageOralParticipationLink(quarter);
                }
            });

            // Initialize all quarters on load for accurate summaries
            for (let quarter = 1; quarter <= 4; quarter++) {
                initializeQuarterCalculations(quarter);
            }
            refreshFinalGradesTable();

            function initializeQuarterCalculations(quarter) {
                $(`#quarter${quarter} tbody tr[data-student-id]`).each(function() {
                    calculateQuarterGrades($(this), quarter);
                });
            }

            function initializeAssessmentInputsState() {
                $('.max-score-input').each(function() {
                    const $maxInput = $(this);
                    const rawValue = ($maxInput.val() ?? '').trim();
                    const parsedValue = rawValue === '' ? null : parseFloat(rawValue);
                    updateAssessmentInputsState($maxInput.data('assessment-id'), parsedValue);
                });
            }

            function updateAssessmentInputsState(assessmentId, maxScoreValue) {
                const $relatedGrades = $(`.grade-input[data-assessment-id="${assessmentId}"]`);
                const numericMax = typeof maxScoreValue === 'number' ? maxScoreValue : parseFloat(maxScoreValue);
                const hasValidMax = !Number.isNaN(numericMax) && numericMax > 0;

                if (!hasValidMax) {
                    $relatedGrades.each(function() {
                        const $gradeInput = $(this);
                        const context = getInputContext($gradeInput);
                        if (context.trackable) {
                            const key = getGradeKey(context.studentId, context.assessmentId);
                            dirtyGrades.delete(key);
                            invalidCells.delete(key);
                        }

                        $gradeInput.removeClass('grade-input-dirty');
                        clearInputInvalidState($gradeInput);
                    });

                    $relatedGrades.val('');
                    $relatedGrades.prop('disabled', true);
                    $relatedGrades.removeAttr('max');
                    $relatedGrades.attr('data-max-score', '');
                    syncSaveStateBadge();

                    return;
                }

                $relatedGrades.prop('disabled', false);
                $relatedGrades.attr('max', numericMax);
                $relatedGrades.attr('data-max-score', numericMax);
                $relatedGrades.each(function() {
                    refreshInputValidationState($(this));
                });
                syncSaveStateBadge();
            }

            function formatScoreValue(value) {
                if (value === null || value === undefined || value === '') {
                    return '';
                }

                const numericValue = typeof value === 'number' ? value : parseFloat(value);

                if (Number.isNaN(numericValue)) {
                    return '';
                }

                return Number.isInteger(numericValue) ? numericValue.toString() :
                    numericValue.toFixed(2).replace(/\.0+$/, '').replace(/0+$/, '');
            }

            /**
             * DepEd Transmutation Table
             * Converts raw/initial grades (0-100%) to transmuted grades (60-100)
             * Based on DepEd Order No. 8, s. 2015
             */
            function transmuteGrade(rawGrade) {
                if (rawGrade === null || rawGrade === undefined || Number.isNaN(rawGrade)) {
                    return null;
                }

                // Clamp between 0 and 100
                rawGrade = Math.max(0, Math.min(100, rawGrade));

                // DepEd Transmutation Table thresholds
                const transmutationTable = [
                    [100.00, 100],
                    [98.40, 99],
                    [96.80, 98],
                    [95.20, 97],
                    [93.60, 96],
                    [92.00, 95],
                    [90.40, 94],
                    [88.80, 93],
                    [87.20, 92],
                    [85.60, 91],
                    [84.00, 90],
                    [82.40, 89],
                    [80.80, 88],
                    [79.20, 87],
                    [77.60, 86],
                    [76.00, 85],
                    [74.40, 84],
                    [72.80, 83],
                    [71.20, 82],
                    [69.60, 81],
                    [68.00, 80],
                    [66.40, 79],
                    [64.80, 78],
                    [63.20, 77],
                    [61.60, 76],
                    [60.00, 75],
                    [56.00, 74],
                    [52.00, 73],
                    [48.00, 72],
                    [44.00, 71],
                    [40.00, 70],
                    [36.00, 69],
                    [32.00, 68],
                    [28.00, 67],
                    [24.00, 66],
                    [20.00, 65],
                    [16.00, 64],
                    [12.00, 63],
                    [8.00, 62],
                    [4.00, 61],
                    [0.00, 60]
                ];

                for (const [threshold, transmuted] of transmutationTable) {
                    if (rawGrade >= threshold) {
                        return transmuted;
                    }
                }

                return 60; // Minimum transmuted grade
            }

            function calculateQuarterGrades($row, quarter) {
                // Dynamically get types from PHP data
                const types = [
                    @foreach (array_keys($assessmentTypeWeights) as $type)
                        '{{ str_replace('_', '-', $type) }}',
                    @endforeach
                ];
                let quarterGrade = 0;
                let quarterHasScores = false;

                types.forEach(type => {
                    let total = 0;
                    let maxTotal = 0;

                    $row.find(`.${type}-score[data-quarter="${quarter}"]`).each(function() {
                        if ($(this).hasClass('is-invalid')) {
                            return;
                        }

                        const rawValue = $(this).val();
                        const hasValue = typeof rawValue === 'string' ? rawValue.trim() !== '' :
                            rawValue !== '';
                        if (!hasValue) {
                            return;
                        }

                        const value = parseFloat(rawValue);
                        const maxScore = parseFloat($(this).data('max-score'));

                        if (Number.isNaN(value) || Number.isNaN(maxScore) || maxScore <= 0) {
                            return;
                        }

                        total += value;
                        maxTotal += maxScore;
                        const displayValue = Number.isInteger(value) ? value.toString() : value
                            .toFixed(2).replace(/\.0+$/, '').replace(/0+$/, '');
                        $(this).val(displayValue);
                    });

                    const ps = maxTotal > 0 ? (total / maxTotal) * 100 : 0;
                    $row.find(`.${type}-total[data-quarter="${quarter}"]`).text(total.toFixed(0));
                    $row.find(`.${type}-ps[data-quarter="${quarter}"]`).text(ps.toFixed(2));
                    const weighted = ps * (typeWeights[type] || 0);
                    $row.find(`.${type}-weighted[data-quarter="${quarter}"]`).text(weighted.toFixed(2));
                    quarterGrade += weighted;

                    if (maxTotal > 0) {
                        quarterHasScores = true;
                    }
                });

                // Round the initial grade to 2 decimal places to avoid floating point issues near transmutation thresholds.
                const initialGrade = quarterHasScores ? parseFloat(quarterGrade.toFixed(2)) : null;

                const initialGradeText = initialGrade !== null ? initialGrade.toFixed(2) : '--';
                const transmutedQuarterGrade = initialGrade !== null ? transmuteGrade(initialGrade) : null;
                const transmutedGradeText = transmutedQuarterGrade !== null ? transmutedQuarterGrade : '--';

                $row.find(`.initial-grade[data-quarter="${quarter}"]`).text(initialGradeText);

                // Quarter grade displays the transmuted grade per DepEd guidelines
                $row.find(`.quarter-grade[data-quarter="${quarter}"]`)
                    .text(transmutedGradeText)
                    .attr('data-has-grade', initialGrade !== null ? '1' : '0')
                    .attr('data-raw-grade', initialGradeText)
                    .data('has-grade', initialGrade !== null ? 1 : 0);

                const studentId = $row.data('student-id');
                updateFinalGradeRow(studentId);
            }

            function updateFinalGradeRow(studentId) {
                const $finalRow = $(`#finalGrade tbody tr[data-student-id="${studentId}"]`);
                if (!$finalRow.length) {
                    return;
                }

                let total = 0;
                let countedQuarters = 0;

                for (let quarter = 1; quarter <= 4; quarter++) {
                    const $quarterRow = $(`#quarter${quarter} tbody tr[data-student-id="${studentId}"]`);
                    if (!$quarterRow.length) {
                        continue;
                    }

                    const $gradeCell = $quarterRow.find(`.quarter-grade[data-quarter="${quarter}"]`).first();
                    const gradeText = $gradeCell.text();
                    const numericGrade = parseFloat(gradeText);
                    const hasGradeFlag = $gradeCell.attr('data-has-grade');
                    const hasGrade = (hasGradeFlag === '1') && !Number.isNaN(numericGrade);
                    $finalRow.find(`.final-quarter-grade[data-quarter="${quarter}"]`).text(hasGrade ? numericGrade :
                        '--');

                    if (hasGrade) {
                        total += numericGrade;
                        countedQuarters++;
                    }
                }

                // The Final Grade is always the sum of all quarter grades divided by 4.
                const finalGrade = countedQuarters > 0 ? Math.round(total / 4) : null;
                $finalRow.find('.final-grade').text(finalGrade !== null ? finalGrade : '--');
                const remarks = finalGrade !== null ? (finalGrade >= 75 ? 'PASSED' : 'FAILED') : '--';
                $finalRow.find('.final-remarks').text(remarks);
            }

            function refreshFinalGradesTable() {
                $('#finalGrade tbody tr[data-student-id]').each(function() {
                    updateFinalGradeRow($(this).data('student-id'));
                });
            }

            $saveStateBadge.on('click', function() {
                if ($(this).hasClass('retry') && collectDirtyGradePayload().length > 0) {
                    void saveDirtyGrades(true);
                }
            });

            $(window).on('beforeunload', function(event) {
                if (dirtyGrades.size === 0 && invalidCells.size === 0) {
                    return;
                }

                const warningMessage = 'You have unsaved grade changes.';
                event.preventDefault();
                event.returnValue = warningMessage;

                return warningMessage;
            });

            // Save all grades
            $('#saveAllGrades').click(function() {
                void saveDirtyGrades(true);
            });

            // Recitation Mode Logic for Assessments Page

            function syncRecitationModal(quarter) {
                $('#recitationQuarter').val(quarter);
                // Default activity max score is 10 for new activities
                $('#recitationMaxScore').val(10);

                // Reset all student inputs to 0
                $('.recitation-score-input').val(0);
            }

            // Open Modal
            $('#recitationModeModal').on('show.bs.modal', function() {
                // Find active tab from assessments page tabs
                // Try standard selector first
                let activeTabLink = $('#quarterTabs .nav-link.active');
                // Fallback if not found (e.g. mobile view or structure change)
                if (!activeTabLink.length) {
                    activeTabLink = $('.nav-tabs .nav-link.active');
                }

                let activeTabId = activeTabLink.attr('id');
                let activeQuarter = 1;
                if (activeTabId) {
                    // Extract number from "quarter1-tab"
                    const match = activeTabId.match(/quarter(\d+)/);
                    if (match && match[1]) {
                        activeQuarter = parseInt(match[1]);
                    }
                }
                syncRecitationModal(activeQuarter);

                // Adjust columns
                setTimeout(() => {
                    if (typeof recitationTable !== 'undefined') {
                        recitationTable.columns.adjust().draw();
                    }
                }, 200);
            });

            // Max Score Change
            $('#recitationMaxScore').on('input change', function() {
                const newMax = parseFloat($(this).val()) || 10;
                // Validate existing scores
                $('.recitation-score-input').each(function() {
                    let val = parseFloat($(this).val()) || 0;
                    if (val > newMax) $(this).val(newMax);
                });
            });

            // Plus Button
            $(document).on('click', '.plus-btn', function() {
                const $input = $(this).closest('div').find('.recitation-score-input');
                const maxScore = parseFloat($('#recitationMaxScore').val()) || 10;
                let currentScore = parseFloat($input.val()) || 0;

                if (currentScore < maxScore) {
                    $input.val(currentScore + 1).trigger('change');
                }
            });

            // Minus Button
            $(document).on('click', '.minus-btn', function() {
                const $input = $(this).closest('div').find('.recitation-score-input');
                let currentScore = parseFloat($input.val()) || 0;

                if (currentScore > 0) {
                    $input.val(currentScore - 1).trigger('change');
                }
            });

            // Direct Input Validation
            $(document).on('input change', '.recitation-score-input', function() {
                const maxScore = parseFloat($('#recitationMaxScore').val()) || 10;
                let val = $(this).val();
                if (val === '') return;

                val = parseFloat(val);
                if (isNaN(val)) val = 0;
                if (val > maxScore) val = maxScore;
                if (val < 0) val = 0;

                $(this).val(val);
            });

            // Reset All
            $('#resetRecitation').click(function() {
                $('.recitation-score-input').val(0);
            });

            // Fill Perfect
            $('#fillPerfectRecitation').click(function() {
                const maxScore = parseFloat($('#recitationMaxScore').val()) || 10;
                $('.recitation-score-input').val(maxScore);
            });

            // Apply & Save
            $('#applyRecitationScores').click(function() {
                const quarter = $('#recitationQuarter').val();
                const sessionMaxScore = parseFloat($('#recitationMaxScore').val()) || 10;
                const subjectId = {{ $selectedSubject->id ?? 'null' }};

                const scoresData = [];
                $('.recitation-score-input').each(function() {
                    const studentId = $(this).data('student-id');
                    const score = parseFloat($(this).val()) || 0;
                    scoresData.push({
                        student_id: studentId,
                        score: score
                    });
                });

                console.log('Sending payload:', {
                    subject_id: subjectId,
                    quarter: quarter,
                    session_max_score: sessionMaxScore,
                    scores: scoresData
                });

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');

                $.ajax({
                    url: '{{ route('teacher.oral-participation.appendScores', $class) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        subject_id: subjectId,
                        quarter: quarter,
                        session_max_score: sessionMaxScore,
                        scores: scoresData
                    },
                    success: function(response) {
                        alert(
                            `Success! Added ${sessionMaxScore} points to the total max score.`
                        );
                        location.reload();
                    },
                    error: function(xhr) {
                        let msg = 'Error saving scores: ' + (xhr.responseJSON?.message ||
                            'Unknown error');
                        if (xhr.responseJSON?.errors) {
                            msg += '\n' + Object.values(xhr.responseJSON.errors).map(e => e
                                .join('\n')).join('\n');
                        }
                        alert(msg);
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-check-circle me-2"></i> Apply & Save');
                    }
                });
            });

            // Initialize Recitation DataTable
            let recitationTable = $('#recitationTable').DataTable({
                paging: false,
                info: false,
                scrollY: '50vh',
                scrollCollapse: true,
                searching: true,
                ordering: true,
                columnDefs: [{
                    orderable: false,
                    targets: [1]
                }],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search student..."
                }
            });

            // Excel-like selection, copy, and paste support for quarter grade table cells
            let isSelectingGradeRange = false;
            let gradeSelectionAnchorCell = null;
            let selectedGradeCells = [];
            let activeSelectionTable = null;

            function clearGradeSelection() {
                selectedGradeCells.forEach((cell) => {
                    cell.classList.remove('grade-cell-selected');

                    const input = cell.querySelector('.grade-input, .max-score-input');
                    if (input) {
                        input.classList.remove('grade-input-selected');
                    }
                });

                selectedGradeCells = [];
            }

            function getSelectionTableFromCell(cell) {
                return cell.closest('table.grade-table[data-quarter]');
            }

            function getSelectableRows(table) {
                if (!table) {
                    return [];
                }

                const headerRows = table.querySelectorAll('thead tr');
                const lastHeaderRow = headerRows.length > 0 ? headerRows[headerRows.length - 1] : null;
                const studentRows = Array.from(table.querySelectorAll('tbody tr[data-student-id]'));

                return lastHeaderRow ? [lastHeaderRow, ...studentRows] : studentRows;
            }

            function getCellPosition(cell) {
                const tr = cell.closest('tr');
                const table = getSelectionTableFromCell(cell);
                if (!tr || !table) {
                    return null;
                }

                const rows = getSelectableRows(table);
                const rowIndex = rows.indexOf(tr);
                const colIndex = Array.from(tr.children).indexOf(cell);

                if (rowIndex < 0 || colIndex < 0) {
                    return null;
                }

                return {
                    table,
                    rowIndex,
                    colIndex,
                };
            }

            function markCellSelected(cell) {
                cell.classList.add('grade-cell-selected');
                const input = cell.querySelector('.grade-input, .max-score-input');
                if (input) {
                    input.classList.add('grade-input-selected');
                }
                selectedGradeCells.push(cell);
            }

            function selectCellRange(anchorCell, focusCell) {
                const anchorPos = getCellPosition(anchorCell);
                const focusPos = getCellPosition(focusCell);

                if (!anchorPos || !focusPos || anchorPos.table !== focusPos.table) {
                    clearGradeSelection();
                    markCellSelected(focusCell);
                    activeSelectionTable = getSelectionTableFromCell(focusCell);
                    return;
                }

                const minRow = Math.min(anchorPos.rowIndex, focusPos.rowIndex);
                const maxRow = Math.max(anchorPos.rowIndex, focusPos.rowIndex);
                const minCol = Math.min(anchorPos.colIndex, focusPos.colIndex);
                const maxCol = Math.max(anchorPos.colIndex, focusPos.colIndex);
                const rows = getSelectableRows(anchorPos.table);

                clearGradeSelection();

                for (let row = minRow; row <= maxRow; row++) {
                    const rowElement = rows[row];
                    if (!rowElement) {
                        continue;
                    }

                    for (let col = minCol; col <= maxCol; col++) {
                        const cell = rowElement.children[col];
                        if (!cell) {
                            continue;
                        }

                        markCellSelected(cell);
                    }
                }

                activeSelectionTable = anchorPos.table;
            }

            function getCellValue(cell) {
                const input = cell.querySelector('.grade-input, .max-score-input');
                if (input) {
                    return String(input.value ?? '').trim();
                }

                return String(cell.textContent ?? '').replace(/\s+/g, ' ').trim();
            }

            function buildClipboardTextFromSelection() {
                if (selectedGradeCells.length === 0) {
                    return '';
                }

                const positioned = selectedGradeCells
                    .map((cell) => ({
                        cell,
                        position: getCellPosition(cell),
                    }))
                    .filter((entry) => entry.position !== null);

                if (positioned.length === 0) {
                    return '';
                }

                const rowIndexes = positioned.map((entry) => entry.position.rowIndex);
                const colIndexes = positioned.map((entry) => entry.position.colIndex);
                const minRow = Math.min(...rowIndexes);
                const maxRow = Math.max(...rowIndexes);
                const minCol = Math.min(...colIndexes);
                const maxCol = Math.max(...colIndexes);

                const valueMap = new Map();
                positioned.forEach((entry) => {
                    const key = `${entry.position.rowIndex}:${entry.position.colIndex}`;
                    valueMap.set(key, getCellValue(entry.cell));
                });

                const lines = [];
                for (let row = minRow; row <= maxRow; row++) {
                    const values = [];
                    for (let col = minCol; col <= maxCol; col++) {
                        values.push(valueMap.get(`${row}:${col}`) ?? '');
                    }
                    lines.push(values.join('\t'));
                }

                return lines.join('\n');
            }

            function getTopLeftSelectedCell() {
                if (selectedGradeCells.length === 0) {
                    return null;
                }

                let topLeftCell = null;
                let topLeftRow = Number.MAX_SAFE_INTEGER;
                let topLeftCol = Number.MAX_SAFE_INTEGER;

                selectedGradeCells.forEach((cell) => {
                    const position = getCellPosition(cell);
                    if (!position) {
                        return;
                    }

                    if (position.rowIndex < topLeftRow || (position.rowIndex === topLeftRow && position
                            .colIndex < topLeftCol)) {
                        topLeftCell = cell;
                        topLeftRow = position.rowIndex;
                        topLeftCol = position.colIndex;
                    }
                });

                return topLeftCell;
            }

            function getEditableInputFromCell(cell) {
                const input = cell.querySelector('.grade-input, .max-score-input');
                if (!input || input.disabled || input.readOnly) {
                    return null;
                }

                return input;
            }

            $(document).on('mousedown', '.grade-table[data-quarter] td, .grade-table[data-quarter] th', function(
                event) {
                if (event.button !== 0) {
                    return;
                }

                const cell = this;
                const table = getSelectionTableFromCell(cell);
                if (!table) {
                    return;
                }

                // Check if target is an input element
                const input = event.target.closest('.grade-input, .max-score-input');
                if (input) {
                    // Prevent native text selection/drag on inputs
                    event.preventDefault();
                    // Focus the input for editing
                    input.focus();
                }

                activeSelectionTable = table;

                if (event.shiftKey && gradeSelectionAnchorCell) {
                    selectCellRange(gradeSelectionAnchorCell, cell);
                    return;
                }

                isSelectingGradeRange = true;
                // Disable user-select during drag to prevent text highlighting
                $('body').addClass('selecting-range');
                gradeSelectionAnchorCell = cell;
                clearGradeSelection();
                markCellSelected(cell);
            });

            $(document).on('mouseover', '.grade-table[data-quarter] td, .grade-table[data-quarter] th',
        function(event) {
                // Only select if left mouse button is physically held down
                if (event.buttons !== 1) {
                    // Button not held - cancel any stuck selection state
                    if (isSelectingGradeRange) {
                        isSelectingGradeRange = false;
                        $('body').removeClass('selecting-range');
                    }
                    return;
                }

                if (!isSelectingGradeRange || !gradeSelectionAnchorCell) {
                    return;
                }

                selectCellRange(gradeSelectionAnchorCell, this);
            });

            $(document).on('mouseup', function() {
                isSelectingGradeRange = false;
                $('body').removeClass('selecting-range');
            });

            $(document).on('mousedown', function(event) {
                if ($(event.target).closest('.grade-table[data-quarter]').length === 0) {
                    activeSelectionTable = null;
                    gradeSelectionAnchorCell = null;
                    clearGradeSelection();
                }
            });

            document.addEventListener('copy', function(e) {
                if (!activeSelectionTable || selectedGradeCells.length === 0) {
                    return;
                }

                const clipboardText = buildClipboardTextFromSelection();
                if (!clipboardText) {
                    return;
                }

                e.preventDefault();
                e.clipboardData.setData('text/plain', clipboardText);
            });

            document.addEventListener('paste', function(e) {
                const targetCell = e.target.closest(
                    '.grade-table[data-quarter] td, .grade-table[data-quarter] th');
                if (!targetCell) {
                    return;
                }

                e.preventDefault();

                const clipboardData = e.clipboardData || window.clipboardData;
                const pastedData = clipboardData.getData('Text');
                if (!pastedData) {
                    return;
                }

                const rows = pastedData.split(/\r?\n/).filter((rowText, index, arr) => {
                    return !(index === arr.length - 1 && rowText.trim() === '');
                });

                const startCell = selectedGradeCells.length > 1 && selectedGradeCells.includes(targetCell) ?
                    (getTopLeftSelectedCell() || targetCell) :
                    targetCell;

                const startPos = getCellPosition(startCell);
                if (!startPos) {
                    return;
                }

                const tableRows = getSelectableRows(startPos.table);

                rows.forEach((rowText, rowIndex) => {
                    const columns = rowText.split('\t');
                    const targetRow = tableRows[startPos.rowIndex + rowIndex];
                    if (!targetRow) {
                        return;
                    }

                    columns.forEach((colText, colIndex) => {
                        const targetCellForPaste = targetRow.children[startPos.colIndex +
                            colIndex];
                        if (!targetCellForPaste) {
                            return;
                        }

                        const input = getEditableInputFromCell(targetCellForPaste);
                        if (!input) {
                            return;
                        }

                        input.value = colText.trim();
                        input.dispatchEvent(new Event('input', {
                            bubbles: true,
                        }));
                        input.dispatchEvent(new Event('change', {
                            bubbles: true,
                        }));
                    });
                });
            });
        });
    </script>
@endpush
