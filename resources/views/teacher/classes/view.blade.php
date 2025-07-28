@extends('base')

@section('title', 'View Class: ' . $class->section->name)

@section('content')
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
            </div>
            <div class="card-body">
                @if ($class->schedules->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th>Day(s)</th>
                                    <th>Time</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($class->schedules as $schedule)
                                    <tr>
                                        <td>{{ implode(', ', array_map('ucfirst', json_decode($schedule->day_of_week))) }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} -
                                            {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}</td>
                                        <td>{{ $schedule->subject->name }}</td>
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
            </div>
        </div>
    </div>
    <!-- Enroll New Student Modal -->
    <div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-labelledby="enrollStudentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="enrollStudentModalLabel">Enroll New Student in {{ $class->section->name }}
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
                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
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
