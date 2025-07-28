@extends('base')

@section('title', 'Classes')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Sections</a></li>
                    <li class="breadcrumb-item active" aria-current="page">List</li>
                </ol>
            </nav>
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal"
                data-bs-target="#addClassModal">
                <i data-feather="plus" class="me-2"></i> Add Class
            </button>
        </div>

        @if ($classes->isEmpty())
            <div class="alert alert-info text-center">
                <h4 class="alert-heading">No Classes Found!</h4>
                <p>There are no classes set up for the active school year. You can start by adding a new class.</p>
                <hr>
                <p class="mb-0">Click the "Add Class" button to get started.</p>
            </div>
        @else
            <ul class="nav nav-tabs" id="gradeLevelTabs" role="tablist">
                @php
                    $gradeLevels = range(1, 6);
                @endphp
                @foreach ($gradeLevels as $gradeLevel)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="grade-{{ $gradeLevel }}-tab"
                            data-bs-toggle="tab" data-bs-target="#grade-{{ $gradeLevel }}-pane" type="button"
                            role="tab" aria-controls="grade-{{ $gradeLevel }}-pane"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">Grade {{ $gradeLevel }}</button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="gradeLevelTabsContent">
                @foreach ($gradeLevels as $gradeLevel)
                    @php
                        // Filter the main $classes collection for the current grade level in the loop
                        $classesInGrade = $classes->filter(function ($class) use ($gradeLevel) {
                            return $class->section->gradeLevel->level == $gradeLevel;
                        });
                    @endphp
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="grade-{{ $gradeLevel }}-pane"
                        role="tabpanel" aria-labelledby="grade-{{ $gradeLevel }}-tab" tabindex="0">
                        <div class="card card-body border-top-0">
                            @if ($classesInGrade->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover" width="100%">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Section Name</th>
                                                <th>Adviser</th>
                                                <th class="text-center">Enrollment</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($classesInGrade as $class)
                                                <tr>
                                                    <td>{{ $class->section->name }}</td>
                                                    <td>{{ $class->teacher->user->first_name ?? 'N/A' }}
                                                        {{ $class->teacher->user->last_name ?? '' }}</td>
                                                    <td class="text-center">{{ $class->enrollments->count() }} /
                                                        {{ $class->capacity ?? 'N/A' }}</td>
                                                    <td class="text-center">
                                                        <a href="{{ route('admin.sections.manage', $class->section->id) }}"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i data-feather="settings" class="feather-sm me-1"></i> Manage
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-center text-muted mb-0">No classes found for Grade {{ $gradeLevel }}.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addClassModalLabel">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.sections.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Section Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="grade_level_id" class="form-label">Grade Level <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="grade_level_id" name="grade_level_id" required>
                                <option value="" selected disabled>Select a grade level...</option>
                                @for ($i = 1; $i <= 6; $i++)
                                    <option value="{{ $i }}">Grade {{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Adviser</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">Select an adviser...</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->user->first_name }}
                                        {{ $teacher->user->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                            rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="capacity" name="capacity"
                                value="{{ old('capacity', 40) }}" required min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
