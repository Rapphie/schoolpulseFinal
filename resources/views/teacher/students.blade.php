@extends('base')

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
                <button type="button" class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal"
                    data-bs-target="#importClassRecordModal">
                    Import Class Record
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
                                            data-id="{{ $student->id }}" data-name="{{ $student->full_name }}"
                                            data-email="{{ $student->email }}"
                                            data-section="{{ $student->section->grade_level . $student->section->name }}">
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
    <!-- Import Class Record Modal -->
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
                            {{-- <h6>Extracted Header Data:</h6>
                            <div id="headerDataTable" class="table-responsive"></div> --}}
                            <h6>Male Students:</h6>
                            <div id="maleStudentsTable" class="table-responsive"></div>
                            <h6>Female Students:</h6>
                            <div id="femaleStudentsTable" class="table-responsive"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="uploadFileButton">Review Data</button>
                        <button type="button" class="btn btn-success" id="saveClassRecordButton"
                            style="display: none;">Save Class Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



@endsection

@push('scripts')
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
                    // for (const key in data.headerData) {
                    //     headerHtml +=
                    //         `<tr><th>${key.replace(/_/g, ' ').toUpperCase()}</th><td>${data.headerData[key]}</td></tr>`;
                    // }
                    headerHtml += '</tbody></table>';
                    // document.getElementById('headerDataTable').innerHTML = headerHtml;

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

            fetch('{{ route('teacher.class-record.save') }}', { // New API endpoint for saving
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
