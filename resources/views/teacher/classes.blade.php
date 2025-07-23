@extends('base')

@section('title', 'My Classes')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.4/css/rowGroup.dataTables.min.css">
@endpush

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">My Classes</h6>
            <div>
                <select id="section-filter" class="form-select form-select-sm">
                    <option value="">Select a Section</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->gradeLevel->name }} - {{ $section->name }}</option>
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
                            <th>Gender</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Student rows will be dynamically inserted here --}}
                    </tbody>
                </table>
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

@push('scripts')
    {{-- Ensure jQuery is loaded first --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- DataTables and extensions --}}
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.1.4/js/dataTables.rowGroup.min.js"></script>

    <script>
        $(document).ready(function() {
            const table = $('#studentsTable').DataTable({
                responsive: true,
                order: [
                    [1, 'asc']
                ],
                rowGroup: {
                    dataSrc: 1 // Group by the 'Gender' column (index 1)
                },
                columnDefs: [{
                    targets: [1], // Hide the 'Gender' column
                    visible: false
                }]
            });

            $('#section-filter').on('change', function() {
                const sectionId = $(this).val();
                table.clear().draw();

                if (sectionId) {
                    fetch(`/teacher/sections/${sectionId}/students`)
                        .then(response => response.json())
                        .then(students => {
                            students.forEach(student => {
                                table.row.add([
                                    student.id,
                                    student.gender,
                                    student.last_name,
                                    student.first_name,
                                    '<a href="#" class="btn btn-info btn-sm">View</a>'
                                ]).draw(false);
                            });
                        });
                }
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
