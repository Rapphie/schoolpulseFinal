@extends('base')

@section('title', 'Manage Section')

@section('content')
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Sections</a></li>
                <li class="breadcrumb-item active" aria-current="page">Manage Section</li>
            </ol>
        </nav>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Section Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Section Name:</strong> {{ $section->name }}</p>
                        <p><strong>Grade Level:</strong> {{ $section->gradeLevel->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Class Adviser:</strong> {{ $section->teacher->user->full_name ?? 'N/A' }}</p>
                        <p><strong>Capacity:</strong> {{ $section->students->count() }} / {{ $section->capacity }} Students
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assigned Subjects</h5>
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#addSubjectModal">
                    <i data-feather="plus" class="me-2"></i> Add Subject
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Teacher Assigned</th>
                                <th>Schedule</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($section->schedules as $schedule)
                                <tr>
                                    <td>{{ $schedule->subject->name }}</td>
                                    <td>{{ $schedule->teacher->user->full_name ?? 'No teacher assigned.' }}</td>
                                    <td>{{ implode(', ', $schedule->day_of_week) }} {{ $schedule->start_time }} -
                                        {{ $schedule->end_time }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('admin.schedules.destroy', $schedule->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to remove this subject from the section?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No subjects assigned yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add Subject to Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.schedules.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="section_id" value="{{ $section->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="" selected>Select Subject</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="" selected>Select Teacher</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->user->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Days of the Week <span class="text-danger">*</span></label>
                            <div>
                                @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="day_of_week[]"
                                            id="day_{{ $day }}" value="{{ $day }}">
                                        <label class="form-check-label"
                                            for="day_{{ $day }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_time" class="form-label">Start Time <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="room" class="form-label">Room</label>
                            <input type="text" class="form-control" id="room" name="room">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#subject_id').select2({
                dropdownParent: $('#addSubjectModal'),
                placeholder: 'Select Subject',
                allowClear: true,
                width: '100%'
            });
            $('#teacher_id').select2({
                dropdownParent: $('#addSubjectModal'),
                placeholder: 'Select Teacher',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endpush
