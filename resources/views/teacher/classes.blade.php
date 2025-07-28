@extends('base')

@section('title', 'My Classes')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Classes</h1>
        </div>

        @if (isset($error))
            <div class="alert alert-warning">{{ $error }}</div>
        @elseif($classes->isEmpty())
            <div class="alert alert-info">You are not assigned to any classes for the current school year.</div>
        @else
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Adviser</th>
                                    <th>My Subjects in this Class</th>
                                    <th class="text-center">Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classes as $class)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $class->section->gradeLevel->name }} -
                                                {{ $class->section->name }}</div>
                                        </td>
                                        <td>
                                            @if ($class->teacher)
                                                {{ $class->teacher->user->first_name }}
                                                {{ $class->teacher->user->last_name }}
                                                @if ($class->teacher_id == $teacher->id)
                                                    <span class="badge bg-primary ms-2">You</span>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                // Filter schedules to get subjects taught by the current teacher in this specific class
                                                $subjectsInClass = $teacher->schedules
                                                    ->where('class_id', $class->id)
                                                    ->map(fn($schedule) => $schedule->subject);
                                            @endphp
                                            @forelse($subjectsInClass->unique('id') as $subject)
                                                <span class="badge bg-info text-dark">{{ $subject->name }}</span>
                                            @empty
                                                <span class="badge bg-secondary">None</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center">{{ $class->enrollments->count() }} / {{ $class->capacity }}
                                        </td>
                                        <td>
                                            <a href="{{ route('teacher.classes.view', $class) }}"
                                                class="btn btn-sm btn-outline-primary">View Class</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection
