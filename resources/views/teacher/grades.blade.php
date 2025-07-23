@extends('base')

@section('title', 'Grades Management')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Grades Management</h6>
            <div class="d-flex align-items-center">
                <select id="section-filter" class="form-select form-select-sm me-2">
                    <option value="">All Sections</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->name }}">{{ $section->name }}</option>
                    @endforeach
                </select>
                <select id="subject-filter" class="form-select form-select-sm">
                    <option value="">All Subjects</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->name }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="gradesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Total Students</th>
                            <th>Grading Period</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes  as $class)
                            <tr>
                                <td>{{ $class->section_name }}</td>
                                <td>{{ $class->subject_name }}</td>
                                <td>{{ $class->student_count }}</td>
                                <td>
                                    <select class="form-select form-select-sm grading-period"
                                        data-class-id="{{ $class->id }}">
                                        <option value="1">1st Quarter</option>
                                        <option value="2">2nd Quarter</option>
                                        <option value="3">3rd Quarter</option>
                                        <option value="4">4th Quarter</option>
                                        <option value="final">Final</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $class->status === 'complete' ? 'success' : 'warning' }}">
                                        {{ $class->status === 'complete' ? 'Complete' : 'Pending' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-start">
                                        <a href="{{ route('teacher.grades.view', ['class_id' => $class->id]) }}"
                                            class="btn btn-info btn-sm mx-1" title="View Grades">
                                            <i data-feather="eye" class="feather-sm text-white"></i>
                                        </a>
                                        <a href="{{ route('teacher.grades.edit', ['class_id' => $class->id]) }}"
                                            class="btn btn-primary btn-sm mx-1" title="Edit Grades">
                                            <i data-feather="edit-2" class="feather-sm"></i>
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm mx-1 publish-grades-btn"
                                            data-bs-toggle="modal" data-bs-target="#publishGradesModal"
                                            data-class-id="{{ $class->id }}" data-section="{{ $class->section_name }}"
                                            data-subject="{{ $class->subject_name }}" title="Publish Grades">
                                            <i data-feather="check-square" class="feather-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No classes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                    <input type="hidden" id="class_id" name="class_id">
                    <input type="hidden" id="grading_period" name="grading_period">
                    <div class="modal-body">
                        <p>Are you sure you want to publish the grades for <span id="modal-section-subject"></span>?</p>
                        <p class="text-warning">Note: Once published, the grades cannot be modified.</p>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = $('#gradesTable').DataTable({
                responsive: true,
                order: [
                    [0, 'asc']
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
                table.column(0).search(value).draw();
            });

            // Subject filter
            $('#subject-filter').on('change', function() {
                const value = $(this).val();
                table.column(1).search(value).draw();
            });

            // Grading Period Change
            $('.grading-period').on('change', function() {
                const classId = $(this).data('class-id');
                const period = $(this).val();

                // Here you would typically make an AJAX call to load the grades for the selected period
                console.log(`Loading grades for class ${classId}, period ${period}`);
            });

            // Publish Grades Button Click Event
            $('.publish-grades-btn').on('click', function() {
                const classId = $(this).data('class-id');
                const section = $(this).data('section');
                const subject = $(this).data('subject');
                const period = $(`select.grading-period[data-class-id="${classId}"]`).val();

                $('#class_id').val(classId);
                $('#grading_period').val(period);
                $('#modal-section-subject').text(`${section} - ${subject} (${getPeriodName(period)})`);
            });

            function getPeriodName(period) {
                switch (period) {
                    case '1':
                        return '1st Quarter';
                    case '2':
                        return '2nd Quarter';
                    case '3':
                        return '3rd Quarter';
                    case '4':
                        return '4th Quarter';
                    case 'final':
                        return 'Final';
                    default:
                        return '';
                }
            }
        });
    </script>
@endpush
