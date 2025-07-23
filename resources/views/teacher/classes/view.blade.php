@extends('base')

@section('title', 'Class Details')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.classes') }}">My Classes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $class->section_name ?? 'Class' }} -
                        {{ $class->subject_name ?? 'Subject' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Class Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold">Section:</label>
                        <p>{{ $class->section_name ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Subject:</label>
                        <p>{{ $class->subject_name ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Schedule:</label>
                        <p>{{ $class->schedule ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Room:</label>
                        <p>{{ $class->room ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Total Students:</label>
                        <p>{{ $class->student_count ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('teacher.attendance.take', ['class_id' => $class->id ?? 0]) }}"
                                class="btn btn-primary btn-block d-flex flex-column align-items-center p-3 h-100">
                                <i data-feather="check-circle" style="width: 36px; height: 36px; margin-bottom: 10px;"></i>
                                <span>Take Attendance</span>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('teacher.attendance.take', ['class_id' => $class->id ?? 0]) }}"
                                class="btn btn-success btn-block d-flex flex-column align-items-center p-3 h-100">
                                <i data-feather="award" style="width: 36px; height: 36px; margin-bottom: 10px;"></i>
                                <span>Manage Grades</span>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('teacher.students', ['section_id' => $class->section_id ?? 0]) }}"
                                class="btn btn-secondary btn-block d-flex flex-column align-items-center p-3 h-100">
                                <i data-feather="users" style="width: 36px; height: 36px; margin-bottom: 10px;"></i>
                                <span>View Students</span>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('teacher.least-learned.subjects', ['class_id' => $class->id ?? 0]) }}"
                                class="btn btn-danger btn-block d-flex flex-column align-items-center p-3 h-100">
                                <i data-feather="alert-circle" style="width: 36px; height: 36px; margin-bottom: 10px;"></i>
                                <span>Least Learned</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#exportModal">
                            <i data-feather="download"></i> Export List
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Attendance Rate</th>
                                    <th>Quiz Avg</th>
                                    <th>Exam Avg</th>
                                    <th>Overall Avg</th>
                                    <th>Performance Trend</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    <tr>
                                        <td>{{ $student->id }}</td>
                                        <td>{{ $student->name }}</td>
                                        <td>{{ $student->email }}</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-{{ $student->attendance_rate >= 90 ? 'success' : ($student->attendance_rate >= 75 ? 'warning' : 'danger') }}"
                                                    role="progressbar"
                                                    style="width: {{ $student->attendance_rate ?? 0 }}%;"
                                                    aria-valuenow="{{ $student->attendance_rate ?? 0 }}" aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    {{ $student->attendance_rate ?? 0 }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($student->quiz_average)
                                                <span
                                                    class="{{ $student->quiz_average >= 75 ? 'text-success' : 'text-danger' }} fw-bold">
                                                    {{ $student->quiz_average }}%
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($student->exam_average)
                                                <span
                                                    class="{{ $student->exam_average >= 75 ? 'text-success' : 'text-danger' }} fw-bold">
                                                    {{ $student->exam_average }}%
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($student->overall_average)
                                                <span
                                                    class="{{ $student->overall_average >= 75 ? 'text-success' : 'text-danger' }} fw-bold">
                                                    {{ $student->overall_average }}%
                                                </span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($student->performance_trend == 'improving')
                                                <span class="badge bg-success"><i data-feather="trending-up"
                                                        class="feather-sm me-1"></i> Improving</span>
                                            @elseif($student->performance_trend == 'declining')
                                                <span class="badge bg-danger"><i data-feather="trending-down"
                                                        class="feather-sm me-1"></i> Declining</span>
                                            @else
                                                <span class="badge bg-secondary"><i data-feather="minus"
                                                        class="feather-sm me-1"></i> Stable</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#studentActionsModal{{ $student->id }}">
                                                <i data-feather="settings" class="feather-sm"></i> Actions
                                            </button>

                                            <!-- Student Actions Modal -->
                                            <div class="modal fade" id="studentActionsModal{{ $student->id }}"
                                                tabindex="-1"
                                                aria-labelledby="studentActionsModalLabel{{ $student->id }}"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"
                                                                id="studentActionsModalLabel{{ $student->id }}">Actions
                                                                for {{ $student->name }}</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="d-flex flex-column gap-2">
                                                                <a href="#" class="btn btn-info">
                                                                    <i data-feather="eye" class="feather-sm me-2"></i>
                                                                    View Student Profile
                                                                </a>
                                                                <a href="#" class="btn btn-primary">
                                                                    <i data-feather="edit-2" class="feather-sm me-2"></i>
                                                                    Manage Grades
                                                                </a>
                                                                <a href="#" class="btn btn-warning">
                                                                    <i data-feather="calendar"
                                                                        class="feather-sm me-2"></i> View Attendance
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No students found in this class.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportModalLabel">Export Student List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatExcel"
                                value="excel" checked>
                            <label class="form-check-label" for="formatExcel">
                                Excel (.xlsx)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatCSV"
                                value="csv">
                            <label class="form-check-label" for="formatCSV">
                                CSV (.csv)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportFormat" id="formatPDF"
                                value="pdf">
                            <label class="form-check-label" for="formatPDF">
                                PDF (.pdf)
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Include Additional Information</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeAttendance" checked>
                            <label class="form-check-label" for="includeAttendance">
                                Attendance Records
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeGrades" checked>
                            <label class="form-check-label" for="includeGrades">
                                Grade Information
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeContacts">
                            <label class="form-check-label" for="includeContacts">
                                Contact Information
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="downloadBtn">Download</button>
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
            $('#studentsTable').DataTable({
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

            // Download button click event
            $('#downloadBtn').on('click', function() {
                const format = $('input[name="exportFormat"]:checked').val();
                const includeAttendance = $('#includeAttendance').is(':checked');
                const includeGrades = $('#includeGrades').is(':checked');
                const includeContacts = $('#includeContacts').is(':checked');

                // In a real application, you would make an AJAX call to download the file
                alert(`Downloading student list in ${format} format with the selected options.`);
                $('#exportModal').modal('hide');
            });
        });
    </script>
@endpush
