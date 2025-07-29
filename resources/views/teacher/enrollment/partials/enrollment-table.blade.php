<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">
            {{ $class->section->gradeLevel->name }} - {{ $class->section->name }}
        </h6>
        <a href="{{ route('teacher.enrollment.export', ['class_id' => $class->id]) }}" class="btn btn-sm btn-primary">
            Export
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Student Name</th>
                        <th>Enrollment Date</th>
                    </tr> 
                </thead>
                <tbody>
                    @foreach ($enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->student->lrn ?? 'N/A' }}</td>
                            <td>{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</td>
                            <td>{{ $enrollment->created_at->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
