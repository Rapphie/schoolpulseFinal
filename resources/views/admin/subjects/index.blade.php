@extends('base')

@section('title', 'Manage Subjects')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">Subjects</a></li>
                    <li class="breadcrumb-item active" aria-current="page">List</li>
                </ol>
            </nav>
            <div>
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center me-2" data-bs-toggle="modal"
                    data-bs-target="#assignSubjectModal">
                    <i data-feather="plus" class="me-1"></i>Assign Subject to Grade Level
                </button>
                <button type="button" class="btn btn-success btn-sm d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#addSubjectModal">
                    <i data-feather="plus" class="me-1"></i>Create Catalog Subject
                </button>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <ul class="nav nav-tabs" id="subjectTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="matrix-tab" data-bs-toggle="tab" data-bs-target="#matrix"
                        type="button" role="tab" aria-controls="matrix" aria-selected="true">Grade Level Matrix</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="catalog-tab" data-bs-toggle="tab" data-bs-target="#catalog"
                        type="button" role="tab" aria-controls="catalog" aria-selected="false">Subject Catalog</button>
                </li>
            </ul>
            <div class="tab-content mt-3" id="subjectTabsContent">
                <div class="tab-pane fade show active" id="matrix" role="tabpanel" aria-labelledby="matrix-tab">
                    <div class="table-responsive">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i data-feather="search"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchMatrix"
                                        placeholder="Search...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="matrixGradeLevelFilter">
                                    <option value="">All Grade Levels</option>
                                    @foreach ($gradeLevels as $gradeLevel)
                                        <option value="{{ $gradeLevel->name }}">{{ $gradeLevel->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="matrixStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <table class="table table-bordered" id="matrixTable" width="100%">
                            <thead>
                                <tr>
                                    <th>Grade Level</th>
                                    <th>Subject</th>
                                    <th>Code</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gradeLevelSubjects as $gls)
                                    <tr class="{{ $gls->is_active ? '' : 'table-secondary' }}">
                                        <td>{{ $gls->gradeLevel->name }}</td>
                                        <td>{{ $gls->subject->name }}</td>
                                        <td>{{ $gls->subject->code }}</td>
                                        <td>{{ $gls->subject->duration_minutes ?? 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="badge rounded-pill bg-{{ $gls->is_active ? 'success' : 'danger' }}">
                                                {{ $gls->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center align-items-start">
                                                <button type="button"
                                                    class="btn btn-{{ $gls->is_active ? 'warning' : 'success' }} btn-sm mx-1 toggle-status-btn"
                                                    data-id="{{ $gls->id }}" data-status="{{ $gls->is_active }}"
                                                    title="{{ $gls->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <i data-feather="{{ $gls->is_active ? 'x-circle' : 'check-circle' }}"
                                                        class="feather-sm"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="catalog" role="tabpanel" aria-labelledby="catalog-tab">
                    <div class="table-responsive">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i data-feather="search"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchCatalog"
                                        placeholder="Search subjects...">
                                </div>
                            </div>
                        </div>
                        <table class="table table-bordered" id="catalogTable" width="100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subjects as $subject)
                                    <tr>
                                        <td>{{ $subject->id }}</td>
                                        <td>{{ $subject->name }}</td>
                                        <td>{{ $subject->code }}</td>
                                        <td>{{ $subject->description ?? 'N/A' }}</td>
                                        <td>{{ $subject->duration_minutes ?? 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center align-items-start">
                                                <button type="button" class="btn btn-primary btn-sm mx-1 edit-catalog-btn"
                                                    data-bs-toggle="modal" data-bs-target="#editSubjectModal" title="Edit"
                                                    data-id="{{ $subject->id }}" data-name="{{ $subject->name }}"
                                                    data-code="{{ $subject->code }}"
                                                    data-description="{{ $subject->description ?? '' }}"
                                                    data-duration_minutes="{{ $subject->duration_minutes ?? '' }}">
                                                    <i data-feather="edit-2" class="feather-sm"></i>
                                                </button>
                                                <form action="{{ route('admin.subjects.destroy', $subject->id) }}"
                                                    method="POST" class="mx-1"
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
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Catalog Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Create Catalog Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSubjectForm" action="{{ route('admin.subjects.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="subject_name" class="form-label">Subject Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" id="subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject_code" class="form-label">Subject Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" id="subject_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject_description" class="form-label">Description</label>
                            <textarea name="description" class="form-control" id="subject_description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="subject_duration" class="form-label">Duration (minutes)</label>
                            <input type="number" name="duration_minutes" class="form-control" id="subject_duration"
                                min="15" max="480" step="15" placeholder="e.g. 60, 90, 120">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-flex align-items-center"
                        data-bs-dismiss="modal">
                        <i data-feather="x" class="me-2"></i> Cancel
                    </button>
                    <button type="submit" form="addSubjectForm"
                        class="btn btn-primary d-flex align-items-center">
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
                        <div class="mb-3">
                            <label for="edit_duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration_minutes"
                                min="15" max="480" step="15" placeholder="e.g. 60, 90, 120">
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
            const matrixTable = $('#matrixTable').DataTable({
                responsive: true,
                order: [
                    [0, 'asc'],
                    [1, 'asc']
                ],
                dom: 'lrtip',
                columnDefs: [{
                    orderable: false,
                    targets: [5]
                }]
            });

            const catalogTable = $('#catalogTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ],
                dom: 'lrtip',
                columnDefs: [{
                    orderable: false,
                    targets: [5]
                }]
            });

            $('#searchMatrix').on('keyup', function() {
                matrixTable.search(this.value).draw();
            });

            $('#matrixGradeLevelFilter').on('change', function() {
                matrixTable.column(0).search(this.value).draw();
            });

            $('#matrixStatusFilter').on('change', function() {
                matrixTable.column(4).search(this.value).draw();
            });

            $('#searchCatalog').on('keyup', function() {
                catalogTable.search(this.value).draw();
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

            $(document).on('click', '.edit-catalog-btn', function() {
                const button = $(this);
                const id = button.data('id');
                const name = button.data('name');
                const code = button.data('code');
                const description = button.data('description');
                const durationMinutes = button.data('duration_minutes');

                $('#editSubjectForm').attr('action', '/admin/subjects/' + id);
                $('#edit_subject_id').val(id);
                $('#edit_name').val(name);
                $('#edit_code').val(code);
                $('#edit_description').val(description);
                $('#edit_duration').val(durationMinutes || '');
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
