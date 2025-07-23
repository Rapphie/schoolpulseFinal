@extends('base')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Enrollment Form</h5>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('teacher.enrollment.index') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                            required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="student_id">Student ID</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id"
                                            required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="section_id">Section</label>
                                        <select class="form-control" id="section_id" name="section_id" required>
                                            <option value="">Select Section</option>
                                            @foreach ($sections as $section)
                                                <option value="{{ $section->id }}">{{ $section->name }} -
                                                    {{ $section->gradeLevel->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="birthdate">Birthdate</label>
                                        <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="guardian_id">Guardian</label>
                                <select class="form-control" id="guardian_id" name="guardian_id" required>
                                    <option value="">Select Guardian</option>
                                    @foreach ($guardians as $guardian)
                                        <option value="{{ $guardian->id }}">{{ $guardian->first_name }}
                                            {{ $guardian->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="teacher_id" value="">
                            <button type="submit" class="btn btn-primary">Enroll Student</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Sections</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            @foreach ($sections as $section)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $section->name }} - {{ $section->gradeLevel->name }}
                                    <span class="badge badge-primary badge-pill">{{ $section->students->count() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Unenrolled Students (Old)</h5>
                        <form action="{{ route('teacher.enrollment.index') }}" method="GET"
                            class="form-inline float-right">
                            <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search"
                                name="search" value="{{ request('search') }}">
                            <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Last Enrollment Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($oldStudents as $student)
                                    <tr>
                                        <td>{{ $student->student_id }}</td>
                                        <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                        <td>{{ $student->enrollment_date ? $student->enrollment_date->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td><span class="badge badge-warning">{{ $student->status }}</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary enroll-old-student"
                                                data-student-id="{{ $student->id }}"
                                                data-first-name="{{ $student->first_name }}"
                                                data-last-name="{{ $student->last_name }}"
                                                data-student-id-no="{{ $student->student_id }}"
                                                data-birthdate="{{ $student->birthdate->format('Y-m-d') }}"
                                                data-gender="{{ $student->gender }}">
                                                Enroll
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No old unenrolled students found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center">
                            {{ $oldStudents->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enrollButtons = document.querySelectorAll('.enroll-old-student');
            enrollButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('first_name').value = this.dataset.firstName;
                    document.getElementById('last_name').value = this.dataset.lastName;
                    document.getElementById('student_id').value = this.dataset.studentIdNo;
                    document.getElementById('birthdate').value = this.dataset.birthdate;
                    document.getElementById('gender').value = this.dataset.gender;
                    document.getElementById('guardian_id').value = this.dataset.guardianId;

                    // Scroll to the form
                    document.querySelector('.card-header h5.card-title').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
@endpush
