@extends('base')

@section('title', 'Oral Participation - My Classes')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
    <style>
        .schedule-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            margin: 0.125rem;
        }

        .filter-card {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('teacher.oral-participation.list') }}">Oral Participation</a>
                </li>
                <li class="breadcrumb-item active">My Classes</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="fas fa-comments me-2"></i>Oral Participation Management</h4>
                <p class="text-muted mb-0">Manage oral participation scores for your classes. Scores are linked to
                    Performance Task 1.</p>
            </div>
        </div>

        @if (isset($error))
            <div class="alert alert-warning">{{ $error }}</div>
        @else
            <!-- Filter Card -->
            <div class="card filter-card shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('teacher.oral-participation.list') }}" id="filterForm">
                        <div class="row align-items-end g-3">
                            <div class="col-md-4">
                                <label for="grade_level_id" class="form-label fw-semibold">
                                    <i class="fas fa-graduation-cap me-1"></i> Grade Level
                                </label>
                                <select name="grade_level_id" id="grade_level_id" class="form-select">
                                    <option value="">All Grade Levels</option>
                                    @foreach ($gradeLevels as $gradeLevel)
                                        <option value="{{ $gradeLevel->id }}"
                                            {{ isset($selectedGradeLevelId) && $selectedGradeLevelId == $gradeLevel->id ? 'selected' : '' }}>
                                            {{ $gradeLevel->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="section_id" class="form-label fw-semibold">
                                    <i class="fas fa-users me-1"></i> Section
                                </label>
                                <select name="section_id" id="section_id" class="form-select"
                                    {{ empty($sections) ? 'disabled' : '' }}>
                                    <option value="">All Sections</option>
                                    @if (!empty($sections))
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}"
                                                {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                                {{ $section->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="fas fa-filter me-1"></i> Apply Filter
                                    </button>
                                    <a href="{{ route('teacher.oral-participation.list') }}"
                                        class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if ($classes->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You are not assigned to any classes for the current school year matching the selected filters.
                </div>
            @else
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="classTable" width="100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Grade Level</th>
                                        <th>Section</th>
                                        <th>Adviser</th>
                                        <th>Schedule</th>
                                        <th class="text-center">Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($classes as $class)
                                        <tr>
                                            <td>
                                                <span
                                                    class="badge bg-primary">{{ $class->section->gradeLevel->name }}</span>
                                            </td>
                                            <td>
                                                <strong>{{ $class->section->name }}</strong>
                                            </td>
                                            <td>
                                                @if ($class->teacher)
                                                    {{ $class->teacher->user->first_name }}
                                                    {{ $class->teacher->user->last_name }}
                                                    @if ($class->teacher_id == $teacher->id)
                                                        <span class="badge bg-success ms-1">You</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $teacherSchedules = $class->schedules->where(
                                                        'teacher_id',
                                                        $teacher->id,
                                                    );
                                                @endphp
                                                @forelse($teacherSchedules as $schedule)
                                                    <div class="mb-1">
                                                        <span class="badge bg-info schedule-badge">
                                                            {{ $schedule->subject->name }}
                                                        </span>
                                                        <small class="text-muted">
                                                            {{ $schedule->day_names_label }}
                                                            {{ $schedule->start_time?->format('g:i A') }} -
                                                            {{ $schedule->end_time?->format('g:i A') }}
                                                        </small>
                                                    </div>
                                                @empty
                                                    <span class="text-muted">No schedule</span>
                                                @endforelse
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">
                                                    {{ $class->enrollments->count() }} / {{ $class->capacity }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $classSchedules = $teacher->schedules->where(
                                                        'class_id',
                                                        $class->id,
                                                    );
                                                    $uniqueSubjects = $classSchedules
                                                        ->map(fn($schedule) => $schedule->subject)
                                                        ->unique('id');
                                                    $subjectsCount = $uniqueSubjects->count();
                                                    $singleSubject =
                                                        $subjectsCount === 1 ? $uniqueSubjects->first() : null;
                                                @endphp
                                                @if ($subjectsCount > 1)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-success d-flex align-items-center subject-modal-btn"
                                                        data-bs-toggle="modal" data-bs-target="#subjectModal"
                                                        data-class-id="{{ $class->id }}"
                                                        data-class-name="{{ $class->section->gradeLevel->name }} - {{ $class->section->name }}">
                                                        <i class="fas fa-comments me-1"></i>
                                                        <span>Oral Participation</span>
                                                    </button>
                                                @else
                                                    <a href="{{ route('teacher.oral-participation.index', $class) }}@if ($singleSubject) ?subject_id={{ $singleSubject->id }} @endif"
                                                        class="btn btn-sm btn-outline-success d-flex align-items-center">
                                                        <i class="fas fa-comments me-1"></i>
                                                        <span>Oral Participation</span>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Subject Selection Modal -->
    <div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="subjectModalLabel">
                        <i class="fas fa-comments me-2"></i>Oral Participation - <span id="modalClassName"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Subject Selection -->
                    <div id="step1SubjectSelection">
                        <p class="mb-3">Select a subject to add oral participation scores:</p>
                        <div id="subjectsList" class="d-grid gap-2">
                            <!-- Subjects will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Step 2: Quick Score Entry (hidden initially) -->
                    <div id="step2ScoreEntry" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="backToSubjects">
                                <i class="fas fa-arrow-left me-1"></i> Back to Subjects
                            </button>
                            <span class="badge bg-info fs-6" id="selectedSubjectBadge"></span>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="modalQuarter" class="form-label fw-bold">Quarter</label>
                                <select id="modalQuarter" class="form-select">
                                    <option value="1">Quarter 1</option>
                                    <option value="2">Quarter 2</option>
                                    <option value="3">Quarter 3</option>
                                    <option value="4">Quarter 4</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100 text-end">
                                    <span class="text-muted">Total Points Given: </span>
                                    <span id="totalPointsDisplay" class="badge bg-success fs-6">0</span>
                                </div>
                            </div>
                        </div>

                        <div id="modalStudentsList" style="display: none;">
                            <div class="alert alert-success py-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Use <strong>+</strong> and <strong>-</strong> buttons to add or remove oral participation
                                points.
                                Click the score to edit directly.
                            </div>

                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-hover table-sm" id="modalStudentsTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="min-width: 180px;">Student Name</th>
                                            <th style="width: 160px;" class="text-center">Score</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalStudentsBody">
                                        <!-- Students will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded mt-2">
                                <strong>Quick Actions:</strong>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success" id="modalAddAllOne">
                                        <i class="fas fa-plus me-1"></i> +1 All
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" id="modalSubtractAllOne">
                                        <i class="fas fa-minus me-1"></i> -1 All
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="modalClearAll">
                                        <i class="fas fa-eraser me-1"></i> Reset All
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="modalLoadingStudents" class="text-center py-4" style="display: none;">
                            <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                            <p class="mt-2">Loading students...</p>
                        </div>
                    </div>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    {{-- <a href="#" id="goToFullPage" class="btn btn-outline-success" style="display: none;">
                        <i class="fas fa-external-link-alt me-1"></i> Open Full Page
                    </a> --}}
                    <button type="button" class="btn btn-success" id="modalSaveScores" style="display: none;">
                        <i class="fas fa-s
                </div>
                <div class="modal-footerave me-1"></i> Save Scores
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            let table = $('#classTable').DataTable({
                responsive: true,
                order: [
                    [0, 'asc'],
                    [1, 'asc']
                ],
                pageLength: 25,
            });

            // Modal state
            let currentClassId = null;
            let currentSubjectId = null;
            let currentSubjectName = null;

            // Handle grade level change to load sections
            const gradeLevelSelect = document.getElementById('grade_level_id');
            const sectionSelect = document.getElementById('section_id');

            gradeLevelSelect.addEventListener('change', function() {
                const gradeLevelId = this.value;

                // Reset section dropdown
                sectionSelect.innerHTML = '<option value="">All Sections</option>';

                if (!gradeLevelId) {
                    sectionSelect.disabled = true;
                    return;
                }

                // Fetch sections for the selected grade level
                fetch(`{{ route('teacher.oral-participation.sections') }}?grade_level_id=${gradeLevelId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.sections && data.sections.length > 0) {
                            data.sections.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section.id;
                                option.textContent = section.name;
                                sectionSelect.appendChild(option);
                            });
                            sectionSelect.disabled = false;
                        } else {
                            sectionSelect.disabled = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching sections:', error);
                        sectionSelect.disabled = true;
                    });
            });

            // Handle subject modal - show subject list
            document.querySelectorAll('.subject-modal-btn').forEach(button => {
                button.addEventListener('click', function() {
                    currentClassId = this.dataset.classId;
                    const className = this.dataset.className;

                    document.getElementById('modalClassName').textContent = className;
                    resetModalToStep1();

                    const subjectsList = document.getElementById('subjectsList');
                    subjectsList.innerHTML =
                        '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading subjects...</div>';

                    // Fetch subjects for the class
                    fetch(`/teacher/classes/${currentClassId}/subjects`)
                        .then(response => response.json())
                        .then(data => {
                            subjectsList.innerHTML = '';
                            if (data.subjects && data.subjects.length > 0) {
                                data.subjects.forEach(subject => {
                                    const btn = document.createElement('button');
                                    btn.type = 'button';
                                    btn.className =
                                        'btn btn-outline-success text-start subject-select-btn';
                                    btn.dataset.subjectId = subject.id;
                                    btn.dataset.subjectName = subject.name;
                                    btn.innerHTML =
                                        `<i class="fas fa-book me-2"></i>${subject.name}`;
                                    btn.addEventListener('click', () => selectSubject(
                                        subject.id, subject.name));
                                    subjectsList.appendChild(btn);
                                });
                            } else {
                                subjectsList.innerHTML =
                                    '<p class="text-muted">No subjects found for this class.</p>';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            subjectsList.innerHTML =
                                '<p class="text-danger">Error loading subjects.</p>';
                        });
                });
            });

            function resetModalToStep1() {
                document.getElementById('step1SubjectSelection').style.display = 'block';
                document.getElementById('step2ScoreEntry').style.display = 'none';
                document.getElementById('modalStudentsList').style.display = 'none';
                document.getElementById('modalLoadingStudents').style.display = 'none';
                document.getElementById('modalSaveScores').style.display = 'none';
                const goToFullPage = document.getElementById('goToFullPage');
                if (goToFullPage) goToFullPage.style.display = 'none';
                document.getElementById('totalPointsDisplay').textContent = '0';
                currentSubjectId = null;
                currentSubjectName = null;
            }

            function selectSubject(subjectId, subjectName) {
                currentSubjectId = subjectId;
                currentSubjectName = subjectName;

                document.getElementById('step1SubjectSelection').style.display = 'none';
                document.getElementById('step2ScoreEntry').style.display = 'block';
                document.getElementById('selectedSubjectBadge').textContent = subjectName;
                const goToFullPage = document.getElementById('goToFullPage');
                if (goToFullPage) {
                    goToFullPage.href = `/teacher/oral-participation/${currentClassId}?subject_id=${subjectId}`;
                    goToFullPage.style.display = 'inline-block';
                }

                // Auto-load students when subject is selected
                loadStudents();
            }

            // Back to subjects button
            document.getElementById('backToSubjects').addEventListener('click', resetModalToStep1);

            // Quarter change - reload students
            document.getElementById('modalQuarter').addEventListener('change', function() {
                if (currentClassId && currentSubjectId) {
                    loadStudents();
                }
            });

            function loadStudents() {
                if (!currentClassId || !currentSubjectId) return;

                const quarter = document.getElementById('modalQuarter').value;
                document.getElementById('modalStudentsList').style.display = 'none';
                document.getElementById('modalLoadingStudents').style.display = 'block';

                // Fetch students and their existing scores
                fetch(
                        `/teacher/oral-participation/${currentClassId}/students?subject_id=${currentSubjectId}&quarter=${quarter}`
                    )
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('modalLoadingStudents').style.display = 'none';
                        document.getElementById('modalStudentsList').style.display = 'block';
                        document.getElementById('modalSaveScores').style.display = 'inline-block';

                        const tbody = document.getElementById('modalStudentsBody');
                        tbody.innerHTML = '';

                        if (data.students && data.students.length > 0) {
                            data.students.forEach(student => {
                                const currentScore = student.score !== null ? parseFloat(student
                                    .score) : 0;
                                const tr = document.createElement('tr');
                                tr.dataset.studentId = student.id;
                                tr.innerHTML = `
                                    <td>${student.last_name}, ${student.first_name}</td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger score-decrement" data-student-id="${student.id}">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm modal-score-input text-center"
                                                data-student-id="${student.id}"
                                                value="${currentScore}"
                                                min="0" step="1" style="width: 60px;">
                                            <button type="button" class="btn btn-sm btn-outline-success score-increment" data-student-id="${student.id}">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                `;
                                tbody.appendChild(tr);
                            });

                            // Update total points display
                            updateTotalPoints();
                        } else {
                            tbody.innerHTML =
                                '<tr><td colspan="2" class="text-center text-muted">No students found</td></tr>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading students:', error);
                        document.getElementById('modalLoadingStudents').style.display = 'none';
                        alert('Error loading students. Please try again.');
                    });
            }

            // Increment score
            $(document).on('click', '.score-increment', function() {
                const studentId = $(this).data('student-id');
                const $input = $(`.modal-score-input[data-student-id="${studentId}"]`);
                let currentVal = parseFloat($input.val()) || 0;
                $input.val(currentVal + 1);
                updateTotalPoints();
            });

            // Decrement score
            $(document).on('click', '.score-decrement', function() {
                const studentId = $(this).data('student-id');
                const $input = $(`.modal-score-input[data-student-id="${studentId}"]`);
                let currentVal = parseFloat($input.val()) || 0;
                if (currentVal > 0) {
                    $input.val(currentVal - 1);
                    updateTotalPoints();
                }
            });

            // Update total when score changes manually
            $(document).on('input change', '.modal-score-input', function() {
                let val = parseFloat($(this).val()) || 0;
                if (val < 0) {
                    $(this).val(0);
                }
                updateTotalPoints();
            });

            function updateTotalPoints() {
                let total = 0;
                $('.modal-score-input').each(function() {
                    const score = parseFloat($(this).val()) || 0;
                    total += score;
                });
                $('#totalPointsDisplay').text(total);
            }

            // Add +1 to all
            $('#modalAddAllOne').click(function() {
                $('.modal-score-input').each(function() {
                    let currentVal = parseFloat($(this).val()) || 0;
                    $(this).val(currentVal + 1);
                });
                updateTotalPoints();
            });

            // Subtract 1 from all
            $('#modalSubtractAllOne').click(function() {
                $('.modal-score-input').each(function() {
                    let currentVal = parseFloat($(this).val()) || 0;
                    if (currentVal > 0) {
                        $(this).val(currentVal - 1);
                    }
                });
                updateTotalPoints();
            });

            // Reset all to 0
            $('#modalClearAll').click(function() {
                $('.modal-score-input').val(0);
                updateTotalPoints();
            });

            // Save scores
            $('#modalSaveScores').click(function() {
                const quarter = $('#modalQuarter').val();
                const scores = [];
                let maxScore = 0;

                // Collect scores and find max score dynamically
                $('.modal-score-input').each(function() {
                    const studentId = $(this).data('student-id');
                    const score = parseFloat($(this).val()) || 0;
                    scores.push({
                        student_id: studentId,
                        score: score
                    });
                    if (score > maxScore) {
                        maxScore = score;
                    }
                });

                if (scores.length === 0) {
                    alert('No students found!');
                    return;
                }

                // Set max score to highest score (minimum 1)
                if (maxScore < 1) maxScore = 1;

                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

                $.ajax({
                    url: `/teacher/oral-participation/${currentClassId}/quick-save`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        subject_id: currentSubjectId,
                        quarter: quarter,
                        max_score: maxScore,
                        scores: scores
                    },
                    success: function(response) {
                        alert('Oral participation scores saved successfully!');
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-1"></i> Save Scores');
                        $('#subjectModal').modal('hide');
                    },
                    error: function(xhr) {
                        alert('Error saving scores: ' + (xhr.responseJSON?.message ||
                            'Unknown error'));
                        $btn.prop('disabled', false).html(
                            '<i class="fas fa-save me-1"></i> Save Scores');
                    }
                });
            });

            // Reset modal when closed
            $('#subjectModal').on('hidden.bs.modal', function() {
                resetModalToStep1();
            });
        });
    </script>
@endpush
