@extends('admin.layout')

@section('title', 'Teacher: ' . $teacher->name)

@section('header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.teachers.index') }}">Teachers</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $teacher->name }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <h1>Teacher: {{ $teacher->name }}</h1>
        <div>
            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                @if($teacher->profile_picture)
                    <img src="{{ asset('storage/' . $teacher->profile_picture) }}" 
                         alt="{{ $teacher->name }}" class="img-fluid rounded-circle mb-3" style="width: 200px; height: 200px; object-fit: cover;">
                @else
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                         style="width: 200px; height: 200px;">
                        <i class="fas fa-user-tie fa-5x text-muted"></i>
                    </div>
                @endif
                
                <h3 class="mb-1">{{ $teacher->name }}</h3>
                <p class="text-muted">{{ $teacher->qualification ?? 'Teacher' }}</p>
                
                <div class="d-flex justify-content-center mb-3">
                    <span class="badge badge-{{ $teacher->is_active ? 'success' : 'secondary' }} p-2">
                        {{ $teacher->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                
                <div class="list-group list-group-flush text-left">
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-envelope mr-2"></i>Email</h6>
                        </div>
                        <p class="mb-1">{{ $teacher->email }}</p>
                    </div>
                    
                    @if($teacher->phone)
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-phone mr-2"></i>Phone</h6>
                        </div>
                        <p class="mb-1">{{ $teacher->phone }}</p>
                    </div>
                    @endif
                    
                    @if($teacher->gender)
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-venus-mars mr-2"></i>Gender</h6>
                        </div>
                        <p class="mb-1">{{ ucfirst($teacher->gender) }}</p>
                    </div>
                    @endif
                    
                    @if($teacher->date_of_birth)
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-birthday-cake mr-2"></i>Date of Birth</h6>
                        </div>
                        <p class="mb-1">{{ $teacher->date_of_birth->format('F j, Y') }} 
                            ({{ $teacher->date_of_birth->age }} years old)
                        </p>
                    </div>
                    @endif
                    
                    @if($teacher->address)
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-map-marker-alt mr-2"></i>Address</h6>
                        </div>
                        <p class="mb-1">{{ $teacher->address }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-4">
                        <div class="display-4 font-weight-bold">{{ $teacher->subjects->count() }}</div>
                        <div class="text-muted">Subjects</div>
                    </div>
                    <div class="col-6 mb-4">
                        <div class="display-4 font-weight-bold">{{ $teacher->sections->count() }}</div>
                        <div class="text-muted">Classes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="teacherTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="subjects-tab" data-toggle="tab" href="#subjects" role="tab" aria-controls="subjects" aria-selected="true">
                            Subjects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="classes-tab" data-toggle="tab" href="#classes" role="tab" aria-controls="classes" aria-selected="false">
                            Classes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="schedule-tab" data-toggle="tab" href="#schedule" role="tab" aria-controls="schedule" aria-selected="false">
                            Schedule
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents" role="tab" aria-controls="documents" aria-selected="false">
                            Documents
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="teacherTabsContent">
                    <!-- Subjects Tab -->
                    <div class="tab-pane fade show active" id="subjects" role="tabpanel" aria-labelledby="subjects-tab">
                        @if($teacher->subjects->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Subject Name</th>
                                            <th>Description</th>
                                            <th>Classes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teacher->subjects as $subject)
                                            <tr>
                                                <td>{{ $subject->code }}</td>
                                                <td>{{ $subject->name }}</td>
                                                <td>{{ Str::limit($subject->description, 50) }}</td>
                                                <td>{{ $subject->sections->where('teacher_id', $teacher->id)->count() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                No subjects assigned to this teacher.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Classes Tab -->
                    <div class="tab-pane fade" id="classes" role="tabpanel" aria-labelledby="classes-tab">
                        @if($teacher->section->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Subject</th>
                                            <th>Section</th>
                                            <th>Students</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teacher->classes as $class)
                                            <tr>
                                                <td>{{ $class->name }}</td>
                                                <td>{{ $class->subject->name }}</td>
                                                <td>{{ $class->section->name ?? 'N/A' }}</td>
                                                <td>{{ $class->students->count() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                No classes assigned to this teacher.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Schedule Tab -->
                    <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                        @if($teacher->schedules->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Time</th>
                                            <th>Class</th>
                                            <th>Subject</th>
                                            <th>Room</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teacher->schedules->sortBy('day')->groupBy('day') as $day => $schedules)
                                            @foreach($schedules->sortBy('start_time') as $index => $schedule)
                                                <tr>
                                                    @if($index === 0)
                                                        <td rowspan="{{ $schedules->count() }}" class="align-middle">
                                                            {{ $day }}
                                                        </td>
                                                    @endif
                                                    <td>{{ $schedule->start_time }} - {{ $schedule->end_time }}</td>
                                                    <td>{{ $schedule->class->name ?? 'N/A' }}</td>
                                                    <td>{{ $schedule->subject->name ?? 'N/A' }}</td>
                                                    <td>{{ $schedule->room ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                No schedule assigned to this teacher.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Documents Tab -->
                    <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                            <p class="text-muted">No documents uploaded yet.</p>
                            <button class="btn btn-primary">
                                <i class="fas fa-upload mr-2"></i>Upload Document
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @if($teacher->recentActivities->count() > 0)
                        @foreach($teacher->recentActivities as $activity)
                            <li class="list-group-item px-0">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $activity->title }}</h6>
                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-1">{{ $activity->description }}</p>
                                @if($activity->type === 'grade')
                                    <span class="badge badge-info">Grade Update</span>
                                @elseif($activity->type === 'attendance')
                                    <span class="badge badge-warning">Attendance</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($activity->type) }}</span>
                                @endif
                            </li>
                        @endforeach
                    @else
                        <li class="list-group-item text-center text-muted">
                            No recent activity found.
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this teacher? This action cannot be undone.</p>
                <p class="mb-0"><strong>Teacher:</strong> {{ $teacher->name }}</p>
                <p class="text-danger"><strong>Warning:</strong> This will permanently delete all associated data including classes, grades, and attendance records.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i> Delete Permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .nav-tabs .nav-link {
        color: #6c757d;
    }
    .nav-tabs .nav-link.active {
        font-weight: 600;
        color: #4e73df;
        border-color: #4e73df #4e73df #fff;
    }
    .nav-tabs .nav-link:hover:not(.active) {
        border-color: #e3e6f0 #e3e6f0 #dee2e6;
    }
</style>
@endpush
