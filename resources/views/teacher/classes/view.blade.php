@extends('base')

@section('title', 'View Class: ' . $class->section->name)

@section('content')
    @php
        $isAdviser = $isAdviser ?? $teacher && (int) $class->teacher_id === (int) $teacher->id;
        $subjects = $subjects ?? collect();
        $assignableTeachers = $assignableTeachers ?? collect();
    @endphp
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('teacher.classes') }}">My Classes</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Class</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-800">{{ $class->section->gradeLevel->name }} -
                    {{ $class->section->name }}</h1>
                <p class="mb-0 text-muted">School Year: {{ $class->schoolYear->name }}</p>
            </div>
        </div>

        <div class="row">
            <!-- Adviser Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Adviser</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    @if ($class->teacher)
                                        {{ $class->teacher->user->first_name }} {{ $class->teacher->user->last_name }}
                                    @else
                                        Not Assigned
                                    @endif
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                            </div>
                        </div>
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
                @if ($isAdviser)
                    <button class="btn btn-sm btn-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#enrollStudentModal">
                        <i data-feather="plus" class="feather-sm me-1"></i> Enroll New Student
                    </button>
                @endif
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
                @if ($isAdviser)
                    <span class="badge bg-info text-dark">Adviser Controls</span>
                @endif
            </div>
            <div class="card-body">
                @if ($isAdviser)
                    @if ($subjects->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Day(s)</th>
                                        <th>Time</th>
                                        <th>Teacher</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjects as $subject)
                                        @php
                                            $schedule = $class->schedules->firstWhere('subject_id', $subject->id);
                                            $assignedTeacher = $schedule?->teacher?->user;
                                            $dayNames = $schedule?->day_names ?? [];
                                        @endphp
                                        <tr>
                                            <td>{{ $subject->name }}</td>
                                            <td>{{ $schedule?->day_names_label ?? 'Not Set' }}</td>
                                            <td>
                                                @if ($schedule)
                                                    {{ $schedule->start_time?->format('g:i A') }} -
                                                    {{ $schedule->end_time?->format('g:i A') }}
                                                @else
                                                    <span class="text-muted">Not Set</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($assignedTeacher)
                                                    {{ $assignedTeacher->first_name }} {{ $assignedTeacher->last_name }}
                                                    @if ($schedule?->teacher_id === $teacher->id)
                                                        <span class="badge bg-primary ms-2">You</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Not Assigned</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="d-flex justify-content-center gap-2 align-items-center">
                                                    @if ($schedule)
                                                        <button class="btn btn-sm btn-outline-secondary edit-schedule-btn"
                                                            data-bs-toggle="modal" data-bs-target="#manageScheduleModal"
                                                            data-subject-id="{{ $subject->id }}"
                                                            data-subject-name="{{ $subject->name }}"
                                                            data-schedule-id="{{ $schedule->id }}"
                                                            data-days="{{ $dayNames ? implode(',', $dayNames) : '' }}"
                                                            data-start-time="{{ $schedule?->start_time?->format('H:i') }}"
                                                            data-end-time="{{ $schedule?->end_time?->format('H:i') }}"
                                                            data-teacher-id="{{ $schedule?->teacher_id ?? '' }}"
                                                            data-room="{{ $schedule?->room ?? '' }}">
                                                            Edit
                                                        </button>

                                                        <form
                                                            action="{{ route('teacher.classes.schedule.destroy', ['class' => $class, 'schedule' => $schedule->id]) }}"
                                                            method="POST" class="m-0"
                                                            onsubmit="return confirm('This action cannot be undone. Confirm to delete Schedule?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                                        </form>
                                                    @else
                                                        <button class="btn btn-sm btn-primary assign-schedule-btn"
                                                            data-bs-toggle="modal" data-bs-target="#manageScheduleModal"
                                                            data-subject-id="{{ $subject->id }}"
                                                            data-subject-name="{{ $subject->name }}">
                                                            Assign Schedule
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">No subjects available for this grade level.</p>
                    @endif
                @else
                    @if ($class->schedules->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Day(s)</th>
                                        <th>Time</th>
                                        <th>Teacher</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($class->schedules as $schedule)
                                        <tr>
                                            <td>{{ $schedule->subject->name }}</td>
                                            <td>{{ $schedule->day_names_label }}</td>
                                            <td>{{ $schedule->start_time?->format('g:i A') }} -
                                                {{ $schedule->end_time?->format('g:i A') }}</td>
                                            <td>
                                                {{ $schedule->teacher->user->first_name }}
                                                {{ $schedule->teacher->user->last_name }}
                                                @if ($schedule->teacher_id == $teacher->id)
                                                    <span class="badge bg-primary ms-2">You</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">No schedule has been set for this class.</p>
                    @endif
                @endif
            </div>
        </div>
        @if ($isAdviser)
            <div class="modal fade" id="manageScheduleModal" tabindex="-1" aria-labelledby="manageScheduleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="manageScheduleModalLabel">Assign Schedule</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="manageScheduleForm" action="{{ route('teacher.classes.schedule.store', $class) }}"
                            method="POST">
                            @csrf
                            <input type="hidden" name="schedule_id" id="schedule_id">
                            <input type="hidden" name="subject_id" id="schedule_subject_id">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="schedule_subject_name" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="schedule_subject_name" disabled>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="schedule_teacher_id" class="form-label">Teacher <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="teacher_id" id="schedule_teacher_id" required>
                                            <option value="">-- Select a teacher --</option>
                                            @foreach ($assignableTeachers as $assignableTeacher)
                                                <option value="{{ $assignableTeacher->id }}">
                                                    @if ($assignableTeacher->user)
                                                        {{ $assignableTeacher->user->first_name }}
                                                        {{ $assignableTeacher->user->last_name }}
                                                    @else
                                                        Teacher #{{ $assignableTeacher->id }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label mb-0">Day(s) of the Week <span
                                                class="text-danger">*</span></label>
                                        <button class="btn btn-link btn-sm p-0" type="button"
                                            data-day-toggle="#manageDaySelector" aria-expanded="false"
                                            aria-controls="manageDaySelector" data-hide-label="Hide days">
                                            Change days
                                        </button>
                                    </div>
                                    <p class="small text-muted mb-2">Defaults to Monday through Friday. Expand if you need
                                        to
                                        adjust.</p>
                                    <div id="manageDaySelector" class="day-selector d-none">
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input schedule-day-checkbox" type="checkbox"
                                                        name="day_of_week[]" value="{{ $day }}"
                                                        id="schedule_day_{{ $day }}">
                                                    <label class="form-check-label text-capitalize"
                                                        for="schedule_day_{{ $day }}">{{ $day }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="schedule_start_time" class="form-label">Start Time <span
                                                class="text-danger">*</span></label>
                                        <input type="time" class="form-control" name="start_time"
                                            id="schedule_start_time" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="schedule_end_time" class="form-label">End Time <span
                                                class="text-danger">*</span></label>
                                        <input type="time" class="form-control" name="end_time"
                                            id="schedule_end_time" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="schedule_room" class="form-label">Room (Optional)</label>
                                        <input type="text" class="form-control" name="room" id="schedule_room">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Schedule</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <!-- Enroll New Student Modal -->
    <div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-labelledby="enrollStudentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="enrollStudentModalLabel">Enroll New Student in
                        {{ $class->section->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('teacher.enrollment.store', $class) }}" method="POST">
                    @csrf
                    <div class="modal-body">
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
@endsection

@if ($isAdviser)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('manageScheduleModal');
                if (!modal) {
                    return;
                }

                const form = document.getElementById('manageScheduleForm');
                const scheduleIdField = document.getElementById('schedule_id');
                const subjectIdField = document.getElementById('schedule_subject_id');
                const subjectNameField = document.getElementById('schedule_subject_name');
                const teacherSelect = document.getElementById('schedule_teacher_id');
                const roomInput = document.getElementById('schedule_room');
                const startInput = document.getElementById('schedule_start_time');
                const endInput = document.getElementById('schedule_end_time');
                const dayCheckboxes = modal.querySelectorAll('.schedule-day-checkbox');
                const modalTitle = document.getElementById('manageScheduleModalLabel');

                const defaultDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

                const setDefaultDays = () => {
                    dayCheckboxes.forEach((checkbox) => {
                        checkbox.checked = defaultDays.includes(checkbox.value);
                    });
                };

                setDefaultDays();

                document.querySelectorAll('.edit-schedule-btn, .assign-schedule-btn').forEach((button) => {
                    button.addEventListener('click', () => {
                        const subjectId = button.getAttribute('data-subject-id') || '';
                        const subjectName = button.getAttribute('data-subject-name') || '';
                        const scheduleId = button.getAttribute('data-schedule-id') || '';
                        const dayData = button.getAttribute('data-days') || '';
                        const teacherId = button.getAttribute('data-teacher-id') || '';
                        const startTime = button.getAttribute('data-start-time') || '';
                        const endTime = button.getAttribute('data-end-time') || '';
                        const room = button.getAttribute('data-room') || '';

                        scheduleIdField.value = scheduleId;
                        subjectIdField.value = subjectId;
                        subjectNameField.value = subjectName;

                        const teacherOptionExists = Array.from(teacherSelect.options).some((opt) => opt
                            .value === teacherId);
                        teacherSelect.value = teacherOptionExists ? teacherId : '';

                        roomInput.value = room;
                        startInput.value = startTime;
                        endInput.value = endTime;

                        modalTitle.textContent = scheduleId ? 'Update Schedule' : 'Assign Schedule';

                        setDefaultDays();

                        if (dayData) {
                            const days = dayData
                                .split(',')
                                .map((day) => day.trim().toLowerCase())
                                .filter((day) => day !== '');

                            dayCheckboxes.forEach((checkbox) => {
                                checkbox.checked = days.includes(checkbox.value);
                            });
                        }
                    });
                });

                modal.addEventListener('hidden.bs.modal', () => {
                    form.reset();
                    scheduleIdField.value = '';
                    subjectIdField.value = '';
                    subjectNameField.value = '';
                    modalTitle.textContent = 'Assign Schedule';
                    setDefaultDays();
                });
            });
        </script>
    @endpush
@endif

@push('scripts')
    @once('teacher-day-toggle-script')
        <script>
            (function() {
                const wireDayToggleButtons = () => {
                    document.querySelectorAll('[data-day-toggle]').forEach((btn) => {
                        if (btn.dataset.dayToggleBound === 'true') return;
                        btn.dataset.dayToggleBound = 'true';

                        const showLabel = btn.dataset.showLabel || btn.textContent.trim() || 'Change days';
                        const hideLabel = btn.dataset.hideLabel || btn.dataset.hideLabel || 'Hide days';
                        btn.dataset.showLabel = showLabel;
                        btn.dataset.hideLabel = hideLabel;

                        btn.addEventListener('click', () => {
                            const selector = btn.getAttribute('data-day-toggle');
                            if (!selector) return;
                            const target = document.querySelector(selector);
                            if (!target) return;

                            const isHidden = target.classList.toggle('d-none');
                            const expanded = !isHidden;
                            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                            btn.textContent = expanded ? hideLabel : showLabel;
                        });
                    });
                };

                const setTeacherDefaultDays = () => {
                    const defaultDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                    // Apply defaults to all day-selector blocks (teacher page and modals)
                    document.querySelectorAll('.day-selector .schedule-day-checkbox').forEach((cb) => {
                        try {
                            cb.checked = defaultDays.includes(cb.value.toLowerCase());
                        } catch (e) {
                            // ignore
                        }
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => {
                        wireDayToggleButtons();
                        setTeacherDefaultDays();
                    });
                } else {
                    wireDayToggleButtons();
                    setTeacherDefaultDays();
                }
            })();
        </script>
    @endonce
@endpush
