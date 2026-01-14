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

        .info-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
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
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.oral-participation.list') }}">Oral
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
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card info-card shadow">
                <div class="card-body d-flex align-items-center">
                    <div class="icon me-3">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h5 class="mb-1">Oral Participation Scores</h5>
                        <p class="mb-0">
                            Scores entered here are automatically linked to <strong>Performance Task 1</strong> in the Grade
                            Management.
                            These scores contribute to the Performance Tasks component (60% weight in DepEd grading).
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
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Student Oral Participation Scores
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered score-table mb-0">
                        <thead class="sticky-top">
                            <tr>
                                <th rowspan="2" class="student-name">LEARNERS' NAMES</th>
                                @for ($quarter = 1; $quarter <= 4; $quarter++)
                                    <th class="quarter-header">Quarter {{ $quarter }}</th>
                                @endfor
                            </tr>
                            <tr>
                                @for ($quarter = 1; $quarter <= 4; $quarter++)
                                    @php
                                        $assessment = $oralParticipationAssessments->get($quarter);
                                        $maxScore = $assessment ? $assessment->max_score : 10;
                                        $assessmentId = $assessment ? $assessment->id : null;
                                    @endphp
                                    <th class="max-score-row">
                                        Max:
                                        <input type="number" class="max-score-input" data-quarter="{{ $quarter }}"
                                            data-assessment-id="{{ $assessmentId }}" value="{{ $maxScore ?? 10 }}"
                                            min="1" max="100" step="1"
                                            {{ !$assessmentId ? 'disabled' : '' }} title="Click to edit max score">
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($studentsData as $studentData)
                                <tr data-student-id="{{ $studentData['student']->id }}">
                                    <td class="student-name">
                                        {{ $studentData['student']->last_name }},
                                        {{ $studentData['student']->first_name }}
                                    </td>

                                    @for ($quarter = 1; $quarter <= 4; $quarter++)
                                        @php
                                            $quarterData = $studentData['quarters'][$quarter] ?? [];
                                            $assessment = $quarterData['assessment'] ?? null;
                                            $score = $quarterData['score'] ?? '';
                                            $maxScore = $quarterData['max_score'] ?? 10;

                                            // Format score display
                                            if ($score !== '' && $score !== null) {
                                                $score =
                                                    fmod((float) $score, 1) === 0.0 ? number_format($score, 0) : $score;
                                            }
                                        @endphp
                                        <td>
                                            @if ($assessment)
                                                <input type="number" class="score-input oral-score"
                                                    data-quarter="{{ $quarter }}"
                                                    data-assessment-id="{{ $assessment->id }}"
                                                    data-max-score="{{ $maxScore }}" value="{{ $score }}"
                                                    min="0" max="{{ $maxScore }}" step="0.5">
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-success btn-lg save-btn" id="saveScores">
            <i class="fas fa-save me-2"></i> Save Scores
        </button>

        <button type="button" class="btn btn-primary btn-lg quick-add-btn" data-bs-toggle="modal"
            data-bs-target="#quickAddModal">
            <i class="fas fa-plus me-2"></i> Quick Add
        </button>
    @endif

    <!-- Quick Add Modal -->
    <div class="modal fade" id="quickAddModal" tabindex="-1" aria-labelledby="quickAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="quickAddModalLabel">
                        <i class="fas fa-comments me-2"></i> Quick Add Oral Participation Scores
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="quickAddQuarter" class="form-label fw-bold">Select Quarter</label>
                            <select id="quickAddQuarter" class="form-select">
                                @for ($q = 1; $q <= 4; $q++)
                                    <option value="{{ $q }}">Quarter {{ $q }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="quickAddMaxScore" class="form-label fw-bold">Max Score (Total)</label>
                            <input type="number" id="quickAddMaxScore" class="form-control" value="10" min="1"
                                max="100">
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Enter scores for students below. Leave blank if no score to record.
                    </div>

                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover table-sm" id="quickAddTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="min-width: 200px;">Student Name</th>
                                    <th style="width: 120px;" class="text-center">Score</th>
                                    <th style="width: 100px;" class="text-center">Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentsData as $studentData)
                                    <tr data-student-id="{{ $studentData['student']->id }}">
                                        <td>{{ $studentData['student']->last_name }},
                                            {{ $studentData['student']->first_name }}</td>
                                        <td class="text-center">
                                            <input type="number"
                                                class="form-control form-control-sm quick-score-input text-center"
                                                data-student-id="{{ $studentData['student']->id }}" min="0"
                                                step="0.5" placeholder="--">
                                        </td>
                                        <td class="text-center quick-percentage">--</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                <div>
                                    <strong>Quick Actions:</strong>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary" id="fillAllPerfect">
                                        <i class="fas fa-star me-1"></i> Fill All Perfect
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="clearAllScores">
                                        <i class="fas fa-eraser me-1"></i> Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="applyQuickAdd">
                        <i class="fas fa-check me-2"></i> Apply Scores
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Subject filter functionality
            $('#subjectFilter').change(function() {
                const classId = {{ $class->id }};
                const subjectId = $(this).val();
                window.location.href = `/teacher/oral-participation/${classId}?subject_id=${subjectId}`;
            });

            // Handle max score updates
            $(document).on('change', '.max-score-input', function() {
                const $input = $(this);
                const quarter = $input.data('quarter');
                const assessmentId = $input.data('assessment-id');
                const maxScore = parseFloat($input.val());

                if (!assessmentId) {
                    alert('No assessment found for this quarter.');
                    return;
                }

                if (isNaN(maxScore) || maxScore <= 0) {
                    alert('Please enter a valid max score greater than zero.');
                    $input.val(10);
                    return;
                }

                // Update all score inputs for this quarter with the new max
                $(`.oral-score[data-quarter="${quarter}"]`).each(function() {
                    $(this).attr('max', maxScore).data('max-score', maxScore);
                    const currentVal = parseFloat($(this).val());
                    if (!isNaN(currentVal) && currentVal > maxScore) {
                        $(this).val(maxScore);
                    }
                });

                // Save max score to server
                $.ajax({
                    url: '{{ route('teacher.oral-participation.updateMaxScore', $class) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        subject_id: {{ $selectedSubject->id ?? 'null' }},
                        quarter: quarter,
                        max_score: maxScore
                    },
                    success: function(response) {
                        console.log('Max score updated successfully');
                    },
                    error: function(xhr) {
                        alert('Error updating max score: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                    }
                });
            });

            // Validate score on input
            $(document).on('input', '.oral-score', function() {
                const $input = $(this);
                const value = parseFloat($input.val());
                const maxScore = parseFloat($input.data('max-score'));

                if (!isNaN(value) && !isNaN(maxScore) && value > maxScore) {
                    $input.val(maxScore);
                }

                if (!isNaN(value) && value < 0) {
                    $input.val(0);
                }
            });

            // Quick Add Modal - Update percentages when scores change
            $(document).on('input', '.quick-score-input', function() {
                updateQuickAddPercentage($(this));
            });

            $(document).on('change', '#quickAddMaxScore', function() {
                updateAllQuickAddPercentages();
                // Update max attribute on all quick score inputs
                const maxScore = parseFloat($(this).val()) || 10;
                $('.quick-score-input').attr('max', maxScore);
            });

            function updateQuickAddPercentage($input) {
                const score = parseFloat($input.val());
                const maxScore = parseFloat($('#quickAddMaxScore').val()) || 10;
                const $row = $input.closest('tr');
                const $percentage = $row.find('.quick-percentage');

                if (!isNaN(score) && score >= 0) {
                    // Clamp to max
                    if (score > maxScore) {
                        $input.val(maxScore);
                        $percentage.text('100%').removeClass('text-danger text-warning').addClass('text-success');
                    } else {
                        const pct = (score / maxScore) * 100;
                        $percentage.text(pct.toFixed(1) + '%');
                        // Color coding
                        $percentage.removeClass('text-success text-warning text-danger');
                        if (pct >= 75) {
                            $percentage.addClass('text-success');
                        } else if (pct >= 50) {
                            $percentage.addClass('text-warning');
                        } else {
                            $percentage.addClass('text-danger');
                        }
                    }
                } else {
                    $percentage.text('--').removeClass('text-success text-warning text-danger');
                }
            }

            function updateAllQuickAddPercentages() {
                $('.quick-score-input').each(function() {
                    updateQuickAddPercentage($(this));
                });
            }

            // Fill All Perfect
            $('#fillAllPerfect').click(function() {
                const maxScore = parseFloat($('#quickAddMaxScore').val()) || 10;
                $('.quick-score-input').val(maxScore);
                updateAllQuickAddPercentages();
            });

            // Clear All Scores
            $('#clearAllScores').click(function() {
                $('.quick-score-input').val('');
                updateAllQuickAddPercentages();
            });

            // Apply Quick Add scores to the main table
            $('#applyQuickAdd').click(function() {
                const quarter = $('#quickAddQuarter').val();
                const maxScore = parseFloat($('#quickAddMaxScore').val()) || 10;

                // Update max score in the main table header
                $(`.max-score-input[data-quarter="${quarter}"]`).val(maxScore).trigger('change');

                // Apply scores to main table
                $('.quick-score-input').each(function() {
                    const $input = $(this);
                    const studentId = $input.data('student-id');
                    const score = $input.val();

                    if (score !== '') {
                        // Find the corresponding input in the main table
                        const $mainRow = $(`tbody tr[data-student-id="${studentId}"]`);
                        const $mainInput = $mainRow.find(`.oral-score[data-quarter="${quarter}"]`);
                        if ($mainInput.length) {
                            $mainInput.val(score).data('max-score', maxScore).attr('max', maxScore);
                        }
                    }
                });

                // Close modal
                $('#quickAddModal').modal('hide');

                // Clear quick add form
                $('.quick-score-input').val('');
                updateAllQuickAddPercentages();

                // Notify user
                alert(
                    `Scores applied to Quarter ${quarter}. Don't forget to click "Save Scores" to save your changes.`);
            });

            // When modal opens, pre-fill with existing scores for the selected quarter
            $('#quickAddModal').on('show.bs.modal', function() {
                const quarter = $('#quickAddQuarter').val();
                loadExistingScoresForQuarter(quarter);
            });

            $('#quickAddQuarter').change(function() {
                loadExistingScoresForQuarter($(this).val());
            });

            function loadExistingScoresForQuarter(quarter) {
                // Get the max score for this quarter
                const $maxInput = $(`.max-score-input[data-quarter="${quarter}"]`);
                const maxScore = parseFloat($maxInput.val()) || 10;
                $('#quickAddMaxScore').val(maxScore);

                // Load existing scores
                $('.quick-score-input').each(function() {
                    const $input = $(this);
                    const studentId = $input.data('student-id');

                    // Find the main table input
                    const $mainRow = $(`tbody tr[data-student-id="${studentId}"]`);
                    const $mainInput = $mainRow.find(`.oral-score[data-quarter="${quarter}"]`);

                    if ($mainInput.length && $mainInput.val() !== '') {
                        $input.val($mainInput.val());
                    } else {
                        $input.val('');
                    }
                });

                updateAllQuickAddPercentages();
            }

            // Save all scores
            $('#saveScores').click(function() {
                const scoresData = [];

                $('tbody tr').each(function() {
                    const $row = $(this);
                    const studentId = $row.data('student-id');

                    $row.find('.oral-score').each(function() {
                        const $input = $(this);
                        const assessmentId = $input.data('assessment-id');
                        const score = $input.val();

                        if (assessmentId) {
                            scoresData.push({
                                student_id: studentId,
                                assessment_id: assessmentId,
                                score: score !== '' ? parseFloat(score) : null
                            });
                        }
                    });
                });

                if (scoresData.length === 0) {
                    alert('No scores to save!');
                    return;
                }

                // Show loading
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Saving...');

                $.ajax({
                    url: '{{ route('teacher.oral-participation.saveScores', $class) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        scores: scoresData
                    },
                    success: function(response) {
                        alert('Oral participation scores saved successfully!');
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-2"></i> Save Scores');
                    },
                    error: function(xhr) {
                        alert('Error saving scores: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-2"></i> Save Scores');
                    }
                });
            });
        });
    </script>
@endpush
