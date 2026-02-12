@extends('base')

@section('title', 'Attendance Records')

@section('content')

    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal"
                    data-bs-target="#summarizeModal">
                    <i data-feather="bar-chart-2" class="feather-sm"></i> Summarize
                </button>
                <a href="{{ route('teacher.attendance.take') }}" class="btn btn-primary btn-sm">
                    <i data-feather="plus" class="feather-sm"></i> New Attendance
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-4 p-3 bg-light border rounded">
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label for="grade-level-filter" class="form-label">Grade Level</label>
                        <select id="grade-level-filter" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($gradeLevels as $gl)
                                <option value="{{ $gl->name }}" data-id="{{ $gl->id }}">{{ $gl->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="section-filter" class="form-label">Section</label>
                        <select id="section-filter" class="form-select form-select-sm">
                            <option value="">All</option>
                            {{-- Will be filtered based on Grade Level --}}
                            @foreach ($sections as $section)
                                <option value="{{ $section->name }}" data-grade-id="{{ $section->grade_level_id }}">
                                    {{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="subject-filter" class="form-label">Subject</label>
                        <select id="subject-filter" class="form-select form-select-sm">
                            <option value="">All</option>
                            {{-- Will be filtered based on Section --}}
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->name }}" data-grade-id="{{ $subject->grade_level_id }}">
                                    {{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date-from" class="form-label">Date From</label>
                        <input type="date" id="date-from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label for="date-to" class="form-label">Date To</label>
                        <input type="date" id="date-to" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="attendanceTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Student Name</th>
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceRecords as $record)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</td>
                                <td>{{ $record->last_name }}, {{ $record->first_name }}</td>
                                <td>{{ $record->grade_level_name }}</td>
                                <td>{{ $record->section_name }}</td>
                                <td>{{ $record->subject_name }}</td>
                                <td>
                                    @php
                                        $statusClass = '';
                                        switch ($record->status) {
                                            case 'present':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'absent':
                                                $statusClass = 'bg-danger';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                                break;
                                        }
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($record->status) }}</span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-start">
                                        <button type="button" class="btn btn-outline-primary btn-sm mx-1 edit-record-btn"
                                            data-bs-toggle="modal" data-bs-target="#editRecordModal"
                                            data-id="{{ $record->id }}" data-status="{{ $record->status }}"
                                            data-student-name="{{ $record->first_name }} {{ $record->last_name }}"
                                            title="Edit Record">
                                            <i data-feather="edit-2" class="feather-sm"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm mx-1 delete-record-btn"
                                            data-bs-toggle="modal" data-bs-target="#deleteRecordModal"
                                            data-id="{{ $record->id }}"
                                            data-student-name="{{ $record->first_name }} {{ $record->last_name }}"
                                            title="Delete Record">
                                            <i data-feather="trash-2" class="feather-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Summarize Modal -->
    <div class="modal fade" id="summarizeModal" tabindex="-1" aria-labelledby="summarizeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="summarizeModalLabel">Attendance Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 bg-light border rounded mb-4">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label for="summary-grade-level" class="form-label">Grade Level</label>
                                <select id="summary-grade-level" class="form-select">
                                    <option value="">Select Grade Level</option>
                                    @foreach ($gradeLevels as $gl)
                                        <option value="{{ $gl->id }}">{{ $gl->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="summary-class" class="form-label">Section</label>
                                <select id="summary-class" class="form-select">
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="summary-subject" class="form-label">Subject</label>
                                <select id="summary-subject" class="form-select">
                                    <option value="">Select Subject</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="summary-date-from" class="form-label">Date From</label>
                                <input type="date" id="summary-date-from" class="form-control"
                                    value="{{ $defaultSummaryDateFrom ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label for="summary-date-to" class="form-label">Date To</label>
                                <input type="date" id="summary-date-to" class="form-control"
                                    value="{{ $defaultSummaryDateTo ?? '' }}">
                            </div>
                            <div class="col-12 mt-2">
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <button type="button" class="btn btn-outline-secondary quick-date"
                                        data-days="7">Last 7 Days</button>
                                    <button type="button" class="btn btn-outline-secondary quick-date"
                                        data-days="14">Last 14 Days</button>
                                    <button type="button" class="btn btn-outline-secondary quick-date"
                                        data-days="30">Last 30 Days</button>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-primary" id="generateSummaryBtn">Generate
                                Summary</button>
                        </div>
                    </div>

                    <div id="summaryResult" class="mt-4" style="display: none;">
                        <h6 class="fw-bold">Summary Results</h6>
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Attendance Trends</h6>
                                        <div id="trend-chart" style="height: 300px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Attendance Distribution</h6>
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
                                                        <th>P</th>
                                                        <th>L</th>
                                                        <th>A</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="summary-students">
                                                    <!-- Will be populated dynamically -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <small class="text-danger">* Red names indicate at-risk students (3+
                                            absences)</small>
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


    <!-- Edit Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" aria-labelledby="editRecordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="editRecordModalLabel">Edit Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editRecordForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <p>Editing attendance for <strong id="edit-student-name"></strong></p>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="excused">Excused</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Record Modal -->
    <div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="deleteRecordModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteRecordForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>Are you sure you want to delete the attendance record for <strong
                                id="delete-student-name"></strong>?</p>
                        <p class="text-danger mt-3"><i data-feather="alert-triangle" class="feather-sm"></i> This
                            action cannot be undone.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const defaultSummaryDateFrom = @json($defaultSummaryDateFrom ?? null);
            const defaultSummaryDateTo = @json($defaultSummaryDateTo ?? null);

            if (defaultSummaryDateFrom && defaultSummaryDateTo) {
                $('#summary-date-from').val(defaultSummaryDateFrom);
                $('#summary-date-to').val(defaultSummaryDateTo);
                $('.quick-date[data-days="14"]').addClass('active');
            }

            // Initialize DataTable
            const table = $('#attendanceTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ]
            });

            // Grade level filter
            $('#grade-level-filter').on('change', function() {
                const name = $(this).val();
                const id = $(this).find(':selected').data('id');
                table.column(2).search(name).draw();

                // Filter Section dropdown
                const sectionFilter = $('#section-filter');
                sectionFilter.val("");
                table.column(3).search("").draw();

                if (!id) {
                    sectionFilter.find('option').show();
                } else {
                    sectionFilter.find('option').each(function() {
                        const gradeId = $(this).data('grade-id');
                        if (!gradeId || gradeId == id) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }

                // Reset Subject filter as well
                $('#subject-filter').val("").find('option').show();
                table.column(4).search("").draw();
            });

            // Section filter
            $('#section-filter').on('change', function() {
                const name = $(this).val();
                table.column(3).search(name).draw();

                // Optional: Filter Subject dropdown based on selected section?
                // Since we don't have section-subject mapping easily here without more data,
                // and the user asked for Grade Level -> Subject, let's stick to Grade Level -> Subject.
            });

            // Subject filter
            $('#subject-filter').on('change', function() {
                const name = $(this).val();
                table.column(4).search(name).draw();
            });

            // Date range filter
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    const dateFrom = $('#date-from').val();
                    const dateTo = $('#date-to').val();
                    const date = data[0];

                    if (!dateFrom && !dateTo) return true;
                    if (dateFrom && !dateTo && new Date(date) >= new Date(dateFrom)) return true;
                    if (!dateFrom && dateTo && new Date(date) <= new Date(dateTo)) return true;
                    if (dateFrom && dateTo && new Date(date) >= new Date(dateFrom) && new Date(date) <=
                        new Date(dateTo)) return true;

                    return false;
                }
            );

            $('#date-from, #date-to').on('change', function() {
                table.draw();
            });

            // Quick date filters for summary
            $('.quick-date').on('click', function() {
                const days = $(this).data('days');
                const to = new Date();
                const from = new Date();
                from.setDate(to.getDate() - days);

                $('#summary-date-to').val(to.toISOString().split('T')[0]);
                $('#summary-date-from').val(from.toISOString().split('T')[0]);

                // Highlight active button
                $('.quick-date').removeClass('active');
                $(this).addClass('active');
            });

            // Grade level filter for summary
            $('#summary-grade-level').on('change', function() {
                const gradeLevelId = $(this).val();
                const sectionDropdown = $('#summary-class');
                const subjectDropdown = $('#summary-subject');

                sectionDropdown.empty().append('<option value="">Select Section</option>');
                subjectDropdown.empty().append('<option value="">Select Subject</option>');

                if (!gradeLevelId) return;

                $.ajax({
                    url: '{{ route('teacher.sections.by-grade-level') }}',
                    type: 'GET',
                    data: {
                        grade_level: gradeLevelId
                    },
                    success: function(response) {
                        if (response.allClasses && response.allClasses.length > 0) {
                            response.allClasses.forEach(classes => {
                                sectionDropdown.append(
                                    `<option value="${classes.id}">${classes.section.name}</option>`
                                );
                            });
                        } else if (response.sections && response.sections.length > 0) {
                            // Fallback for different response structure
                            response.sections.forEach(section => {
                                sectionDropdown.append(
                                    `<option value="${section.id}">${section.name}</option>`
                                );
                            });
                        }
                    }
                });
            });

            $('#summary-class').on('change', function() {
                const sectionId = $(this).val();
                const subjectDropdown = $('#summary-subject');

                subjectDropdown.empty().append('<option value="">Select Subject</option>');

                if (!sectionId) return;

                // Add "All Subjects" option
                subjectDropdown.append('<option value="all">All Subjects</option>');

                $.ajax({
                    url: "{{ route('teacher.subjects.by-section', ['section' => ':sectionId']) }}"
                        .replace(':sectionId', sectionId),
                    type: 'GET',
                    success: function(response) {
                        const subjects = response.subjects || response.data || response;
                        if (Array.isArray(subjects)) {
                            subjects.forEach(subject => {
                                subjectDropdown.append(
                                    `<option value="${subject.id}">${subject.name}</option>`
                                );
                            });
                        }
                    }
                });
            });

            // Edit Record Button Click Event
            $('#attendanceTable').on('click', '.edit-record-btn', function() {
                const id = $(this).data('id');
                const status = $(this).data('status');
                const studentName = $(this).data('student-name');

                $('#edit-student-name').text(studentName);
                $('#edit_status').val(status);

                const actionUrl = "{{ route('teacher.attendance.update', ['id' => '__id__']) }}".replace(
                    '__id__', id);
                $('#editRecordForm').attr('action', actionUrl);
            });

            // Delete Record Button Click Event
            $('#attendanceTable').on('click', '.delete-record-btn', function() {
                const id = $(this).data('id');
                const studentName = $(this).data('student-name');

                $('#delete-student-name').text(studentName);

                const actionUrl = "{{ route('teacher.attendance.delete', ['id' => '__id__']) }}".replace(
                    '__id__', id);
                $('#deleteRecordForm').attr('action', actionUrl);
            });

            // Generate Summary Button Click Event
            $('#generateSummaryBtn').on('click', function() {
                const classId = $('#summary-class').val();
                const subjectId = $('#summary-subject').val();
                const dateFrom = $('#summary-date-from').val();
                const dateTo = $('#summary-date-to').val();

                if (!classId || !subjectId || !dateFrom || !dateTo) {
                    alert('Please fill in all fields to generate a summary');
                    return;
                }

                $.ajax({
                    url: "{{ route('teacher.attendance.summary') }}",
                    type: 'GET',
                    data: {
                        class_id: classId,
                        subject_id: subjectId,
                        date_from: dateFrom,
                        date_to: dateTo
                    },
                    success: function(response) {
                        const stats = response.stats;

                        // Pie Chart
                        const chartOptions = {
                            series: [
                                parseInt(stats.present_count),
                                parseInt(stats.late_count),
                                parseInt(stats.absent_count),
                                parseInt(stats.excused_count)
                            ],
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
                        document.querySelector('#attendance-chart').innerHTML = '';
                        const chart = new ApexCharts(document.querySelector(
                            "#attendance-chart"), chartOptions);
                        chart.render();

                        // Trend Chart
                        const trendData = response.trend_data;
                        const trendChartOptions = {
                            series: [{
                                name: 'Present',
                                data: trendData.map(item => item.present)
                            }, {
                                name: 'Absent',
                                data: trendData.map(item => item.absent)
                            }],
                            chart: {
                                height: 300,
                                type: 'line',
                                toolbar: {
                                    show: false
                                }
                            },
                            colors: ['#1cc88a', '#e74a3b'],
                            dataLabels: {
                                enabled: false
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 3
                            },
                            xaxis: {
                                categories: trendData.map(item => {
                                    const date = new Date(item.date);
                                    return date.toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric'
                                    });
                                }),
                            },
                            legend: {
                                position: 'top'
                            }
                        };
                        document.querySelector('#trend-chart').innerHTML = '';
                        const trendChart = new ApexCharts(document.querySelector(
                            "#trend-chart"), trendChartOptions);
                        trendChart.render();

                        const studentTableBody = $('#summary-students');
                        studentTableBody.empty();
                        response.student_details.forEach(student => {
                            const atRiskClass = student.is_at_risk ?
                                'text-danger fw-bold' : '';
                            const row = `
                                <tr>
                                    <td class="${atRiskClass}">${student.last_name}, ${student.first_name} ${student.is_at_risk ? '<i class="fa fa-exclamation-triangle"></i>' : ''}</td>
                                    <td>${student.present_count}</td>
                                    <td>${student.late_count}</td>
                                    <td>${student.absent_count}</td>
                                </tr>`;
                            studentTableBody.append(row);
                        });

                        $('#summaryResult').show();
                        $('#printSummaryBtn').show();
                    },
                    error: function(xhr) {
                        alert('An error occurred while generating the summary.');
                        console.error(xhr.responseText);
                    }
                });
            });

        });
    </script>
@endpush
