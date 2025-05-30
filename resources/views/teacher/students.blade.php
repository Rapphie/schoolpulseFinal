@extends('teacher.layout')

@section('title', 'Students')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">My Students</h6>
            <div>
                <select id="section-filter" class="form-select form-select-sm">
                    <option value="">All Sections</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->name }}">{{ $section->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Section</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students  as $student)
                            <tr>
                                <td>{{ $student->id }}</td>
                                <td>{{ $student->full_name }}</td>
                                <td>{{ $student->section->grade_level . $student->section->name }}</td>
                                <td>{{ $student->email }}</td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-start">
                                        <button type="button" class="btn btn-info btn-sm mx-1 view-student-btn"
                                            data-bs-toggle="modal" data-bs-target="#viewStudentModal" title="View"
                                            data-id="{{ $student->id }}" data-name="{{ $student->name }}"
                                            data-email="{{ $student->email }}" data-section="{{ $student->section }}">
                                            <i data-feather="eye" class="feather-sm text-white"></i>
                                        </button>
                                        <a href="#" class="btn btn-primary btn-sm mx-1" title="View Progress">
                                            <i data-feather="trending-up" class="feather-sm"></i>
                                        </a>
                                        <a href="#" class="btn btn-success btn-sm mx-1" title="View Grades">
                                            <i data-feather="award" class="feather-sm"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Student Modal -->
    <div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStudentModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Student ID:</label>
                        <p id="view-student-id"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name:</label>
                        <p id="view-student-name"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email:</label>
                        <p id="view-student-email"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Section:</label>
                        <p id="view-student-section"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            const table = $('#studentsTable').DataTable({
                responsive: true,
                order: [
                    [1, 'asc']
                ]
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Section filter
            $('#section-filter').on('change', function() {
                const value = $(this).val();
                table.column(2).search(value).draw();
            });

            // View Student Button Click Event
            $('.view-student-btn').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const email = $(this).data('email');
                const section = $(this).data('section');

                $('#view-student-id').text(id);
                $('#view-student-name').text(name);
                $('#view-student-email').text(email);
                $('#view-student-section').text(section);
            });
        });
    </script>
@endpush
