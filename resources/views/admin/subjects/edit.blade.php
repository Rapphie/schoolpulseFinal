@extends('admin.layout')

@section('title', 'Edit Subject')

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">Subjects</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit: {{ $subject->name }}</li>
        </ol>
    </nav>
    <h1>Edit Subject</h1>
@endsection

@section('content')
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.subjects.update', $subject->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Subject Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="name" name="name" value="{{ old('name', $subject->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description" rows="3">{{ old('description', $subject->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.subjects.index') }}" class="btn btn-secondary">
                    <i data-feather="arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i data-feather="save"></i> Update Subject
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
