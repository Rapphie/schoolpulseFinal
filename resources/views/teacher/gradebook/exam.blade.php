@extends('base')

@section('title', 'Gradebook - Exams')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Exams Management</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addExamModal">
                <i data-feather="plus"></i> Add Exam
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="section-filter" class="form-label">Filter by Section</label>
                        <select id="section-filter" class="form-select">
                            <option value="">All Sections</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->name }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="subject-filter" class="form-label">Filter by Subject</label>
                        <select id="subject-filter" class="form-select">
                            <option value="">All Subjects</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->name }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="exam-type-filter" class="form-label">Filter by Type</label>
                        <select id="exam-type-filter" class="form-select">
                            <option value="">All Types</option>
                            <option value="Midterm">Midterm</option>
                            <option value="Final">Final</option>
                            <option value="Quarterly">Quarterly</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="examTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Total Items</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        <tr>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>N/A</td>
                            <td>
                                <span class="badge bg-'warning'">
                                    No data available
                                </span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-start">
                                    <a href="" class="btn btn-info btn-sm mx-1" title="View Exam">
                                        <i data-feather="eye" class="feather-sm text-white"></i>
                                    </a>
                                    <a href="" class="btn btn-primary btn-sm mx-1" title="Edit Exam">
                                        <i data-feather="edit-2" class="feather-sm"></i>
                                    </a>
                                    <a href="" class="btn btn-success btn-sm mx-1" title="Student Records">
                                        <i data-feather="list" class="feather-sm"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm mx-1 delete-exam-btn"
                                        data-bs-toggle="modal" data-bs-target="#deleteExamModal" data-id=""
                                        data-title="" title="Delete Exam">
                                        <i data-feather="trash-2" class="feather-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-center">No exams found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Exam Modal -->
    <div class="modal fade" id="addExamModal" tabindex="-1" aria-labelledby="addExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addExamModalLabel">Add Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addExamForm" action="#" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Exam Title <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="section_id" class="form-label">Section <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="section_id" name="section_id" required>
                                    <option value="">Select Section</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="subject_id" class="form-label">Subject <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="exam_type" class="form-label">Exam Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="exam_type" name="exam_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Midterm">Midterm</option>
                                    <option value="Final">Final</option>
                                    <option value="Quarterly">Quarterly</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="total_items" class="form-label">Total Items <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="total_items" name="total_items"
                                    min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label for="passing_percentage" class="form-label">Passing Percentage <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="passing_percentage"
                                    name="passing_percentage" min="1" max="100" value="70" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Exam Modal -->
    <div class="modal fade" id="deleteExamModal" tabindex="-1" aria-labelledby="deleteExamModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteExamModalLabel">Delete Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteExamForm" action="#" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" id="delete_exam_id" name="exam_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the exam: <span id="delete-exam-title"></span>?</p>
                        <p class="text-danger">This action cannot be undone and will remove all associated records.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
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
            const table = $('#examTable').DataTable({
                responsive: true,
                order: [
                    [4, 'desc']
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
                table.column(1).search(value).draw();
            });

            // Subject filter
            $('#subject-filter').on('change', function() {
                const value = $(this).val();
                table.column(2).search(value).draw();
            });

            // Exam Type filter
            $('#exam-type-filter').on('change', function() {
                const value = $(this).val();
                table.column(3).search(value).draw();
            });

            // Delete Exam Button Click Event
            $('.delete-exam-btn').on('click', function() {
                const id = $(this).data('id');
                const title = $(this).data('title');

                $('#delete_exam_id').val(id);
                $('#delete-exam-title').text(title);

                const actionUrl = `/teacher/gradebook/exam/${id}/delete`;
                $('#deleteExamForm').attr('action', actionUrl);
            });
        });
    </script>
@endpush
