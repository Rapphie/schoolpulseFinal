@extends('base')

@section('title', 'View Teacher')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.teachers.index') }}">Teachers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Teacher</li>
                </ol>
            </nav>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-outline-success btn-sm">
                    <i data-feather="edit-2" class="me-1"></i> Edit
                </a>
                <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary btn-sm">Back</a>
            </div>
        </div>

        @php
            $profilePath = $teacher->profile_picture ?: optional($teacher->teacher)->profile_picture ?? null;
            $profileUrl = $profilePath
                ? asset('storage/' . ltrim($profilePath, '/'))
                : asset('images/user-placeholder.png');
        @endphp

        <div class="row">
            <div class="col-lg-8 col-md-8 col-sm-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Teacher Profile</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img src="{{ $profileUrl }}" class="rounded-circle" style="width: 72px; height: 72px;"
                                alt="Teacher">
                            <div>
                                <div class="fw-bold" style="font-size: 1.1rem;">{{ $teacher->full_name }}</div>
                                <div class="text-muted">{{ $teacher->email }}</div>
                            </div>
                        </div>

                        <h5 class="mb-3 border-bottom pb-2">Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="{{ $teacher->first_name }}" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" value="{{ $teacher->last_name }}" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="{{ $teacher->email }}" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control"
                                    value="{{ optional($teacher->teacher)->phone ?? 'N/A' }}" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <input type="text" class="form-control"
                                    value="{{ optional($teacher->teacher)->gender ?? 'N/A' }}" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="text" class="form-control"
                                    value="{{ optional($teacher->teacher)->date_of_birth ? \Carbon\Carbon::parse($teacher->teacher->date_of_birth)->toFormattedDateString() : 'N/A' }}"
                                    disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="2" disabled>{{ optional($teacher->teacher)->address ?? 'N/A' }}</textarea>
                        </div>

                        <h5 class="mt-4 mb-3 border-bottom pb-2">Professional Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Qualification</label>
                                <input type="text" class="form-control"
                                    value="{{ optional($teacher->teacher)->qualification ?? 'N/A' }}" disabled>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <input type="text" class="form-control"
                                    value="{{ optional($teacher->teacher)->status ?? 'N/A' }}" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-4 col-sm-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Advisory Classes (Current SY)</h6>
                    </div>
                    <div class="card-body">
                        @forelse ($advisoryClasses as $class)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ optional($class->section->gradeLevel)->name }} -
                                    {{ optional($class->section)->name }}</span>
                                @if ($class->section)
                                    <a href="{{ route('admin.sections.manage', $class->section->id) }}"
                                        class="btn btn-sm btn-outline-secondary">Manage</a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted text-center mb-0">Not an adviser for any class this year.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Subjects Taught (Current SY)</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($scheduledSubjects as $schedule)
                            <li class="list-group-item">
                                <span class="fw-bold">{{ optional($schedule->subject)->name }}</span>
                                <small class="d-block text-muted">Class:
                                    {{ optional(optional($schedule->class)->section)->name }}</small>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center">Not scheduled to teach any subjects.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
