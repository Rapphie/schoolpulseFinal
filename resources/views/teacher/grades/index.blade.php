@extends('base')

@section('title', 'Grades - Report Card')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.list') }}">Grades</a></li>
            <li class="breadcrumb-item active" aria-current="page">Report Card</li>
        </ol>
    </nav>
    <div class="card shadow mb-4">
        <div class="card-body">
            @if (isset($error))
                <div class="alert alert-danger">{{ $error }}</div>
            @elseif($classes->isEmpty())
                <div class="alert alert-warning">You don't have an access. No advisory classes for the current school year.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered" id="classesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Section</th>
                                <th>Grade Level</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $class)
                                <tr>
                                    <td>{{ $class->section->name }}</td>
                                    <td>{{ $class->section->gradeLevel->name }}</td>
                                    <td>
                                        <a href="{{ route('teacher.grades.show', ['class' => $class->id]) }}"
                                            class="btn btn-primary btn-sm">
                                            <div class="d-flex justify-content-center gap-2"><i data-feather="eye"
                                                    class="feather-sm"></i> View Grades</div>
                                        </a>
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
            $('#classesTable').DataTable();
        });
    </script>
@endpush
