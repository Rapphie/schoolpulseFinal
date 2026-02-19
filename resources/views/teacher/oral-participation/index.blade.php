@extends('base')

@section('title', 'Oral Participation - ' . $class->section->name)

@push('styles')
    <style>
        .score-table {
            font-size: 13px;
        }

        .score-table th,
        .score-table td {
            padding: 8px 12px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }

        .score-input {
            width: 60px;
            height: 32px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        .score-input:focus {
            outline: 2px solid #28a745;
            border-color: #28a745;
        }

        .score-input::-webkit-outer-spin-button,
        .score-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .score-input[type='number'] {
            -moz-appearance: textfield;
        }

        .student-name {
            text-align: left;
            font-weight: bold;
            min-width: 200px;
            background-color: #f8f9fa;
            position: sticky;
            left: 0;
            z-index: 10;
        }

        .quarter-header {
            background-color: #28a745;
            color: white;
            font-weight: bold;
        }

        .max-score-row {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .save-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }


        .info-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }

        .percentage-display {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .max-score-input {
            width: 50px;
            height: 28px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            background-color: #fff;
        }

        .max-score-input:focus {
            outline: 2px solid #ffc107;
            border-color: #ffc107;
        }

        .quick-add-btn {
            position: fixed;
            bottom: 20px;
            right: 140px;
            z-index: 1000;
        }
    </style>
@endpush

@section('content')
    @php
        $lastMaxScores = [];
        for ($i = 1; $i <= 4; $i++) {
            $lastMaxScores[$i] = $oralParticipationAssessments->get($i, collect())->first()?->max_score ?? 10;
        }
    @endphp
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.list') }}">Oral
                            Participation</a></li>
                    <li class="breadcrumb-item">{{ $class->section->gradeLevel->name }} {{ $class->section->name }}</li>
                    <li class="breadcrumb-item active" aria-current="page">Scores</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4>
                        <i class="fas fa-comments me-2 text-success"></i>
                        Oral Participation - {{ $class->section->gradeLevel->name }} {{ $class->section->name }}
                    </h4>
                    @if ($selectedSubject)
                        <span class="badge bg-info fs-6">{{ $selectedSubject->name }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if ($subjects->count() > 1)
                        <label class="me-2">Subject:</label>
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

    <!-- Info Card -->
    <div class="row mb-2">
        <div class="col-12">
            <div class="card info-card shadow">
                <div class="card-body d-flex align-items-center py-2 px-3">
                    <div class="icon me-2" style="font-size:1.2rem; opacity:0.85;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Oral Participation Scores</h6>
                        <p class="mb-0 small">
                            Scores entered here are automatically linked to <strong>Performance Task 1</strong> in the Grade
                            Management.
                        </p>
                    </div>
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
        <!-- Quarter Tabs -->
        <div class="card shadow mb-4">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="quarterTabs" role="tablist">
                    @for ($quarter = 1; $quarter <= 4; $quarter++)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $quarter === 1 ? 'active' : '' }} py-3 px-4 fw-bold"
                                id="quarter{{ $quarter }}-tab" data-bs-toggle="tab"
                                data-bs-target="#quarter{{ $quarter }}" type="button" role="tab"
                                aria-controls="quarter{{ $quarter }}"
                                aria-selected="{{ $quarter === 1 ? 'true' : 'false' }}">
                                Quarter {{ $quarter }}
                            </button>
                        </li>
                    @endfor
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="quarterTabContent">
                    @for ($quarter = 1; $quarter <= 4; $quarter++)
                        @php
                            $quarterSessions = $oralParticipationAssessments->get($quarter, collect());
                        @endphp
                        <div class="tab-pane fade {{ $quarter === 1 ? 'show active' : '' }}"
                            id="quarter{{ $quarter }}" role="tabpanel"
                            aria-labelledby="quarter{{ $quarter }}-tab">

                            <!-- Sessions Table -->
                            <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Participation Sessions</h5>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#recitationModeModal">
                                    <i class="fas fa-plus me-1"></i> New Session
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-bordered mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center" style="width: 33%;">Date</th>
                                            <th class="text-center" style="width: 33%;">Max Score</th>
                                            <th class="text-center" style="width: 34%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($quarterSessions as $session)
                                            <tr>
                                                <td class="text-center align-middle">
                                                    {{ \Carbon\Carbon::parse($session->assessment_date)->format('M d, Y') }}
                                                </td>
                                                <td class="text-center align-middle fw-bold">
                                                    {{ $session->max_score }}
                                                </td>
                                                <td class="text-center align-middle">
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary edit-session-btn"
                                                            data-session-id="{{ $session->id }}"
                                                            data-session-name="{{ $session->name }}"
                                                            data-session-date="{{ \Carbon\Carbon::parse($session->assessment_date)->format('M d, Y') }}"
                                                            data-session-max="{{ $session->max_score }}"
                                                            title="Edit Session">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-info view-session-btn"
                                                            data-session-id="{{ $session->id }}"
                                                            data-session-name="{{ $session->name }}"
                                                            data-session-date="{{ \Carbon\Carbon::parse($session->assessment_date)->format('M d, Y') }}"
                                                            data-session-max="{{ $session->max_score }}"
                                                            title="View Scores">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-muted">
                                                    No oral participation sessions recorded for this quarter.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary Table (Students) -->
                            <div class="p-3 bg-light border-top border-bottom">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Quarterly Summary (PT 1)</h5>
                                <small class="text-muted">Total accumulated scores across all sessions</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="student-name" style="width: 40%;">LEARNERS' NAMES</th>
                                            <th class="text-center" style="width: 20%;">Total Sessions</th>
                                            <th class="text-center" style="width: 20%;">Total Score</th>
                                            <th class="text-center" style="width: 20%;">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (['male', 'female'] as $gender)
                                            @if ($studentsData->has($gender))
                                                <tr class="table-light">
                                                    <td colspan="4" class="fw-bold text-primary bg-light">
                                                        {{ strtoupper($gender) }}
                                                    </td>
                                                </tr>
                                                @foreach ($studentsData->get($gender) as $studentData)
                                                    @php
                                                        $quarterData = $studentData['quarters'][$quarter] ?? [];
                                                        $score = $quarterData['score'] ?? '';
                                                        $maxScore = $quarterData['max_score'] ?? 0;
                                                        $sessionsCount = $quarterData['sessions_count'] ?? 0;

                                                        // Format score display
                                                        $scoreDisplay =
                                                            $score === null || $score === ''
                                                                ? '--'
                                                                : (fmod((float) $score, 1) === 0.0
                                                                    ? number_format((float) $score, 0)
                                                                    : $score);

                                                        // Calculate percentage for display
                                                        $percentage = '--';
                                                        $pctClass = '';
                                                        if ($score !== '' && $score !== null && $maxScore > 0) {
                                                            $pctVal = ($score / $maxScore) * 100;
                                                            $percentage = number_format($pctVal, 1) . '%';
                                                            if ($pctVal >= 75) {
                                                                $pctClass = 'text-success fw-bold';
                                                            } elseif ($pctVal >= 50) {
                                                                $pctClass = 'text-warning fw-bold';
                                                            } else {
                                                                $pctClass = 'text-danger fw-bold';
                                                            }
                                                        }
                                                    @endphp
                                                    <tr data-student-id="{{ $studentData['student']->id }}">
                                                        <td class="student-name align-middle">
                                                            {{ $studentData['student']->last_name }},
                                                            {{ $studentData['student']->first_name }}
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            {{ $sessionsCount }}
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            {{ $scoreDisplay }} <span class="text-muted small">/
                                                                {{ $maxScore }}</span>
                                                        </td>
                                                        <td
                                                            class="text-center align-middle percentage-cell {{ $pctClass }}">
                                                            {{ $percentage }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="fixed-bottom bg-white border-top shadow p-3 d-flex justify-content-between align-items-center"
            style="z-index: 1020;">
            <div>
                <span class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Use <strong>Recitation Mode</strong> for real-time scoring.
                </span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#recitationModeModal">
                    <i class="fas fa-chalkboard-teacher me-2"></i> Recitation Mode
                </button>
                <button type="button" class="btn btn-success" id="saveScores">
                    <i class="fas fa-save me-2"></i> Save Changes
                </button>
            </div>
        </div>

        <!-- Spacer for fixed bottom bar -->
        <div style="height: 80px;"></div>
    @endif

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
                                    style="width: 120px;" {{ $activeQuarterNumber ? 'disabled' : '' }}>
                                    @for ($q = 1; $q <= 4; $q++)
                                        <option value="{{ $q }}"
                                            {{ $activeQuarterNumber === $q ? 'selected' : '' }}
                                            {{ $activeQuarterNumber && $activeQuarterNumber !== $q ? 'disabled' : '' }}>
                                            Quarter {{ $q }}
                                        </option>
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
                                @foreach (['male', 'female'] as $gender)
                                    @if ($studentsData->has($gender))
                                        <tr class="table-light">
                                            <td class="fw-bold text-primary">{{ strtoupper($gender) }}</td>
                                            <td></td>
                                        </tr>
                                        @foreach ($studentsData->get($gender) as $studentData)
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
                                                            data-student-id="{{ $studentData['student']->id }}"
                                                            value="0" min="0">
                                                        <button class="btn btn-outline-success plus-btn" type="button">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
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

    <!-- View Session Modal -->
    <div class="modal fade" id="viewSessionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i> Session Scores
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <strong>Date:</strong> <span id="viewSessionDate"></span>
                        </div>
                        <div>
                            <strong>Max Score:</strong> <span id="viewSessionMax"></span>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th class="text-center">Score</th>
                                    <th class="text-center">Percentage</th>
                                </tr>
                            </thead>
                            <tbody id="viewSessionScoresBody">
                                <!-- Scores will be loaded here via AJAX -->
                                <tr>
                                    <td colspan="3" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        Loading scores...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editFromViewBtn">
                        <i class="fas fa-edit me-1"></i> Edit Scores
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Session Modal -->
    <div class="modal fade" id="editSessionModal" tabindex="-1" aria-labelledby="editSessionModalLabel"
        aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <div class="d-flex align-items-center w-100 justify-content-between">
                        <h5 class="modal-title mb-0" id="editSessionModalLabel">
                            <i class="fas fa-edit me-2"></i> Edit Session Scores
                        </h5>
                        <div class="d-flex gap-3 align-items-center">
                            <div class="d-flex align-items-center">
                                <label for="editSessionMaxScore" class="me-2 fw-bold text-white-50">Max Score:</label>
                                <input type="number" id="editSessionMaxScore"
                                    class="form-control form-control-sm text-center fw-bold" value="10"
                                    min="1" max="100" style="width: 70px;" readonly>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body bg-light">
                    <div id="editSessionLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Loading session data...</p>
                    </div>
                    <div id="editSessionContent" style="display: none;">
                        <input type="hidden" id="editAssessmentId">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="editSessionTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <th class="text-center" style="width: 200px;">Score</th>
                                    </tr>
                                </thead>
                                <tbody id="editSessionScoresBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <button type="button" class="btn btn-outline-info btn-sm" id="fillPerfectEdit">
                            <i class="fas fa-star me-1"></i> All Perfect
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveEditScores">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Store last max scores for each quarter
            const lastMaxScores = @json($lastMaxScores);
            const activeQuarterNumber = @json($activeQuarterNumber);

            // Subject filter functionality
            $('#subjectFilter').change(function() {
                const classId = {{ $class->id }};
                const subjectId = $(this).val();
                window.location.href = `/teacher/oral-participation/${classId}?subject_id=${subjectId}`;
            });

            // Recitation Mode Logic (Table based)

            function syncRecitationModal(quarter) {
                const resolvedQuarter = activeQuarterNumber || quarter;
                $('#recitationQuarter').val(resolvedQuarter);
                // Use the last max score for this quarter, default to 10
                const defaultMax = lastMaxScores[resolvedQuarter] || 10;
                $('#recitationMaxScore').val(defaultMax);
                // Reset all student inputs to 0
                $('.recitation-score-input').val(0);
            }

            // Update max score when quarter is changed in the modal
            $('#recitationQuarter').change(function() {
                const quarter = $(this).val();
                const defaultMax = lastMaxScores[quarter] || 10;
                $('#recitationMaxScore').val(defaultMax);
            });

            $('#recitationModeModal').on('show.bs.modal', function() {
                // Find active tab from oral participation page tabs
                let activeTabLink = $('#quarterTabs .nav-link.active');
                let activeTabId = activeTabLink.attr('id');
                let activeQuarter = 1;
                if (activeTabId) {
                    const match = activeTabId.match(/quarter(\d+)/);
                    if (match && match[1]) {
                        activeQuarter = parseInt(match[1]);
                    }
                }
                syncRecitationModal(activeQuarterNumber || activeQuarter);

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

                // Update the sticky default for this session
                const quarter = $('#recitationQuarter').val();
                if (quarter) {
                    lastMaxScores[quarter] = newMax;
                }

                // Validate existing scores
                $('.recitation-score-input').each(function() {
                    let val = parseFloat($(this).val()) || 0;
                    if (val > newMax) $(this).val(newMax);
                });
            });

            // Plus Button
            $(document).on('click', '.plus-btn', function() {
                const $input = $(this).siblings('.recitation-score-input');
                const maxScore = parseFloat($('#recitationMaxScore').val()) || 10;
                let currentScore = parseFloat($input.val()) || 0;

                if (currentScore < maxScore) {
                    $input.val(currentScore + 1).trigger('change');
                }
            });

            // Minus Button
            $(document).on('click', '.minus-btn', function() {
                const $input = $(this).siblings('.recitation-score-input');
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
                const quarter = activeQuarterNumber || $('#recitationQuarter').val();
                const sessionMaxScore = parseFloat($('#recitationMaxScore').val()) || 10;
                const subjectId = {{ $selectedSubject->id ?? 'null' }};

                if (!quarter) {
                    alert('No active quarter available. Please contact the administrator.');
                    return;
                }

                const scoresData = [];
                $('.recitation-score-input').each(function() {
                    const studentId = $(this).data('student-id');
                    const score = parseFloat($(this).val()) || 0;
                    scoresData.push({
                        student_id: studentId,
                        score: score
                    });
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
                        alert(`Success! Participation recorded.`);
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
                ordering: false,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search student..."
                }
            });

            // View Session Scores Logic
            $(document).on('click', '.view-session-btn', function() {
                const sessionId = $(this).data('session-id');
                const sessionDate = $(this).data('session-date');
                const sessionMax = $(this).data('session-max');
                const classId = {{ $class->id }};

                $('#viewSessionDate').text(sessionDate);
                $('#viewSessionMax').text(sessionMax);

                // Set data attributes for the edit button within view modal
                $('#editFromViewBtn').data('session-id', sessionId);

                // Show modal
                $('#viewSessionModal').modal('show');

                // Reset table
                $('#viewSessionScoresBody').html(
                    '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading scores...</td></tr>'
                );

                // Fetch scores via AJAX
                $.ajax({
                    url: `/teacher/oral-participation/${classId}/${sessionId}/scores-json`,
                    method: 'GET',
                    success: function(response) {
                        let html = '';
                        if (!response.scores || response.scores.length === 0) {
                            html =
                                '<tr><td colspan="3" class="text-center py-3 text-muted">No student scores found for this session.</td></tr>';
                        } else {
                            // Group by gender in frontend
                            const grouped = {
                                'male': [],
                                'female': []
                            };
                            response.scores.forEach(s => {
                                const g = s.gender.toLowerCase();
                                if (grouped[g]) grouped[g].push(s);
                                else {
                                    if (!grouped['other']) grouped['other'] = [];
                                    grouped['other'].push(s);
                                }
                            });

                            ['male', 'female', 'other'].forEach(gender => {
                                if (grouped[gender] && grouped[gender].length > 0) {
                                    html +=
                                        `<tr class="table-light"><td class="fw-bold text-primary small">${gender.toUpperCase()}</td><td></td><td></td></tr>`;
                                    grouped[gender].forEach(function(score) {
                                        let pctClass = '';
                                        if (score.percentage >= 75) pctClass =
                                            'text-success fw-bold';
                                        else if (score.percentage >= 50)
                                            pctClass =
                                            'text-warning fw-bold';
                                        else pctClass = 'text-danger fw-bold';

                                        html += `
                                            <tr>
                                                <td class="align-middle text-start ps-4">${score.student_name}</td>
                                                <td class="text-center align-middle fw-bold">${score.score}</td>
                                                <td class="text-center align-middle ${pctClass}">${score.percentage.toFixed(1)}%</td>
                                            </tr>
                                        `;
                                    });
                                }
                            });
                        }
                        $('#viewSessionScoresBody').html(html);
                    },
                    error: function() {
                        $('#viewSessionScoresBody').html(
                            '<tr><td colspan="3" class="text-center py-3 text-danger">Failed to load scores.</td></tr>'
                        );
                    }
                });
            });

            // Edit Session Modal Logic
            function openEditModal(sessionId) {
                const classId = {{ $class->id }};

                $('#viewSessionModal').modal('hide');
                $('#editSessionModal').modal('show');

                $('#editSessionLoading').show();
                $('#editSessionContent').hide();
                $('#editSessionScoresBody').empty();

                $.ajax({
                    url: `/teacher/oral-participation/${classId}/${sessionId}/scores-json`,
                    method: 'GET',
                    success: function(response) {
                        $('#editAssessmentId').val(response.assessment.id);
                        $('#editSessionMaxScore').val(response.assessment.max_score);

                        let html = '';
                        const grouped = {
                            'male': [],
                            'female': []
                        };
                        response.scores.forEach(s => {
                            const g = s.gender.toLowerCase();
                            if (grouped[g]) grouped[g].push(s);
                            else {
                                if (!grouped['other']) grouped['other'] = [];
                                grouped['other'].push(s);
                            }
                        });

                        ['male', 'female', 'other'].forEach(gender => {
                            if (grouped[gender] && grouped[gender].length > 0) {
                                html +=
                                    `<tr class="table-light"><td class="fw-bold text-primary">${gender.toUpperCase()}</td><td></td></tr>`;
                                grouped[gender].forEach(s => {
                                    html += `
                                        <tr class="edit-score-row" data-student-id="${s.student_id}">
                                            <td class="align-middle fw-bold ps-4">${s.student_name}</td>
                                            <td class="align-middle text-center">
                                                <div class="input-group justify-content-center" style="max-width: 160px; margin: 0 auto;">
                                                    <button class="btn btn-outline-danger edit-minus-btn" type="button"><i class="fas fa-minus"></i></button>
                                                    <input type="number" class="form-control text-center edit-score-input fw-bold"
                                                        data-student-id="${s.student_id}" value="${s.score}" min="0" max="${response.assessment.max_score}">
                                                    <button class="btn btn-outline-success edit-plus-btn" type="button"><i class="fas fa-plus"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                                });
                            }
                        });

                        $('#editSessionScoresBody').html(html);
                        $('#editSessionLoading').hide();
                        $('#editSessionContent').show();
                    }
                });
            }

            $(document).on('click', '.edit-session-btn', function() {
                openEditModal($(this).data('session-id'));
            });

            $('#editFromViewBtn').click(function() {
                openEditModal($(this).data('session-id'));
            });

            // Edit Modal Plus/Minus
            $(document).on('click', '.edit-plus-btn', function() {
                const $input = $(this).siblings('.edit-score-input');
                const max = parseFloat($('#editSessionMaxScore').val());
                let val = parseFloat($input.val()) || 0;
                if (val < max) $input.val(val + 1).trigger('change');
            });

            $(document).on('click', '.edit-minus-btn', function() {
                const $input = $(this).siblings('.edit-score-input');
                let val = parseFloat($input.val()) || 0;
                if (val > 0) $input.val(val - 1).trigger('change');
            });

            $(document).on('input change', '.edit-score-input', function() {
                const max = parseFloat($('#editSessionMaxScore').val());
                let val = parseFloat($(this).val());
                if (isNaN(val)) val = 0;
                if (val > max) val = max;
                if (val < 0) val = 0;
                $(this).val(val);
            });

            $('#fillPerfectEdit').click(function() {
                const max = $('#editSessionMaxScore').val();
                $('.edit-score-input').val(max);
            });

            $('#saveEditScores').click(function() {
                const assessmentId = $('#editAssessmentId').val();
                const scores = [];
                $('.edit-score-input').each(function() {
                    scores.push({
                        student_id: $(this).data('student-id'),
                        assessment_id: assessmentId,
                        score: $(this).val()
                    });
                });

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: '{{ route('teacher.oral-participation.saveScores', $class) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        scores: scores
                    },
                    success: function() {
                        alert('Scores updated successfully!');
                        location.reload();
                    },
                    error: function(xhr) {
                        alert('Error saving scores: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-2"></i> Save Changes');
                    }
                });
            });
        });
    </script>
@endpush
