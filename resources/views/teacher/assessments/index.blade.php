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
            min-width: 180px;
            background-color: #f8f9fa;
            position: sticky;
            left: 0;
            z-index: 10;
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
        <div class="card shadow">
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
                </ul>
            </div>

            <div class="card-body p-0">
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

                                                    if ($type === 'performance_task') {
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
                                                                'max_score' => null, // View will default to 10 for display
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
                                                        // Check if assessment is oral participation by type
                                                        $isOralParticipation =
                                                            $assessment->type === 'oral_participation';
                                                    @endphp
                                                    <th
                                                        class="highest-score-row {{ $isOralParticipation ? 'oral-participation-cell' : '' }}">
                                                        @if ($isOralParticipation)
                                                            <span style="font-size: 10px;"
                                                                title="Edit in Oral Participation page">{{ $assessment->max_score ?? 10 }}</span>
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
                                        @foreach ($studentsData as $studentData)
                                            <tr data-student-id="{{ $studentData['student']->id }}">
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
                                                            $studentScore = collect($studentTypeData)->first(function (
                                                                $item,
                                                            ) use ($assessment) {
                                                                return isset($item['assessment']) &&
                                                                    $item['assessment']->id === $assessment->id;
                                                            });
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
                                    @foreach ($studentsData as $studentData)
                                        <tr data-student-id="{{ $studentData['student']->id }}">
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
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-success btn-lg save-btn" id="saveAllGrades">
            <i class="fas fa-save"></i> Save Grades
        </button>
    @endif

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            const highlightStudentId = @json($highlightStudentId);
            // Convert PHP weights (with underscores) to JS format (with dashes)
            const typeWeights = {
                @foreach ($assessmentTypeWeights as $type => $weight)
                    '{{ str_replace('_', '-', $type) }}': {{ $weight }},
                @endforeach
            };

            initializeAssessmentInputsState();

            function updateManageOralParticipationLink(quarter) {
                const baseUrl = "{{ route('teacher.oral-participation.index', $class) }}";
                const subjectId = {{ $selectedSubject->id ?? 'null' }};
                const params = new URLSearchParams();

                if (subjectId) {
                    params.append('subject_id', subjectId);
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

            // Set initial link on page load
            updateManageOralParticipationLink(getActiveQuarter());

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
                const classId = {{ $class->id }};
                const subjectId = $(this).val();
                window.location.href = `/teacher/classes/assessments/${classId}?subject_id=${subjectId}`;
            });

            // Calculate grades when input changes
            $(document).on('input change', '.grade-input', function() {
                const $input = $(this);
                const scoreRaw = $input.val();

                if (scoreRaw.trim() !== '') {
                    const maxScoreRaw = $input.data('max-score');
                    const score = parseFloat(scoreRaw);

                    if (score < 0) {
                        alert('Score cannot be negative.');
                        $input.val('');
                    } else if (maxScoreRaw.toString().trim() !== '') {
                        const maxScore = parseFloat(maxScoreRaw);
                        if (!isNaN(maxScore) && score > maxScore) {
                            alert(`Score (${score}) cannot exceed the maximum of ${maxScore}.`);
                            $input.val('');
                        }
                    }
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
                $(`#quarter${quarter} tbody tr`).each(function() {
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
                    $relatedGrades.val('');
                    $relatedGrades.prop('disabled', true);
                    $relatedGrades.removeAttr('max');
                    $relatedGrades.attr('data-max-score', '');
                    return;
                }

                $relatedGrades.prop('disabled', false);
                $relatedGrades.attr('max', numericMax);
                $relatedGrades.attr('data-max-score', numericMax);
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

                // The Final Grade is the average of the quarters with available grades.
                const finalGrade = countedQuarters > 0 ? Math.round(total / countedQuarters) : null;
                $finalRow.find('.final-grade').text(finalGrade !== null ? finalGrade : '--');
                const remarks = finalGrade !== null ? (finalGrade >= 75 ? 'PASSED' : 'FAILED') : '--';
                $finalRow.find('.final-remarks').text(remarks);
            }

            function refreshFinalGradesTable() {
                $('#finalGrade tbody tr').each(function() {
                    updateFinalGradeRow($(this).data('student-id'));
                });
            }

            // Save all grades
            $('#saveAllGrades').click(function() {
                const gradesData = [];

                // Collect detailed assessment grades from all quarter tabs
                $('.tab-pane[id^="quarter"]').each(function() {
                    $(this).find('tbody tr').each(function() {
                        const $row = $(this);
                        const studentId = $row.data('student-id');

                        $row.find('.grade-input:not([disabled])').each(function() {
                            const $input = $(this);
                            const assessmentId = $input.data('assessment-id');
                            const isOral = $input.data('is-oral-participation') ==
                                '1';
                            const scoreRaw = $input.val();

                            if (assessmentId && assessmentId !== -999 && !isOral &&
                                scoreRaw !== '' && scoreRaw !== null) {
                                const scoreNum = parseFloat(scoreRaw);
                                if (!isNaN(scoreNum)) {
                                    gradesData.push({
                                        student_id: studentId,
                                        assessment_id: assessmentId,
                                        score: scoreNum
                                    });
                                }
                            }
                        });
                    });
                });

                if (gradesData.length === 0) {
                    alert('No grades to save!');
                    return;
                }

                // Show loading
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: '{{ route('teacher.assessments.saveGrades', $class) }}',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        _token: '{{ csrf_token() }}',
                        grades: gradesData,
                    }),
                    success: function(response) {
                        alert(response.message || 'Grades saved successfully!');
                        $('#saveAllGrades').prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Save Grades');
                    },
                    error: function(xhr) {
                        let errorMsg = 'Validation failed.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.status === 413) {
                            errorMsg = 'Request too large. Try saving in smaller batches.';
                        }

                        alert('Error saving grades: ' + errorMsg);
                        $('#saveAllGrades').prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Save Grades');
                    }
                });
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
        });
    </script>
@endpush
