@extends('base')

@section('title', 'School-Wide Enrollment')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.enrollment.index') }}">Schedules</a></li>
                    <li class="breadcrumb-item active" aria-current="page">School-Wide Enrollment</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Enroll New Student</h6>
            </div>
            <div class="card-body">
                @if (isset($error))
                    <div class="alert alert-warning text-center">
                        <h4 class="alert-heading">Enrollment Closed</h4>
                        <p>{{ $error }}</p>
                    </div>
                @else
                    <form action="{{ route('teacher.enrollment.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="class_id" class="form-label">1. Select Class to Enroll In <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="" selected disabled>-- Choose a class --</option>
                                    @foreach ($classes as $class)
                                        @if ($class->enrollments->count() < $class->capacity)
                                            <option value="{{ $class->id }}"
                                                {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->section->gradeLevel->name }} - {{ $class->section->name }}
                                                ({{ $class->enrollments->count() }}/{{ $class->capacity }})
                                            </option>
                                        @else
                                            <option value="{{ $class->id }}" disabled>
                                                {{ $class->section->gradeLevel->name }} - {{ $class->section->name }}
                                                (Full)
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="enrollment-form-fields" class="d-none">
                            <h6 class="mt-4 mb-3 border-bottom pb-2">Student Information</h6>
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
                                <select class="form-select" id="guardian_relationship" name="guardian_relationship"
                                    required>
                                    <option value="parent">Parent</option>
                                    <option value="sibling">Sibling</option>
                                    <option value="relative">Relative</option>
                                    <option value="guardian">Guardian</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary">Enroll Student</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card shadow mb-4 mt-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Students to Enroll from Previous Year</h6>
            </div>
            <div class="card-body">
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if ($students->isEmpty())
                    <div class="alert alert-info text-center">
                        <h4 class="alert-heading">No Students Found</h4>
                        <p>There are no students from the previous school year that are not yet enrolled in the current one.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Student Name</th>
                                    <th>Last Enrolled Grade</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $student)
                                    <tr>
                                        <td>{{ $student->lrn ?? 'N/A' }}</td>
                                        <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                        <td>
                                            @php
                                                $lastEnrollment = $student->enrollments
                                                    ->sortByDesc('school_year_id')
                                                    ->first();
                                            @endphp
                                            {{ $lastEnrollment->class->section->gradeLevel->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            <form action="{{ route('teacher.enrollment.storePastStudent') }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <div class="input-group">
                                                    <select name="class_id" class="form-control" required>
                                                        <option value="" selected disabled>Select Class</option>
                                                        @foreach ($classes as $class)
                                                            <option value="{{ $class->id }}">
                                                                {{ $class->section->gradeLevel->name }} -
                                                                {{ $class->section->name }}
                                                                ({{ $class->enrollments->count() }}/{{ $class->capacity }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit"
                                                        class="btn btn-sm btn-primary ml-2">Enroll</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const classSelect = document.getElementById('class_id');
            const formFields = document.getElementById('enrollment-form-fields');

            function toggleForm() {
                if (classSelect.value) {
                    formFields.classList.remove('d-none');
                } else {
                    formFields.classList.add('d-none');
                }
            }

            // Check on page load in case of validation errors
            toggleForm();

            classSelect.addEventListener('change', toggleForm);
        });
    </script>
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/demo/datatables-demo.js') }}"></script>
@endpush
