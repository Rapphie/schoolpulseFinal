@extends('base')

@section('title', 'Edit Section: ' . $section->name)

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Sections</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit: {{ $section->name }}</li>
        </ol>
    </nav>
    <h1>Edit Section: {{ $section->name }}</h1>
@endsection

@section('content')
    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('admin.sections.update', $section) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name', $section->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="grade_level_id">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-control @error('grade_level_id') is-invalid @enderror" id="grade_level_id"
                                name="grade_level_id" required>
                                <option value="">Select Grade Level</option>
                                @foreach ($gradeLevels as $gradeLevel)
                                    <option value="{{ $gradeLevel->id }}"
                                        {{ old('grade_level_id', $section->grade_level_id) == $gradeLevel->id ? 'selected' : '' }}>
                                        {{ $gradeLevel->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('grade_level_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="teacher_id">Adviser</label>
                    <select class="form-control @error('teacher_id') is-invalid @enderror" id="teacher_id"
                        name="teacher_id">
                        <option value="">Select Adviser</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}"
                                {{ old('teacher_id', $section->teacher_id) == $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('teacher_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                        rows="3">{{ old('description', $section->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('admin.sections.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Section
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            <div class="mt-4">
                <h5>Danger Zone</h5>
                <p class="text-muted">Be careful with these actions as they cannot be undone.</p>

                <form action="{{ route('admin.sections.destroy', $section) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Are you sure you want to delete this section? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Section
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {});
    </script>
@endpush
