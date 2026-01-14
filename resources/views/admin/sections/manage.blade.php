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
                <h1 class="h3 mb-0 text-gray-800">Manage: {{ $section->gradeLevel->name }}-{{ $section->name }}</h1>
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
                </div>
            </div>

            <!-- Enrollment Card -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100">
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
                    <div class="card-footer text-center">
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                            data-bs-target="#updateCapacityModal">
                            Update Capacity
                        </button>
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
                                        <td class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ route('admin.students.show', $enrollment->student) }}">
                                                View
                                            </a>

                                            <button class="btn btn-sm btn-outline-secondary edit-student-btn"
                                                data-bs-toggle="modal" data-bs-target="#editStudentModal"
                                                data-update-url="{{ route('admin.students.update', $enrollment->student) }}"
                                                data-student-id="{{ $enrollment->student->id }}"
                                                data-lrn="{{ $enrollment->student->lrn ?? '' }}"
                                                data-first-name="{{ $enrollment->student->first_name }}"
                                                data-last-name="{{ $enrollment->student->last_name }}"
                                                data-gender="{{ $enrollment->student->gender }}"
                                                data-birthdate="{{ $enrollment->student->birthdate?->format('Y-m-d') }}"
                                                data-address="{{ $enrollment->student->address ?? '' }}"
                                                data-distance="{{ $enrollment->student->distance_km ?? '' }}"
                                                data-transportation="{{ $enrollment->student->transportation ?? '' }}"
                                                data-family-income="{{ $enrollment->student->family_income ?? '' }}"
                                                data-guardian-first-name="{{ $enrollment->student->guardian->user->first_name ?? '' }}"
                                                data-guardian-last-name="{{ $enrollment->student->guardian->user->last_name ?? '' }}"
                                                data-guardian-email="{{ $enrollment->student->guardian->user->email ?? '' }}"
                                                data-guardian-phone="{{ $enrollment->student->guardian->phone ?? '' }}"
                                                data-guardian-relationship="{{ $enrollment->student->guardian->relationship ?? '' }}">
                                                Edit
                                            </button>

                                            <form
                                                action="{{ route('admin.sections.students.destroy', [$section, $enrollment->student]) }}"
                                                method="POST" class="m-0"
                                                onsubmit="return confirm('Remove this student from the section? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
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
                                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        @else
                                            <td class="text-muted"><em>Not Assigned</em></td>
                                            <td class="text-muted"><em>Not Set</em></td>
                                            <td class="text-center">
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

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editStudentForm" action="#" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="_form" value="edit-student">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_lrn" class="form-label">LRN</label>
                                <input type="text" class="form-control" id="edit_lrn" name="lrn"
                                    value="{{ old('lrn') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_first_name" class="form-label">First Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name"
                                    value="{{ old('first_name') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_last_name" class="form-label">Last Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name"
                                    value="{{ old('last_name') }}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_gender" class="form-label">Gender <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="edit_gender" name="gender" required>
                                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_birthdate" class="form-label">Birthdate <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_birthdate" name="birthdate"
                                    value="{{ old('birthdate') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2">{{ old('address') }}</textarea>
                        </div>

                        <h6 class="mt-4 mb-3 border-bottom pb-2">Additional Information (For Analytics)</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_distance_km" class="form-label">Distance from School (km)</label>
                                <input type="number" step="0.01" min="0" class="form-control"
                                    id="edit_distance_km" name="distance_km" value="{{ old('distance_km') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_transportation" class="form-label">Mode of Transportation</label>
                                <select class="form-select" id="edit_transportation" name="transportation">
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
                                <label for="edit_family_income" class="form-label">Socioeconomic Status</label>
                                <select class="form-select" id="edit_family_income" name="family_income">
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
                                <label for="edit_guardian_first_name" class="form-label">First Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_guardian_first_name"
                                    name="guardian_first_name" value="{{ old('guardian_first_name') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_guardian_last_name" class="form-label">Last Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_guardian_last_name"
                                    name="guardian_last_name" value="{{ old('guardian_last_name') }}" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_guardian_email" class="form-label">Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="edit_guardian_email"
                                    name="guardian_email" value="{{ old('guardian_email') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_guardian_phone" class="form-label">Phone <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_guardian_phone"
                                    name="guardian_phone" value="{{ old('guardian_phone') }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_guardian_relationship" class="form-label">Relationship to Student <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="edit_guardian_relationship" name="guardian_relationship"
                                required>
                                <option value="parent" {{ old('guardian_relationship') == 'parent' ? 'selected' : '' }}>
                                    Parent</option>
                                <option value="sibling" {{ old('guardian_relationship') == 'sibling' ? 'selected' : '' }}>
                                    Sibling</option>
                                <option value="relative"
                                    {{ old('guardian_relationship') == 'relative' ? 'selected' : '' }}>Relative</option>
                                <option value="guardian"
                                    {{ old('guardian_relationship') == 'guardian' ? 'selected' : '' }}>Guardian</option>
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
                        <input type="hidden" name="_form" value="enroll">
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
                                    <option value="" {{ old('family_income') == '' ? 'selected' : '' }}>-- Select
                                        --
                                    </option>
                                    <option value="Low" {{ old('family_income') == 'Low' ? 'selected' : '' }}>Low
                                    </option>
                                    <option value="Medium" {{ old('family_income') == 'Medium' ? 'selected' : '' }}>
                                        Medium
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
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="capacity" name="capacity"
                                value="{{ $class->capacity }}" min="1" required>
                            <div class="form-text">Current enrollment: {{ $class->enrollments->count() }} students</div>
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
                const isLowerGrade = button?.getAttribute('data-is-lower-grade') === 'true';

                const form = document.getElementById('editScheduleForm');
                const teacherSelect = document.getElementById('edit_teacher_id');
                const teacherLabel = document.querySelector('label[for="edit_teacher_id"]');
                const subjectInput = document.getElementById('edit_subject_id');
                const subjectDisplay = document.getElementById('edit_subject_display');
                const startInput = document.getElementById('edit_start_time');
                const endInput = document.getElementById('edit_end_time');
                const roomInput = document.getElementById('edit_room');

                // Set form action
                if (form && updateUrl) {
                    form.setAttribute('action', updateUrl);
                }

                // Populate teacher and handle lower grade restriction
                if (teacherSelect) {
                    teacherSelect.value = teacherId || '';
                    if (isLowerGrade) {
                        teacherSelect.setAttribute('disabled', 'disabled');
                        // Add a hidden input to maintain the teacher_id value
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
                        const hiddenTeacher = form.querySelector('input[name="teacher_id"][type="hidden"]');
                        if (hiddenTeacher) hiddenTeacher.remove();
                    }
                }

                // Update label to show restriction note for lower grades
                const teacherNote = editModalEl.querySelector('#teacher-restriction-note');
                if (isLowerGrade) {
                    if (!teacherNote) {
                        const note = document.createElement('small');
                        note.id = 'teacher-restriction-note';
                        note.className = 'text-muted d-block';
                        note.textContent = 'Teacher is locked to the class adviser for Grade 1-3.';
                        teacherSelect.parentNode.appendChild(note);
                    }
                } else {
                    if (teacherNote) teacherNote.remove();
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
                document.getElementById('edit_room').value = '';
                document.querySelectorAll('.edit-day-checkbox').forEach(cb => cb.checked = false);
            });
        })();
    </script>
    <script>
        (function() {
            const editStudentModal = document.getElementById('editStudentModal');
            if (!editStudentModal) return;

            editStudentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const form = document.getElementById('editStudentForm');

                if (form) {
                    const updateUrl = button?.getAttribute('data-update-url');
                    if (updateUrl) {
                        form.setAttribute('action', updateUrl);
                    }
                }

                const setValue = (id, value) => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.value = value ?? '';
                    }
                };

                const data = button?.dataset ?? {};
                setValue('edit_lrn', data.lrn);
                setValue('edit_first_name', data.firstName);
                setValue('edit_last_name', data.lastName);
                setValue('edit_gender', data.gender);
                setValue('edit_birthdate', data.birthdate);
                setValue('edit_address', data.address);
                setValue('edit_distance_km', data.distance);
                setValue('edit_transportation', data.transportation);
                setValue('edit_family_income', data.familyIncome);
                setValue('edit_guardian_first_name', data.guardianFirstName);
                setValue('edit_guardian_last_name', data.guardianLastName);
                setValue('edit_guardian_email', data.guardianEmail);
                setValue('edit_guardian_phone', data.guardianPhone);
                setValue('edit_guardian_relationship', data.guardianRelationship);
            });

            editStudentModal.addEventListener('hidden.bs.modal', function() {
                const form = document.getElementById('editStudentForm');
                if (form) {
                    form.removeAttribute('action');
                    form.reset();
                }
            });
        })();
    </script>
    @if (($errors->any() && old('_form') === 'enroll') || session('error_form') === 'enroll')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    var modalEl = document.getElementById('enrollStudentModal');
                    if (modalEl) {
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                } catch (e) {
                    // fail silently if bootstrap not available on the page load
                    console.warn('Could not show enroll modal automatically:', e);
                }
            });
        </script>
    @endif

    <script>
        (function() {
            // Teacher data for the combobox
            const teachersList = @json($teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->user->first_name . ' ' . $t->user->last_name]));

            const adviserInput = document.getElementById('adviser_search');
            const adviserHidden = document.getElementById('adviser_teacher_id');
            const adviserMenu = document.getElementById('adviserDropdown');

            if (!adviserInput || !adviserHidden || !adviserMenu) return;

            const renderDropdown = (teachers) => {
                adviserMenu.innerHTML = '';
                if (teachers.length === 0) {
                    adviserMenu.innerHTML = '<div class="dropdown-item text-muted">No teachers found</div>';
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

            // Filter and show dropdown on input
            adviserInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const filtered = teachersList.filter(t => t.name.toLowerCase().includes(query));
                renderDropdown(filtered);
                adviserMenu.classList.add('show');
            });

            // Show all teachers on focus
            adviserInput.addEventListener('focus', function() {
                const query = this.value.toLowerCase().trim();
                const filtered = query ?
                    teachersList.filter(t => t.name.toLowerCase().includes(query)) :
                    teachersList;
                renderDropdown(filtered);
                adviserMenu.classList.add('show');
            });

            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!adviserInput.contains(e.target) && !adviserMenu.contains(e.target)) {
                    adviserMenu.classList.remove('show');
                }
            });

            // Reset modal on open
            const assignAdviserModal = document.getElementById('assignAdviserModal');
            if (assignAdviserModal) {
                assignAdviserModal.addEventListener('show.bs.modal', function() {
                    // Set initial value if there's a current adviser
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
