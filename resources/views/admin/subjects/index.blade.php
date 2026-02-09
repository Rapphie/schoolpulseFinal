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
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal"
                data-bs-target="#addSubjectModal">
                <i data-feather="plus" class="me-1"></i>Add Subject
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i data-feather="search"></i></span>
                            <input type="text" class="form-control border-start-0" id="searchSubject"
                                placeholder="Search subjects...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="gradeLevelFilter">
                            <option value="">All Grade Levels</option>
                            @foreach ($gradeLevels as $gradeLevel)
                                <option value="{{ $gradeLevel->name }}">{{ $gradeLevel->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <table class="table table-bordered" id="subjectsTable" width="100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Grade Level</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjects as $subject)
                            <tr>
                                <td>{{ $subject->id }}</td>
                                <td>{{ $subject->name }}</td>
                                <td>{{ $subject->code }}</td>
                                <td>{{ $subject->gradeLevel->name }}</td>
                                <td>{{ $subject->description ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge rounded-pill bg-{{ $subject->is_active ? 'success' : 'danger' }}">
                                        {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-start">
                                        <button type="button" class="btn btn-info btn-sm mx-1 view-subject-btn"
                                            data-bs-toggle="modal" data-bs-target="#viewSubjectModal" title="View"
                                            data-id="{{ $subject->id }}" data-name="{{ $subject->name }}"
                                            data-code="{{ $subject->code }}"
                                            data-description="{{ $subject->description ?? 'N/A' }}"
                                            data-is_active="{{ $subject->is_active }}"
                                            data-grade_level_id="{{ $subject->grade_level_id }}">
                                            <i data-feather="eye" class="feather-sm text-white"></i>
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm mx-1 edit-subject-btn"
                                            data-bs-toggle="modal" data-bs-target="#editSubjectModal" title="Edit"
                                            data-id="{{ $subject->id }}" data-name="{{ $subject->name }}"
                                            data-code="{{ $subject->code }}"
                                            data-description="{{ $subject->description ?? '' }}"
                                            data-is_active="{{ $subject->is_active }}"
                                            data-grade_level_id="{{ $subject->grade_level_id }}"
                                            data-duration_minutes="{{ $subject->duration_minutes ?? '' }}">
                                            <i data-feather="edit-2" class="feather-sm"></i>
                                        </button>
                                        <form action="{{ route('admin.subjects.destroy', $subject->id) }}" method="POST"
                                            class="mx-1"
                                            onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip"
                                                title="Delete">
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

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSubjectForm" action="{{ route('admin.subjects.store') }}" method="POST">
                        @csrf
                        {{-- Step 1: Select Grade Level --}}
                        <div class="mb-3">
                            <label for="grade_level_id" class="form-label">Assign to Grade Level <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="grade_level_id" name="grade_level_id" required>
                                <option value="" disabled selected>-- Select a Grade Level --</option>
                                @foreach ($gradeLevels as $gradeLevel)
                                    <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="grade_level_id_error"></div>
                        </div>

                        <hr>

                        {{-- Step 2: Add Subjects --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Subject Entries</h6>
                            <button type="button" id="add-subject-entry-btn"
                                class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                <i data-feather="plus" class="me-1 feather-sm"></i> Add Row
                            </button>
                        </div>

                        <div id="subject-entries-container">
                            <div class="row subject-entry mb-2">
                                <div class="col-md-4">
                                    <input type="text" name="subjects[0][name]" class="form-control"
                                        placeholder="Subject Name" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="subjects[0][code]" class="form-control"
                                        placeholder="Subject Code" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="subjects[0][duration_minutes]" class="form-control"
                                        placeholder="Duration (mins)" min="15" max="480" step="15"
                                        title="Duration in minutes (e.g. 60, 90, 120)">
                                </div>
                                <div class="col-md-2">
                                    {{-- The first row doesn't have a remove button --}}
                                </div>
                            </div>
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

    <!-- View Subject Modal -->
    <div class="modal fade" id="viewSubjectModal" tabindex="-1" aria-labelledby="viewSubjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewSubjectModalLabel">Subject Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Subject ID:</strong></div>
                        <div class="col-md-8" id="view_subject_id"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Subject Name:</strong></div>
                        <div class="col-md-8" id="view_name"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Subject Code:</strong></div>
                        <div class="col-md-8" id="view_code"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Description:</strong></div>
                        <div class="col-md-8" id="view_description"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Status:</strong></div>
                        <div class="col-md-8" id="view_status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-flex align-items-center" data-bs-dismiss="modal">
                        <i data-feather="x"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editSubjectForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_subject_id" name="edit_subject_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Subject Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <div class="invalid-feedback" id="edit_name_error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_code" class="form-label">Subject Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_code" name="code" required>
                            <div class="invalid-feedback" id="edit_code_error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            <div class="invalid-feedback" id="edit_description_error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_grade_level_id" class="form-label">Assign to Grade Level <span
                                    class="text-danger">*</span></label>
                            <select class="form-control" id="edit_grade_level_id" name="grade_level_id" required>
                                <option value="">Select Grade Level</option>
                                @foreach ($gradeLevels as $gradeLevel)
                                    <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="edit_grade_level_id_error"></div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active">
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                        <div class="mb-3">
                            <label for="edit_duration_minutes" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="edit_duration_minutes"
                                name="duration_minutes" min="15" max="480" step="15"
                                placeholder="e.g. 60, 90, 120">
                            <small class="form-text text-muted">Leave blank if duration can vary. Used to auto-calculate
                                end time in schedules.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-flex align-items-center" data-bs-dismiss="modal">
                        <i data-feather="x"></i> Cancel
                    </button>
                    <button type="submit" form="editSubjectForm" class="btn btn-primary d-flex align-items-center">
                        <i data-feather="save"></i> Update Subject
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let subjectIndex = 1; // Start index for new rows, since 0 is already in the HTML

            $('#add-subject-entry-btn').on('click', function() {
                const newEntryHtml = `
                    <div class="row subject-entry mb-2">
                        <div class="col-md-4">
                            <input type="text" name="subjects[${subjectIndex}][name]" class="form-control" placeholder="Subject Name" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="subjects[${subjectIndex}][code]" class="form-control" placeholder="Subject Code" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="subjects[${subjectIndex}][duration_minutes]" class="form-control" placeholder="Duration (mins)" min="15" max="480" step="15" title="Duration in minutes">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-sm remove-subject-entry-btn w-100">Remove</button>
                        </div>
                    </div>
                `;
                $('#subject-entries-container').append(newEntryHtml);
                subjectIndex++; // Increment index for the next row
            });

            // Use event delegation to handle remove button clicks
            $('#subject-entries-container').on('click', '.remove-subject-entry-btn', function() {
                $(this).closest('.subject-entry').remove();
            });

            // Ensure only one modal is open at a time
            $('.modal').on('show.bs.modal', function() {
                $('.modal').not(this).modal('hide');
            });

            // Initialize DataTable
            const table = $('#subjectsTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ],
                dom: 'lrtip', // Removes the default search box
                columnDefs: [{
                    orderable: true,
                    targets: '_all'
                }, {
                    orderable: false,
                    targets: [6] // Actions column is not sortable
                }]
            });

            // Connect the custom search box to DataTable
            $('#searchSubject').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Connect the grade level filter
            $('#gradeLevelFilter').on('change', function() {
                let value = $(this).val();
                table.column(3).search(value).draw();
            });

            // Connect the status filter
            $('#statusFilter').on('change', function() {
                let value = $(this).val();
                table.column(5).search(value).draw();
            });

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize Feather Icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            // Re-initialize feather icons when modals are shown
            $('#addSubjectModal, #viewSubjectModal, #editSubjectModal').on('shown.bs.modal', function() {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });

            // Check if URL has openModal parameter and open the modal automatically
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openModal') === 'true') {
                const addSubjectModal = new bootstrap.Modal(document.getElementById('addSubjectModal'));
                addSubjectModal.show();

                // Clear the URL parameter without refreshing the page
                const url = new URL(window.location);
                url.searchParams.delete('openModal');
                window.history.pushState({}, '', url);
            }

            // View subject button click event
            $(document).on('click', '.view-subject-btn', function() {
                const button = $(this);
                const id = button.data('id');
                const name = button.data('name');
                const code = button.data('code');
                const description = button.data('description');
                const isActive = button.data('is_active');

                // Fill the view modal fields
                $('#view_subject_id').text(id);
                $('#view_name').text(name);
                $('#view_code').text(code);
                $('#view_description').text(description);
                $('#view_status').html('<span class="badge rounded-pill bg-' + (isActive ? 'success' :
                    'danger') + '">' + (isActive ? 'Active' : 'Inactive') + '</span>');


                // Show the modal using getOrCreateInstance
                const modalElement = document.getElementById('viewSubjectModal');
                const viewSubjectModal = bootstrap.Modal.getOrCreateInstance(modalElement);
                viewSubjectModal.show();
            });

            // Edit subject button click event
            $(document).on('click', '.edit-subject-btn', function() {
                const button = $(this);
                const id = button.data('id');
                const name = button.data('name');
                const code = button.data('code');
                const description = button.data('description');
                const isActive = button.data('is_active');
                const durationMinutes = button.data('duration_minutes');

                // Set the form action URL
                $('#editSubjectForm').attr('action', '/admin/subjects/' + id);

                // Fill the form fields
                $('#edit_subject_id').val(id);
                $('#edit_name').val(name);
                $('#edit_code').val(code);
                $('#edit_description').val(description);
                $('#edit_is_active').prop('checked', isActive);
                $('#edit_duration_minutes').val(durationMinutes || '');

                // Show the modal using getOrCreateInstance
                const modalElement = document.getElementById('editSubjectModal');
                const editSubjectModal = bootstrap.Modal.getOrCreateInstance(modalElement);
                editSubjectModal.show();
            });
        });
    </script>
@endpush
