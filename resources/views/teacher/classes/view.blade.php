@extends('base')

@section('title', 'View Class: ' . $class->section->name)

@section('content')
    @php
        $isAdviser = $isAdviser ?? $teacher && (int) $class->teacher_id === (int) $teacher->id;
        $subjects = $subjects ?? collect();
        $assignableTeachers = $assignableTeachers ?? collect();
        $gradeLevel = $class->section->gradeLevel->level ?? null;
        $isLowerGrade = !is_null($gradeLevel) && in_array($gradeLevel, [1, 2, 3]);
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
                <h1 class="h3 mb-0 text-dark d-inline-flex align-items-center">
                    {{ $class->section->gradeLevel->name }} -
                    {{ $class->section->name }}
                    @if ($isAdviser && $class->schoolYear->is_active)
                        <button type="button" class="btn btn-link text-secondary p-0 ms-2" data-bs-toggle="modal"
                            data-bs-target="#renameSectionModal" title="Rename Section">
                            <i class="fas fa-edit"></i>
                        </button>
                    @endif
                </h1>
                <p class="mb-0 text-muted">School Year: {{ $class->schoolYear->name }}</p>
            </div>
            @if ($isAdviser)
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#sectionHistoryModal">
                        <i data-feather="history" class="icon-sm me-1"></i> History
                    </button>
                </div>
            @endif
        </div>

        <div class="row">
            <!-- Adviser Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Adviser</div>
                                <div class="h5 mb-0 font-weight-bold text-dark">
                                    @if ($class->teacher)
                                        {{ $class->teacher->user->first_name }} {{ $class->teacher->user->last_name }}
                                    @else
                                        Not Assigned
                                    @endif
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chalkboard-teacher fa-2x text-muted"></i>
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
                                <div class="h5 mb-0 font-weight-bold text-dark">{{ $class->enrollments->count() }} /
                                    {{ $class->capacity }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-muted"></i>
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
                {{-- @if ($isAdviser)
                    <button class="btn btn-sm btn-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#enrollStudentModal">
                        <i data-feather="plus" class="feather-sm me-1"></i> Enroll New Student
                    </button>
                @endif --}}
            </div>
            <div class="card-body">
                @if ($class->enrollments->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered" id="teacherEnrolledStudentsTable" width="100%">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Guardian</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($class->enrollments as $enrollment)
                                    <tr>
                                        <td>{{ $enrollment->student->lrn ?? 'N/A' }}</td>
                                        <td>{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}
                                        </td>
                                        <td>{{ ucfirst($enrollment->student->gender) }}</td>
                                        <td>{{ $enrollment->student->guardian->user->last_name ?? 'N/A' }}, {{ $enrollment->student->guardian->user->first_name ?? '' }} @if($enrollment->student->guardian)<span class="badge bg-secondary text-white ms-1">{{ $enrollment->student->guardian->relationship }}</span>@endif</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('teacher.students.show', $enrollment->student) }}"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    View
                                                </a>
                                                @if ($isAdviser && $class->schoolYear->is_active)
                                                    <a href="{{ route('teacher.students.edit', $enrollment->student) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        Edit
                                                    </a>
                                                @endif
                                            </div>
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
                @if ($isAdviser)
                    @if ($isLowerGrade)
                        <span class="badge bg-info text-dark">Adviser Controls</span>
                    @endif
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
                                                    @if ($startTimeDisplay && $endTimeDisplay)
                                                        {{ $startTimeDisplay }} - {{ $endTimeDisplay }}
                                                    @else
                                                        <span class="text-muted">Not Set</span>
                                                    @endif
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
                                                        @if ($class->schoolYear->is_active)
                                                            <button
                                                                class="btn btn-sm btn-outline-secondary edit-schedule-btn"
                                                                data-bs-toggle="modal" data-bs-target="#manageScheduleModal"
                                                                data-subject-id="{{ $subject->id }}"
                                                                data-subject-name="{{ $subject->name }}"
                                                                data-schedule-id="{{ $schedule->id }}"
                                                                data-days="{{ $dayNames ? implode(',', $dayNames) : '' }}"
                                                                data-start-time="{{ $schedule?->start_time?->format('H:i') }}"
                                                                data-end-time="{{ $schedule?->end_time?->format('H:i') }}"
                                                                data-teacher-id="{{ $schedule?->teacher_id ?? '' }}"
                                                                data-room="{{ $schedule?->room ?? '' }}"
                                                                data-is-lower-grade="{{ $isLowerGrade ? 'true' : 'false' }}">
                                                                Edit
                                                            </button>

                                                            @if (!$isLowerGrade)
                                                                <form
                                                                    action="{{ route('teacher.classes.schedule.destroy', ['class' => $class, 'schedule' => $schedule->id]) }}"
                                                                    method="POST" class="m-0"
                                                                    onsubmit="return confirm('This action cannot be undone. Confirm to delete Schedule?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button
                                                                        class="btn btn-sm btn-outline-danger">Remove</button>
                                                                </form>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">Locked</span>
                                                        @endif
                                                    @else
                                                        @if ($class->schoolYear->is_active)
                                                            @if (!$isLowerGrade)
                                                                <button class="btn btn-sm btn-primary assign-schedule-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#manageScheduleModal"
                                                                    data-subject-id="{{ $subject->id }}"
                                                                    data-subject-name="{{ $subject->name }}">
                                                                    Assign Schedule
                                                                </button>
                                                            @else
                                                                <span class="text-muted"><em>Auto-assigned to
                                                                        adviser</em></span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">No Schedule</span>
                                                        @endif
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
                                        @php
                                            $startTimeDisplay =
                                                $schedule->start_time && $schedule->start_time->format('H:i') !== '00:00'
                                                    ? $schedule->start_time->format('g:i A')
                                                    : null;
                                            $endTimeDisplay =
                                                $schedule->end_time && $schedule->end_time->format('H:i') !== '00:00'
                                                    ? $schedule->end_time->format('g:i A')
                                                    : null;
                                        @endphp
                                        <tr>
                                            <td>{{ $schedule->subject->name }}</td>
                                            <td>{{ $schedule->day_names_label }}</td>
                                            <td>
                                                @if ($startTimeDisplay && $endTimeDisplay)
                                                    {{ $startTimeDisplay }} - {{ $endTimeDisplay }}
                                                @else
                                                    <span class="text-muted">Not Set</span>
                                                @endif
                                            </td>
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
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
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
                                        <select class="form-select" name="start_time" id="schedule_start_time"
                                            required></select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="schedule_end_time" class="form-label">End Time <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="end_time" id="schedule_end_time"
                                            required></select>
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
                <form action="{{ route('teacher.enrollment.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $class->id }}">
                    <div class="modal-body">
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif
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

                        <h6 class="mt-4 mb-3 border-bottom pb-2">Additional Information (For Analytics)</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="distance_km" class="form-label">Distance from School (km)</label>
                                <input type="number" step="0.01" min="0" class="form-control"
                                    id="distance_km" name="distance_km" value="{{ old('distance_km') }}"
                                    placeholder="e.g., 2.5">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="transportation" class="form-label">Mode of Transportation</label>
                                <select class="form-select" id="transportation" name="transportation">
                                    <option value="" {{ old('transportation') == '' ? 'selected' : '' }}>-- Select
                                        --</option>
                                    <option value="Walk" {{ old('transportation') == 'Walk' ? 'selected' : '' }}>Walk
                                    </option>
                                    <option value="Bicycle" {{ old('transportation') == 'Bicycle' ? 'selected' : '' }}>
                                        Bicycle</option>
                                    <option value="Motorcycle"
                                        {{ old('transportation') == 'Motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                    <option value="Tricycle" {{ old('transportation') == 'Tricycle' ? 'selected' : '' }}>
                                        Tricycle</option>
                                    <option value="Jeepney" {{ old('transportation') == 'Jeepney' ? 'selected' : '' }}>
                                        Jeepney</option>
                                    <option value="Bus" {{ old('transportation') == 'Bus' ? 'selected' : '' }}>Bus
                                    </option>
                                    <option value="Private Vehicle"
                                        {{ old('transportation') == 'Private Vehicle' ? 'selected' : '' }}>Private Vehicle
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="family_income" class="form-label">Socioeconomic Status</label>
                                <select class="form-select" id="family_income" name="family_income">
                                    <option value="" {{ old('family_income') == '' ? 'selected' : '' }}>-- Select --
                                    </option>
                                    <option value="Low" {{ old('family_income') == 'Low' ? 'selected' : '' }}>Low
                                    </option>
                                    <option value="Medium" {{ old('family_income') == 'Medium' ? 'selected' : '' }}>Medium
                                    </option>
                                    <option value="High" {{ old('family_income') == 'High' ? 'selected' : '' }}>High
                                    </option>
                                </select>
                            </div>
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
                                <label for="guardian_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="guardian_email" name="guardian_email"
                                    value="{{ old('guardian_email') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="guardian_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="guardian_phone" name="guardian_phone"
                                    value="{{ old('guardian_phone') }}">
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

    @if ($isAdviser && $class->schoolYear->is_active)
        <!-- Section History Modal -->
        <div class="modal fade" id="sectionHistoryModal" tabindex="-1" aria-labelledby="sectionHistoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sectionHistoryModalLabel">Section History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
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
                                    <!-- Data populated by DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rename Section Modal -->
        <div class="modal fade" id="renameSectionModal" tabindex="-1" aria-labelledby="renameSectionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="renameSectionModalLabel">Rename Section</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('teacher.classes.section.rename', $class) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="section_name" class="form-label">Section Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="section_name" name="name"
                                    value="{{ $class->section->name }}" required>
                                <div class="form-text">Grade Level: {{ $class->section->gradeLevel->name }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @elseif ($isAdviser)
        <!-- Section History Modal (view-only for non-active school years) -->
        <div class="modal fade" id="sectionHistoryModal" tabindex="-1" aria-labelledby="sectionHistoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sectionHistoryModalLabel">Section History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
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
                                    <!-- Data populated by DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@if ($isAdviser)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // ── Time dropdown population (15-min intervals, 7 AM – 5 PM) ──
                function populateTimeDropdowns(startId, endId) {
                    const startSel = document.getElementById(startId);
                    const endSel = document.getElementById(endId);
                    if (!startSel || !endSel) return;
                    startSel.innerHTML = '<option value="">-- Select --</option>';
                    endSel.innerHTML = '<option value="">-- Select --</option>';
                    let cur = new Date();
                    cur.setHours(7, 0, 0, 0);
                    const stop = new Date();
                    stop.setHours(17, 0, 0, 0);
                    while (cur <= stop) {
                        const h = cur.getHours(),
                            m = cur.getMinutes();
                        const ampm = h >= 12 ? 'PM' : 'AM';
                        const dh = h % 12 === 0 ? 12 : h % 12;
                        const dm = m < 10 ? '0' + m : m;
                        const label = `${dh}:${dm} ${ampm}`;
                        const val = `${h < 10 ? '0'+h : h}:${dm}`;
                        startSel.add(new Option(label, val));
                        endSel.add(new Option(label, val));
                        cur.setMinutes(m + 15);
                    }
                }
                populateTimeDropdowns('schedule_start_time', 'schedule_end_time');

                const scheduleModal = document.getElementById('manageScheduleModal');
                if (scheduleModal) {
                    const form = document.getElementById('manageScheduleForm');
                    const scheduleIdField = document.getElementById('schedule_id');
                    const subjectIdField = document.getElementById('schedule_subject_id');
                    const subjectNameField = document.getElementById('schedule_subject_name');
                    const teacherSelect = document.getElementById('schedule_teacher_id');
                    const roomInput = document.getElementById('schedule_room');
                    const startInput = document.getElementById('schedule_start_time');
                    const endInput = document.getElementById('schedule_end_time');
                    const dayCheckboxes = scheduleModal.querySelectorAll('.schedule-day-checkbox');
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
                            const isLowerGrade = button.getAttribute('data-is-lower-grade') === 'true';

                            if (scheduleIdField) scheduleIdField.value = scheduleId;
                            if (subjectIdField) subjectIdField.value = subjectId;
                            if (subjectNameField) subjectNameField.value = subjectName;

                            if (teacherSelect) {
                                const teacherOptionExists = Array.from(teacherSelect.options).some((
                                    opt) => opt.value === teacherId);
                                teacherSelect.value = teacherOptionExists ? teacherId : '';

                                // For Grade 1, 2, 3: disable teacher selection
                                if (isLowerGrade) {
                                    teacherSelect.setAttribute('disabled', 'disabled');
                                    // Add hidden input to maintain teacher_id value
                                    let hiddenTeacher = form.querySelector(
                                        'input[name="teacher_id"][type="hidden"]');
                                    if (!hiddenTeacher) {
                                        hiddenTeacher = document.createElement('input');
                                        hiddenTeacher.type = 'hidden';
                                        hiddenTeacher.name = 'teacher_id';
                                        hiddenTeacher.id = 'schedule_teacher_id_hidden';
                                        form.appendChild(hiddenTeacher);
                                    }
                                    hiddenTeacher.value = teacherId || '';

                                    // Add restriction note
                                    let teacherNote = scheduleModal.querySelector(
                                        '#teacher-restriction-note');
                                    if (!teacherNote) {
                                        teacherNote = document.createElement('small');
                                        teacherNote.id = 'teacher-restriction-note';
                                        teacherNote.className = 'text-muted d-block';
                                        teacherNote.textContent =
                                            'Teacher is locked to the class adviser for Grade 1-3.';
                                        teacherSelect.parentNode.appendChild(teacherNote);
                                    }
                                } else {
                                    teacherSelect.removeAttribute('disabled');
                                    const hiddenTeacher = form.querySelector(
                                        'input[name="teacher_id"][type="hidden"]');
                                    if (hiddenTeacher) hiddenTeacher.remove();
                                    const teacherNote = scheduleModal.querySelector(
                                        '#teacher-restriction-note');
                                    if (teacherNote) teacherNote.remove();
                                }
                            }

                            if (roomInput) roomInput.value = room;
                            if (startInput) startInput.value = startTime ? startTime.substring(0, 5) :
                                '';
                            if (endInput) endInput.value = endTime ? endTime.substring(0, 5) : '';

                            if (modalTitle) {
                                modalTitle.textContent = scheduleId ? 'Update Schedule' :
                                    'Assign Schedule';
                            }

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

                    scheduleModal.addEventListener('hidden.bs.modal', () => {
                        if (form) {
                            form.reset();
                        }
                        if (scheduleIdField) scheduleIdField.value = '';
                        if (subjectIdField) subjectIdField.value = '';
                        // Re-enable teacher select and clean up
                        if (teacherSelect) {
                            teacherSelect.removeAttribute('disabled');
                        }
                        const hiddenTeacher = form?.querySelector('input[name="teacher_id"][type="hidden"]');
                        if (hiddenTeacher) hiddenTeacher.remove();
                        const teacherNote = scheduleModal.querySelector('#teacher-restriction-note');
                        if (teacherNote) teacherNote.remove();
                        if (subjectNameField) subjectNameField.value = '';
                        if (modalTitle) {
                            modalTitle.textContent = 'Assign Schedule';
                        }
                        setDefaultDays();
                    });
                }
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

@push('scripts')
    @if ($isAdviser)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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
                                    `{{ route('teacher.classes.view', $class) }}?class_id=${row.class_id}`;
                                return `<a href="${url}" class="btn btn-sm btn-outline-primary">View</a>`;
                            }
                        }
                    ],
                    order: [
                        [0, 'desc']
                    ],
                    responsive: true,
                    destroy: true
                });

                $('#sectionHistoryModal').on('shown.bs.modal', function() {
                    table.columns.adjust().draw();
                });
            });
        </script>
    @endif
    @if ($errors->any() || session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    var modalEl = document.getElementById('enrollStudentModal');
                    if (modalEl) {
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                } catch (e) {
                    console.warn('Could not show enroll modal automatically:', e);
                }
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jQuery !== 'undefined' && $.fn.DataTable) {
                $('#teacherEnrolledStudentsTable').DataTable({
                    columnDefs: [
                        { orderable: false, targets: 4 }
                    ],
                    order: [[1, 'asc']],
                    responsive: true,
                    destroy: true
                });
            }
        });
    </script>
@endpush
