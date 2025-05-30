@extends('admin.layout')

@section('title', 'Manage Subjects')

@section('content')
    <main class="p-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Subjects List</h6>
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#addSubjectModal">
                    <i data-feather="plus" class="me-1"></i>Add New Subject
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="subjectsTable" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Units</th>
                                <th>Hours/Week</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subjects as $subject)
                                <tr>
                                    <td>{{ $subject->id }}</td>
                                    <td>{{ $subject->name }}</td>
                                    <td>{{ $subject->code }}</td>
                                    <td>{{ $subject->description ?? 'N/A' }}</td>
                                    <td>{{ $subject->units }}</td>
                                    <td>{{ $subject->hours_per_week }}</td>
                                    <td>
                                        @if ($subject->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center align-items-start">
                                            <button type="button" class="btn btn-info btn-sm mx-1 view-subject-btn"
                                                data-bs-toggle="modal" data-bs-target="#viewSubjectModal" title="View"
                                                data-id="{{ $subject->id }}" data-name="{{ $subject->name }}"
                                                data-code="{{ $subject->code }}"
                                                data-description="{{ $subject->description ?? 'N/A' }}"
                                                data-units="{{ $subject->units }}"
                                                data-hours_per_week="{{ $subject->hours_per_week }}"
                                                data-is_active="{{ $subject->is_active }}">
                                                <i data-feather="eye" class="feather-sm text-white"></i>
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm mx-1 edit-subject-btn"
                                                data-bs-toggle="modal" data-bs-target="#editSubjectModal" title="Edit"
                                                data-id="{{ $subject->id }}" data-name="{{ $subject->name }}"
                                                data-code="{{ $subject->code }}"
                                                data-description="{{ $subject->description ?? '' }}"
                                                data-units="{{ $subject->units }}"
                                                data-hours_per_week="{{ $subject->hours_per_week }}"
                                                data-is_active="{{ $subject->is_active ? 1 : 0 }}">
                                                <i data-feather="edit-2" class="feather-sm"></i>
                                            </button>
                                            <form action="{{ route('admin.subjects.destroy', $subject->id) }}"
                                                method="POST" class="mx-1"
                                                onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="tooltip" title="Delete">
                                                    <i data-feather="trash-2" class="feather-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No subjects found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addSubjectForm" action="{{ route('admin.subjects.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="name_error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="code" class="form-label">Subject Code <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" required>
                            <div class="invalid-feedback" id="code_error"></div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            <div class="invalid-feedback" id="description_error"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="units" class="form-label">Units</label>
                                    <input type="number" class="form-control" id="units" name="units"
                                        min="1" value="3">
                                    <div class="invalid-feedback" id="units_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="hours_per_week" class="form-label">Hours Per Week</label>
                                    <input type="number" class="form-control" id="hours_per_week" name="hours_per_week"
                                        min="1" value="3">
                                    <div class="invalid-feedback" id="hours_per_week_error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    Active Subject
                                </label>
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
                        <div class="col-md-4"><strong>Units:</strong></div>
                        <div class="col-md-8" id="view_units"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>Hours Per Week:</strong></div>
                        <div class="col-md-8" id="view_hours_per_week"></div>
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_units" class="form-label">Units</label>
                                    <input type="number" class="form-control" id="edit_units" name="units"
                                        min="1">
                                    <div class="invalid-feedback" id="edit_units_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_hours_per_week" class="form-label">Hours Per Week</label>
                                    <input type="number" class="form-control" id="edit_hours_per_week"
                                        name="hours_per_week" min="1">
                                    <div class="invalid-feedback" id="edit_hours_per_week_error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active"
                                    value="1">
                                <label class="form-check-label" for="edit_is_active">
                                    Active Subject
                                </label>
                            </div>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            $('#subjectsTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ]
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
                const units = button.data('units');
                const hoursPerWeek = button.data('hours_per_week');
                const isActive = button.data('is_active');

                // Fill the view modal fields
                $('#view_subject_id').text(id);
                $('#view_name').text(name);
                $('#view_code').text(code);
                $('#view_description').text(description);
                $('#view_units').text(units);
                $('#view_hours_per_week').text(hoursPerWeek);
                $('#view_status').html(isActive ? '<span class="badge bg-success">Active</span>' :
                    '<span class="badge bg-danger">Inactive</span>');

                // Show the modal
                const viewSubjectModal = new bootstrap.Modal(document.getElementById('viewSubjectModal'));
                viewSubjectModal.show();
            });

            // Edit subject button click event
            $(document).on('click', '.edit-subject-btn', function() {
                const button = $(this);
                const id = button.data('id');
                const name = button.data('name');
                const code = button.data('code');
                const description = button.data('description');
                const units = button.data('units');
                const hoursPerWeek = button.data('hours_per_week');
                const isActive = button.data('is_active');

                // Set the form action URL
                $('#editSubjectForm').attr('action', '/admin/subjects/' + id);

                // Fill the form fields
                $('#edit_subject_id').val(id);
                $('#edit_name').val(name);
                $('#edit_code').val(code);
                $('#edit_description').val(description);
                $('#edit_units').val(units);
                $('#edit_hours_per_week').val(hoursPerWeek);
                $('#edit_is_active').prop('checked', isActive == 1);

                // Show the modal
                const editSubjectModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
                editSubjectModal.show();
            });
        });
    </script>
@endpush
