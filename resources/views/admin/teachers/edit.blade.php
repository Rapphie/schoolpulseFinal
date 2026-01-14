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
        </div>

        <div class="row">
            <!-- Edit Teacher Details Form -->
            <div class="col-lg-8 col-md-8 col-sm-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Edit Profile: {{ $teacher->first_name }}
                            {{ $teacher->last_name }}</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.teachers.update', $teacher) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Validation Errors Summary --}}
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong><i data-feather="alert-triangle" class="icon-sm me-2"></i> Please fix the
                                        following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <h5 class="mb-3 border-bottom pb-2">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                        id="first_name" name="first_name"
                                        value="{{ old('first_name', $teacher->first_name) }}" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                        id="last_name" name="last_name" value="{{ old('last_name', $teacher->last_name) }}"
                                        required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email', $teacher->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" name="phone" value="{{ old('phone', $teacher->teacher->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select id="gender" name="gender"
                                        class="form-select @error('gender') is-invalid @enderror">
                                        <option value="">Select Gender</option>
                                        <option value="male"
                                            {{ old('gender', $teacher->teacher->gender) == 'male' ? 'selected' : '' }}>
                                            Male
                                        </option>
                                        <option value="female"
                                            {{ old('gender', $teacher->teacher->gender) == 'female' ? 'selected' : '' }}>
                                            Female
                                        </option>
                                        <option value="other"
                                            {{ old('gender', $teacher->teacher->gender) == 'other' ? 'selected' : '' }}>
                                            Other
                                        </option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                                        id="date_of_birth" name="date_of_birth"
                                        value="{{ old('date_of_birth', $teacher->teacher->date_of_birth ? \Carbon\Carbon::parse($teacher->teacher->date_of_birth)->format('Y-m-d') : '') }}">
                                    @error('date_of_birth')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $teacher->teacher->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control @error('profile_picture') is-invalid @enderror"
                                    id="profile_picture" name="profile_picture" accept="image/*">
                                @error('profile_picture')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @php
                                    $profilePath =
                                        $teacher->profile_picture ?:
                                        optional($teacher->teacher)->profile_picture ?? null;
                                    if ($profilePath && preg_match('/^https?:\/\//i', $profilePath)) {
                                        $profileUrl = $profilePath;
                                    } elseif ($profilePath) {
                                        $normalized = ltrim($profilePath, '/');
                                        $profileUrl = str_starts_with($normalized, 'storage/')
                                            ? asset($normalized)
                                            : asset('storage/' . $normalized);
                                    } else {
                                        $profileUrl = asset('images/user-placeholder.png');
                                    }
                                @endphp
                                <div class="mt-2">
                                    <img id="profile_picture_preview" src="{{ $profileUrl }}"
                                        data-original-src="{{ $profileUrl }}" class="img-thumbnail" width="150"
                                        alt="Current Profile Picture">
                                    <div class="mt-2">
                                        <button type="button" id="profile_picture_cancel"
                                            class="btn btn-outline-secondary btn-sm" style="display: none;">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3 border-bottom pb-2">Professional Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="qualification" class="form-label">Qualification</label>
                                    <input type="text"
                                        class="form-control @error('qualification') is-invalid @enderror"
                                        id="qualification" name="qualification"
                                        value="{{ old('qualification', $teacher->teacher->qualification) }}">
                                    @error('qualification')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status"
                                        name="status" required>
                                        <option value="active"
                                            {{ old('status', $teacher->teacher->status) == 'active' ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="on-leave"
                                            {{ old('status', $teacher->teacher->status) == 'on-leave' ? 'selected' : '' }}>
                                            On Leave
                                        </option>
                                        <option value="inactive"
                                            {{ old('status', $teacher->teacher->status) == 'inactive' ? 'selected' : '' }}>
                                            Inactive
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Display Teacher's Assignments -->
            <div class="col-lg-4 col-md-4 col-sm-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Advisory Classes (Current SY)</h6>
                    </div>
                    <div class="card-body">
                        @forelse ($advisoryClasses as $class)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>{{ $class->section->gradeLevel->name }} - {{ $class->section->name }}</span>
                                <a href="{{ route('admin.sections.manage', $class->section->id) }}"
                                    class="btn btn-sm btn-outline-secondary">Manage</a>
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
                                <span class="fw-bold">{{ $schedule->subject->name }}</span>
                                <small class="d-block text-muted">Class: {{ $schedule->class->section->name }}</small>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center">Not scheduled to teach any subjects.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @endsection

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const input = document.getElementById('profile_picture');
                    const preview = document.getElementById('profile_picture_preview');
                    const cancelBtn = document.getElementById('profile_picture_cancel');

                    if (!input || !preview || !cancelBtn) return;

                    let objectUrl = null;

                    // Only show cancel when a new file is selected
                    cancelBtn.style.display = 'none';

                    function resetToOriginal() {
                        if (objectUrl) {
                            URL.revokeObjectURL(objectUrl);
                            objectUrl = null;
                        }
                        input.value = '';
                        preview.src = preview.getAttribute('data-original-src') || preview.src;
                        cancelBtn.style.display = 'none';
                    }

                    input.addEventListener('change', function() {
                        const file = input.files && input.files[0];
                        if (!file) {
                            resetToOriginal();
                            return;
                        }
                        if (!file.type || !file.type.startsWith('image/')) {
                            resetToOriginal();
                            return;
                        }
                        if (objectUrl) URL.revokeObjectURL(objectUrl);
                        objectUrl = URL.createObjectURL(file);
                        preview.src = objectUrl;
                        cancelBtn.style.display = '';
                    });

                    cancelBtn.addEventListener('click', function() {
                        resetToOriginal();
                    });
                });
            </script>
        @endpush
