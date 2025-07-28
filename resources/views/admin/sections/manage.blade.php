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
                                                <span class="d-block">
                                                    {{ implode(', ', array_map('ucfirst', explode(',', $schedule->day_of_week))) }}</span>
                                                <small
                                                    class="text-muted">{{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('admin.schedules.destroy', $schedule->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('This action cannot be undone. Confirm to delete Schedule?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            </td>
                                        @else
                                            <td class="text-muted"><em>Not Assigned</em></td>
                                            <td class="text-muted"><em>Not Set</em></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#addScheduleModal">
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
                                <label for="subject_id" class="form-label">Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">-- Select a subject --</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
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
                            <label class="form-label">Day(s) of the Week</label>
                            <div>
                                @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="day_of_week[]"
                                            value="{{ $day }}" id="day_{{ $day }}">
                                        <label class="form-check-label"
                                            for="day_{{ $day }}">{{ ucfirst($day) }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="start_time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">End Time</label>
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
