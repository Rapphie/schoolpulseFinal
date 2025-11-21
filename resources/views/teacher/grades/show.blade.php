@extends('base')

@section('title', 'Class Grades')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
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
                                        <div class="d-flex flex-column gap-2">
                                            <a href="{{ route('teacher.report-card.show', ['studentId' => $student->id]) }}"
                                                class="btn btn-primary btn-sm" target="_blank">
                                                <div class="d-flex justify-content-center gap-2"><i data-feather="download"
                                                        class="feather-sm"></i> Download Report Card</div>
                                            </a>
                                            <a href="{{ route('teacher.assessments.index', ['class' => $class->id, 'highlight_student' => $student->id]) }}"
                                                class="btn btn-warning btn-sm">
                                                <div class="d-flex justify-content-center gap-2 text-white"><i
                                                        data-feather="edit-2" class="feather-sm"></i> Edit Grades</div>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#studentsTable').DataTable();
        });
    </script>
@endpush
