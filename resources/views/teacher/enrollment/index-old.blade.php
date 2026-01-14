@extends('base')

@section('title', 'School-Wide Enrollment')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.enrollment.index') }}">Schedules</a></li>
                    <li class="breadcrumb-item active" aria-current="page">School-Wide Enrollment</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Enroll New Student</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                    data-bs-target="#enrollmentModal">
                    View My Enrollments
                </button>
            </div>
            <div class="card-body">
                @if (isset($error))
                    <div class="alert alert-warning text-center">
                        <h4 class="alert-heading">Enrollment Closed</h4>
                        <p>{{ $error }}</p>
                    </div>
                @else
                    <form action="{{ route('teacher.enrollment.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="class_id" class="form-label">1. Select Class to Enroll In <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="" selected disabled>-- Choose a class --</option>
                                    @foreach ($classes as $class)
                                        @if ($class->enrollments->count() < $class->capacity)
                                            <option value="{{ $class->id }}"
                                                {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->section->gradeLevel->name }} - {{ $class->section->name }}
                                                ({{ $class->enrollments->count() }}/{{ $class->capacity }})
                                            </option>
                                        @else
                                            <option value="{{ $class->id }}" disabled>
                                                {{ $class->section->gradeLevel->name }} - {{ $class->section->name }}
                                                (Full)
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="enrollment-form-fields" class="{{ old('class_id') ? '' : 'd-none' }}">
                            <h6 class="mt-4 mb-3 border-bottom pb-2">Student Information</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="lrn" class="form-label">LRN</label>
                                    <input type="text" class="form-control" id="lrn" name="lrn"
                                        value="{{ old('lrn') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="first_name" class="form-label">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="{{ old('first_name') }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="{{ old('last_name') }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="birthdate" class="form-label">Birthdate <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="birthdate" name="birthdate"
                                        value="{{ old('birthdate') }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                            </div>

                            <h6 class="mt-4 mb-3 border-bottom pb-2">Additional Information (For Analytics)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="distance_km" class="form-label">Distance from School (km)</label>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        id="distance_km" name="distance_km" value="{{ old('distance_km') }}"
                                        placeholder="e.g., 2.5">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="transportation" class="form-label">Mode of Transportation</label>
                                    <select class="form-select" id="transportation" name="transportation">
                                        <option value="" {{ old('transportation') == '' ? 'selected' : '' }}>--
                                            Select --</option>
                                        <option value="Walk" {{ old('transportation') == 'Walk' ? 'selected' : '' }}>
                                            Walk</option>
                                        <option value="Bicycle"
                                            {{ old('transportation') == 'Bicycle' ? 'selected' : '' }}>Bicycle</option>
                                        <option value="Motorcycle"
                                            {{ old('transportation') == 'Motorcycle' ? 'selected' : '' }}>Motorcycle
                                        </option>
                                        <option value="Tricycle"
                                            {{ old('transportation') == 'Tricycle' ? 'selected' : '' }}>Tricycle</option>
                                        <option value="Jeepney"
                                            {{ old('transportation') == 'Jeepney' ? 'selected' : '' }}>Jeepney</option>
                                        <option value="Bus" {{ old('transportation') == 'Bus' ? 'selected' : '' }}>Bus
                                        </option>
                                        <option value="Private Vehicle"
                                            {{ old('transportation') == 'Private Vehicle' ? 'selected' : '' }}>Private
                                            Vehicle</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="family_income" class="form-label">Socioeconomic Status</label>
                                    <select class="form-select" id="family_income" name="family_income">
                                        <option value="" {{ old('family_income') == '' ? 'selected' : '' }}>--
                                            Select --</option>
                                        <option value="Low" {{ old('family_income') == 'Low' ? 'selected' : '' }}>Low
                                        </option>
                                        <option value="Medium" {{ old('family_income') == 'Medium' ? 'selected' : '' }}>
                                            Medium</option>
                                        <option value="High" {{ old('family_income') == 'High' ? 'selected' : '' }}>High
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="mt-4 mb-3 border-bottom pb-2">Guardian Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_first_name" class="form-label">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="guardian_first_name"
                                        name="guardian_first_name" value="{{ old('guardian_first_name') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_last_name" class="form-label">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="guardian_last_name"
                                        name="guardian_last_name" value="{{ old('guardian_last_name') }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_email" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="guardian_email" name="guardian_email"
                                        value="{{ old('guardian_email') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="guardian_phone" class="form-label">Phone <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="guardian_phone" name="guardian_phone"
                                        value="{{ old('guardian_phone') }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="guardian_relationship" class="form-label">Relationship to Student <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="guardian_relationship" name="guardian_relationship"
                                    required>
                                    <option value="parent">Parent</option>
                                    <option value="sibling">Sibling</option>
                                    <option value="relative">Relative</option>
                                    <option value="guardian">Guardian</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary">Enroll Student</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card shadow mb-4 mt-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Students to Enroll from Previous Year</h6>
            </div>
            <div class="card-body">
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if ($students->isEmpty())
                    <div class="alert alert-info text-center">
                        <h4 class="alert-heading">No Students Found</h4>
                        <p>There are no students from the previous school year that are not yet enrolled in the current one.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered" id="previousYearStudentsTable" width="100%"
                            cellspacing="0">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Student Name</th>
                                    <th>Last Enrolled Grade</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $student)
                                    <tr>
                                        <td>{{ $student->lrn ?? 'N/A' }}</td>
                                        <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                        <td>
                                            @php
                                                // Use profile for grade level (distinct by academic year)
                                                $lastProfile = $student->profiles
                                                    ->sortByDesc('school_year_id')
                                                    ->first();
                                            @endphp
                                            {{ $lastProfile->gradeLevel->name ?? 'N/A' }}
                                            @if ($lastProfile && $lastProfile->status !== 'active')
                                                <span
                                                    class="badge bg-{{ $lastProfile->status === 'promoted' ? 'success' : ($lastProfile->status === 'retained' ? 'warning' : 'secondary') }} ms-1">
                                                    {{ ucfirst($lastProfile->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <form action="{{ route('teacher.enrollment.storePastStudent') }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <div class="input-group">
                                                    <select name="class_id" class="form-control" required>
                                                        <option value="" selected disabled>Select Class</option>
                                                        @foreach ($classes as $class)
                                                            <option value="{{ $class->id }}">
                                                                {{ $class->section->gradeLevel->name }} -
                                                                {{ $class->section->name }}
                                                                ({{ $class->enrollments->count() }}/{{ $class->capacity }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit"
                                                        class="btn btn-sm btn-primary ml-2">Enroll</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Enrollment Modal -->
    <div class="modal fade" id="enrollmentModal" tabindex="-1" role="dialog" aria-labelledby="enrollmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="modal-title" id="enrollmentModalLabel">My Enrollments</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <a href="{{ route('teacher.enrollment.exportAll') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-file-excel"></i> Export All
                    </a>
                </div>
                <div class="modal-body">
                    @if ($teacherEnrollments->isEmpty())
                        <div class="alert alert-info text-center">
                            <h4 class="alert-heading">No Enrollments Found</h4>
                            <p>You have not enrolled any students yet for the current school year.</p>
                        </div>
                    @else
                        @foreach ($teacherEnrollments as $classId => $enrollments)
                            @php
                                $class = $enrollments->first()->class;
                            @endphp
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        {{ $class->section->gradeLevel->name }} - {{ $class->section->name }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>LRN</th>
                                                    <th>Student Name</th>
                                                    <th>Enrollment Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($enrollments as $enrollment)
                                                    <tr>
                                                        <td>{{ $enrollment->student->lrn ?? 'N/A' }}</td>
                                                        <td>{{ $enrollment->student->first_name }}
                                                            {{ $enrollment->student->last_name }}</td>
                                                        <td>{{ $enrollment->created_at->format('M d, Y') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTables for the previous year students table
            $('#previousYearStudentsTable').DataTable({
                searching: true,
                paging: true,
                info: true,
                lengthChange: true,
                pageLength: 10,
                responsive: true,
                // Layout configuration to ensure search box shows
                dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-5"i><"col-sm-7"p>>',
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search students...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ students",
                    infoEmpty: "No students found",
                    infoFiltered: "(filtered from _MAX_ total students)",
                    zeroRecords: "No matching students found",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                columnDefs: [{
                        orderable: false,
                        targets: [3]
                    }, // Disable sorting on Action column
                    {
                        width: "15%",
                        targets: [0]
                    }, // LRN column
                    {
                        width: "30%",
                        targets: [1]
                    }, // Student Name column
                    {
                        width: "25%",
                        targets: [2]
                    }, // Grade column
                    {
                        width: "30%",
                        targets: [3]
                    } // Action column
                ],
                order: [
                    [1, 'asc']
                ], // Default sort by Student Name
                initComplete: function() {
                    console.log('DataTable initialized successfully');
                    // Style the search input
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    $('.dataTables_filter input').css({
                        'width': '250px',
                        'display': 'inline-block'
                    });
                    $('.dataTables_length select').addClass('form-control form-control-sm');
                    $('.dataTables_length select').css({
                        'width': 'auto',
                        'display': 'inline-block'
                    });
                }
            });

            // Form toggle functionality
            const classSelect = document.getElementById('class_id');
            const formFields = document.getElementById('enrollment-form-fields');

            function toggleForm() {
                if (classSelect && classSelect.value) {
                    formFields.classList.remove('d-none');
                } else if (formFields) {
                    formFields.classList.add('d-none');
                }
            }

            // Check on page load in case of validation errors
            toggleForm();

            if (classSelect) {
                classSelect.addEventListener('change', toggleForm);
            }
        });
    </script>
@endpush
