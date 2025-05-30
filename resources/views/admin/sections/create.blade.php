@extends('admin.layout')

@section('title', 'Add New Section')

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Sections</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add New</li>
        </ol>
    </nav>
    <h1>Add New Section</h1>
@endsection

@section('content')
<div class="card shadow">
    <div class="card-body">
        <form action="{{ route('admin.sections.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Section Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="grade_level">Grade Level <span class="text-danger">*</span></label>
                        <select class="form-control @error('grade_level') is-invalid @enderror"
                                id="grade_level" name="grade_level" required>
                            <option value="">Select Grade Level</option>
                            @for($i = 7; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ old('grade_level') == $i ? 'selected' : '' }}>Grade {{ $i }}</option>
                            @endfor
                        </select>
                        @error('grade_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="adviser_id">Adviser</label>
                <select class="form-control @error('adviser_id') is-invalid @enderror"
                        id="adviser_id" name="adviser_id">
                    <option value="">Select Adviser</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ old('adviser_id') == $teacher->id ? 'selected' : '' }}>
                            {{ $teacher->name }}
                        </option>
                    @endforeach
                </select>
                @error('adviser_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.sections.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Section
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {

    });
</script>
@endpush
