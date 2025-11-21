@extends('base')

@section('title', 'Section: ' . $section->name)

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Sections</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $section->name }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <h1>Section: {{ $section->name }}</h1>
        <div>
            <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Section
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Section Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 font-weight-bold">Section Name:</div>
                        <div class="col-md-8">{{ $section->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 font-weight-bold">Grade Level:</div>
                        <div class="col-md-8">Grade {{ $section->grade_level_id }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 font-weight-bold">Adviser:</div>
                        <div class="col-md-8">
                            @if ($class->teacher && $class->teacher->user)
                                {{ $class->teacher->user->full_name }}
                            @else
                                <span class="text-muted">No adviser assigned</span>
                            @endif
                        </div>
                    </div>
                    @if ($section->description)
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Description:</div>
                            <div class="col-md-8">{{ $section->description }}</div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-4 font-weight-bold">Created At:</div>
                        <div class="col-md-8">{{ $section->created_at->format('F j, Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Students</h5>
                    <div class="d-flex">

                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="fas fa-plus"></i> Add Student
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if ($class->enrollments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Number</th>
                                        <th>Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($class->enrollments as $enrollment)
                                        @php $student = $enrollment->student; @endphp
                                        <tr>
                                            <td>{{ $student->id ?? 'N/A' }}</td>
                                            <td>{{ $student->full_name }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <form
                                                    action="{{ route('admin.sections.students.destroy', [$section, $student]) }}"
                                                    method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" title="Remove from Section"
                                                        onclick="return confirm('Are you sure you want to remove this student from the section?')">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            No students assigned to this section yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    @php
                        $students = $class->enrollments->pluck('student');
                        $maleCount = $students->where('gender', 'male')->count();
                        $femaleCount = $students->where('gender', 'female')->count();
                        $totalCount = $students->count();
                    @endphp
                    <div class="text-center mb-4">
                        <div class="display-4 font-weight-bold">{{ $totalCount }}</div>
                        <div class="text-muted">Total Students</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Male</span>
                            <span>{{ $maleCount }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar"
                                style="width: {{ $totalCount > 0 ? ($maleCount / $totalCount) * 100 : 0 }}%"
                                aria-valuenow="{{ $maleCount }}" aria-valuemin="0" aria-valuemax="{{ $totalCount }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Female</span>
                            <span>{{ $femaleCount }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-pink" role="progressbar"
                                style="width: {{ $totalCount > 0 ? ($femaleCount / $totalCount) * 100 : 0 }}%"
                                aria-valuenow="{{ $femaleCount }}" aria-valuemin="0" aria-valuemax="{{ $totalCount }}">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Class Schedule</h6>
                        @if ($class->schedules->count() > 0)
                            <ul class="list-group">
                                @foreach ($class->schedules as $schedule)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $schedule->subject->name }}</strong><br>
                                            <small class="text-muted">
                                                {{ $schedule->day }} • {{ $schedule->start_time }} -
                                                {{ $schedule->end_time }}
                                            </small>
                                        </div>
                                        <span class="badge badge-primary">{{ $schedule->room }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="alert alert-warning mb-0">
                                No schedule assigned yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Student to Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                </div>
                <form action="{{ route('admin.sections.students.store', $section) }}" method="POST">
                    @csrf
                    <div class="modal-body">
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
                        <h6>Student Information</h6>
                        <div class="form-group mb-2">
                            <label for="student_id">Student ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="student_id" name="student_id" required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="first_name">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="last_name">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="birthdate">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="gender">Gender <span class="text-danger">*</span></label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="alumni">Alumni</option>
                                <option value="transferee">Transferee</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="enrollment_date">Enrollment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="enrollment_date" name="enrollment_date"
                                required>
                        </div>
                        <hr>
                        <h6>Guardian Information</h6>
                        <div class="form-group mb-2">
                            <label for="guardian_first_name">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="guardian_first_name"
                                name="guardian_first_name" required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="guardian_last_name">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="guardian_last_name" name="guardian_last_name"
                                required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="guardian_email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="guardian_email" name="guardian_email"
                                required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="guardian_phone">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="guardian_phone" name="guardian_phone"
                                required>
                        </div>
                        <div class="form-group mb-2">
                            <label for="guardian_relationship">Relationship <span class="text-danger">*</span></label>
                            <select class="form-control" id="guardian_relationship" name="guardian_relationship"
                                required>
                                <option value="">Select Relationship</option>
                                <option value="parent">Parent</option>
                                <option value="sibling">Sibling</option>
                                <option value="relative">Relative</option>
                                <option value="guardian">Guardian</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="importClassRecordModal" tabindex="-1" role="dialog"
        aria-labelledby="importClassRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importClassRecordModalLabel">Import Class Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importClassRecordForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="class_record_file">Upload Excel File</label>
                            <input type="file" class="form-control" id="class_record_file" name="class_record_file"
                                accept=".xlsx,.xls" required>
                        </div>
                        <div id="importResults" style="display: none;">
                            <h6>Extracted Header Data:</h6>
                            <div id="headerDataTable" class="table-responsive d-none"></div>
                            <h3>Male Students:</h3>
                            <div id="maleStudentsTable" class="table-responsive"></div>
                            <h3>Female Students:</h3>
                            <div id="femaleStudentsTable" class="table-responsive"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            aria-label="Close">Close</button>
                        <button type="submit" class="btn btn-primary" id="uploadFileButton">Review Data</button>
                        <button type="button" class="btn btn-success" id="saveClassRecordButton"
                            style="display: none;">Save Class Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: 'Select a student',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
    <script>
        let extractedClassRecordData = {}; // To store data after initial upload

        document.getElementById('importClassRecordForm').addEventListener('submit', function(e) {
            e.preventDefault();

            let formData = new FormData(this);

            fetch('{{ route('teacher.class-record.upload') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    extractedClassRecordData = data; // Store the data

                    // Display Header Data
                    let headerHtml = '<table class="table table-bordered table-sm"><tbody>';
                    for (const key in data.headerData) {
                        headerHtml +=
                            `<tr><th>${key.replace(/_/g, ' ').toUpperCase()}</th><td>${data.headerData[key]}</td></tr>`;
                    }
                    headerHtml += '</tbody></table>';
                    document.getElementById('headerDataTable').innerHTML = headerHtml;

                    // Display Male Students
                    let maleStudentsHtml =
                        '<table class="table table-bordered table-sm"><thead><tr><th>LRN</th><th>Last Name</th><th>First Name</th></tr></thead><tbody>';
                    data.maleStudents.forEach(student => {
                        maleStudentsHtml +=
                            `<tr><td>${student.lrn}</td><td>${student.last_name}</td><td>${student.first_name}</td></tr>`;
                    });
                    maleStudentsHtml += '</tbody></table>';
                    document.getElementById('maleStudentsTable').innerHTML = maleStudentsHtml;

                    // Display Female Students
                    let femaleStudentsHtml =
                        '<table class="table table-bordered table-sm"><thead><tr><th>LRN</th><th>Last Name</th><th>First Name</th></tr></thead><tbody>';
                    data.femaleStudents.forEach(student => {
                        femaleStudentsHtml +=
                            `<tr><tr><td>${student.lrn}</td><td>${student.last_name}</td><td>${student.first_name}</td></tr>`;
                    });
                    femaleStudentsHtml += '</tbody></table>';
                    document.getElementById('femaleStudentsTable').innerHTML = femaleStudentsHtml;


                    document.getElementById('importResults').style.display = 'block';
                    document.getElementById('saveClassRecordButton').style.display =
                        'block'; // Show save button
                    document.getElementById('uploadFileButton').style.display = 'none'; // Hide review button
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during import. Error: ' + error.message);
                });
        });

        // Event listener for the new "Save Class Record" button
        document.getElementById('saveClassRecordButton').addEventListener('click', function() {
            if (Object.keys(extractedClassRecordData).length === 0) {
                alert('No data to save. Please upload a file first.');
                return;
            }

            fetch('{{ route('teacher.class-record.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(extractedClassRecordData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Class record saved successfully!');
                        // Optionally close modal or refresh page
                        $('#importClassRecordModal').modal('hide');
                        location.reload(); // Reload the page to show updated student list
                    } else {
                        alert('Failed to save class record: ' + (data.message || 'Unknown error.'));
                    }
                })
                .catch(error => {
                    console.error('Error saving class record:', error);
                    alert('An error occurred while saving the class record. Error: ' + error.message);
                });
        });
    </script>
@endpush
