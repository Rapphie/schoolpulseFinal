@extends('base')

@section('title', 'Take Attendance')

@section('content')
    @if ($isActiveQuarterLocked)
        <div class="alert alert-warning d-flex align-items-start mb-3" role="alert">
            <i class="fas fa-lock mt-1 me-2"></i>
            <div>
                <strong>{{ $activeQuarter?->name ?? 'Active quarter' }} is locked.</strong>
                Attendance saving is disabled until an administrator unlocks this quarter.
                @if ($activeQuarterLockReason)
                    <span class="badge bg-warning text-dark ms-1">{{ $activeQuarterLockReason }}</span>
                @endif
            </div>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Class Selection</h6>
                </div>
                <div class="card-body">
                    <form id="classSelectionForm">
                        <div class="mb-3">
                            <label for="quarter" class="form-label">Quarter <span class="text-danger">*</span></label>
                            @if ($activeQuarter)
                                <input type="text" class="form-control" value="{{ $activeQuarter->name }}" readonly
                                    disabled>
                                <input type="hidden" id="quarter" name="quarter" value="{{ $activeQuarter->name }}"
                                    required>
                                <small class="form-text text-muted">Current active quarter is automatically
                                    selected.</small>
                            @else
                                <div class="alert alert-warning py-2 mb-0">
                                    <small><i class="fa fa-exclamation-triangle me-1"></i> No active quarter found. Please
                                        contact the administrator.</small>
                                </div>
                                <input type="hidden" id="quarter" name="quarter" value="" required>
                            @endif
                        </div>
                        <div class="mb-3">
                            <!-- Grade level selection removed: only section and quarter are selectable -->
                        </div>
                        <div class="mb-3">
                            <label for="section_id" class="form-label">Section <span class="text-danger">*</span></label>
                            <div class="dropdown" id="sectionDropdownWrapper">
                                <button class="form-select text-start" type="button" id="sectionDropdownBtn"
                                    data-bs-toggle="dropdown" aria-expanded="false" style="text-align:left;">
                                    <span id="sectionDropdownSelected" class="text-start">Select Section</span>
                                </button>
                                <ul class="dropdown-menu w-100" id="sectionDropdownMenu"
                                    aria-labelledby="sectionDropdownBtn" style="max-height:200px;overflow-y:auto;">
                                    <li class="px-3 py-2">
                                        <input type="text" class="form-control" id="sectionDropdownSearch"
                                            placeholder="Search section...">
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>

                                    @foreach ($sections as $class)
                                        <li>
                                            <span class="dropdown-item section-option" style="cursor:pointer;"
                                                data-id="{{ $class->section->id }}"
                                                data-section-id="{{ $class->section->id }}"
                                                data-class-id="{{ $class->id }}"
                                                data-is-adviser="{{ (int) ((int) $class->teacher_id === (int) $teacherId) }}"
                                                data-grade-level="{{ (int) ($class->section->gradeLevel->level ?? 0) }}">
                                                {{ 'Grade ' . ($class->section->gradeLevel->level ?? 'Error') }} -
                                                {{ $class->section->name }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                <input type="hidden" id="section_id" name="section_id" required>
                            </div>
                            <small class="form-text text-muted">Only your assigned sections are shown. Click to search and
                                select.</small>
                            <div class="alert alert-warning py-2 mt-2 mb-0 {{ $sections->isEmpty() ? '' : 'd-none' }}"
                                id="noHandledSubjectWarning" role="alert">
                                <i class="fa fa-exclamation-triangle me-1"></i>
                                <span id="noHandledSubjectWarningText">You do not have any handled subjects yet. Please
                                    contact the administrator.</span>
                            </div>
                        </div>

                        <div class="mb-3 d-none" id="allDayToggleWrapper">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allDayToggle" name="all_day">
                                <label class="form-check-label fw-bold" for="allDayToggle">
                                    Apply to All Subjects of the Day
                                </label>
                            </div>
                            <div class="alert alert-warning py-2 mt-2 mb-0 d-none" id="allDayGradeWarning" role="alert">
                                All-subject attendance marks all scheduled class subjects, including those not handled by
                                you.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                            <div class="dropdown" id="subjectDropdownWrapper">
                                <button class="form-select text-start" type="button" id="subjectDropdownBtn"
                                    data-bs-toggle="dropdown" aria-expanded="false" style="text-align:left;">
                                    <span id="subjectDropdownSelected" class="text-start">Select Subject</span>
                                </button>
                                <ul class="dropdown-menu w-100" id="subjectDropdownMenu"
                                    aria-labelledby="subjectDropdownBtn" style="max-height:200px;overflow-y:auto;">
                                    <li class="px-3 py-2">
                                        <input type="text" class="form-control" id="subjectDropdownSearch"
                                            placeholder="Search subject...">
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li id="subjectDropdownLoading" class="px-3 py-2 text-muted">
                                        Please select a section first
                                    </li>
                                    <!-- Subjects will be loaded dynamically when section is selected -->
                                </ul>
                                <input type="hidden" id="subject_id" name="subject_id" required>
                            </div>
                            <small class="form-text text-muted">Select a section first to load available subjects.</small>
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
                                <p><strong>Code:</strong> <span id="info-subject-code"></span></p>
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
                        data-bs-toggle="dropdown" aria-expanded="false" @disabled($isActiveQuarterLocked)>
                        <i data-feather="check-square" class="align-middle"></i> Mark All
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="markAllDropdown">
                        <li><a class="dropdown-item mark-all-status" href="#" data-status="present">Present</a>
                        </li>
                        <li><a class="dropdown-item mark-all-status" href="#" data-status="absent">Absent</a></li>
                        <li><a class="dropdown-item mark-all-status" href="#" data-status="late">Late</a></li>
                        <li><a class="dropdown-item mark-all-status" href="#" data-status="excused">Excused</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Search input for attendance sheet -->
            <div class="mb-3">
                <input type="text" id="attendanceSearchInput" class="form-control"
                    placeholder="Search student by name or ID...">
            </div>
            <form id="attendanceForm" action="#" method="POST">
                @csrf
                <input type="hidden" name="section_id" id="form_section_id">
                <input type="hidden" name="subject_id" id="form_subject_id">
                <input type="hidden" name="date" id="form_date">
                <input type="hidden" name="quarter" id="form_quarter">
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
                    <button type="submit" class="btn btn-primary" id="saveAttendanceBtn" @disabled($isActiveQuarterLocked)>Save Attendance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Summary Modal -->
    <div class="modal fade" id="attendanceSummaryModal" tabindex="-1" aria-labelledby="attendanceSummaryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attendanceSummaryModalLabel">Attendance Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success mb-3" id="successAlert">
                        <i class="fa fa-check-circle"></i> Attendance has been successfully recorded.
                    </div>
                    <p><strong>Total Students:</strong> <span id="summary-total-students"></span></p>
                    <hr>
                    <h6>Absentees</h6>
                    <p><strong>Total Absents:</strong> <span id="summary-total-absent"></span></p>
                    <p><strong>Male Absents:</strong> <span id="summary-male-absent"></span></p>
                    <p><strong>Female Absents:</strong> <span id="summary-female-absent"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalTitle">Error</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorModalBody"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isActiveQuarterLocked = @json($isActiveQuarterLocked);
            let selectedSectionMeta = null;

            // Attendance sheet search functionality
            $('#attendanceSearchInput').on('keyup', function() {
                const search = $(this).val().toLowerCase();
                $('#studentsTableBody tr').each(function() {
                    const studentId = $(this).find('td').eq(1).text().toLowerCase();
                    const studentName = $(this).find('td').eq(2).text().toLowerCase();
                    if (studentId.indexOf(search) !== -1 || studentName.indexOf(search) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            // Section dropdown search and select
            const $dropdownBtn = $('#sectionDropdownBtn');
            const $dropdownMenu = $('#sectionDropdownMenu');
            const $dropdownSearch = $('#sectionDropdownSearch');
            const $dropdownSelected = $('#sectionDropdownSelected');
            const $sectionIdInput = $('#section_id');
            const $allDayToggleWrapper = $('#allDayToggleWrapper');
            const $allDayToggle = $('#allDayToggle');
            const $allDayGradeWarning = $('#allDayGradeWarning');
            const $subjectDropdownBtn = $('#subjectDropdownBtn');
            const $subjectDropdownMenu = $('#subjectDropdownMenu');
            const $subjectDropdownSearch = $('#subjectDropdownSearch');
            const $subjectDropdownSelected = $('#subjectDropdownSelected');
            const $subjectIdInput = $('#subject_id');
            const $noHandledSubjectWarning = $('#noHandledSubjectWarning');
            const $noHandledSubjectWarningText = $('#noHandledSubjectWarningText');

            function isGradeFourToSix(gradeLevel) {
                const level = Number(gradeLevel || 0);
                return level >= 4 && level <= 6;
            }

            function updateAllDayControls() {
                const isAdviser = Boolean(selectedSectionMeta && selectedSectionMeta.isAdviser);
                $allDayToggle.prop('disabled', !isAdviser);

                if (isAdviser) {
                    $allDayToggleWrapper.removeClass('d-none');
                } else {
                    $allDayToggleWrapper.addClass('d-none');
                    $allDayToggle.prop('checked', false);
                    $subjectDropdownBtn.prop('disabled', false);
                    $subjectDropdownSelected.text('Select Subject');
                    $subjectIdInput.val('');
                }

                const showGradeWarning = isAdviser && isGradeFourToSix(selectedSectionMeta?.gradeLevel);
                $allDayGradeWarning.toggleClass('d-none', !showGradeWarning);
            }

            function showNoHandledSubjectWarning(message) {
                $noHandledSubjectWarningText.text(message);
                $noHandledSubjectWarning.removeClass('d-none');
            }

            function hideNoHandledSubjectWarning() {
                $noHandledSubjectWarning.addClass('d-none');
            }

            function getNoHandledSubjectWarningMessage() {
                const baseMessage = 'You do not handle any subjects for this section.';
                if (selectedSectionMeta && selectedSectionMeta.isAdviser) {
                    return `${baseMessage} You can still use "Apply to All Subjects of the Day" for your advisory class.`;
                }

                return baseMessage;
            }

            updateAllDayControls();
            // Filter items as user types
            $dropdownSearch.on('keyup', function() {
                const search = $(this).val().toLowerCase();
                $dropdownMenu.find('.section-option').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).parent().toggle(text.indexOf(search) !== -1);
                });
            });
            // Select item
            $dropdownMenu.on('click', '.section-option', function(e) {
                const $selectedOption = $(this);
                const sectionName = $(this).text();
                const sectionId = $(this).data('id');
                selectedSectionMeta = {
                    sectionId: Number($selectedOption.data('section-id')),
                    classId: Number($selectedOption.data('class-id')),
                    isAdviser: Number($selectedOption.data('is-adviser')) === 1,
                    gradeLevel: Number($selectedOption.data('grade-level')),
                };

                $dropdownSelected.text(sectionName);
                $sectionIdInput.val(sectionId).trigger('change');
                updateAllDayControls();
                hideNoHandledSubjectWarning();
                // Hide dropdown after selection
                $dropdownBtn.dropdown('toggle');

                // Load subjects for the selected section
                loadSubjectsForSection(sectionId);
            });
            // Reset search on open
            $dropdownBtn.on('click', function() {
                $dropdownSearch.val('');
                $dropdownMenu.find('.section-option').parent().show();
                setTimeout(() => $dropdownSearch.focus(), 100);
            });
            // Filter subject items as user types
            $subjectDropdownSearch.on('keyup', function() {
                const search = $(this).val().toLowerCase();
                $subjectDropdownMenu.find('.subject-option').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).parent().toggle(text.indexOf(search) !== -1);
                });
            });

            // Select subject item
            $subjectDropdownMenu.on('click', '.subject-option', function(e) {
                const subjectName = $(this).text();
                const subjectId = $(this).data('id');
                $subjectDropdownSelected.text(subjectName);
                $subjectIdInput.val(subjectId).trigger('change');
                // Hide dropdown after selection
                $subjectDropdownBtn.dropdown('toggle');
            });

            // Reset subject search on open
            $subjectDropdownBtn.on('click', function() {
                $subjectDropdownSearch.val('');
                $subjectDropdownMenu.find('.subject-option').parent().show();
                setTimeout(() => $subjectDropdownSearch.focus(), 100);
            });

            // Handle All-Day Toggle
            $allDayToggle.on('change', function() {
                const isAdviser = Boolean(selectedSectionMeta && selectedSectionMeta.isAdviser);
                const isChecked = $(this).is(':checked');
                if (isChecked && !isAdviser) {
                    $(this).prop('checked', false);
                    return;
                }

                if (isChecked) {
                    $subjectDropdownBtn.prop('disabled', true);
                    $subjectDropdownSelected.text('All Scheduled Subjects');
                    $('#subject_id').val('all'); // Special value for backend
                } else {
                    $subjectDropdownBtn.prop('disabled', false);
                    $subjectDropdownSelected.text('Select Subject');
                    $('#subject_id').val('');
                    // Trigger section change to reload subjects if a section is already selected
                    const sectionId = $('#section_id').val();
                    if (sectionId) {
                        loadSubjectsForSection(sectionId);
                    }
                }
            });

            // Function to load subjects for a selected section
            function loadSubjectsForSection(sectionId) {
                // Reset subject dropdown
                $subjectDropdownSelected.text('Loading subjects...');
                $subjectIdInput.val('');

                // Show loading message
                $('#subjectDropdownMenu li:not(:first-child):not(:nth-child(2))').remove();
                $('#subjectDropdownLoading').text('Loading subjects...').show();

                $.ajax({
                    url: "{{ route('teacher.subjects.by-section', ['section' => ':sectionId']) }}".replace(
                        ':sectionId', sectionId),
                    type: 'GET',
                    success: function(response) {
                        // Remove loading message
                        $('#subjectDropdownLoading').hide();

                        // Reset dropdown
                        $subjectDropdownSelected.text('Select Subject');
                        let subjects = [];

                        if (Array.isArray(response)) {
                            subjects = response;
                        } else if (response.subjects && Array.isArray(response.subjects)) {
                            subjects = response.subjects;
                        } else if (response.data && Array.isArray(response.data)) {
                            subjects = response.data;
                        } else {
                            $('#subjectDropdownLoading').text('Unexpected data format from server').show();
                            return;
                        }

                        if (subjects.length > 0) {
                            subjects.forEach(subject => {
                                const $li = $('<li></li>');
                                const $span = $('<span></span>')
                                    .addClass('dropdown-item subject-option')
                                    .attr('data-id', subject.id)
                                    .text(subject.name)
                                    .css('cursor', 'pointer');

                                $li.append($span);
                                $('#subjectDropdownMenu').append($li);
                            });
                            hideNoHandledSubjectWarning();
                        } else {
                            $('#subjectDropdownLoading').text('No subjects available for this section').show();
                            showNoHandledSubjectWarning(getNoHandledSubjectWarningMessage());
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMsg = 'Error loading subjects';

                        // Try to get more detailed error information
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            try {
                                const errorData = JSON.parse(xhr.responseText);
                                if (errorData.message) {
                                    errorMsg = errorData.message;
                                }
                            } catch (e) {
                                // If we can't parse the response, use the status text
                                if (xhr.statusText) {
                                    errorMsg += ': ' + xhr.statusText;
                                }
                            }
                        }

                        // Show the error with status code if available
                        if (xhr.status) {
                            errorMsg += ' (Status: ' + xhr.status + ')';
                        }

                        $('#subjectDropdownLoading').text(errorMsg).show();
                        $subjectDropdownSelected.text('Error loading subjects');
                    }
                });
            }

            // Initialize feather icons
            feather.replace();

            let studentsData = {}; // To store loaded students data

            // Load Students Button Click Event
            $('#loadStudentsBtn').on('click', function() {
                const sectionId = $('#section_id').val();
                const selectedSubjectId = $('#subject_id').val();
                const date = $('#date').val();
                const quarter = $('#quarter').val();
                const canUseAllDay = Boolean(selectedSectionMeta && selectedSectionMeta.isAdviser);
                const isAllDay = canUseAllDay && $('#allDayToggle').is(':checked');
                const effectiveSubjectId = isAllDay ? 'all' : selectedSubjectId;

                if (!quarter) {
                    alert('No active quarter available. Please contact the administrator.');
                    return;
                }

                if (!sectionId) {
                    alert('Please select a section');
                    return;
                }

                if (isAllDay && !canUseAllDay) {
                    alert('All-subject attendance is only available for your advisory class.');
                    return;
                }

                if (!isAllDay && !effectiveSubjectId) {
                    if ($subjectDropdownMenu.find('.subject-option').length === 0) {
                        showNoHandledSubjectWarning(getNoHandledSubjectWarningMessage());
                    }
                    alert('Please select a subject');
                    return;
                }

                if (!date) {
                    alert('Please select a date');
                    return;
                }

                loadStudents(sectionId, effectiveSubjectId, date);
            });

            // Mark All Status Event
            $('.mark-all-status').on('click', function(e) {
                e.preventDefault();

                if (isActiveQuarterLocked) {
                    return;
                }
                const status = $(this).data('status');
                $('.status-select').val(status);
            });

            // Cancel Button Click Event
            $('#cancelBtn').on('click', function() {
                $('#attendanceCard').hide();
                $('#studentsTableBody').empty();
            });

            // Function to load students
            function loadStudents(sectionId, subjectId, date) {
                // Show loading indicator
                $('#studentsTableBody').html(
                    '<tr><td colspan="5" class="text-center">Loading students...</td></tr>');
                $('#attendanceCard').show();

                const isAllDay = subjectId === 'all';

                // Make AJAX call to get students for this section, subject and date
                $.ajax({
                    url: '{{ route('teacher.attendance.get-students') }}',
                    type: 'GET',
                    data: {
                        section_id: sectionId,
                        subject_id: subjectId,
                        date: date
                    },
                    success: function(response) {
                        if (response.warning) {
                            $allDayGradeWarning.text(response.warning).removeClass('d-none');
                        } else {
                            updateAllDayControls();
                        }

                        // Update class info
                        $('#info-section').text(response.section.name);
                        $('#info-subject').text(isAllDay ? 'All Scheduled Subjects' : response.subject
                            .name);
                        $('#info-subject-code').text(isAllDay ? 'MULTIPLE' : response.subject.code);

                        let scheduleText = 'N/A';
                        let roomText = 'N/A';

                        if (response.schedule) {

                            let dayOfWeek = response.schedule.day_of_week;
                            let displayDays = '';

                            try {
                                // Check if dayOfWeek have more than one day
                                if (typeof dayOfWeek === 'object') {
                                    // It's already an object/array, use it directly
                                    const daysArray = Array.isArray(dayOfWeek) ? dayOfWeek : Object
                                        .values(dayOfWeek);

                                    if (Array.isArray(daysArray) && daysArray.length > 0) {
                                        // If multiple days, abbreviate each and join
                                        displayDays = daysArray.map(day => {
                                            const dayLower = day.toLowerCase();
                                            switch (dayLower) {
                                                case 'monday':
                                                    return 'M';
                                                case 'tuesday':
                                                    return 'T';
                                                case 'wednesday':
                                                    return 'W';
                                                case 'thursday':
                                                    return 'Th';
                                                case 'friday':
                                                    return 'F';
                                                case 'saturday':
                                                    return 'Sa';
                                                case 'sunday':
                                                    return 'Su';
                                                default:
                                                    return day;
                                            }
                                        }).join(', ');
                                    } else {
                                        displayDays = dayOfWeek; // Keep original if parsing failed
                                    }
                                } else {
                                    // Single day as string
                                    displayDays = dayOfWeek;
                                }
                            } catch (e) {
                                displayDays = dayOfWeek;
                            }

                            scheduleText = displayDays + '<br>' +
                                response.schedule.start_time.substring(0, 5) + '-' +
                                response.schedule.end_time.substring(0, 5);
                            roomText = response.schedule.room || 'N/A';
                        }

                        $('#info-schedule').html(scheduleText);
                        $('#info-room').text(roomText);
                        $('#info-students').text(response.students.length);

                        $('#classInfo').show();
                        $('#noClassInfo').hide();

                        // Update form hidden fields
                        $('#form_section_id').val(sectionId);
                        $('#form_subject_id').val(isAllDay ? 'all' : subjectId);
                        $('#form_date').val(date);
                        $('#form_quarter').val($('#quarter').val());

                        // Clear previous students
                        $('#studentsTableBody').empty();

                        // Reset studentsData
                        studentsData = {};

                        // Add students from response
                        if (response.students.length > 0) {
                            $.each(response.students, function(index, student) {
                                const i = index + 1;

                                // Store student data for QR code processing and summary
                                studentsData[student.student_id] = {
                                    id: student.id,
                                    student_id: student.student_id,
                                    name: student.name,
                                    gender: student.gender // Store gender for summary
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
                                    <td>${student.student_id}</td>
                                    <td>${student.name}</td>
                                    <td>
                                        <select class="form-select status-select" name="status[${student.id}]" required ${isActiveQuarterLocked ? 'disabled' : ''}>
                                            <option value="present" ${selectedStatus === 'present' ? 'selected' : ''}>Present</option>
                                            <option value="late" ${selectedStatus === 'late' ? 'selected' : ''}>Late</option>
                                            <option value="absent" ${selectedStatus === 'absent' ? 'selected' : ''}>Absent</option>
                                            <option value="excused" ${selectedStatus === 'excused' ? 'selected' : ''}>Excused</option>
                                        </select>
                                    </td>
                        <td>
                                        <input type="text" class="form-control" name="remarks[${student.id}]" value="${remarks}" placeholder="Optional remarks" ${isActiveQuarterLocked ? 'disabled' : ''}>
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
                    }
                });

                // Show attendance card
                $('#attendanceCard').show();
            }

            // Form Submit Event
            $('#attendanceForm').on('submit', function(e) {
                e.preventDefault();

                if (isActiveQuarterLocked) {
                    alert('The active quarter is locked. Attendance saving is disabled.');
                    return;
                }

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
                        // Show success message at the top of the modal
                        $('#attendanceSummaryModalLabel').html(
                            '<i class="text-success fa fa-check-circle me-2"></i> Attendance Saved Successfully'
                        );
                        const warningHtml = response.warning ?
                            `<div class="mt-2 small text-warning"><i class="fa fa-exclamation-triangle me-1"></i>${response.warning}</div>` :
                            '';
                        $('#successAlert').html(
                            `<i class="fa fa-check-circle me-1"></i> Attendance has been successfully recorded.${warningHtml}`
                        );

                        // Calculate summary
                        const totalStudents = $('#studentsTableBody tr')
                            .length;
                        let absentCount = 0;
                        let maleAbsentCount = 0;
                        let femaleAbsentCount = 0;
                        let maleStudentsCount = 0;
                        let femaleStudentsCount = 0;

                        $('#studentsTableBody tr').each(function() {
                            const status = $(this).find('.status-select').val();
                            const studentId = $(this).find('td').eq(1).text().trim();
                            const student = studentsData[studentId];
                            if (student && student.gender) {
                                if (student.gender.toLowerCase() === 'male')
                                    maleStudentsCount++;
                                if (student.gender.toLowerCase() === 'female')
                                    femaleStudentsCount++;
                            }
                            if (status === 'absent') {
                                absentCount++;
                                if (student && student.gender) {
                                    if (student.gender.toLowerCase() === 'male')
                                        maleAbsentCount++;
                                    if (student.gender.toLowerCase() === 'female')
                                        femaleAbsentCount++;
                                }
                            }
                        });

                        const absentPercentage = totalStudents > 0 ? ((absentCount /
                            totalStudents) * 100).toFixed(2) : 0;
                        const maleAbsentPercentage = maleStudentsCount > 0 ? ((maleAbsentCount /
                            maleStudentsCount) * 100).toFixed(2) : 0;
                        const femaleAbsentPercentage = femaleStudentsCount > 0 ? ((
                            femaleAbsentCount / femaleStudentsCount) * 100).toFixed(2) : 0;

                        // Populate and show summary modal
                        $('#summary-total-students').text(totalStudents);
                        $('#summary-total-absent').text(
                            `${absentCount} (${absentPercentage}%)`);
                        $('#summary-male-absent').text(
                            `${maleAbsentCount} (${maleAbsentPercentage}%)`);
                        $('#summary-female-absent').text(
                            `${femaleAbsentCount} (${femaleAbsentPercentage}%)`);

                        $('#attendanceSummaryModal').modal('show');

                        // Reset form and UI after modal is closed
                        $('#attendanceSummaryModal').on('hidden.bs.modal', function() {
                            $('#attendanceCard').hide();
                            $('#classInfo').hide();
                            $('#noClassInfo').show().find('p').text(
                                'Select a class to view information');
                            $('#classSelectionForm')[0].reset();
                            $dropdownSelected.text('Select Section');
                            $sectionIdInput.val('');
                            $subjectDropdownSelected.text('Select Subject');
                            $subjectIdInput.val('');
                            selectedSectionMeta = null;
                            updateAllDayControls();
                            $(this).off('hidden.bs.modal'); // Unbind event
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while saving attendance.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        // Show error in modal instead of alert
                        $('#errorModalTitle').text('Error Saving Attendance');
                        $('#errorModalBody').text(errorMessage);
                        $('#errorModal').modal('show');
                    },
                    complete: function() {
                        // Re-enable the submit button
                        $('#saveAttendanceBtn').prop('disabled', isActiveQuarterLocked).html('Save Attendance');
                    }
                });
            });
        });
    </script>
@endpush
