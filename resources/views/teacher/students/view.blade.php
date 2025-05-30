@extends('teacher.layout')

@section('title', 'Student Details')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.students') }}">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $student->name ?? 'Student Details' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Personal Information</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="{{ $student->profile_image ?? asset('images/default-profile.png') }}"
                            class="img-profile rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        <h5 class="mt-3">{{ $student->name ?? 'N/A' }}</h5>
                        <p class="text-muted">{{ $student->section ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Student ID:</label>
                        <p>{{ $student->id ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Email:</label>
                        <p>{{ $student->email ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Date of Birth:</label>
                        <p>{{ $student->dob ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Gender:</label>
                        <p>{{ $student->gender ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Contact Number:</label>
                        <p>{{ $student->contact_number ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Address:</label>
                        <p>{{ $student->address ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Academic Performance</h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="studentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="grades-tab" data-bs-toggle="tab" data-bs-target="#grades"
                                type="button" role="tab" aria-controls="grades" aria-selected="true">Grades</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance"
                                type="button" role="tab" aria-controls="attendance"
                                aria-selected="false">Attendance</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assessments-tab" data-bs-toggle="tab" data-bs-target="#assessments"
                                type="button" role="tab" aria-controls="assessments"
                                aria-selected="false">Assessments</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="studentTabContent">
                        <div class="tab-pane fade show active" id="grades" role="tabpanel" aria-labelledby="grades-tab">
                            <div class="p-3">
                                <div class="mb-3">
                                    <select id="subject-filter" class="form-select">
                                        <option value="">All Subjects</option>
                                        @foreach ($subjects as $subject)
                                            <option value="{{ $subject->name }}">{{ $subject->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <select id="period-filter" class="form-select">
                                        <option value="">All Grading Periods</option>
                                        <option value="1">1st Quarter</option>
                                        <option value="2">2nd Quarter</option>
                                        <option value="3">3rd Quarter</option>
                                        <option value="4">4th Quarter</option>
                                        <option value="final">Final</option>
                                    </select>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="gradesTable">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Quarter</th>
                                                <th>Quiz Avg</th>
                                                <th>Exam</th>
                                                <th>Projects</th>
                                                <th>Final Grade</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($grades  as $grade)
                                                <tr>
                                                    <td>{{ $grade->subject }}</td>
                                                    <td>{{ $grade->quarter }}</td>
                                                    <td>{{ $grade->quiz_avg }}</td>
                                                    <td>{{ $grade->exam }}</td>
                                                    <td>{{ $grade->projects }}</td>
                                                    <td>{{ $grade->final_grade }}</td>
                                                    <td>
                                                        <span
                                                            class="badge bg-{{ $grade->final_grade >= 75 ? 'success' : 'danger' }}">
                                                            {{ $grade->final_grade >= 75 ? 'Passed' : 'Failed' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">No grade records found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                            <div class="p-3">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <select id="attendance-subject-filter" class="form-select">
                                            <option value="">All Subjects</option>
                                            @foreach ($subjects as $subject)
                                                <option value="{{ $subject->name }}">{{ $subject->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <select id="month-filter" class="form-select">
                                            <option value="">All Months</option>
                                            <option value="January">January</option>
                                            <option value="February">February</option>
                                            <option value="March">March</option>
                                            <option value="April">April</option>
                                            <option value="May">May</option>
                                            <option value="June">June</option>
                                            <option value="July">July</option>
                                            <option value="August">August</option>
                                            <option value="September">September</option>
                                            <option value="October">October</option>
                                            <option value="November">November</option>
                                            <option value="December">December</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Attendance Summary</h6>
                                            <div class="row">
                                                <div class="col-md-3 text-center">
                                                    <div class="p-3 rounded bg-success text-white mb-2">
                                                        <h3>{{ $attendance_summary->present_count ?? 0 }}</h3>
                                                    </div>
                                                    <p>Present</p>
                                                </div>
                                                <div class="col-md-3 text-center">
                                                    <div class="p-3 rounded bg-warning text-white mb-2">
                                                        <h3>{{ $attendance_summary->late_count ?? 0 }}</h3>
                                                    </div>
                                                    <p>Late</p>
                                                </div>
                                                <div class="col-md-3 text-center">
                                                    <div class="p-3 rounded bg-danger text-white mb-2">
                                                        <h3>{{ $attendance_summary->absent_count ?? 0 }}</h3>
                                                    </div>
                                                    <p>Absent</p>
                                                </div>
                                                <div class="col-md-3 text-center">
                                                    <div class="p-3 rounded bg-info text-white mb-2">
                                                        <h3>{{ $attendance_summary->excused_count ?? 0 }}</h3>
                                                    </div>
                                                    <p>Excused</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="attendanceTable">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Subject</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($attendance_records as $record)
                                                <tr>
                                                    <td>{{ $record->date }}</td>
                                                    <td>{{ $record->subject }}</td>
                                                    <td>
                                                        <span
                                                            class="badge bg-{{ $record->status === 'present'
                                                                ? 'success'
                                                                : ($record->status === 'late'
                                                                    ? 'warning'
                                                                    : ($record->status === 'excused'
                                                                        ? 'info'
                                                                        : 'danger')) }}">
                                                            {{ ucfirst($record->status) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $record->remarks }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">No attendance records found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="assessments" role="tabpanel" aria-labelledby="assessments-tab">
                            <div class="p-3">
                                <div class="mb-3">
                                    <select id="assessment-type-filter" class="form-select">
                                        <option value="">All Assessment Types</option>
                                        <option value="Quiz">Quizzes</option>
                                        <option value="Exam">Exams</option>
                                        <option value="Project">Projects</option>
                                    </select>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="assessmentsTable">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Subject</th>
                                                <th>Type</th>
                                                <th>Title</th>
                                                <th>Score</th>
                                                <th>Percentage</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($assessments  as $assessment)
                                                <tr>
                                                    <td>{{ $assessment->date }}</td>
                                                    <td>{{ $assessment->subject }}</td>
                                                    <td>{{ $assessment->type }}</td>
                                                    <td>{{ $assessment->title }}</td>
                                                    <td>{{ $assessment->score }} / {{ $assessment->total_items }}</td>
                                                    <td>{{ $assessment->percentage }}%</td>
                                                    <td>
                                                        <span
                                                            class="badge bg-{{ $assessment->percentage >= 75 ? 'success' : 'danger' }}">
                                                            {{ $assessment->percentage >= 75 ? 'Passed' : 'Failed' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">No assessment records found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Least Learned Competencies</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="llcTable">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Quarter</th>
                                    <th>Competencies</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($llcs  as $llc)
                                    <tr>
                                        <td>{{ $llc->subject }}</td>
                                        <td>{{ $llc->quarter }}</td>
                                        <td>{{ $llc->competency }}</td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $llc->status === 'resolved' ? 'success' : 'warning' }}">
                                                {{ ucfirst($llc->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('teacher.least-learned.view', $llc->id) }}"
                                                class="btn btn-info btn-sm" title="View Details">
                                                <i data-feather="eye" class="feather-sm text-white"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No least learned competencies found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
            // Initialize DataTables
            const gradesTable = $('#gradesTable').DataTable({
                responsive: true,
                order: [
                    [1, 'asc']
                ],
                paging: false
            });

            const attendanceTable = $('#attendanceTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ]
            });

            const assessmentsTable = $('#assessmentsTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ]
            });

            const llcTable = $('#llcTable').DataTable({
                responsive: true,
                order: [
                    [1, 'asc']
                ],
                paging: false
            });

            // Grades Filters
            $('#subject-filter').on('change', function() {
                const value = $(this).val();
                gradesTable.column(0).search(value).draw();
            });

            $('#period-filter').on('change', function() {
                const value = $(this).val();
                gradesTable.column(1).search(value).draw();
            });

            // Attendance Filters
            $('#attendance-subject-filter').on('change', function() {
                const value = $(this).val();
                attendanceTable.column(1).search(value).draw();
            });

            $('#month-filter').on('change', function() {
                const value = $(this).val();
                // This assumes the date format includes the month name
                attendanceTable.column(0).search(value).draw();
            });

            // Assessments Filter
            $('#assessment-type-filter').on('change', function() {
                const value = $(this).val();
                assessmentsTable.column(2).search(value).draw();
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
