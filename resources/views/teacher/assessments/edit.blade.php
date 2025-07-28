@extends('base')

@section('title', 'Enter Scores for ' . $assessment->name)

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('teacher.classes.view', $class) }}">{{ $class->section->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.index', $class) }}">Manage
                            Assessments</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Enter Scores</li>
                </ol>
            </nav>
            <h4 class="mb-4">Enter Scores for "{{ $assessment->name }}"</h4>
            <p><strong>Subject:</strong> {{ $assessment->subject->name }} | <strong>Max Score:</strong>
                {{ $assessment->max_score }} | <strong>Date:</strong>
                {{ \Carbon\Carbon::parse($assessment->assessment_date)->format('M d, Y') }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student Scores</h6>
        </div>
        <div class="card-body">
            <form
                action="{{ route('teacher.assessments.scores.update', ['class' => $class, 'assessment' => $assessment]) }}"
                method="POST">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-bordered" id="studentScoresTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Score (out of {{ $assessment->max_score }})</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr>
                                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                    <td>
                                        {{-- Find the student's existing score --}}
                                        @php
                                            $existingScore = $scores->firstWhere('student_id', $student->id);
                                        @endphp
                                        <input type="hidden" name="scores[{{ $student->id }}][student_id]"
                                            value="{{ $student->id }}">
                                        <input type="number" name="scores[{{ $student->id }}][score]"
                                            value="{{ old('scores.' . $student->id . '.score', $existingScore->score ?? '') }}"
                                            class="form-control" step="0.01" min="0"
                                            max="{{ $assessment->max_score }}">
                                    </td>
                                    <td>
                                        <input type="text" name="scores[{{ $student->id }}][remarks]"
                                            value="{{ old('scores.' . $student->id . '.remarks', $existingScore->remarks ?? '') }}"
                                            class="form-control">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No students enrolled in this class.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-success mt-3">Save Scores</button>
                <a href="{{ route('teacher.assessments.index', $class) }}" class="btn btn-secondary mt-3">Back to
                    Assessments</a>
            </form>
        </div>
    </div>
@endsection
