@extends('teacher.layout')

@section('title', 'Attendance Records')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
            <div>
                <a href="{{ route('teacher.attendance.take') }}" class="btn btn-primary btn-sm">
                    <i data-feather="plus"></i> Take New Attendance
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
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
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date-from" class="form-label">Date From</label>
                        <input type="date" id="date-from" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date-to" class="form-label">Date To</label>
                        <input type="date" id="date-to" class="form-control">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Excused</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceRecords as $record)
                            <tr>
                                <td>{{ now() }}</td>
                                <td>{{ $record->section_name }}</td>
                                <td>{{ $record->subject_name }}</td>
                                <td>{{ $record->present_count }}</td>
                                <td>{{ $record->late_count }}</td>
                                <td>{{ $record->absent_count }}</td>
                                <td>{{ $record->excused_count }}</td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-start">
                                        <a href="{{ route('teacher.attendance.view', $record->id) }}"
                                            class="btn btn-info btn-sm mx-1" title="View Details">
                                            <i data-feather="eye" class="feather-sm text-white"></i>
                                        </a>
                                        <a href="{{ route('teacher.attendance.edit', $record->id) }}"
                                            class="btn btn-primary btn-sm mx-1" title="Edit Record">
                                            <i data-feather="edit-2" class="feather-sm"></i>
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm mx-1" title="Print Record"
                                            onclick="printRecord({{ $record->id }})">
                                            <i data-feather="printer" class="feather-sm"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm mx-1 delete-record-btn"
                                            data-bs-toggle="modal" data-bs-target="#deleteRecordModal"
                                            data-id="{{ $record->id }}" data-date="{{ $record->date }}"
                                            data-section="{{ $record->section_name }}"
                                            data-subject="{{ $record->subject_name }}" title="Delete Record">
                                            <i data-feather="trash-2" class="feather-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Record Modal -->
    <div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRecordModalLabel">Delete Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteRecordForm" action="#" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" id="delete_record_id" name="record_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the attendance record for:</p>
                        <p><strong>Date:</strong> <span id="delete-record-date"></span></p>
                        <p><strong>Section:</strong> <span id="delete-record-section"></span></p>
                        <p><strong>Subject:</strong> <span id="delete-record-subject"></span></p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Attendance Summary Modal -->
    <div class="modal fade" id="summarizeModal" tabindex="-1" aria-labelledby="summarizeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="summarizeModalLabel">Attendance Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="summary-section" class="form-label">Section</label>
                                <select id="summary-section" class="form-select">
                                    <option value="">Select Section</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="summary-subject" class="form-label">Subject</label>
                                <select id="summary-subject" class="form-select">
                                    <option value="">Select Subject</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="summary-date-from" class="form-label">Date From</label>
                                <input type="date" id="summary-date-from" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="summary-date-to" class="form-label">Date To</label>
                                <input type="date" id="summary-date-to" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn btn-primary" id="generateSummaryBtn">Generate Summary</button>
                    </div>

                    <div id="summaryResult" class="mt-4" style="display: none;">
                        <h6 class="fw-bold">Summary Results</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Attendance Statistics</h6>
                                        <div id="attendance-chart" style="height: 300px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Student Details</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Present</th>
                                                        <th>Late</th>
                                                        <th>Absent</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="summary-students">
                                                    <!-- Will be populated dynamically -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="printSummaryBtn" style="display: none;">
                        <i data-feather="printer"></i> Print Summary
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            const table = $('#attendanceTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
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

            // Date range filter
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    const dateFrom = $('#date-from').val();
                    const dateTo = $('#date-to').val();
                    const date = data[0]; // date is in the first column

                    if (!dateFrom && !dateTo) {
                        return true;
                    }

                    if (!dateFrom && new Date(date) <= new Date(dateTo)) {
                        return true;
                    }

                    if (!dateTo && new Date(date) >= new Date(dateFrom)) {
                        return true;
                    }

                    if (new Date(date) >= new Date(dateFrom) && new Date(date) <= new Date(dateTo)) {
                        return true;
                    }

                    return false;
                }
            );

            $('#date-from, #date-to').on('change', function() {
                table.draw();
            });

            // Delete Record Button Click Event
            $('.delete-record-btn').on('click', function() {
                const id = $(this).data('id');
                const date = $(this).data('date');
                const section = $(this).data('section');
                const subject = $(this).data('subject');

                $('#delete_record_id').val(id);
                $('#delete-record-date').text(date);
                $('#delete-record-section').text(section);
                $('#delete-record-subject').text(subject);

                const actionUrl = `/teacher/attendance/${id}/delete`;
                $('#deleteRecordForm').attr('action', actionUrl);
            });

            // Generate Summary Button Click Event
            $('#generateSummaryBtn').on('click', function() {
                const sectionId = $('#summary-section').val();
                const subjectId = $('#summary-subject').val();
                const dateFrom = $('#summary-date-from').val();
                const dateTo = $('#summary-date-to').val();

                if (!sectionId || !subjectId || !dateFrom || !dateTo) {
                    alert('Please fill in all fields to generate a summary');
                    return;
                }

                // In a real application, you would make an AJAX call to get the summary data
                // For this demo, we'll use sample data

                // Sample data for the chart
                const options = {
                    series: [{
                        name: 'Attendance',
                        data: [75, 15, 8, 2]
                    }],
                    chart: {
                        type: 'pie',
                        height: 300
                    },
                    labels: ['Present', 'Late', 'Absent', 'Excused'],
                    colors: ['#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc'],
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                // Clear previous chart if it exists
                document.querySelector('#attendance-chart').innerHTML = '';

                // Create the chart
                const chart = new ApexCharts(document.querySelector('#attendance-chart'), options);
                chart.render();

                // Sample data for student details
                const students = [{
                        name: 'Student 1',
                        present: 18,
                        late: 2,
                        absent: 0
                    },
                    {
                        name: 'Student 2',
                        present: 15,
                        late: 3,
                        absent: 2
                    },
                    {
                        name: 'Student 3',
                        present: 12,
                        late: 5,
                        absent: 3
                    },
                    {
                        name: 'Student 4',
                        present: 20,
                        late: 0,
                        absent: 0
                    },
                    {
                        name: 'Student 5',
                        present: 10,
                        late: 5,
                        absent: 5
                    }
                ];

                // Clear previous student details
                $('#summary-students').empty();

                // Add student details to the table
                students.forEach(student => {
                    const row = `
                    <tr>
                        <td>${student.name}</td>
                        <td>${student.present}</td>
                        <td>${student.late}</td>
                        <td>${student.absent}</td>
                    </tr>
                `;

                    $('#summary-students').append(row);
                });

                // Show summary and print button
                $('#summaryResult').show();
                $('#printSummaryBtn').show();
            });

            // Print Summary Button Click Event
            $('#printSummaryBtn').on('click', function() {
                alert('In a real application, this would print the summary report');
            });
        });

        // Function to print a specific attendance record
        function printRecord(recordId) {
            alert('In a real application, this would print the attendance record with ID: ' + recordId);
        }
    </script>
@endpush
