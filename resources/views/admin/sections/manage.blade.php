@extends('base')

@section('title', 'Manage: ' . $section->gradeLevel->name . '-' . $section->name)

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Classes</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage Class</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-dark">Manage: {{ $section->gradeLevel->name }}-{{ $section->name }}</h1>
                <p class="mb-0 text-muted">School Year: {{ $class->schoolYear->name }}</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-toggle="modal"
                    data-bs-target="#sectionHistoryModal">History</button>
                @if ($isEditable)
                    <form action="{{ route('admin.sections.destroy', $class) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this section? This action cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-lg">Delete</button>
                    </form>
                @endif
            </div>
        </div>

        @if (!$isEditable)
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <div>You are viewing a historical school year (<strong>{{ $class->schoolYear->name }}</strong>). Editing is
                    disabled.</div>
            </div>
        @endif

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
                                <div class="h5 mb-0 font-weight-bold text-dark">
                                    {{ $class->teacher->user->first_name ?? 'Not' }}
                                    {{ $class->teacher->user->last_name ?? 'Assigned' }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard-teacher fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                    @if ($isEditable)
                        <div class="card-footer text-center d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#assignAdviserModal">
                                {{ $class->teacher ? 'Change' : 'Assign' }} Adviser
                            </button>
                            @if ($class->teacher)
                                <form action="{{ route('admin.sections.adviser.remove', $class) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to remove the adviser from this class?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove Adviser</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Enrollment Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Enrollment</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">{{ $class->enrollments->count() }} /
                                    {{ $class->capacity }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-muted"></i>
                            </div>
                        </div>
                    </div>
                    @if ($isEditable)
                        <div class="card-footer text-center">
                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                                data-bs-target="#updateCapacityModal">
                                Update Capacity
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Enrolled Students Table -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Enrolled Students</h6>
                @if ($isEditable)
                    <a href="{{ route('admin.enrollment.create', $class) }}"
                        class="btn btn-sm btn-primary d-flex align-items-center">
                        <i data-feather="plus" class="feather-sm me-1"></i> Enroll New Student
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if ($class->enrollments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered" id="enrolledStudentsTable" width="100%">
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
                                        <td>{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}</td>
                                        <td>{{ ucfirst($enrollment->student->gender) }}</td>
                                        <td>{{ $enrollment->student->guardian->user->last_name ?? 'N/A' }}, {{ $enrollment->student->guardian->user->first_name ?? '' }} @if($enrollment->student->guardian)<span class="badge bg-secondary text-white ms-1">{{ $enrollment->student->guardian->relationship }}</span>@endif</td>
                                        <td class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('admin.students.show', $enrollment->student) }}">
                                                View
                                            </a>

                                            @if ($isEditable)
                                                <a href="{{ route('admin.students.edit', $enrollment->student) }}"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    Edit
                                                </a>

                                                <form
                                                    action="{{ route('admin.sections.students.destroy', [$section, $enrollment->student]) }}"
                                                    method="POST" class="m-0"
                                                    onsubmit="return confirm('Remove this student from the section? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endif
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
                @php
                    $gradeLevel = $section->gradeLevel->level ?? null;
                    $isLowerGrade = !is_null($gradeLevel) && in_array($gradeLevel, [1, 2, 3]);
                @endphp

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
                                                @php
                                                    $startTimeDisplay =
                                                        $schedule->start_time &&
                                                        $schedule->start_time->format('H:i') !== '00:00'
                                                            ? $schedule->start_time->format('g:i A')
                                                            : null;
                                                    $endTimeDisplay =
                                                        $schedule->end_time &&
                                                        $schedule->end_time->format('H:i') !== '00:00'
                                                            ? $schedule->end_time->format('g:i A')
                                                            : null;
                                                @endphp
                                                <small class="text-muted">{!! $startTimeDisplay ?? '<em>Not Set</em>' !!} -
                                                    {!! $endTimeDisplay ?? '<em>Not Set</em>' !!}</small>
                                            </td>
                                            <td class="align-middle text-center">
                                                @if ($isEditable)
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
                                                            data-subject-name="{{ $schedule->subject?->name }}"
                                                            data-is-lower-grade="{{ $isLowerGrade ? 'true' : 'false' }}">
                                                            Edit
                                                        </button>

                                                        @if (!$isLowerGrade)
                                                            <form
                                                                action="{{ route('admin.schedules.destroy', $schedule->id) }}"
                                                                method="POST" class="m-0"
                                                                onsubmit="return confirm('This action cannot be undone. Confirm to delete Schedule?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button
                                                                    class="btn btn-sm btn-outline-danger">Remove</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted"><em>View only</em></span>
                                                @endif
                                            </td>
                                        @else
                                            <td class="text-muted"><em>Not Assigned</em></td>
                                            <td class="text-muted"><em>Not Set</em></td>
                                            <td class="text-center">
                                                @if ($isEditable)
                                                    @if (!$isLowerGrade)
                                                        <button class="btn btn-sm btn-primary assign-schedule-btn"
                                                            data-bs-toggle="modal" data-bs-target="#addScheduleModal"
                                                            data-subject-id="{{ $subject->id }}"
                                                            data-subject-name="{{ $subject->name }}">
                                                            Assign Schedule
                                                        </button>
                                                    @else
                                                        <span class="text-muted"><em>Assign an adviser to auto-create
                                                                schedules</em></span>
                                                    @endif
                                                @else
                                                    <span class="text-muted"><em>—</em></span>
                                                @endif
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



    @if ($isEditable)
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
                                    <select class="form-select" name="start_time" id="start_time" required></select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_time" class="form-label">End Time <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="end_time" id="end_time" required></select>
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
    @endif

    @if ($isEditable)
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
                            <input type="hidden" name="section_id" value="{{ $section->id }}">
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
                                    <select class="form-select" name="start_time" id="edit_start_time" required></select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="edit_end_time" class="form-label">End Time <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="end_time" id="edit_end_time" required></select>
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
    @endif

    @if ($isEditable)
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
                                <label for="adviser_search" class="form-label">Select Teacher</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control adviser-input" id="adviser_search"
                                        placeholder="Search for a teacher..." autocomplete="off"
                                        value="{{ $class->teacher ? $class->teacher->user->first_name . ' ' . $class->teacher->user->last_name : '' }}">
                                    <input type="hidden" name="teacher_id" id="adviser_teacher_id"
                                        value="{{ $class->teacher_id ?? '' }}" required>
                                    <div class="dropdown-menu adviser-menu w-100" id="adviserDropdown"></div>
                                </div>
                                <div class="form-text">Type to search teachers by name.</div>
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
    @endif

    @if ($isEditable)
        <!-- Update Capacity Modal -->
        <div class="modal fade" id="updateCapacityModal" tabindex="-1" aria-labelledby="updateCapacityModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateCapacityModalLabel">Update Classroom Capacity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('admin.sections.capacity.update', $class) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacity <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="capacity" name="capacity"
                                    value="{{ $class->capacity }}" min="1" required>
                                <div class="form-text">Current enrollment: {{ $class->enrollments->count() }} students
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Capacity</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

<!-- Section/Class History Modal -->
<div class="modal fade" id="sectionHistoryModal" tabindex="-1" aria-labelledby="sectionHistoryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionHistoryModalLabel">Section/Class History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body w-100 col-md-8 col-sm-8 col-lg-8 col-xl-8">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="sectionHistoryTable" width="100%">
                        <thead>
                            <tr>
                                <th>School Year</th>
                                <th>Adviser</th>
                                <th>Enrolled</th>
                                <th>Capacity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Enrolled Students DataTable
            if (typeof jQuery !== 'undefined' && $.fn.DataTable) {
                $('#enrolledStudentsTable').DataTable({
                    columnDefs: [
                        { orderable: false, targets: 4 }
                    ],
                    order: [[1, 'asc']],
                    responsive: true,
                    destroy: true
                });
            }

            // Initialize Section History DataTable
            const sectionHistory = @json($sectionHistory ?? []);
            const table = $('#sectionHistoryTable').DataTable({
                data: sectionHistory,
                columns: [{
                        data: 'school_year',
                        title: 'School Year'
                    },
                    {
                        data: 'adviser',
                        title: 'Adviser'
                    },
                    {
                        data: 'enrolled',
                        title: 'Enrolled'
                    },
                    {
                        data: 'capacity',
                        title: 'Capacity'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (!row.class_id) return '';
                            const url =
                                `{{ route('admin.sections.manage', $section) }}?class_id=${row.class_id}`;
                            return `<a href="${url}" class="btn btn-sm btn-outline-primary" title="View">View</a>`;
                        }
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                responsive: true,
                destroy: true,
                drawCallback: function(settings) {
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            });

            $('#sectionHistoryModal').on('shown.bs.modal', function() {
                table.columns.adjust().draw();
            });
        });
    </script>
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
                const button = event.relatedTarget;
                const subjectId = button?.getAttribute('data-subject-id');
                const subjectName = button?.getAttribute('data-subject-name');

                const select = document.getElementById('subject_select');
                const hidden = document.getElementById('subject_id_input');
                const title = modalEl.querySelector('.modal-title');

                if (subjectId) {
                    hidden.value = subjectId;
                    if (select) {
                        select.value = subjectId;
                        select.setAttribute('disabled', 'disabled');
                    }
                    if (title && subjectName) {
                        title.textContent = `Add Schedule Entry — ${subjectName}`;
                    }
                } else {
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
            function populateTimeDropdowns(startId, endId) {
                const startTimeSelect = document.getElementById(startId);
                const endTimeSelect = document.getElementById(endId);
                if (!startTimeSelect || !endTimeSelect) return;

                startTimeSelect.innerHTML = '<option value="">-- Select --</option>';
                endTimeSelect.innerHTML = '<option value="">-- Select --</option>';

                // Default options
                let startOptions = [];
                let endOptions = [];

                // Fixed start time: 7:30 AM
                startOptions.push({ value: '07:30', text: '7:30 AM' });

                // Fixed end times based on grade level
                // Grade 1-3: 3:30 PM, Grade 4-6: 4:00 PM
                // However, user wants specific end times for each grade.
                // Let's assume the user wants the dropdowns to ONLY contain the allowed times.
                // Wait, "the choices for the schedule modal".
                // "start time should be 7:30am" -> only choice is 7:30am?
                // "end time for grade 1 2 3 should be 3:30pm" -> only choice is 3:30pm?
                // "grade 4 5 6 4:00pm" -> only choice is 4:00pm?

                if (typeof isLowerGrade !== 'undefined' && isLowerGrade) {
                    // Grade 1, 2, 3
                    endOptions.push({ value: '15:30', text: '3:30 PM' });
                } else {
                    // Grade 4, 5, 6
                    endOptions.push({ value: '16:00', text: '4:00 PM' });
                }

                startOptions.forEach(opt => {
                    startTimeSelect.add(new Option(opt.text, opt.value));
                });

                endOptions.forEach(opt => {
                    endTimeSelect.add(new Option(opt.text, opt.value));
                });

                // Set default selection
                if (startTimeSelect.options.length > 0) startTimeSelect.selectedIndex = 0;
                if (endTimeSelect.options.length > 0) endTimeSelect.selectedIndex = 0;
            }

            populateTimeDropdowns('start_time', 'end_time');
            populateTimeDropdowns('edit_start_time', 'edit_end_time');

            const editModalEl = document.getElementById('editScheduleModal');
            if (!editModalEl) return;

            editModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const updateUrl = button?.getAttribute('data-update-url');
                const teacherId = button?.getAttribute('data-teacher-id');
                const days = button?.getAttribute('data-days');
                const startTime = button?.getAttribute('data-start-time');
                const endTime = button?.getAttribute('data-end-time');
                const room = button?.getAttribute('data-room');
                const subjectId = button?.getAttribute('data-subject-id');
                const subjectName = button?.getAttribute('data-subject-name');
                const isLowerGrade = button?.getAttribute('data-is-lower-grade') === 'true';

                const form = document.getElementById('editScheduleForm');
                const teacherSelect = document.getElementById('edit_teacher_id');
                const subjectInput = document.getElementById('edit_subject_id');
                const subjectDisplay = document.getElementById('edit_subject_display');
                const startInput = document.getElementById('edit_start_time');
                const endInput = document.getElementById('edit_end_time');

                if (form && updateUrl) {
                    form.setAttribute('action', updateUrl);
                }

                if (teacherSelect) {
                    teacherSelect.value = teacherId || '';
                    if (isLowerGrade) {
                        teacherSelect.setAttribute('disabled', 'disabled');
                        let hiddenTeacher = form.querySelector('input[name="teacher_id"][type="hidden"]');
                        if (!hiddenTeacher) {
                            hiddenTeacher = document.createElement('input');
                            hiddenTeacher.type = 'hidden';
                            hiddenTeacher.name = 'teacher_id';
                            hiddenTeacher.id = 'edit_teacher_id_hidden';
                            form.appendChild(hiddenTeacher);
                        }
                        hiddenTeacher.value = teacherId || '';
                    } else {
                        teacherSelect.removeAttribute('disabled');
                        const hiddenTeacher = form.querySelector(
                            'input[name="teacher_id"][type="hidden"]');
                        if (hiddenTeacher) hiddenTeacher.remove();
                    }
                }

                const teacherNote = editModalEl.querySelector('#teacher-restriction-note');
                if (isLowerGrade) {
                    if (!teacherNote) {
                        const note = document.createElement('small');
                        note.id = 'teacher-restriction-note';
                        note.className = 'text-muted d-block';
                        note.textContent =
                            'Teacher is locked to the class adviser for Grade 1-3.';
                        teacherSelect.parentNode.appendChild(note);
                    }
                } else {
                    if (teacherNote) teacherNote.remove();
                }

                if (subjectInput) subjectInput.value = subjectId || '';
                if (subjectDisplay) subjectDisplay.value = subjectName || '';

                const dayArray = days ? days.split(',').map(d => d.trim().toLowerCase()) : [];
                document.querySelectorAll('.edit-day-checkbox').forEach((cb) => {
                    cb.checked = dayArray.includes(cb.value.toLowerCase());
                });

                if (startInput) startInput.value = startTime || '';
                if (endInput) endInput.value = endTime || '';
            });

            editModalEl.addEventListener('hidden.bs.modal', function() {
                const form = document.getElementById('editScheduleForm');
                if (form) {
                    form.removeAttribute('action');
                }
                const teacherSelect = document.getElementById('edit_teacher_id');
                if (teacherSelect) {
                    teacherSelect.removeAttribute('disabled');
                }
                const hiddenTeacher = form?.querySelector('input[name="teacher_id"][type="hidden"]');
                if (hiddenTeacher) hiddenTeacher.remove();
                const teacherNote = document.querySelector('#teacher-restriction-note');
                if (teacherNote) teacherNote.remove();

                document.getElementById('edit_subject_id').value = '';
                document.getElementById('edit_subject_display').value = '';
                document.getElementById('edit_teacher_id').value = '';
                document.getElementById('edit_start_time').value = '';
                document.getElementById('edit_end_time').value = '';
                document.querySelectorAll('.edit-day-checkbox').forEach(cb => cb.checked = false);
            });
        })();
    </script>
    <script>
        (function() {
            const teachersList = @json($teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->user->first_name . ' ' . $t->user->last_name]));

            const adviserInput = document.getElementById('adviser_search');
            const adviserHidden = document.getElementById('adviser_teacher_id');
            const adviserMenu = document.getElementById('adviserDropdown');

            if (!adviserInput || !adviserHidden || !adviserMenu) return;

            const renderDropdown = (teachers) => {
                adviserMenu.innerHTML = '';
                if (teachers.length === 0) {
                    adviserMenu.innerHTML =
                        '<div class="dropdown-item text-muted">No teachers found</div>';
                    return;
                }
                teachers.forEach(teacher => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.textContent = teacher.name;
                    item.dataset.id = teacher.id;
                    item.addEventListener('click', () => {
                        adviserInput.value = teacher.name;
                        adviserHidden.value = teacher.id;
                        adviserMenu.classList.remove('show');
                    });
                    adviserMenu.appendChild(item);
                });
            };

            adviserInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const filtered = teachersList.filter(t => t.name.toLowerCase().includes(query));
                renderDropdown(filtered);
                adviserMenu.classList.add('show');
            });

            adviserInput.addEventListener('focus', function() {
                const query = this.value.toLowerCase().trim();
                const filtered = query ?
                    teachersList.filter(t => t.name.toLowerCase().includes(query)) :
                    teachersList;
                renderDropdown(filtered);
                adviserMenu.classList.add('show');
            });

            document.addEventListener('click', function(e) {
                if (!adviserInput.contains(e.target) && !adviserMenu.contains(e.target)) {
                    adviserMenu.classList.remove('show');
                }
            });

            const assignAdviserModal = document.getElementById('assignAdviserModal');
            if (assignAdviserModal) {
                assignAdviserModal.addEventListener('show.bs.modal', function() {
                    const currentTeacherId = '{{ $class->teacher_id ?? '' }}';
                    const currentTeacher = teachersList.find(t => t.id == currentTeacherId);
                    if (currentTeacher) {
                        adviserInput.value = currentTeacher.name;
                        adviserHidden.value = currentTeacher.id;
                    } else {
                        adviserInput.value = '';
                        adviserHidden.value = '';
                    }
                });
            }
        })();
    </script>
@endpush
