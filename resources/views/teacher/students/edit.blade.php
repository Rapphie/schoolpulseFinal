@extends('base')

@section('title', 'Edit Student - ' . $student->first_name . ' ' . $student->last_name)

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
                            <a href="{{ route('teacher.students.index') }}">Student Profiles</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('teacher.students.show', $student) }}">{{ $student->first_name }}
                                {{ $student->last_name }}</a>
                        </li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
                <h4 class="mb-0">Edit Student Profile</h4>
            </div>
        </div>

        <form action="{{ route('teacher.students.update', $student) }}" method="POST" id="editStudentForm">
            @csrf
            @method('PUT')

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
                                        id="first_name" name="first_name"
                                        value="{{ old('first_name', $student->first_name) }}" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                        id="last_name" name="last_name" value="{{ old('last_name', $student->last_name) }}"
                                        required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="lrn" class="form-label">LRN (Learner Reference Number)</label>
                                    <input type="text" class="form-control @error('lrn') is-invalid @enderror"
                                        id="lrn" name="lrn" value="{{ old('lrn', $student->lrn) }}"
                                        maxlength="12">
                                    @error('lrn')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="gender" class="form-label">Gender <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select @error('gender') is-invalid @enderror" id="gender"
                                        name="gender" required>
                                        <option value="male"
                                            {{ old('gender', $student->gender) === 'male' ? 'selected' : '' }}>Male
                                        </option>
                                        <option value="female"
                                            {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Female
                                        </option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="birthdate" class="form-label">Birthdate <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('birthdate') is-invalid @enderror"
                                        id="birthdate" name="birthdate"
                                        value="{{ old('birthdate', $student->birthdate?->format('Y-m-d')) }}" required>
                                    @error('birthdate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" class="form-control" value="{{ $student->student_id }}" disabled>
                                    <small class="text-muted">Auto-generated, cannot be changed</small>
                                </div>
                                <div class="col-12">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $student->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i data-feather="info" class="icon-sm me-2"></i>
                                Additional Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="distance_km" class="form-label">Distance from School (km)</label>
                                    <input type="number" class="form-control @error('distance_km') is-invalid @enderror"
                                        id="distance_km" name="distance_km"
                                        value="{{ old('distance_km', $student->distance_km) }}" step="0.1"
                                        min="0" max="100">
                                    @error('distance_km')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="transportation" class="form-label">Mode of Transportation</label>
                                    <select class="form-select @error('transportation') is-invalid @enderror"
                                        id="transportation" name="transportation">
                                        <option value="">Select</option>
                                        <option value="Walking"
                                            {{ old('transportation', $student->transportation) === 'Walking' ? 'selected' : '' }}>
                                            Walking</option>
                                        <option value="Bicycle"
                                            {{ old('transportation', $student->transportation) === 'Bicycle' ? 'selected' : '' }}>
                                            Bicycle</option>
                                        <option value="Public Transport"
                                            {{ old('transportation', $student->transportation) === 'Public Transport' ? 'selected' : '' }}>
                                            Public Transport</option>
                                        <option value="Private Vehicle"
                                            {{ old('transportation', $student->transportation) === 'Private Vehicle' ? 'selected' : '' }}>
                                            Private Vehicle</option>
                                        <option value="School Bus"
                                            {{ old('transportation', $student->transportation) === 'School Bus' ? 'selected' : '' }}>
                                            School Bus</option>
                                    </select>
                                    @error('transportation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="family_income" class="form-label">Family Income Level</label>
                                    <select class="form-select @error('family_income') is-invalid @enderror"
                                        id="family_income" name="family_income">
                                        <option value="">Select</option>
                                        <option value="Low"
                                            {{ old('family_income', $student->family_income) === 'Low' ? 'selected' : '' }}>
                                            Low</option>
                                        <option value="Medium"
                                            {{ old('family_income', $student->family_income) === 'Medium' ? 'selected' : '' }}>
                                            Medium</option>
                                        <option value="High"
                                            {{ old('family_income', $student->family_income) === 'High' ? 'selected' : '' }}>
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
                            @if ($student->guardian && $student->guardian->user)
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="guardian_first_name" class="form-label">First Name</label>
                                        <input type="text"
                                            class="form-control @error('guardian_first_name') is-invalid @enderror"
                                            id="guardian_first_name" name="guardian_first_name"
                                            value="{{ old('guardian_first_name', $student->guardian->user->first_name) }}">
                                        @error('guardian_first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_last_name" class="form-label">Last Name</label>
                                        <input type="text"
                                            class="form-control @error('guardian_last_name') is-invalid @enderror"
                                            id="guardian_last_name" name="guardian_last_name"
                                            value="{{ old('guardian_last_name', $student->guardian->user->last_name) }}">
                                        @error('guardian_last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_relationship" class="form-label">Relationship</label>
                                        <select class="form-select @error('guardian_relationship') is-invalid @enderror"
                                            id="guardian_relationship" name="guardian_relationship">
                                            <option value="parent"
                                                {{ old('guardian_relationship', $student->guardian->relationship) === 'parent' ? 'selected' : '' }}>
                                                Parent</option>
                                            <option value="sibling"
                                                {{ old('guardian_relationship', $student->guardian->relationship) === 'sibling' ? 'selected' : '' }}>
                                                Sibling</option>
                                            <option value="relative"
                                                {{ old('guardian_relationship', $student->guardian->relationship) === 'relative' ? 'selected' : '' }}>
                                                Relative</option>
                                            <option value="guardian"
                                                {{ old('guardian_relationship', $student->guardian->relationship) === 'guardian' ? 'selected' : '' }}>
                                                Guardian</option>
                                        </select>
                                        @error('guardian_relationship')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_email" class="form-label">Email</label>
                                        <input type="email"
                                            class="form-control @error('guardian_email') is-invalid @enderror"
                                            id="guardian_email" name="guardian_email"
                                            value="{{ old('guardian_email', $student->guardian->user->email) }}">
                                        @error('guardian_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="guardian_phone" class="form-label">Phone Number</label>
                                        <input type="text"
                                            class="form-control @error('guardian_phone') is-invalid @enderror"
                                            id="guardian_phone" name="guardian_phone"
                                            value="{{ old('guardian_phone', $student->guardian->phone) }}">
                                        @error('guardian_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-3">
                                    <i data-feather="user-x" class="text-muted mb-2"
                                        style="width: 32px; height: 32px;"></i>
                                    <p class="text-muted mb-0">No guardian linked to this student.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="save" class="icon-sm me-2"></i>
                            Save Changes
                        </button>
                        <a href="{{ route('teacher.students.show', $student) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            const form = document.getElementById('editStudentForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                });
            }
        });
    </script>
@endpush
