@extends('base')

@section('title', 'My Classes')


@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Classes</h1>
        </div>

        @if (isset($error))
            <div class="alert alert-warning">{{ $error }}</div>
        @elseif($classes->isEmpty())
            <div class="alert alert-info">You are not assigned to any classes for the current school year.</div>
        @else
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="classTable" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Grade</th>
                                    <th>Section Name</th>
                                    <th>Adviser</th>
                                    <th>My Subjects in this Class</th>
                                    <th class="text-center">Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classes as $class)
                                    <tr>
                                        <td>
                                            {{ $class->section->gradeLevel->name }}

                                        </td>
                                        <td>

                                            {{ $class->section->name }}
                                        </td>
                                        <td>
                                            @if ($class->teacher)
                                                {{ $class->teacher->user->first_name }}
                                                {{ $class->teacher->user->last_name }}
                                                @if ($class->teacher_id == $teacher->id)
                                                    <span class="badge bg-primary ms-2">You</span>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                // Filter schedules to get subjects taught by the current teacher in this specific class
                                                $subjectsInClass = $teacher->schedules
                                                    ->where('class_id', $class->id)
                                                    ->map(fn($schedule) => $schedule->subject);
                                            @endphp
                                            @forelse($subjectsInClass->unique('id') as $subject)
                                                <span class="badge bg-info">{{ $subject->name }}</span>
                                            @empty
                                                <span class="badge bg-secondary">None</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center">{{ $class->enrollments->count() }} / {{ $class->capacity }}
                                        </td>
                                        <td>
                                            @php
                                                $classSchedules = $teacher->schedules->where('class_id', $class->id);
                                                $uniqueSubjects = $classSchedules
                                                    ->map(fn($schedule) => $schedule->subject)
                                                    ->unique('id');
                                                $subjectsCount = $uniqueSubjects->count();
                                                $singleSubject = $subjectsCount === 1 ? $uniqueSubjects->first() : null;
                                            @endphp
                                            @if ($subjectsCount > 1)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary d-flex align-items-center subject-modal-btn"
                                                    data-bs-toggle="modal" data-bs-target="#subjectModal"
                                                    data-class-id="{{ $class->id }}"
                                                    data-class-name="{{ $class->section->gradeLevel->name }} - {{ $class->section->name }}">
                                                    <i data-feather="edit-3" class="me-1"></i>
                                                    <span>Assessments</span>
                                                </button>
                                            @else
                                                <a href="{{ route('teacher.assessments.index', $class) }}@if ($singleSubject) ?subject_id={{ $singleSubject->id }} @endif"
                                                    class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                                    <i data-feather="edit-3" class="me-1"></i>
                                                    <span>Assessments</span>
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
    </div>

    <!-- Subject Selection Modal -->
    <div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subjectModalLabel">Select Subject for Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please select which subject you want to manage assessments for:</p>
                    <div class="mb-3">
                        <strong>Class: <span id="modalClassName"></span></strong>
                    </div>
                    <div id="subjectsList" class="d-grid gap-2">
                        <!-- Subjects will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
            let table = $('#classTable').DataTable({
                responsive: true,
            });

            // Handle subject modal
            const subjectModal = document.getElementById('subjectModal');
            const modalClassName = document.getElementById('modalClassName');
            const subjectsList = document.getElementById('subjectsList');

            // Add click event listeners to all subject modal buttons
            document.querySelectorAll('.subject-modal-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const classId = this.dataset.classId;
                    const className = this.dataset.className;

                    // Set the class name in modal
                    modalClassName.textContent = className;

                    // Fetch subjects for this class
                    fetchSubjectsForClass(classId);
                });
            });

            function fetchSubjectsForClass(classId) {
                // Clear existing subjects
                subjectsList.innerHTML =
                    '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></div>';

                // Fetch subjects from the server
                fetch(`/teacher/classes/${classId}/subjects`)
                    .then(response => response.json())
                    .then(data => {
                        subjectsList.innerHTML = '';

                        if (data.subjects && data.subjects.length > 0) {
                            data.subjects.forEach(subject => {
                                const subjectButton = document.createElement('a');
                                subjectButton.href =
                                    `/teacher/classes/assessments/${classId}?subject_id=${subject.id}`;
                                subjectButton.className = 'btn btn-outline-primary text-start';
                                const timeRange = (subject.start_time && subject.end_time) ?
                                    `${subject.start_time}-${subject.end_time}` : '';
                                const roomInfo = subject.room ? `Room ${subject.room}` : '';
                                subjectButton.innerHTML =
                                    `<i data-feather="book-open" class="me-2"></i>${subject.name}<br><small class="text-muted">${subject.days || ''} ${timeRange} ${roomInfo}</small>`;
                                subjectsList.appendChild(subjectButton);
                            });

                            // Re-initialize feather icons for new buttons
                            feather.replace();
                        } else {
                            subjectsList.innerHTML =
                                '<div class="alert alert-info">No subjects found for this class.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching subjects:', error);
                        subjectsList.innerHTML =
                            '<div class="alert alert-danger">Error loading subjects. Please try again.</div>';
                    });
            }
        });
    </script>
@endpush
