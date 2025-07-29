@extends('base')

@section('title', 'My Classes')


@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
@endpush

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
                        <table class="table table-hover" id="classTable" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Grade</th>
                                    <th>Section Name</th>
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
                                            {{ $class->section->gradeLevel->name }}

                                        </td>
                                        <td>

                                            {{ $class->section->name }}
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
                                                <span class="badge bg-info">{{ $subject->name }}</span>
                                            @empty
                                                <span class="badge bg-secondary">None</span>
                                            @endforelse
                                        </td>
                                        <td class="text-center">{{ $class->enrollments->count() }} / {{ $class->capacity }}
                                        </td>
                                        <td>
                                            <a href="{{ route('teacher.assessments.index', $class) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center"><i
                                                    data-feather="edit-3" class="me-1"></i><span>Assessments</span></a>
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

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let table = $('#classTable').DataTable({
                responsive: true,
            });
        });
    </script>
@endpush
