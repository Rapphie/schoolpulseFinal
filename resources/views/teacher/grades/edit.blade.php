@extends('base')

@section('title', 'Manage Grades')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.grades') }}">Grades</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $class->section_name ?? 'Section' }} -
                        {{ $class->subject_name ?? 'Subject' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Class Information</h6>
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="exportDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="download"></i> Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" id="exportExcel">Excel (.xlsx)</a></li>
                            <li><a class="dropdown-item" href="#" id="exportPDF">PDF (.pdf)</a></li>
                            <li><a class="dropdown-item" href="#" id="exportCSV">CSV (.csv)</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Section:</label>
                                <p>{{ $class->section_name ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Subject:</label>
                                <p>{{ $class->subject_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Schedule:</label>
                                <p>{{ $class->schedule ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Total Students:</label>
                                <p>{{ $class->student_count ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Grading Period Settings</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="grading_period" class="form-label">Select Grading Period <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="grading_period" name="grading_period" required>
                            <option value="1" {{ ($current_period ?? '1') == '1' ? 'selected' : '' }}>1st Quarter
                            </option>
                            <option value="2" {{ ($current_period ?? '1') == '2' ? 'selected' : '' }}>2nd Quarter
                            </option>
                            <option value="3" {{ ($current_period ?? '1') == '3' ? 'selected' : '' }}>3rd Quarter
                            </option>
                            <option value="4" {{ ($current_period ?? '1') == '4' ? 'selected' : '' }}>4th Quarter
                            </option>
                            <option value="final" {{ ($current_period ?? '1') == 'final' ? 'selected' : '' }}>Final
                            </option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date"
                                    value="{{ $period_dates->start_date ?? '' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date"
                                    value="{{ $period_dates->end_date ?? '' }}" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grading Status</label>
                        <div class="p-2 rounded" style="background-color: {{ $is_locked ? '#f8d7da' : '#d1e7dd' }};">
                            <div class="d-flex align-items-center">
                                <i data-feather="{{ $is_locked ? 'lock' : 'unlock' }}" class="me-2"
                                    style="width: 18px; height: 18px;"></i>
                                <span>{{ $is_locked ? 'Grades are locked for this period.' : 'Grades can be modified for this period.' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="gradesForm" action="#" method="POST">
        @csrf
        <input type="hidden" name="class_id" value="{{ $class->id ?? 0 }}">
        <input type="hidden" name="grading_period" value="{{ $current_period ?? '1' }}">

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Grading Sheet</h6>
                <div>
                    <button type="button" class="btn btn-success btn-sm me-2" id="autoCalculateBtn"
                        {{ $is_locked ? 'disabled' : '' }}>
                        <i data-feather="refresh-cw"></i> Auto Calculate
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" {{ $is_locked ? 'disabled' : '' }}>
                        <i data-feather="save"></i> Save Grades
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="gradesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th rowspan="2">Student Name</th>
                                <th colspan="3" class="text-center">Written Work
                                    ({{ $weightings->written_work ?? 30 }}%)</th>
                                <th colspan="3" class="text-center">Performance Task
                                    ({{ $weightings->performance_task ?? 40 }}%)</th>
                                <th colspan="3" class="text-center">Quarterly Assessment
                                    ({{ $weightings->quarterly_assessment ?? 30 }}%)</th>
                                <th rowspan="2" class="text-center">Initial Grade</th>
                                <th rowspan="2" class="text-center">Quarterly Grade</th>
                                <th rowspan="2" class="text-center">Remarks</th>
                            </tr>
                            <tr>
                                <th class="text-center">WW Score</th>
                                <th class="text-center">WW Total</th>
                                <th class="text-center">WW %</th>
                                <th class="text-center">PT Score</th>
                                <th class="text-center">PT Total</th>
                                <th class="text-center">PT %</th>
                                <th class="text-center">QA Score</th>
                                <th class="text-center">QA Total</th>
                                <th class="text-center">QA %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students  as $student)
                                <tr>
                                    <td>{{ $student->name }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm ww-score"
                                            name="grades[{{ $student->id }}][ww_score]"
                                            value="{{ $student->grades->ww_score ?? '' }}" min="0" step="0.01"
                                            {{ $is_locked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm ww-total"
                                            name="grades[{{ $student->id }}][ww_total]"
                                            value="{{ $student->grades->ww_total ?? '' }}" min="0" step="0.01"
                                            {{ $is_locked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm ww-percent"
                                            name="grades[{{ $student->id }}][ww_percent]"
                                            value="{{ $student->grades->ww_percent ?? '' }}" min="0"
                                            max="100" step="0.01" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm pt-score"
                                            name="grades[{{ $student->id }}][pt_score]"
                                            value="{{ $student->grades->pt_score ?? '' }}" min="0" step="0.01"
                                            {{ $is_locked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm pt-total"
                                            name="grades[{{ $student->id }}][pt_total]"
                                            value="{{ $student->grades->pt_total ?? '' }}" min="0" step="0.01"
                                            {{ $is_locked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm pt-percent"
                                            name="grades[{{ $student->id }}][pt_percent]"
                                            value="{{ $student->grades->pt_percent ?? '' }}" min="0"
                                            max="100" step="0.01" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm qa-score"
                                            name="grades[{{ $student->id }}][qa_score]"
                                            value="{{ $student->grades->qa_score ?? '' }}" min="0" step="0.01"
                                            {{ $is_locked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm qa-total"
                                            name="grades[{{ $student->id }}][qa_total]"
                                            value="{{ $student->grades->qa_total ?? '' }}" min="0" step="0.01"
                                            {{ $is_locked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm qa-percent"
                                            name="grades[{{ $student->id }}][qa_percent]"
                                            value="{{ $student->grades->qa_percent ?? '' }}" min="0"
                                            max="100" step="0.01" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm initial-grade"
                                            name="grades[{{ $student->id }}][initial_grade]"
                                            value="{{ $student->grades->initial_grade ?? '' }}" min="0"
                                            max="100" step="0.01" readonly>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm quarterly-grade"
                                            name="grades[{{ $student->id }}][quarterly_grade]"
                                            value="{{ $student->grades->quarterly_grade ?? '' }}" min="60"
                                            max="100" step="0.01" {{ $is_locked ? 'readonly' : '' }}>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm"
                                            name="grades[{{ $student->id }}][remarks]"
                                            {{ $is_locked ? 'disabled' : '' }}>
                                            <option value="Passed"
                                                {{ ($student->grades->remarks ?? '') == 'Passed' ? 'selected' : '' }}>
                                                Passed</option>
                                            <option value="Failed"
                                                {{ ($student->grades->remarks ?? '') == 'Failed' ? 'selected' : '' }}>
                                                Failed</option>
                                            <option value="Incomplete"
                                                {{ ($student->grades->remarks ?? '') == 'Incomplete' ? 'selected' : '' }}>
                                                Incomplete</option>
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center">No students found in this class.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>

    <!-- Publish Grades Modal -->
    <div class="modal fade" id="publishGradesModal" tabindex="-1" aria-labelledby="publishGradesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="publishGradesModalLabel">Publish Grades</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="publishGradesForm" action="#" method="POST">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $class->id ?? 0 }}">
                    <input type="hidden" name="grading_period" value="{{ $current_period ?? '1' }}">
                    <div class="modal-body">
                        <p>Are you sure you want to publish the grades for <span
                                class="fw-bold">{{ $class->section_name ?? 'Section' }} -
                                {{ $class->subject_name ?? 'Subject' }}</span>?</p>
                        <p class="text-danger">Warning: Once published, the grades cannot be modified for this grading
                            period.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Publish Grades</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize feather icons
            feather.replace();

            // Set up event listeners for grade calculations
            setupGradeCalculations();

            // Grading Period Change
            $('#grading_period').on('change', function() {
                const period = $(this).val();
                const classId = $('input[name="class_id"]').val();

                // Redirect to the same page with the new period
                window.location.href =
                    `{{ route('teacher.grades.edit') }}?class_id=${classId}&period=${period}`;
            });

            // Auto Calculate Button
            $('#autoCalculateBtn').on('click', function() {
                calculateAllGrades();
            });

            // Form Submit Event
            $('#gradesForm').on('submit', function(e) {
                e.preventDefault();

                // In a real app, you would submit the form using AJAX
                alert('Grades have been saved successfully!');
            });

            // Export buttons
            $('#exportExcel').on('click', function() {
                alert('Exporting grades to Excel...');
            });

            $('#exportPDF').on('click', function() {
                alert('Exporting grades to PDF...');
            });

            $('#exportCSV').on('click', function() {
                alert('Exporting grades to CSV...');
            });
        });

        function setupGradeCalculations() {
            // Written Work percentage calculation
            $('.ww-score, .ww-total').on('input', function() {
                const row = $(this).closest('tr');
                calculateComponentPercentage(row, 'ww');
                calculateInitialGrade(row);
            });

            // Performance Task percentage calculation
            $('.pt-score, .pt-total').on('input', function() {
                const row = $(this).closest('tr');
                calculateComponentPercentage(row, 'pt');
                calculateInitialGrade(row);
            });

            // Quarterly Assessment percentage calculation
            $('.qa-score, .qa-total').on('input', function() {
                const row = $(this).closest('tr');
                calculateComponentPercentage(row, 'qa');
                calculateInitialGrade(row);
            });

            // Quarterly Grade input
            $('.quarterly-grade').on('input', function() {
                const row = $(this).closest('tr');
                updateRemarks(row);
            });
        }

        function calculateComponentPercentage(row, component) {
            const score = parseFloat(row.find(`.${component}-score`).val()) || 0;
            const total = parseFloat(row.find(`.${component}-total`).val()) || 0;

            if (total > 0) {
                const percentage = (score / total) * 100;
                row.find(`.${component}-percent`).val(percentage.toFixed(2));
            } else {
                row.find(`.${component}-percent`).val('');
            }
        }

        function calculateInitialGrade(row) {
            // Get component percentages
            const wwPercent = parseFloat(row.find('.ww-percent').val()) || 0;
            const ptPercent = parseFloat(row.find('.pt-percent').val()) || 0;
            const qaPercent = parseFloat(row.find('.qa-percent').val()) || 0;

            // Get weightings
            const wwWeight = {{ $weightings->written_work ?? 30 }} / 100;
            const ptWeight = {{ $weightings->performance_task ?? 40 }} / 100;
            const qaWeight = {{ $weightings->quarterly_assessment ?? 30 }} / 100;

            // Calculate initial grade
            const initialGrade = (wwPercent * wwWeight) + (ptPercent * ptWeight) + (qaPercent * qaWeight);

            // Set the initial grade
            row.find('.initial-grade').val(initialGrade.toFixed(2));

            // If quarterly grade is empty, set it to the initial grade
            if (!row.find('.quarterly-grade').val()) {
                row.find('.quarterly-grade').val(initialGrade.toFixed(2));
            }

            // Update remarks based on the quarterly grade
            updateRemarks(row);
        }

        function updateRemarks(row) {
            const quarterlyGrade = parseFloat(row.find('.quarterly-grade').val()) || 0;
            const remarksSelect = row.find('select[name$="[remarks]"]');

            if (quarterlyGrade >= 75) {
                remarksSelect.val('Passed');
            } else if (quarterlyGrade >= 60) {
                remarksSelect.val('Failed');
            } else {
                remarksSelect.val('Incomplete');
            }
        }

        function calculateAllGrades() {
            $('#gradesTable tbody tr').each(function() {
                calculateComponentPercentage($(this), 'ww');
                calculateComponentPercentage($(this), 'pt');
                calculateComponentPercentage($(this), 'qa');
                calculateInitialGrade($(this));
            });
        }
    </script>
@endpush
