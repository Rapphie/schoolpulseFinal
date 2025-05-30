@extends('admin.layout')

@section('title', 'Edit Teacher: ' . $teacher->name)

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.teachers.index') }}">Teachers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit: {{ $teacher->name }}</li>
        </ol>
    </nav>
    <h1>Edit Teacher: {{ $teacher->name }}</h1>
@endsection

@section('content')
    <main class="p-4">
        <div class="card shadow">
            <div class="card-body">
                <form action="{{ route('admin.teachers.update', $teacher) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                    id="first_name" name="first_name" value="{{ old('first_name', $teacher->first_name) }}"
                                    required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                    id="last_name" name="last_name" value="{{ old('last_name', $teacher->last_name) }}"
                                    required>
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
                                    id="email" name="email" value="{{ old('email', $teacher->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                    id="phone" name="phone" value="{{ old('phone', $teacher->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Leave blank to keep current password</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control @error('gender') is-invalid @enderror" id="gender"
                                    name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male"
                                        {{ old('gender', $teacher->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female"
                                        {{ old('gender', $teacher->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other"
                                        {{ old('gender', $teacher->gender) == 'other' ? 'selected' : '' }}>Other</option>
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
                                    id="date_of_birth" name="date_of_birth"
                                    value="{{ old('date_of_birth', $teacher->date_of_birth ? $teacher->date_of_birth->format('Y-m-d') : '') }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $teacher->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="qualification">Qualification</label>
                        <input type="text" class="form-control @error('qualification') is-invalid @enderror"
                            id="qualification" name="qualification"
                            value="{{ old('qualification', $teacher->qualification) }}">
                        @error('qualification')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Subjects</label>
                        <div id="subjects-container">
                            @php
                                $teacherSubjects = $teacher->subjects->keyBy('id');
                            @endphp
                            
                            @foreach($subjects as $subject)
                                <div class="subject-row mb-3 p-3 border rounded">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input subject-checkbox" 
                                               name="subjects[{{ $subject->id }}][id]" 
                                               value="{{ $subject->id }}"
                                               id="subject-{{ $subject->id }}"
                                               {{ $teacherSubjects->has($subject->id) ? 'checked' : '' }}>
                                        <label class="form-check-label font-weight-bold" for="subject-{{ $subject->id }}">
                                            {{ $subject->name }} ({{ $subject->code }})
                                        </label>
                                    </div>
                                    <div class="form-group mt-2 ml-4">
                                        <label for="section-{{ $subject->id }}">Section</label>
                                        <select class="form-control section-select" 
                                                name="subjects[{{ $subject->id }}][section_id]"
                                                id="section-{{ $subject->id }}"
                                                {{ $teacherSubjects->has($subject->id) ? '' : 'disabled' }}>
                                            <option value="">Select Section</option>
                                            @foreach($sections as $section)
                                                <option value="{{ $section->id }}"
                                                    {{ ($teacherSubjects->has($subject->id) && 
                                                        $teacherSubjects[$subject->id]->pivot->section_id == $section->id) ? 'selected' : '' }}>
                                                    {{ $section->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('subjects')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        @if ($teacher->profile_picture)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $teacher->profile_picture) }}"
                                    alt="{{ $teacher->name }}" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                        @endif
                        <div class="custom-file">
                            <input type="file"
                                class="custom-file-input @error('profile_picture') is-invalid @enderror"
                                id="profile_picture" name="profile_picture">
                            <label class="custom-file-label" for="profile_picture">
                                {{ $teacher->profile_picture ? 'Change file' : 'Choose file' }}
                            </label>
                            @error('profile_picture')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="form-text text-muted">Max file size: 2MB. Allowed formats: jpg, jpeg, png,
                            gif</small>
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                            {{ old('is_active', $teacher->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Teacher
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .subject-row {
            transition: all 0.3s ease;
        }
        .subject-row:hover {
            background-color: #f8f9fa;
        }
        .section-select:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Show file name when file is selected
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName || 'Choose file');
            });

            // Enable/disable section select based on subject checkbox
            $('.subject-checkbox').change(function() {
                const sectionSelect = $(this).closest('.subject-row').find('.section-select');
                if ($(this).is(':checked')) {
                    sectionSelect.prop('disabled', false);
                    // Make section required when subject is checked
                    sectionSelect.prop('required', true);
                } else {
                    sectionSelect.prop('disabled', true);
                    sectionSelect.prop('required', false);
                    sectionSelect.val('');
                }
            });

            // Initialize any existing checked checkboxes
            $('.subject-checkbox:checked').trigger('change');

            // Validate form before submission
            $('form').on('submit', function(e) {
                let isValid = true;
                
                // Check each subject row
                $('.subject-row').each(function() {
                    const checkbox = $(this).find('.subject-checkbox');
                    const sectionSelect = $(this).find('.section-select');
                    
                    if (checkbox.is(':checked') && !sectionSelect.val()) {
                        isValid = false;
                        sectionSelect.addClass('is-invalid');
                        sectionSelect.after('<div class="invalid-feedback d-block">Please select a section for this subject.</div>');
                    } else {
                        sectionSelect.removeClass('is-invalid');
                        sectionSelect.nextAll('.invalid-feedback').remove();
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    // Scroll to the first error
                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 500);
                }
            });
        });
    </script>
@endpush
