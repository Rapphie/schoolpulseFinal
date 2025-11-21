@extends('base')

@section('title', 'Manage ' . $section->name)

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Classes</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage {{ $section->name }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-800">Manage: {{ $section->name }}</h1>
                <p class="mb-0 text-muted">School Year: {{ $class->schoolYear->name }}</p>
            </div>
            <form action="{{ route('admin.sections.destroy', $class) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this section? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-lg">Delete</button>
            </form>
        </div>

        <div class="row">
            <!-- Adviser Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    {{ $section->gradeLevel->name }} Adviser
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $class->teacher->user->first_name ?? 'Not' }}
                                    {{ $class->teacher->user->last_name ?? 'Assigned' }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#assignAdviserModal">
                            Change Adviser
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enrollment Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Enrollment</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $class->enrollments->count() }} /
                                    {{ $class->capacity }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrolled Students Table -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Enrolled Students</h6>
                <button class="btn btn-sm btn-primary d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#enrollStudentModal">
                    <i data-feather="plus" class="feather-sm me-1"></i> Enroll New Student
                </button>
            </div>
            <div class="card-body">
                @if ($class->enrollments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Guardian</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($class->enrollments as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->student->lrn ?? 'N/A' }}</td>
                                        <td>{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}
                                        </td>
                                        <td>{{ ucfirst($enrollment->student->gender) }}</td>
                                        <td>{{ $enrollment->student->guardian->user->first_name ?? 'N/A' }}
                                            {{ $enrollment->student->guardian->user->last_name ?? '' }}</td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-outline-secondary">View Profile</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-muted">No students are currently enrolled in this class.</p>
                @endif
            </div>
        </div>

        <!-- Class Schedule Table -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Class Schedule</h6>
            </div>
            <div class="card-body">
                @if ($subjects->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Subject</th>
                                    <th>Assigned Teacher</th>
                                    <th>Schedule</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subjects as $subject)
                                    @php
                                        // Find the schedule for this specific subject in this class
                                        $schedule = $class->schedules->where('subject_id', $subject->id)->first();
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $subject->name }}</td>
                                        @if ($schedule)
                                            <td>{{ $schedule->teacher->user->first_name }}
                                                {{ $schedule->teacher->user->last_name }}</td>
                                            <td>
                                                @php
                                                    $dayArray = is_array($schedule->day_of_week)
                                                        ? $schedule->day_of_week
                                                        : explode(',', $schedule->day_of_week);
                                                @endphp
                                                <span class="d-block">
                                                    {{ implode(', ', array_map('ucfirst', array_map('trim', $dayArray))) }}</span>
                                                <small class="text-muted">{!! $schedule->start_time?->format('g:i A') ?? '<em>Not Set</em>' !!} -
                                                    {!! $schedule->end_time?->format('g:i A') ?? '<em>Not Set</em>' !!}</small>
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="d-flex justify-content-center gap-2 align-items-center">
                                                    <button class="btn btn-sm btn-outline-secondary edit-schedule-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editScheduleModal"
                                                        data-schedule-id="{{ $schedule->id }}"
                                                        data-update-url="{{ route('admin.schedules.update', $schedule->id) }}"
                                                        data-teacher-id="{{ $schedule->teacher_id }}"
                                                        data-days="{{ is_array($schedule->day_of_week) ? implode(',', $schedule->day_of_week) : $schedule->day_of_week }}"
                                                        data-start-time="{{ $schedule->start_time }}"
                                                        data-end-time="{{ $schedule->end_time }}"
                                                        data-room="{{ $schedule->room }}"
                                                        data-subject-id="{{ $schedule->subject_id }}"
                                                        data-subject-name="{{ $schedule->subject?->name }}">
                                                        Edit
                                                    </button>

                                                    <form action="{{ route('admin.schedules.destroy', $schedule->id) }}"
                                                        method="POST" class="m-0"
                                                        onsubmit="return confirm('This action cannot be undone. Confirm to delete Schedule?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger">Remove</button>
                                                    </form>
                                                </div>
                                            </td>
                                        @else
                                            <td class="text-muted"><em>Not Assigned</em></td>
                                            <td class="text-muted"><em>Not Set</em></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-primary assign-schedule-btn"
                                                    data-bs-toggle="modal" data-bs-target="#addScheduleModal"
                                                    data-subject-id="{{ $subject->id }}"
                                                    data-subject-name="{{ $subject->name }}">
                                                    Assign Schedule
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-muted">No subjects have been created for {{ $section->gradeLevel->name }}
                        yet.
                        <a href="{{ route('admin.subjects.index') }}">Add subjects here.</a>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Enroll New Student Modal -->
    <div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-labelledby="enrollStudentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="enrollStudentModalLabel">Enroll New Student in {{ $section->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.enrollment.store', $class) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="class_id" value="{{ $class->id }}">
                        <h6 class="mb-3 border-bottom pb-2">Student Information</h6>
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
                            <select class="form-select" id="guardian_relationship" name="guardian_relationship" required>
                                <option value="parent">Parent</option>
                                <option value="sibling">Sibling</option>
                                <option value="relative">Relative</option>
                                <option value="guardian">Guardian</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Enroll Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addScheduleModalLabel">Add Schedule Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.sections.schedule.store', $class) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="subject_select" class="form-label">Subject</label>
                                <select class="form-select" id="subject_select" aria-describedby="subjectHelp">
                                    <option value="">-- Select a subject --</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="subject_id" id="subject_id_input">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="teacher_id" class="form-label">Teacher</label>
                                <select class="form-select" name="teacher_id" required>
                                    <option value="">-- Select a teacher --</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->user->first_name }}
                                            {{ $teacher->user->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0">Day(s) of the Week</label>
                                <button class="btn btn-link btn-sm p-0" type="button"
                                    data-day-toggle="#adminDaySelector" aria-expanded="false"
                                    aria-controls="adminDaySelector" data-hide-label="Hide days">
                                    Change days
                                </button>
                            </div>
                            <p class="small text-muted mb-2">Defaults to Monday through Friday. Expand if you need to
                                adjust.</p>
                            <div id="adminDaySelector" class="day-selector d-none">
                                @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="day_of_week[]"
                                            value="{{ $day }}" id="day_{{ $day }}" checked>
                                        <label class="form-check-label"
                                            for="day_{{ $day }}">{{ ucfirst($day) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label">Start Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="start_time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">End Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="end_time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="room" class="form-label">Room (Optional)</label>
                                <input type="text" class="form-control" name="room">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add to Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editScheduleForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="subject_id" id="edit_subject_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_subject_display" class="form-label">Subject</label>
                                <input type="text" id="edit_subject_display" class="form-control" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_teacher_id" class="form-label">Teacher</label>
                                <select class="form-select" id="edit_teacher_id" name="teacher_id" required>
                                    <option value="">-- Select a teacher --</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}">{{ $teacher->user->first_name }}
                                            {{ $teacher->user->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label mb-0">Day(s) of the Week</label>
                            <div class="small text-muted mb-2">Select the days for this schedule.</div>
                            <div id="editDaySelector">
                                @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input edit-day-checkbox" type="checkbox"
                                            name="day_of_week[]" value="{{ $day }}"
                                            id="edit_day_{{ $day }}">
                                        <label class="form-check-label"
                                            for="edit_day_{{ $day }}">{{ ucfirst($day) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_start_time" class="form-label">Start Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="start_time" id="edit_start_time"
                                    required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_end_time" class="form-label">End Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="end_time" id="edit_end_time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_room" class="form-label">Room (Optional)</label>
                                <input type="text" class="form-control" name="room" id="edit_room">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Assign Adviser Modal -->
    <div class="modal fade" id="assignAdviserModal" tabindex="-1" aria-labelledby="assignAdviserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignAdviserModalLabel">Assign Adviser</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.sections.adviser.assign', $class) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Select Teacher</label>
                            <select class="form-select" name="teacher_id" id="teacher_id" required>
                                <option value="">-- Select an adviser --</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}"
                                        {{ $class->teacher_id == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->user->first_name }} {{ $teacher->user->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @once('day-toggle-script')
        <script>
            (() => {
                const wireDayToggleButtons = () => {
                    document.querySelectorAll('[data-day-toggle]').forEach((btn) => {
                        if (btn.dataset.dayToggleBound === 'true') {
                            return;
                        }

                        btn.dataset.dayToggleBound = 'true';
                        const showLabel = btn.dataset.showLabel || btn.textContent.trim() || 'Change days';
                        const hideLabel = btn.dataset.hideLabel || 'Hide days';
                        btn.dataset.showLabel = showLabel;
                        btn.dataset.hideLabel = hideLabel;

                        btn.addEventListener('click', () => {
                            const selector = btn.getAttribute('data-day-toggle');
                            if (!selector) {
                                return;
                            }

                            const target = document.querySelector(selector);
                            if (!target) {
                                return;
                            }

                            const isHidden = target.classList.toggle('d-none');
                            const expanded = !isHidden;
                            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                            btn.textContent = expanded ? hideLabel : showLabel;
                        });
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', wireDayToggleButtons);
                } else {
                    wireDayToggleButtons();
                }

                document.addEventListener('shown.bs.modal', wireDayToggleButtons);
            })();
        </script>
    @endonce
@endpush

@push('scripts')
    <script>
        (function() {
            const modalEl = document.getElementById('addScheduleModal');
            if (!modalEl) return;

            modalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Button that triggered the modal
                const subjectId = button?.getAttribute('data-subject-id');
                const subjectName = button?.getAttribute('data-subject-name');

                const select = document.getElementById('subject_select');
                const hidden = document.getElementById('subject_id_input');
                const title = modalEl.querySelector('.modal-title');

                if (subjectId) {
                    // Set hidden input for submission
                    hidden.value = subjectId;

                    // Set the select to the value and disable it to prevent changes
                    if (select) {
                        select.value = subjectId;
                        select.setAttribute('disabled', 'disabled');
                    }

                    // Update modal title to indicate subject
                    if (title && subjectName) {
                        title.textContent = `Add Schedule Entry — ${subjectName}`;
                    }
                } else {
                    // No subject provided: ensure select is enabled and cleared
                    if (select) {
                        select.removeAttribute('disabled');
                        select.value = '';
                    }
                    if (hidden) hidden.value = '';
                }
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                const select = document.getElementById('subject_select');
                const hidden = document.getElementById('subject_id_input');
                const title = modalEl.querySelector('.modal-title');

                if (select) {
                    select.removeAttribute('disabled');
                    select.value = '';
                }
                if (hidden) hidden.value = '';
                if (title) title.textContent = 'Add Schedule Entry';
            });
        })();
    </script>
    <script>
        (function() {
            const editModalEl = document.getElementById('editScheduleModal');
            if (!editModalEl) return;

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const scheduleId = button?.getAttribute('data-schedule-id');
                const updateUrl = button?.getAttribute('data-update-url');
                const teacherId = button?.getAttribute('data-teacher-id');
                const days = button?.getAttribute('data-days') || '';
                const startTime = button?.getAttribute('data-start-time');
                const endTime = button?.getAttribute('data-end-time');
                const room = button?.getAttribute('data-room');
                const subjectId = button?.getAttribute('data-subject-id');
                const subjectName = button?.getAttribute('data-subject-name');

                const form = document.getElementById('editScheduleForm');
                const teacherSelect = document.getElementById('edit_teacher_id');
                const subjectInput = document.getElementById('edit_subject_id');
                const subjectDisplay = document.getElementById('edit_subject_display');
                const startInput = document.getElementById('edit_start_time');
                const endInput = document.getElementById('edit_end_time');
                const roomInput = document.getElementById('edit_room');

                // Set form action
                if (form && updateUrl) {
                    form.setAttribute('action', updateUrl);
                }

                // Populate teacher
                if (teacherSelect) {
                    teacherSelect.value = teacherId || '';
                }

                // Populate subject display and hidden input
                if (subjectInput) subjectInput.value = subjectId || '';
                if (subjectDisplay) subjectDisplay.value = subjectName || '';

                // Populate days checkboxes
                const dayArray = days ? days.split(',').map(d => d.trim().toLowerCase()) : [];
                document.querySelectorAll('.edit-day-checkbox').forEach((cb) => {
                    cb.checked = dayArray.includes(cb.value.toLowerCase());
                });

                // Populate times and room
                if (startInput) startInput.value = startTime || '';
                if (endInput) endInput.value = endTime || '';
                if (roomInput) roomInput.value = room || '';
            });

            editModalEl.addEventListener('hidden.bs.modal', function() {
                const form = document.getElementById('editScheduleForm');
                if (form) {
                    form.removeAttribute('action');
                }
                document.getElementById('edit_subject_id').value = '';
                document.getElementById('edit_subject_display').value = '';
                document.getElementById('edit_teacher_id').value = '';
                document.getElementById('edit_start_time').value = '';
                document.getElementById('edit_end_time').value = '';
                document.getElementById('edit_room').value = '';
                document.querySelectorAll('.edit-day-checkbox').forEach(cb => cb.checked = false);
            });
        })();
    </script>
@endpush
