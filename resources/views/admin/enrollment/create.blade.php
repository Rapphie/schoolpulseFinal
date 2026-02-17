@extends('base')

@section('title', 'Enroll New Student')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/students/students.css') }}">
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.sections.index') }}">Sections</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.sections.manage', $section) }}">{{ $section->name }}</a>
                        </li>
                        <li class="breadcrumb-item active">Enroll New Student</li>
                    </ol>
                </nav>
                <h4 class="mb-0">Enroll New Student in {{ $section->name }}</h4>
                <small class="text-muted">{{ $section->gradeLevel->name ?? '' }} &mdash; {{ $class->schoolYear->name ?? '' }}</small>
            </div>
        </div>

        @if (!$activeSchoolYear)
            <div class="alert alert-warning">
                <i data-feather="alert-circle" class="icon-sm me-2"></i>
                <strong>No Active School Year</strong><br>
                Please ensure there is an active school year before enrolling students.
            </div>
        @else
            <form action="{{ route('admin.enrollment.store', $class) }}" method="POST" id="enrollStudentForm">
                @csrf

                <div class="row">
                    <!-- Student Information -->
                    <div class="col-lg-8">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i data-feather="user" class="icon-sm me-2"></i>
                                    Student Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                            id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                            id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lrn" class="form-label">LRN (Learner Reference Number)</label>
                                        <input type="text" class="form-control @error('lrn') is-invalid @enderror"
                                            id="lrn" name="lrn" value="{{ old('lrn') }}" maxlength="12">
                                        @error('lrn')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Optional - 12-digit LRN</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('gender') is-invalid @enderror" id="gender"
                                            name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male
                                            </option>
                                            <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>
                                                Female</option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="birthdate" class="form-label">Birthdate <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('birthdate') is-invalid @enderror"
                                            id="birthdate" name="birthdate" value="{{ old('birthdate') }}" required>
                                        @error('birthdate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Capacity info -->
                                        <label class="form-label">Class Capacity</label>
                                        <div class="form-control-plaintext">
                                            <span class="badge {{ $enrolledCount >= $class->capacity ? 'bg-danger' : 'bg-success' }}">
                                                {{ $enrolledCount }} / {{ $class->capacity }}
                                            </span>
                                            enrolled
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information (Analytics) -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i data-feather="info" class="icon-sm me-2"></i>
                                    Additional Information
                                </h6>
                                <small class="text-muted">Optional - Used for analytics</small>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="distance_km" class="form-label">Distance from School (km)</label>
                                        <input type="number"
                                            class="form-control @error('distance_km') is-invalid @enderror"
                                            id="distance_km" name="distance_km" value="{{ old('distance_km') }}"
                                            step="0.1" min="0" max="100">
                                        @error('distance_km')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="transportation" class="form-label">Mode of Transportation</label>
                                        <select class="form-select @error('transportation') is-invalid @enderror"
                                            id="transportation" name="transportation">
                                            <option value="">Select</option>
                                            <option value="Walk"
                                                {{ old('transportation') === 'Walk' ? 'selected' : '' }}>Walk
                                            </option>
                                            <option value="Bicycle"
                                                {{ old('transportation') === 'Bicycle' ? 'selected' : '' }}>Bicycle
                                            </option>
                                            <option value="Motorcycle"
                                                {{ old('transportation') === 'Motorcycle' ? 'selected' : '' }}>
                                                Motorcycle</option>
                                            <option value="Tricycle"
                                                {{ old('transportation') === 'Tricycle' ? 'selected' : '' }}>
                                                Tricycle</option>
                                            <option value="Jeepney"
                                                {{ old('transportation') === 'Jeepney' ? 'selected' : '' }}>
                                                Jeepney</option>
                                            <option value="Bus"
                                                {{ old('transportation') === 'Bus' ? 'selected' : '' }}>Bus
                                            </option>
                                            <option value="Private Vehicle"
                                                {{ old('transportation') === 'Private Vehicle' ? 'selected' : '' }}>
                                                Private Vehicle</option>
                                        </select>
                                        @error('transportation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="family_income" class="form-label">Socioeconomic Status</label>
                                        <select class="form-select @error('family_income') is-invalid @enderror"
                                            id="family_income" name="family_income">
                                            <option value="">Select</option>
                                            <option value="Low" {{ old('family_income') === 'Low' ? 'selected' : '' }}>
                                                Low</option>
                                            <option value="Medium"
                                                {{ old('family_income') === 'Medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="High"
                                                {{ old('family_income') === 'High' ? 'selected' : '' }}>
                                                High</option>
                                        </select>
                                        @error('family_income')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Information -->
                    <div class="col-lg-4">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i data-feather="users" class="icon-sm me-2"></i>
                                    Guardian Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="guardian_first_name" class="form-label">First Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('guardian_first_name') is-invalid @enderror"
                                            id="guardian_first_name" name="guardian_first_name"
                                            value="{{ old('guardian_first_name') }}" required>
                                        @error('guardian_first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_last_name" class="form-label">Last Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('guardian_last_name') is-invalid @enderror"
                                            id="guardian_last_name" name="guardian_last_name"
                                            value="{{ old('guardian_last_name') }}" required>
                                        @error('guardian_last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_relationship" class="form-label">Relationship <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('guardian_relationship') is-invalid @enderror"
                                            id="guardian_relationship" name="guardian_relationship" required>
                                            <option value="">Select Relationship</option>
                                            <option value="parent"
                                                {{ old('guardian_relationship') === 'parent' ? 'selected' : '' }}>Parent
                                            </option>
                                            <option value="sibling"
                                                {{ old('guardian_relationship') === 'sibling' ? 'selected' : '' }}>
                                                Sibling</option>
                                            <option value="relative"
                                                {{ old('guardian_relationship') === 'relative' ? 'selected' : '' }}>
                                                Relative</option>
                                            <option value="guardian"
                                                {{ old('guardian_relationship') === 'guardian' ? 'selected' : '' }}>
                                                Guardian</option>
                                        </select>
                                        @error('guardian_relationship')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_email" class="form-label">Email <span
                                                class="text-danger">*</span></label>
                                        <input type="email"
                                            class="form-control @error('guardian_email') is-invalid @enderror"
                                            id="guardian_email" name="guardian_email"
                                            value="{{ old('guardian_email') }}" required>
                                        @error('guardian_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Login credentials will be sent here</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_phone" class="form-label">Phone Number <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('guardian_phone') is-invalid @enderror"
                                            id="guardian_phone" name="guardian_phone"
                                            value="{{ old('guardian_phone') }}" required>
                                        @error('guardian_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="alert alert-info">
                            <i data-feather="info" class="icon-sm me-2"></i>
                            <strong>What happens next?</strong>
                            <ul class="mb-0 mt-2 ps-3">
                                <li>Student profile is created</li>
                                <li>Guardian account is created</li>
                                <li>Login credentials sent to guardian</li>
                                <li>Student is enrolled in <strong>{{ $section->name }}</strong></li>
                            </ul>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i data-feather="user-plus" class="icon-sm me-2"></i>
                                Enroll Student
                            </button>
                            <a href="{{ route('admin.sections.manage', $section) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            const form = document.getElementById('enrollStudentForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = document.getElementById('submitBtn');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Enrolling...';
                });
            }
        });
    </script>
@endpush
