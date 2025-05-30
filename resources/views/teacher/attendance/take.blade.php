@extends('teacher.layout')

@section('title', 'Take Attendance')

@section('content')
    <main class="p-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Class Selection</h6>
                    </div>
                    <div class="card-body">
                        <form id="classSelectionForm">
                            <div class="mb-3">
                                <label for="grade_level_id" class="form-label">Grade Level <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="grade_level_id" name="grade_level_id" required>
                                    <option value="">Select Grade Level</option>
                                    @php
                                        $gradeLevels = $sections->pluck('grade_level')->unique();
                                    @endphp
                                    @foreach ($gradeLevels as $gradeLevel)
                                        <option value="{{ $gradeLevel }}"
                                            {{ old('grade_level_id') == $gradeLevel ? 'selected' : '' }}>
                                            {{ $gradeLevel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="section_id" class="form-label">Section <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="section_id" name="section_id" required disabled>
                                    <option value="">Select Grade Level First</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="subject_id" class="form-label">Subject <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>
                            <button type="button" id="loadStudentsBtn" class="btn btn-primary">Load Students</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Class Information</h6>
                    </div>
                    <div class="card-body">
                        <div id="classInfo" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Section:</strong> <span id="info-section"></span></p>
                                    <p><strong>Subject:</strong> <span id="info-subject"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Schedule:</strong> <span id="info-schedule"></span></p>
                                    <p><strong>Room:</strong> <span id="info-room"></span></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Total Students:</strong> <span id="info-students"></span></p>
                                </div>
                            </div>
                        </div>
                        <div id="noClassInfo" class="text-center py-4">
                            <p class="text-muted">Select a class to view information</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow mb-4" id="attendanceCard" style="display: none;">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Attendance Sheet</h6>
                <div>
                    <div class="dropdown d-inline-block me-2">
                        <button type="button" class="btn btn-success btn-sm dropdown-toggle" id="markAllDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="check-square"></i> Mark All
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="markAllDropdown">
                            <li><a class="dropdown-item mark-all-status" href="#" data-status="present">Mark All
                                    Present</a></li>
                            <li><a class="dropdown-item mark-all-status" href="#" data-status="absent">Mark All
                                    Absent</a></li>
                            <li><a class="dropdown-item mark-all-status" href="#" data-status="late">Mark All Late</a>
                            </li>
                            <li><a class="dropdown-item mark-all-status" href="#" data-status="excused">Mark All
                                    Excused</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-info btn-sm" id="scanQRBtn">
                        <i data-feather="camera"></i> Scan QR Code
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="attendanceForm" action="#" method="POST">
                    @csrf
                    <input type="hidden" name="section_id" id="form_section_id">
                    <input type="hidden" name="subject_id" id="form_subject_id">
                    <input type="hidden" name="date" id="form_date">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Student ID</th>
                                    <th width="30%">Name</th>
                                    <th width="15%">Status</th>
                                    <th width="40%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <!-- Students will be loaded here dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" id="cancelBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveAttendanceBtn">Save Attendance</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- QR Code Scanner Modal -->
        <div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrScannerModalLabel">Scan Student QR Code</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div id="qrScanner" style="width: 100%;"></div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    Point the camera at a student's QR code to mark them as present.
                                </div>
                                <div id="scanResult" class="alert alert-success" style="display: none;">
                                    Student marked as present: <span id="scannedStudentInfo"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <!-- HTML5 QR Code Scanner Script -->
    <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize feather icons
            feather.replace();

            let html5QrCode;
            let studentsData = {}; // To store loaded students data

            // Grade Level Change Event
            $('#grade_level_id').on('change', function() {
                const gradeLevelId = $(this).val();

                // Reset and disable section dropdown
                $('#section_id').empty().prop('disabled', true);
                $('#section_id').append('<option value="">Loading sections...</option>');

                if (gradeLevelId) {
                    // Make AJAX call to get sections for this grade level
                    $.ajax({
                        url: '{{ route('teacher.sections.by-grade-level') }}',
                        type: 'GET',
                        data: {
                            grade_level: gradeLevelId, // This is the grade_level value we're filtering by
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            // Clear and enable section dropdown
                            $('#section_id').empty().prop('disabled', false);
                            $('#section_id').append('<option value="">Select Section</option>');

                            // Add sections to dropdown
                            if (response.sections && response.sections.length > 0) {
                                $.each(response.sections, function(index, section) {
                                    $('#section_id').append('<option value="' + section
                                        .id +
                                        '">' + section.name + '</option>');
                                });
                            } else {
                                $('#section_id').append(
                                    '<option value="">No sections available for this grade level</option>'
                                );
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Error loading sections';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            $('#section_id').empty().prop('disabled', true);
                            $('#section_id').append(
                                `<option value="">Error: ${errorMessage}</option>`);
                            console.error('Error loading sections:', xhr);
                        }
                    });
                } else {
                    // Reset dropdown if no grade level is selected
                    $('#section_id').empty().prop('disabled', true);
                    $('#section_id').append('<option value="">Select Grade Level First</option>');
                }
            });

            // Load Students Button Click Event
            $('#loadStudentsBtn').on('click', function() {
                const sectionId = $('#section_id').val();
                const subjectId = $('#subject_id').val();
                const date = $('#date').val();

                if (!sectionId || !subjectId || !date) {
                    alert('Please select section, subject, and date');
                    return;
                }

                // In a real application, you would make an AJAX call to get the students
                // For this demo, we'll simulate loading students
                loadStudents(sectionId, subjectId, date);
            });

            // Mark All Status Event
            $('.mark-all-status').on('click', function(e) {
                e.preventDefault();
                const status = $(this).data('status');
                $('.status-select').val(status);
            });

            // Cancel Button Click Event
            $('#cancelBtn').on('click', function() {
                $('#attendanceCard').hide();
                $('#studentsTableBody').empty();
            });

            // Scan QR Code Button Click Event
            $('#scanQRBtn').on('click', function() {
                // Open the QR scanner modal
                $('#qrScannerModal').modal('show');

                // Initialize QR scanner when modal is opened
                initializeQRScanner();
            });

            // Initialize QR Scanner
            function initializeQRScanner() {
                const qrScannerDiv = document.getElementById('qrScanner');

                // Clear previous instances
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        qrScannerDiv.innerHTML = '';
                        startQRScanner();
                    });
                } else {
                    qrScannerDiv.innerHTML = '';
                    startQRScanner();
                }
            } // Removed duplicate QR code initialization
            // Start QR Scanner
            function startQRScanner() {
                html5QrCode = new Html5Qrcode("qrScanner");

                const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                    // Stop scanning after a successful scan
                    html5QrCode.stop().then(() => {
                        console.log('QR Code scanning stopped');
                        processQRCode(decodedText);
                    }).catch(err => {
                        console.error('Error stopping QR Code scanner:', err);
                    });
                };

                const config = {
                    fps: 10,
                    qrbox: {
                        width: 250,
                        height: 250
                    }
                };

                // Start scanning
                setTimeout(function() {
                    html5QrCode.start({
                            facingMode: "environment"
                        },
                        config,
                        qrCodeSuccessCallback
                    ).catch(err => {
                        console.error('Error starting QR Code scanner:', err);
                        alert(
                            'Could not access the camera. Please make sure you have given camera permission. '
                        );
                    }, 1000);
                });

                // Stop scanning when modal is closed
                $('#qrScannerModal').on('hidden.bs.modal', function() {
                    if (html5QrCode && html5QrCode.isScanning) {
                        html5QrCode.stop().then(() => {
                            console.log('QR Code scanning stopped due to modal close');
                        }).catch(err => {
                            console.error('Error stopping QR Code scanner:', err);
                        });
                    }
                });
            }

            // Process QR Code
            function processQRCode(decodedText) {
                const studentId = decodedText.trim();

                // Find the student in the table
                let found = false;
                $('#studentsTableBody tr').each(function() {
                    const rowStudentId = $(this).find('td').eq(1).text().trim();

                    if (rowStudentId === studentId) {
                        found = true;
                        // Mark the student as present
                        $(this).find('select.status-select').val('present');

                        // Show success message
                        const studentName = $(this).find('td').eq(2).text().trim();
                        $('#scannedStudentInfo').text(`${studentId} - ${studentName}`);
                        $('#scanResult').show();

                        // Hide success message after 3 seconds
                        setTimeout(() => {
                            $('#scanResult').hide();
                            $('#qrScannerModal').modal('hide');
                        }, 3000);

                        return false; // Break the loop
                    }
                });

                if (!found) {
                    alert(`Student with ID ${studentId} not found in this class!`);
                    // Restart the scanner
                    startQRScanner();
                }
            }

            // Function to load students
            function loadStudents(sectionId, subjectId, date) {
                // Show loading indicator
                $('#studentsTableBody').html(
                    '<tr><td colspan="5" class="text-center">Loading students...</td></tr>');
                $('#attendanceCard').show();

                // Make AJAX call to get students for this section, subject and date
                $.ajax({
                    url: '{{ route('teacher.attendance.get-students') }}',
                    type: 'GET',
                    data: {
                        section_id: sectionId,
                        subject_id: subjectId,
                        date: date,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Update class info
                        $('#info-section').text(response.section.name);
                        $('#info-subject').text(response.subject.name);

                        // Set schedule and room info if available
                        let scheduleText = 'N/A';
                        let roomText = 'N/A';

                        if (response.schedule) {
                            scheduleText = response.schedule.day + ' ' + response.schedule.start_time +
                                ' - ' + response.schedule.end_time;
                            roomText = response.schedule.room || 'N/A';
                        }

                        $('#info-schedule').text(scheduleText);
                        $('#info-room').text(roomText);
                        $('#info-students').text(response.students.length);

                        $('#classInfo').show();
                        $('#noClassInfo').hide();

                        // Update form hidden fields
                        $('#form_section_id').val(sectionId);
                        $('#form_subject_id').val(subjectId);
                        $('#form_date').val(date);

                        // Clear previous students
                        $('#studentsTableBody').empty();

                        // Reset studentsData
                        studentsData = {};

                        // Add students from response
                        if (response.students.length > 0) {
                            $.each(response.students, function(index, student) {
                                const i = index + 1;

                                // Store student data for QR code processing
                                studentsData[student.student_id] = {
                                    id: student.id,
                                    qr: student.qr_code,
                                    name: student.name
                                };

                                // Set status values based on existing attendance data
                                let selectedStatus = 'present';
                                let remarks = '';

                                if (student.attendance) {
                                    selectedStatus = student.attendance.status;
                                    remarks = student.attendance.remarks || '';
                                }

                                const row = `
                                <tr>
                                    <td>${i}</td>
                                    <td>${student.qr_code}</td>
                                    <td>${student.name}</td>
                                    <td>
                                        <select class="form-select status-select" name="status[${student.id}]" required>
                                            <option value="present" ${selectedStatus === 'present' ? 'selected' : ''}>Present</option>
                                            <option value="late" ${selectedStatus === 'late' ? 'selected' : ''}>Late</option>
                                            <option value="absent" ${selectedStatus === 'absent' ? 'selected' : ''}>Absent</option>
                                            <option value="excused" ${selectedStatus === 'excused' ? 'selected' : ''}>Excused</option>
                                        </select>
                                    </td>
                        <td>
                                        <input type="text" class="form-control" name="remarks[${student.id}]" value="${remarks}" placeholder="Optional remarks">
                                    </td>
                                </tr>
                                `;

                                $('#studentsTableBody').append(row);
                            });
                        } else {
                            $('#studentsTableBody').html(
                                '<tr><td colspan="5" class="text-center">No students found in this section</td></tr>'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error loading students';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        $('#studentsTableBody').html(
                            `<tr><td colspan="5" class="text-center text-danger">${errorMessage}</td></tr>`
                        );
                        console.error('Error loading students:', xhr);
                    }
                });

                // Show attendance card
                $('#attendanceCard').show();
            }

            // Form Submit Event
            $('#attendanceForm').on('submit', function(e) {
                e.preventDefault();

                // Disable the submit button to prevent double submission
                $('#saveAttendanceBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                );

                // Get form data
                const formData = $(this).serialize();

                // Submit form data via AJAX
                $.ajax({
                    url: '{{ route('teacher.attendance.save') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        // Show success message
                        alert('Attendance saved successfully!');

                        // Reset form
                        $('#attendanceCard').hide();
                        $('#studentsTableBody').empty();
                        $('#classSelectionForm').trigger('reset');
                        $('#classInfo').hide();
                        $('#noClassInfo').show();
                    },
                    error: function(xhr) {
                        // Show error message
                        let errorMessage = 'An error occurred while saving attendance';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert('Error: ' + errorMessage);
                    },
                    complete: function() {
                        // Re-enable the submit button
                        $('#saveAttendanceBtn').prop('disabled', false).html(
                            'Save Attendance');
                    }
                });
            });
        });
    </script>
@endpush
