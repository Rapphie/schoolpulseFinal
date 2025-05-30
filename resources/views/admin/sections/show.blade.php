@extends('admin.layout')

@section('title', 'Section: ' . $section->name)

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Sections</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $section->name }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <h1>Section: {{ $section->name }}</h1>
        <div>
            <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Section
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Section Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 font-weight-bold">Section Name:</div>
                        <div class="col-md-8">{{ $section->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 font-weight-bold">Grade Level:</div>
                        <div class="col-md-8">Grade {{ $section->grade_level }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4 font-weight-bold">Adviser:</div>
                        <div class="col-md-8">
                            @if ($section->adviser)
                                {{ $section->adviser->name }}
                            @else
                                <span class="text-muted">No adviser assigned</span>
                            @endif
                        </div>
                    </div>
                    @if ($section->description)
                        <div class="row mb-3">
                            <div class="col-md-4 font-weight-bold">Description:</div>
                            <div class="col-md-8">{{ $section->description }}</div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-4 font-weight-bold">Created At:</div>
                        <div class="col-md-8">{{ $section->created_at->format('F j, Y') }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Students</h5>
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addStudentModal">
                        <i class="fas fa-plus"></i> Add Student
                    </button>
                </div>
                <div class="card-body">
                    @if ($section->students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Number</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section->students as $student)
                                        <tr>
                                            <td>{{ $student->id ?? 'N/A' }}</td>
                                            <td>{{ $student->full_name }}</td>
                                            <td>{{ $student->email }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" title="Remove from Section"
                                                    onclick="return confirm('Are you sure you want to remove this student from the section?')">
                                                    <i class="fas fa-user-minus"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            No students assigned to this section yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-4 font-weight-bold">{{ $section->students->count() }}</div>
                        <div class="text-muted">Total Students</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Male</span>
                            <span>{{ $section->students->where('gender', 'male')->count() }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar"
                                style="width: {{ $section->students->count() > 0 ? ($section->students->where('gender', 'male')->count() / $section->students->count()) * 100 : 0 }}%"
                                aria-valuenow="{{ $section->students->where('gender', 'male')->count() }}"
                                aria-valuemin="0" aria-valuemax="{{ $section->students->count() }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Female</span>
                            <span>{{ $section->students->where('gender', 'female')->count() }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-pink" role="progressbar"
                                style="width: {{ $section->students->count() > 0 ? ($section->students->where('gender', 'female')->count() / $section->students->count()) * 100 : 0 }}%"
                                aria-valuenow="{{ $section->students->where('gender', 'female')->count() }}"
                                aria-valuemin="0" aria-valuemax="{{ $section->students->count() }}">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Class Schedule</h6>
                        @if ($section->schedules->count() > 0)
                            <ul class="list-group">
                                @foreach ($section->schedules as $schedule)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $schedule->subject->name }}</strong><br>
                                            <small class="text-muted">
                                                {{ $schedule->day }} • {{ $schedule->start_time }} -
                                                {{ $schedule->end_time }}
                                            </small>
                                        </div>
                                        <span class="badge badge-primary">{{ $schedule->room }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="alert alert-warning mb-0">
                                No schedule assigned yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" role="dialog" aria-labelledby="addStudentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Student to Section</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.sections.students.store', $section) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="student_id">Select Student</label>
                            <select class="form-control select2" id="student_id" name="student_id" required>
                                <option value="">Select a student</option>
                                @foreach ($availableStudents as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->name }} ({{ $student->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: 'Select a student',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endpush
