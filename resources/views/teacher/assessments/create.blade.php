@extends('base')

@section('title', 'Create Assessment for ' . $class->section->name)

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('teacher.classes.view', $class) }}">Section-{{ $class->section->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.index', $class) }}">Manage
                            Assessments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create Assessment</li>
                </ol>
            </nav>
            <h4 class="mb-4">Create New Assessment for {{ $class->section->name }}</h4>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Assessment Details</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('teacher.assessments.store', $class) }}" method="POST">
                @csrf

                {{-- Hidden fields for class, teacher, and school year --}}
                <input type="hidden" name="class_id" value="{{ $class->id }}">
                <input type="hidden" name="teacher_id" value="{{ auth()->user()->teacher->id }}">
                <input type="hidden" name="school_year_id" value="{{ $class->school_year_id }}">

                <div class="form-group">
                    <label for="subject_id">Subject</label>
                    <select class="form-control" id="subject_id" name="subject_id" required>
                        <option value="">Select Subject</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="name">Assessment Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}"
                        required>
                </div>

                <div class="form-group">
                    <label for="type">Assessment Type</label>
                    <select class="form-control" id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="quiz" {{ old('type') == 'quiz' ? 'selected' : '' }}>Quiz</option>
                        <option value="exam" {{ old('type') == 'exam' ? 'selected' : '' }}>Exam</option>
                        <option value="assignment" {{ old('type') == 'assignment' ? 'selected' : '' }}>Assignment</option>
                        <option value="project" {{ old('type') == 'project' ? 'selected' : '' }}>Project</option>
                        <option value="performance_task" {{ old('type') == 'performance_task' ? 'selected' : '' }}>
                            Performance Task</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="max_score">Maximum Score</label>
                    <input type="number" class="form-control" id="max_score" name="max_score"
                        value="{{ old('max_score') }}" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="quarter">Quarter</label>
                    <select class="form-control" id="quarter" name="quarter" required>
                        <option value="">Select Quarter</option>
                        <option value="1" {{ old('quarter') == 1 ? 'selected' : '' }}>1</option>
                        <option value="2" {{ old('quarter') == 2 ? 'selected' : '' }}>2</option>
                        <option value="3" {{ old('quarter') == 3 ? 'selected' : '' }}>3</option>
                        <option value="4" {{ old('quarter') == 4 ? 'selected' : '' }}>4</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assessment_date">Date</label>
                    <input type="date" class="form-control" id="assessment_date" name="assessment_date"
                        value="{{ old('assessment_date') }}" required>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Create Assessment</button>
                <a href="{{ route('teacher.assessments.index', $class) }}" class="btn btn-secondary mt-3">Cancel</a>
            </form>
        </div>
    </div>
@endsection
