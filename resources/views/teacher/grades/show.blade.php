@extends('base')

@section('title', 'Class Grades')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.list') }}">Grades</a></li>
            <li class="breadcrumb-item">Report Card</li>
            <li class="breadcrumb-item active" aria-current="page">Class Grades</li>
        </ol>
    </nav>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Students for {{ $class->section->gradeLevel->name }} {{ $class->section->name }}
            </h6>
        </div>
        <div class="card-body">
            @if ($students->isEmpty())
                <div class="alert alert-info">No students enrolled in this class.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered" id="studentsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>LRN</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr>
                                    <td>{{ $student->lrn }}</td>
                                    <td>{{ $student->full_name }}</td>
                                    <td>{{ $student->gender }}</td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="{{ route('teacher.grades.student', ['class' => $class->id, 'student' => $student->id]) }}"
                                                class="btn btn-info btn-sm" title="View Grades">
                                                <i data-feather="eye" class="feather-sm text-white"></i>
                                            </a>
                                            <a href="{{ route('teacher.assessments.index', ['class' => $class->id, 'highlight_student' => $student->id]) }}"
                                                class="btn btn-warning btn-sm" title="Edit Grades">
                                                <i data-feather="edit-2" class="feather-sm text-white"></i>
                                            </a>
                                            <a href="{{ route('teacher.grades.student.download', ['class' => $class->id, 'student' => $student->id]) }}"
                                                class="btn btn-primary btn-sm" target="_blank" title="Download Report Card">
                                                <i data-feather="download" class="feather-sm"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#studentsTable').DataTable();
        });
    </script>
@endpush
