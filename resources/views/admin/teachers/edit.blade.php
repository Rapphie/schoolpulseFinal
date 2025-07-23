@extends('base')

@section('title', 'Edit Teacher')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.teachers.index') }}">Teachers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Teacher</li>
                </ol>
            </nav>
            <a class="btn btn-primary d-flex align-items-center" href="{{ route('admin.teachers.index') }}">
                <i data-feather="arrow-left" class="me-1"></i> Back to Teachers
            </a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Teacher Details</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.teachers.update', $teacher) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="first_name"
                                    value="{{ $teacher->user->first_name }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="last_name"
                                    value="{{ $teacher->user->last_name }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ $teacher->user->email }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="{{ $teacher->phone }}">
                            </div>

                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ $teacher->gender == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ $teacher->gender == 'female' ? 'selected' : '' }}>Female
                                    </option>
                                    <option value="other" {{ $teacher->gender == 'other' ? 'selected' : '' }}>Other
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                    value="{{ $teacher->date_of_birth }}">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address"
                                    value="{{ $teacher->address }}">
                            </div>

                            <div class="mb-3">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification"
                                    value="{{ $teacher->qualification }}">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select" required>
                                    <option value="active" {{ $teacher->status == 'active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="on-leave" {{ $teacher->status == 'on-leave' ? 'selected' : '' }}>On
                                        Leave</option>
                                    <option value="inactive" {{ $teacher->status == 'inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                                @if ($teacher->profile_picture)
                                    <img src="{{ asset('storage/' . $teacher->profile_picture) }}"
                                        class="img-thumbnail mt-2" width="150">
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary">Update Teacher</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Assign Subjects</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.teachers.assign_subject', $teacher) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label for="subject_id" class="form-label">Subject</label>
                                        <select name="subject_id" id="subject_id" class="form-select" required>
                                            <option value="" selected>Select Subject</option>
                                            @foreach ($subjects as $subject)
                                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label for="section_id" class="form-label">Section</label>
                                        <select name="section_id" id="section_id" class="form-select">
                                            <option value="" selected>Select Section</option>
                                            @foreach ($sections as $section)
                                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary mb-3">Assign</button>
                                </div>
                            </div>
                        </form>

                        <hr>

                        <h6 class="mt-5">Assigned Subjects</h6>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assignedSubjects as $assignedSubject)
                                    <tr>
                                        <td>{{ $assignedSubject->subject_name }}</td>
                                        <td>{{ $assignedSubject->section_name ?? 'No section assigned' }}</td>
                                        <td>
                                            <form action="{{ route('admin.teachers.unassign_subject', $teacher) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="subject_id"
                                                    value="{{ $assignedSubject->subject_id }}">
                                                <input type="hidden" name="section_id"
                                                    value="{{ $assignedSubject->section_id }}">
                                                <button type="submit" class="btn btn-danger btn-sm">Unassign</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No subjects assigned yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
