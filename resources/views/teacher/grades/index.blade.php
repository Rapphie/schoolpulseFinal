@extends('base')

@section('title', 'My Classes for Grades')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">My Classes</h6>
        </div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#classesTable').DataTable();
        });
    </script>
@endpush
