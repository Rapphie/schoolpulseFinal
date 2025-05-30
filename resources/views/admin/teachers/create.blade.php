@extends('admin.layout')

@section('title', 'Add New Teacher')

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.teachers.index') }}">Teachers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add New</li>
        </ol>
    </nav>
    <h1>Add New Teacher</h1>
@endsection

@section('content')
<div class="card shadow">
    <div class="card-body">
        <form action="{{ route('admin.teachers.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                               id="phone" name="phone" value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control"
                               id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                               id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea class="form-control @error('address') is-invalid @enderror"
                          id="address" name="address" rows="2">{{ old('address') }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="qualification">Qualification</label>
                <input type="text" class="form-control @error('qualification') is-invalid @enderror"
                       id="qualification" name="qualification" value="{{ old('qualification') }}">
                @error('qualification')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="subjects">Subjects (Hold Ctrl to select multiple)</label>
                <select multiple class="form-control select2 @error('subjects') is-invalid @enderror"
                        id="subjects" name="subjects[]">
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ in_array($subject->id, old('subjects', [])) ? 'selected' : '' }}>
                            {{ $subject->name }} ({{ $subject->code }})
                        </option>
                    @endforeach
                </select>
                @error('subjects')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="profile_picture">Profile Picture</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input @error('profile_picture') is-invalid @enderror"
                           id="profile_picture" name="profile_picture">
                    <label class="custom-file-label" for="profile_picture">Choose file</label>
                    @error('profile_picture')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <small class="form-text text-muted">Max file size: 2MB. Allowed formats: jpg, jpeg, png, gif</small>
            </div>

            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                       {{ old('is_active', 1) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Teacher
                </button>
            </div>
        </form>
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
            placeholder: 'Select subjects',
            allowClear: true,
            width: '100%'
        });

        // Show file name when file is selected
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });
    });
</script>
@endpush
