@extends('base')

@section('title', 'Assessments for ' . $class->section->name)

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('teacher.classes.view', $class) }}">{{ $class->section->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Manage Assessments</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Assessments for Grade-{{ $class->section->grade_level_id }} Section-{{ $class->section->name }}</h4>
                <a href="{{ route('teacher.assessments.create', $class) }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Assessment
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Assessment List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="assessmentsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Assessment Name</th>
                            <th>Type</th>
                            <th>Max Score</th>
                            <th>Quarter</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assessments as $assessment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($assessment->assessment_date)->format('M d, Y') }}</td>
                                <td>{{ $assessment->subject->name }}</td>
                                <td>{{ $assessment->name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $assessment->type)) }}</td>
                                <td>{{ $assessment->max_score }}</td>
                                <td>{{ $assessment->quarter }}</td>
                                <td>
                                    <a href="{{ route('teacher.assessments.scores.edit', ['class' => $class, 'assessment' => $assessment]) }}"
                                        class="btn btn-sm btn-info" title="Enter/Edit Scores">
                                        <i class="fas fa-edit"></i> Scores
                                    </a>
                                    <form
                                        action="{{ route('teacher.assessments.destroy', ['class' => $class, 'assessment' => $assessment]) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this assessment and all its scores? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete Assessment">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No assessments found for this class yet. Click
                                    "Create New Assessment" to begin.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
