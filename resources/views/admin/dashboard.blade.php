@extends('admin.layout')
@section('title', 'Admin - Dashboard')
@section('content')
    <main class="p-4">
        <!-- Header with Welcome Message -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-1 fw-bold text-dark">Welcome back, {{ Auth::user()->last_name }}</h2>
                <p class="text-muted mb-0">Here's what's happening with your school today</p>
            </div>
            <div>
                <button class="btn btn-primary">
                    <i data-feather="plus" class="feather-sm me-1"></i> Add New
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="bg-soft-primary rounded p-2">
                                <i data-feather="users" class="text-primary"></i>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success">+12%</span>
                        </div>
                        <h3 class="mb-1">355</h3>
                        <p class="text-muted mb-0">Enrolled Students</p>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 75%" aria-valuenow="75"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="bg-soft-success rounded p-2">
                                <i data-feather="user-check" class="text-success"></i>
                            </div>
                            <span class="badge bg-danger bg-opacity-10 text-danger">-2%</span>
                        </div>
                        <h3 class="mb-1">42</h3>
                        <p class="text-muted mb-0">Teaching Staff</p>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 65%" aria-valuenow="65"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="bg-soft-warning rounded p-2">
                                <i data-feather="book-open" class="text-warning"></i>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success">+5%</span>
                        </div>
                        <h3 class="mb-1">18</h3>
                        <p class="text-muted mb-0">Active Classes</p>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 85%" aria-valuenow="85"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="bg-soft-info rounded p-2">
                                <i data-feather="check-circle" class="text-info"></i>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success">+8%</span>
                        </div>
                        <h3 class="mb-1">94%</h3>
                        <p class="text-muted mb-0">Attendance Today</p>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 94%" aria-valuenow="94"
                                aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">Student Enrollment Overview</h5>
                    </div>
                    <div class="card-body">
                        <div id="enrollmentChart" style="height: 300px;">
                            <!-- Chart will be rendered here by JavaScript -->
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">Class Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div id="classDistributionChart" style="height: 300px;">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Upcoming Events -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold">Recent Activity</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm bg-soft-primary text-primary rounded-circle p-2 me-3">
                                            <i data-feather="user-plus" class="feather-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1 fw-semibold">New Student Enrolled</h6>
                                            <small class="text-muted">2m ago</small>
                                        </div>
                                        <p class="mb-0 text-muted">John Doe has been enrolled in Grade 5-A</p>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm bg-soft-success text-success rounded-circle p-2 me-3">
                                            <i data-feather="check-circle" class="feather-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1 fw-semibold">Attendance Marked</h6>
                                            <small class="text-muted">1h ago</small>
                                        </div>
                                        <p class="mb-0 text-muted">Attendance marked for Class 10-A (95% present)
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-sm bg-soft-warning text-warning rounded-circle p-2 me-3">
                                            <i data-feather="alert-triangle" class="feather-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1 fw-semibold">Low Attendance Alert</h6>
                                            <small class="text-muted">3h ago</small>
                                        </div>
                                        <p class="mb-0 text-muted">Attendance below 80% for Class 8-B</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Upcoming Events</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 text-center me-3">
                                        <div class="bg-soft-primary text-primary p-2 rounded-3" style="width: 50px;">
                                            <div class="fw-bold">25</div>
                                            <small class="d-block">MAY</small>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold">Annual Sports Day</h6>
                                        <p class="mb-0 text-muted small">9:00 AM - 2:00 PM</p>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 text-center me-3">
                                        <div class="bg-soft-success text-success p-2 rounded-3" style="width: 50px;">
                                            <div class="fw-bold">30</div>
                                            <small class="d-block">MAY</small>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold">Parent-Teacher Meeting</h6>
                                        <p class="mb-0 text-muted small">10:00 AM - 4:00 PM</p>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 text-center me-3">
                                        <div class="bg-soft-warning text-warning p-2 rounded-3" style="width: 50px;">
                                            <div class="fw-bold">05</div>
                                            <small class="d-block">JUN</small>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold">First Term Exams Begin</h6>
                                        <p class="mb-0 text-muted small">All Day</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
