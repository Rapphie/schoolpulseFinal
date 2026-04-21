@extends('base')

@section('title', 'Manage Subjects')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">Subjects</a></li>
                    <li class="breadcrumb-item active" aria-current="page">List</li>
                </ol>
            </nav>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="subjectTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix"
                        type="button" role="tab" aria-controls="matrix" aria-selected="true">Subject Assignment</button>
                </li>
            </ul>

            <div class="tab-content mt-3" id="subjectTabsContent">
                <div class="tab-pane fade show active" id="matrix" role="tabpanel" aria-labelledby="matrix-tab">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i data-feather="search"></i></span>
                                <input type="text" class="form-control border-start-0" id="searchMatrix"
                                    placeholder="Search grade level subjects...">
                            </div>
                        </div>
                         <div class="d-flex flex-column gap-2">
                            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center"
                                                            data-bs-toggle="modal" data-bs-target="#assignSubjectModal">
                                                            <i data-feather="plus" class="me-1"></i>Assign Subject
                            </button>
                            <button type="button" class="btn btn-success btn-sm d-flex align-items-center"
                                                            data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                <i data-feather="plus" class="me-1"></i>Create Subject
                            </button>
                         </div>
                    </div>

                    <ul class="nav nav-pills mb-3" id="gradeLevelMatrixTabs" role="tablist">
                        @foreach ($gradeLevels as $index => $gradeLevel)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                    id="grade-tab-{{ $gradeLevel->id }}" data-bs-toggle="tab"
                                    data-bs-target="#grade-pane-{{ $gradeLevel->id }}" type="button" role="tab"
                                    aria-controls="grade-pane-{{ $gradeLevel->id }}"
                                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                    {{ $gradeLevel->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content" id="gradeLevelMatrixTabsContent">
                        @foreach ($gradeLevels as $index => $gradeLevel)
                            @php
                                $gradeRows = $gradeLevelSubjects
                                    ->where('grade_level_id', $gradeLevel->id)
                                    ->sortBy([
                                        fn ($gradeLevelSubject) => $gradeLevelSubject->is_active ? 0 : 1,
                                        fn ($gradeLevelSubject) => mb_strtolower((string) $gradeLevelSubject->subject?->name),
                                    ])
                                    ->values();
                            @endphp
                            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                id="grade-pane-{{ $gradeLevel->id }}" role="tabpanel"
                                aria-labelledby="grade-tab-{{ $gradeLevel->id }}">
                                <div class="table-responsive">
                                    <table class="table table-bordered matrix-grade-table" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Code</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($gradeRows as $gls)
                                                <tr class="{{ $gls->is_active ? '' : 'table-secondary' }}">
                                                    <td>{{ $gls->subject->name }}</td>
                                                    <td>{{ $gls->subject->code }}</td>
                                                    <td>
                                                        <span class="badge rounded-pill bg-{{ $gls->is_active ? 'success' : 'danger' }}">
                                                            {{ $gls->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-center align-items-start">
                                                            <button type="button"
                                                                class="btn btn-{{ $gls->is_active ? 'warning' : 'success' }} btn-sm mx-1 toggle-status-btn"
                                                                data-id="{{ $gls->id }}" data-status="{{ $gls->is_active }}"
                                                                title="{{ $gls->is_active ? 'Deactivate' : 'Activate' }}">
                                                                <i data-feather="{{ $gls->is_active ? 'x-circle' : 'check-circle' }}" class="feather-sm"></i>
                                                            </button>

                                                <button type="button" class="btn btn-primary btn-sm mx-1 edit-catalog-btn"
                                                    data-bs-toggle="modal" data-bs-target="#editSubjectModal" title="Edit"
                                                    data-id="{{ $gls->subject->id }}" data-name="{{ $gls->subject->name }}"
                                                    data-code="{{ $gls->subject->code }}"
                                                    data-description="{{ $gls->subject->description ?? '' }}">
                                                    <i data-feather="edit-2" class="feather-sm"></i>
                                                </button>
                                                <form action="{{ route('admin.subjects.destroy', $gls->subject->id) }}" method="POST"
                                                    class="mx-1"
                                                    onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                        <i data-feather="trash-2" class="feather-sm"></i>
                                                    </button>
                                                </form>

                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No subjects assigned to this grade level yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Create Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSubjectForm" action="{{ route('admin.subjects.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" id="subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" id="subject_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject_description" class="form-label">Description</label>
                            <textarea name="description" class="form-control" id="subject_description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-flex align-items-center" data-bs-dismiss="modal">
                        <i data-feather="x" class="me-2"></i> Cancel
                    </button>
                    <button type="submit" form="addSubjectForm" class="btn btn-primary d-flex align-items-center">
                        <i data-feather="save" class="me-2"></i> Save Subject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Subject Modal -->
    <div class="modal fade" id="assignSubjectModal" tabindex="-1" aria-labelledby="assignSubjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignSubjectModalLabel">Assign Subject to Grade Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="assignSubjectForm" action="{{ route('admin.subject-assignments.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="assign_grade_level_id" class="form-label">Grade Level <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assign_grade_level_id" name="grade_level_id" required>
                                <option value="" disabled {{ $selectedGradeLevel ? '' : 'selected' }}>-- Select a Grade Level --</option>
                                @foreach ($gradeLevels as $gradeLevel)
                                    <option value="{{ $gradeLevel->id }}"
                                        {{ (int) $selectedGradeLevel === (int) $gradeLevel->id ? 'selected' : '' }}>
                                        {{ $gradeLevel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="assign_subject_id" class="form-label">Subject <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="assign_subject_id" name="subject_id" required>
                                <option value="" disabled selected>-- Select a Subject --</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-flex align-items-center"
                        data-bs-dismiss="modal">
                        <i data-feather="x" class="me-2"></i> Cancel
                    </button>
                    <button type="submit" form="assignSubjectForm"
                        class="btn btn-primary d-flex align-items-center">
                        <i data-feather="save" class="me-2"></i> Assign Subject
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSubjectForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_subject_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Subject Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_code" class="form-label">Subject Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-flex align-items-center"
                        data-bs-dismiss="modal">
                        <i data-feather="x" class="me-2"></i> Cancel
                    </button>
                    <button type="submit" form="editSubjectForm" class="btn btn-primary d-flex align-items-center">
                        <i data-feather="save" class="me-2"></i> Update Subject
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gradeMatrixTabStorageKey = 'admin-subjects-active-grade-tab';
            const subjectUpdateUrlTemplate = @json(route('admin.subjects.update', ['subject' => '__SUBJECT_ID__']));

            const restoreTab = function(containerSelector, storageKey) {
                const savedTarget = sessionStorage.getItem(storageKey);
                if (!savedTarget) {
                    return;
                }

                const savedTabTrigger = document.querySelector(
                    `${containerSelector} [data-bs-toggle="tab"][data-bs-target="${savedTarget}"]`
                );

                if (savedTabTrigger) {
                    bootstrap.Tab.getOrCreateInstance(savedTabTrigger).show();
                }
            };

            document.querySelectorAll('#gradeLevelMatrixTabs [data-bs-toggle="tab"]').forEach(function(tabTrigger) {
                tabTrigger.addEventListener('shown.bs.tab', function(event) {
                    sessionStorage.setItem(gradeMatrixTabStorageKey, event.target.getAttribute('data-bs-target'));
                });
            });

            restoreTab('#gradeLevelMatrixTabs', gradeMatrixTabStorageKey);

            $('#searchMatrix').on('keyup', function() {
                const query = this.value.toLowerCase().trim();

                $('.matrix-grade-table tbody tr').each(function() {
                    const $row = $(this);
                    const isEmptyStateRow = $row.find('td').length === 1;

                    if (isEmptyStateRow) {
                        $row.show();
                        return;
                    }

                    const rowText = $row.text().toLowerCase();
                    $row.toggle(rowText.includes(query));
                });
            });

            $('.toggle-status-btn').on('click', function() {
                const id = $(this).data('id');
                const currentStatus = $(this).data('status');
                const newStatus = !currentStatus;

                fetch(`/admin/subject-assignments/${id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        is_active: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the status.');
                });
            });

            $('.edit-catalog-btn').on('click', function() {
                const subjectId = $(this).data('id');
                const formAction = subjectUpdateUrlTemplate.replace('__SUBJECT_ID__', subjectId);

                $('#editSubjectForm').attr('action', formAction);
                $('#edit_subject_id').val(subjectId);
                $('#edit_name').val($(this).data('name') || '');
                $('#edit_code').val($(this).data('code') || '');
                $('#edit_description').val($(this).data('description') || '');
            });

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openModal') === 'true') {
                const assignSubjectModal = new bootstrap.Modal(document.getElementById('assignSubjectModal'));
                assignSubjectModal.show();

                const url = new URL(window.location);
                url.searchParams.delete('openModal');
                window.history.pushState({}, '', url);
            }

            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            $('.modal').on('shown.bs.modal', function() {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });

        });
    </script>
@endpush
