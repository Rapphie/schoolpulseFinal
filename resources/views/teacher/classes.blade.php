@extends('teacher.layout')

@section('title', 'My Classes')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">My Classes</h6>
            <div>
                <select id="semester-filter" class="form-select form-select-sm">
                    <option value="">All Semesters</option>
                    <option value="1">1st Semester</option>
                    <option value="2">2nd Semester</option>
                    <option value="summer">Summer</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="classesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Schedule</th>
                            <th>Room</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                            <tr>
                                <td>{{ $class->section_name }}</td>
                                <td>{{ $class->subject_name }}</td>
                                <td>{{ $class->schedule_time }}</td>
                                <td>{{ $class->room }}</td>
                                <td>{{ $class->student_count }}</td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-start">
                                        <a href="{{ route('teacher.classes', $class->id) }}"
                                            class="btn btn-info btn-sm mx-1" title="View Class">
                                            <i data-feather="eye" class="feather-sm text-white"></i>
                                        </a>
                                        <a href="{{ route('teacher.attendance.take', ['class_id' => $class->id]) }}"
                                            class="btn btn-primary btn-sm mx-1" title="Take Attendance">
                                            <i data-feather="check-circle" class="feather-sm"></i>
                                        </a>
                                        {{-- <a href="{{ route('teacher.grades.manage', ['class_id' => $class->id]) }}"
                                            class="btn btn-success btn-sm mx-1" title="Manage Grades">
                                            <i data-feather="award" class="feather-sm"></i>
                                        </a> --}}
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
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = $('#classesTable').DataTable({
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

            // Semester filter
            $('#semester-filter').on('change', function() {
                const value = $(this).val();
                table.column(2).search(value).draw();
            });
        });
    </script>
@endpush
