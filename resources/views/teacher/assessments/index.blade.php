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
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.list') }}">My Classes</a></li>
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
                                            $types = [
                                                'written_works' => 'WRITTEN WORKS (20%)',
                                                'performance_tasks' => 'PERFORMANCE TASKS (60%)',
                                                'quarterly_assessments' => 'QUARTERLY ASSESSMENT (20%)',
                                            ];
                                            $typeWeights = [
                                                'written_works' => 20,
                                                'performance_tasks' => 60,
                                                'quarterly_assessments' => 20,
                                            ];
                                            $limitedAssessments = [];
                                        @endphp

                                        <tr>
                                            <th rowspan="3" class="student-name">LEARNERS' NAMES</th>
                                            @foreach ($types as $type => $label)
                                                @php
                                                    $availableAssessments = $quarterAssessments->get($type, collect());
                                                    $maxColumns =
                                                        $fixedAssessmentCounts[$type] ?? $availableAssessments->count();
                                                    $limitedAssessments[$type] = $availableAssessments->take(
                                                        $maxColumns,
                                                    );
                                                    // +3 for Total, PS, and Weighted Score columns
                                                    $colCount = $limitedAssessments[$type]->count() + 3;
                                                @endphp
                                                <th colspan="{{ $colCount }}" class="type-header">
                                                    {{ $label }}
                                                </th>
                                            @endforeach
                                            <th rowspan="2" class="grade-col">Quarter Grade</th>
                                        </tr>

                                        <tr>
                                            @foreach ($types as $type => $label)
                                                @php
                                                    $typeAssessments = $limitedAssessments[$type] ?? collect();
                                                @endphp
                                                @foreach ($typeAssessments as $index => $assessment)
                                                    <th class="assessment-header position-relative"
                                                        title="{{ $assessment->name }}">
                                                        {{ $index + 1 }}
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
                                                    $typeWeight = $typeWeights[$type];
                                                @endphp
                                                @foreach ($typeAssessments as $assessment)
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
                                                    @endphp
                                                    <th class="highest-score-row">
                                                        <input type="number" class="max-score-input"
                                                            data-assessment-id="{{ $assessment->id }}"
                                                            value="{{ $assessment->max_score !== null ? $maxScoreDisplay : '' }}"
                                                            max="1000"
                                                            style="width: 30px; border: none; background: transparent; text-align: center; font-weight: bold;"
                                                            placeholder="--">
                                                    </th>
                                                @endforeach
                                                <th class="highest-score-row">Total</th>
                                                <th class="highest-score-row">100.00</th>
                                                <th class="highest-score-row">{{ number_format($typeWeight) }}%</th>
                                            @endforeach
                                            <th class="highest-score-row">100.00</th>
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
                                                            $studentScore = collect($studentTypeData)->firstWhere(
                                                                'assessment.id',
                                                                $assessment->id,
                                                            );
                                                            $scoreValue = $studentScore['score'] ?? '';
                                                            if (
                                                                $scoreValue !== '' &&
                                                                fmod((float) $scoreValue, 1) === 0.0
                                                            ) {
                                                                $scoreValue = number_format($scoreValue, 0);
                                                            }
                                                        @endphp
                                                        <td>
                                                            <input type="number"
                                                                class="grade-input {{ str_replace('_', '-', $type) }}-score"
                                                                data-quarter="{{ $quarter }}"
                                                                data-type="{{ $type }}"
                                                                data-index="{{ $index }}"
                                                                data-assessment-id="{{ $assessment->id }}"
                                                                data-max-score="{{ $assessment->max_score }}"
                                                                value="{{ $scoreValue }}">
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
                                                <td class="calculated-field quarter-grade grade-col"
                                                    data-quarter="{{ $quarter }}" data-has-grade="0">0.00</td>
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
    <script>
        $(document).ready(function() {
            const highlightStudentId = @json($highlightStudentId);
            const typeWeights = {
                'written-works': 0.2,
                'performance-tasks': 0.6,
                'quarterly-assessments': 0.2
            };

            initializeAssessmentInputsState();

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
                        alert('Error updating max score: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                    }
                });
            });


            // Initialize calculations when tab is shown
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const quarter = $(e.target).attr('aria-controls').replace('quarter', '');
                initializeQuarterCalculations(parseInt(quarter));
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

            function calculateQuarterGrades($row, quarter) {
                const types = ['written-works', 'performance-tasks', 'quarterly-assessments'];
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

                // Quarter grade equals the sum of weighted component scores
                $row.find(`.quarter-grade[data-quarter="${quarter}"]`)
                    .text(quarterGrade.toFixed(2))
                    .attr('data-has-grade', quarterHasScores ? '1' : '0')
                    .data('has-grade', quarterHasScores ? 1 : 0);

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
                    $finalRow.find(`.final-quarter-grade[data-quarter="${quarter}"]`).text(hasGrade ? numericGrade
                        .toFixed(2) : '--');

                    if (hasGrade) {
                        total += numericGrade;
                        countedQuarters++;
                    }
                }

                const finalGrade = countedQuarters > 0 ? (total / countedQuarters) : null;
                $finalRow.find('.final-grade').text(finalGrade !== null ? finalGrade.toFixed(2) : '--');
            }

            function refreshFinalGradesTable() {
                $('#finalGrade tbody tr').each(function() {
                    updateFinalGradeRow($(this).data('student-id'));
                });
            }

            // Save all grades
            $('#saveAllGrades').click(function() {
                const gradesData = [];

                // Collect grades from all tabs
                $('.tab-pane').each(function() {
                    $(this).find('tbody tr').each(function() {
                        const $row = $(this);
                        const studentId = $row.data('student-id');

                        $row.find('.grade-input:not([disabled])').each(function() {
                            const $input = $(this);
                            const assessmentId = $input.data('assessment-id');
                            const score = $input.val();

                            if (assessmentId && score !== '') {
                                gradesData.push({
                                    student_id: studentId,
                                    assessment_id: assessmentId,
                                    score: parseFloat(score)
                                });
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
                    data: {
                        _token: '{{ csrf_token() }}',
                        grades: gradesData
                    },
                    success: function(response) {
                        alert('Grades saved successfully!');
                        $('#saveAllGrades').prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Save Grades');
                    },
                    error: function(xhr) {
                        alert('Error saving grades: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                        $('#saveAllGrades').prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Save Grades');
                    }
                });
            });

        });
    </script>
@endpush
