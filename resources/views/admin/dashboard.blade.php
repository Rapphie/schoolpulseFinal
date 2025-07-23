@extends('base')
@section('title', 'Admin - Dashboard')
@section('content')
    <!-- Header with Welcome Message -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1 fw-bold text-dark">Welcome back, {{ Auth::user()->first_name }}</h2>
            <p class="text-muted mb-0">Here's what's happening with your school today</p>
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
                    <p class="text-muted mb-0">Active Sections</p>
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
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Student Enrollment Overview</h5>
                    <div class="d-flex align-items-center">
                        <label class="me-2 mb-0 small text-muted">School Year:</label>
                        <select class="form-select form-select-sm" id="enrollmentYearSelect" style="width: 150px;">
                            <option value="current" selected>2025-2026 (Current)</option>
                            <option value="2024-2025">2024-2025</option>
                            <option value="2023-2024">2023-2024</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <h6 class="mb-0 me-3">Enrolled Students</h6>
                        <div class="badge bg-soft-primary text-primary">Total: <span id="totalEnrollment">355</span>
                            students</div>
                    </div>
                    <div id="enrollmentChart" style="height: 275px;">
                        <!-- Chart will be rendered here by JavaScript -->
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div class="small text-muted">Showing enrollment data for <span
                                id="currentSelectedYear">2025-2026</span> school year</div>
                        <div class="d-flex gap-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">New Students</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-success rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Continuing</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-warning rounded-circle me-1" style="width: 10px; height: 10px;"></div>
                                <span class="small">Transfers</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">Grade-Level Distribution</h5>
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
        <!-- School Year Management Section -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">School Year Management</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAcademicYearModal">
                        <i data-feather="plus" class="feather-sm me-1"></i> Add School Year
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 bg-soft-primary">
                                <div class="card-body">
                                    <h6 class="text-primary fw-bold">Current School Year</h6>
                                    <h3 id="currentAcademicYear">2025-2026</h3>
                                    <p class="mb-2">Status: <span class="badge bg-success">Active</span></p>
                                    <p class="mb-0">Duration: <span id="currentAcademicYearDuration">Jun 2025 - Mar
                                            2026</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-3">School Year Timeline</h6>
                            <div class="position-relative" id="academicYearTimeline" style="height: 100px;">
                                <!-- Timeline will be rendered here by JavaScript -->
                                <div class="text-center py-2 d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Overview by School Year -->
                    <div class="row mb-4 mt-4">
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">Enrollment Statistics by School Year</h6>
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="p-4 bg-light rounded-3">
                                                <h5 class="fw-bold text-primary mb-4"><i data-feather="bar-chart-2"
                                                        class="feather-sm me-2"></i>Key Enrollment Figures</h5>
                                                <div class="row g-4">
                                                    <div class="col-6 col-lg-3">
                                                        <div class="card border-0 bg-white shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="display-6 text-dark fw-bold mb-2"
                                                                    id="totalStudents">355</div>
                                                                <div class="text-muted">Total Students</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-lg-3">
                                                        <div class="card border-0 bg-white shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="display-6 text-success fw-bold mb-2"
                                                                    id="newEnrollees">+48</div>
                                                                <div class="text-muted">New Enrollees</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-lg-3">
                                                        <div class="card border-0 bg-white shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="display-6 text-primary fw-bold mb-2"
                                                                    id="transferStudents">12</div>
                                                                <div class="text-muted">Transfers</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 col-lg-3">
                                                        <div class="card border-0 bg-white shadow-sm h-100">
                                                            <div class="card-body text-center">
                                                                <div class="display-6 text-success fw-bold mb-2"
                                                                    id="retentionRate">95%</div>
                                                                <div class="text-muted">Retention Rate</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-4 bg-white rounded-3 h-100">
                                                <h5 class="fw-bold text-primary mb-3"><i data-feather="pie-chart"
                                                        class="feather-sm me-2"></i>Grade Level Distribution</h5>
                                                <div id="enrollmentStats">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Graduates:</span>
                                                        <span class="fw-bold text-info" id="graduates">42</span>
                                                    </div>
                                                    <hr>
                                                    <div id="gradeLevelDistribution" class="mt-3">
                                                        <!-- Grade level distribution will be populated by JavaScript -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>School Year</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th width="200">Action</th>
                                </tr>
                            </thead>
                            <tbody id="academicYearTableBody">
                                <!-- Table rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

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

    <!-- Add School Year Modal -->
    <div class="modal fade" id="addAcademicYearModal" tabindex="-1" aria-labelledby="addAcademicYearModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAcademicYearModalLabel">Add School Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="academicYearForm">
                        <div class="mb-3">
                            <label for="yearName" class="form-label">School Year Name</label>
                            <input type="text" class="form-control" id="yearName" placeholder="e.g. 2025-2026"
                                required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" rows="3"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="isCurrentYear">
                            <label class="form-check-label" for="isCurrentYear">
                                Set as current school year
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveAcademicYear">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit School Year Modal -->
    <div class="modal fade" id="editAcademicYearModal" tabindex="-1" aria-labelledby="editAcademicYearModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAcademicYearModalLabel">Edit School Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editAcademicYearForm">
                        <input type="hidden" id="editYearId">
                        <div class="mb-3">
                            <label for="editYearName" class="form-label">School Year Name</label>
                            <input type="text" class="form-control" id="editYearName" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="editStartDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="editEndDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="editDescription" rows="3"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="editIsCurrentYear">
                            <label class="form-check-label" for="editIsCurrentYear">
                                Set as current school year
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateAcademicYear">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAcademicYearModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this school year? This action cannot be undone.</p>
                    <input type="hidden" id="deleteYearId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteYear">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
                    // Sample school year data
                    const academicYears = [{
                            id: 1,
                            name: '2025-2026',
                            startDate: '2025-06-15',
                            endDate: '2026-03-31',
                            description: 'Regular school year',
                            isCurrent: true
                        },
                        {
                            id: 2,
                            name: '2024-2025',
                            startDate: '2024-06-10',
                            endDate: '2025-03-25',
                            description: 'Previous school year',
                            isCurrent: false
                        },
                        {
                            id: 3,
                            name: '2023-2024',
                            startDate: '2023-06-12',
                            endDate: '2024-03-28',
                            description: 'Archive',
                            isCurrent: false
                        }
                    ];

                    // Initialize the section
                    initAcademicYearManagement();

                    // Initialize enrollment chart functionality
                    function initEnrollmentChart() {
                        // Sample enrollment data by school year
                        const enrollmentData = {
                            'current': {
                                'Grade 1': 45,
                                'Grade 2': 42,
                                'Grade 3': 48,
                                'Grade 4': 52,
                                'Grade 5': 46,
                                'Grade 6': 43,
                                'Grade 7': 38,
                                'Grade 8': 41,
                                'Grade 9': 50,
                                'Grade 10': 0 // Not yet enrolled (current year)
                            },
                            '2024-2025': {
                                'Grade 1': 40,
                                'Grade 2': 38,
                                'Grade 3': 45,
                                'Grade 4': 48,
                                'Grade 5': 42,
                                'Grade 6': 39,
                                'Grade 7': 35,
                                'Grade 8': 37,
                                'Grade 9': 46,
                                'Grade 10': 47
                            },
                            '2023-2024': {
                                'Grade 1': 38,
                                'Grade 2': 36,
                                'Grade 3': 42,
                                'Grade 4': 45,
                                'Grade 5': 40,
                                'Grade 6': 37,
                                'Grade 7': 33,
                                'Grade 8': 35,
                                'Grade 9': 43,
                                'Grade 10': 45
                            }
                        };

                        // Sample enrollment statistics by school year
                        const enrollmentStats = {
                            'current': {
                                totalStudents: 355,
                                newEnrollees: 48,
                                transferStudents: 12,
                                graduates: 42,
                                retentionRate: 95,
                                retentionTrend: [88, 90, 92, 95, 95, 93, 96, 95]
                            },
                            '2024-2025': {
                                totalStudents: 332,
                                newEnrollees: 43,
                                transferStudents: 10,
                                graduates: 40,
                                retentionRate: 93,
                                retentionTrend: [86, 89, 90, 91, 93, 92, 93]
                            },
                            '2023-2024': {
                                totalStudents: 310,
                                newEnrollees: 38,
                                transferStudents: 8,
                                graduates: 38,
                                retentionRate: 92,
                                retentionTrend: [85, 86, 89, 91, 90, 92]
                            }
                        };

                        // Connect enrollment dropdown with academic years
                        const enrollmentYearSelect = document.getElementById('enrollmentYearSelect');
                        enrollmentYearSelect.innerHTML = '';

                        // Connect stats year selector with academic years
                        const statsYearSelect = document.getElementById('statsYearSelect');
                        statsYearSelect.innerHTML = '';

                        // Add academic years from the academic years array to dropdowns
                        academicYears.forEach(year => {
                            // For enrollment chart dropdown
                            const option = document.createElement('option');
                            option.value = year.isCurrent ? 'current' : year.name;
                            option.textContent = year.name + (year.isCurrent ? ' (Current)' : '');
                            option.selected = year.isCurrent;
                            enrollmentYearSelect.appendChild(option);

                            // For stats dropdown
                            const statsOption = option.cloneNode(true);
                            statsYearSelect.appendChild(statsOption);
                        });

                        // Initial chart render with current year
                        updateEnrollmentChart('current');
                        updateEnrollmentStats('current');

                        // Event listener for enrollment dropdown change
                        enrollmentYearSelect.addEventListener('change', function() {
                            updateEnrollmentChart(this.value);
                        });

                        // Event listener for stats dropdown change
                        statsYearSelect.addEventListener('change', function() {
                            updateEnrollmentStats(this.value);
                        });

                        // Function to update enrollment statistics based on selected year
                        function updateEnrollmentStats(yearKey) {
                            const yearData = enrollmentStats[yearKey] || enrollmentStats['current'];

                            // Update stat values
                            document.getElementById('totalStudents').textContent = yearData.totalStudents;
                            document.getElementById('newEnrollees').textContent = '+' + yearData.newEnrollees;
                            document.getElementById('transferStudents').textContent = yearData.transferStudents;
                            document.getElementById('graduates').textContent = yearData.graduates;
                            document.getElementById('retentionRate').textContent = yearData.retentionRate + '%';

                            // Update retention trend sparkline chart
                            const retentionTrendChart = document.getElementById('retentionTrendChart');
                            retentionTrendChart.innerHTML = '';

                            const canvas = document.createElement('canvas');
                            canvas.id = 'retentionSparkline';
                            canvas.height = 50;
                            retentionTrendChart.appendChild(canvas);

                            new Chart(canvas, {
                                type: 'line',
                                data: {
                                    labels: Array(yearData.retentionTrend.length).fill(''),
                                    datasets: [{
                                        data: yearData.retentionTrend,
                                        borderColor: '#20c997',
                                        borderWidth: 2,
                                        pointRadius: 0,
                                        pointHoverRadius: 3,
                                        fill: true,
                                        backgroundColor: 'rgba(32, 201, 151, 0.1)',
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            enabled: true,
                                            callbacks: {
                                                label: function(context) {
                                                    return `${context.raw}% retention`;
                                                },
                                                title: function() {
                                                    return '';
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            display: false
                                        },
                                        y: {
                                            display: false,
                                            min: Math.min(...yearData.retentionTrend) - 5,
                                            max: 100
                                        }
                                    }
                                }
                            });
                        }

                        // Function to update chart based on selected year
                        function updateEnrollmentChart(yearKey) {
                            const chartContainer = document.getElementById('enrollmentChart');
                            chartContainer.innerHTML = '';

                            // Get enrollment data for selected year
                            const yearData = enrollmentData[yearKey] || enrollmentData['current'];

                            // Calculate total enrollment
                            const totalStudents = Object.values(yearData).reduce((sum, count) => sum + count, 0);
                            document.getElementById('totalEnrollment').textContent = totalStudents;

                            // Canvas for Chart.js
                            const canvas = document.createElement('canvas');
                            canvas.id = 'enrollmentBarChart';
                            chartContainer.appendChild(canvas);

                            // Prepare data for Chart.js
                            const labels = Object.keys(yearData);
                            const data = Object.values(yearData);

                            // Create gradient for bars
                            const ctx = canvas.getContext('2d');
                            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            gradient.addColorStop(0, 'rgba(13, 110, 253, 0.9)');
                            gradient.addColorStop(1, 'rgba(13, 110, 253, 0.3)');

                            // Create chart
                            new Chart(canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: 'Number of Students',
                                            data: data,
                                            backgroundColor: gradient,
                                            borderColor: '#0d6efd',
                                            borderWidth: 1,
                                            borderRadius: 4,
                                            barThickness: 20
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                display: false
                                            },
                                            tooltip: {
                                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                                padding: 10,
                                                titleFont: {
                                                    size: 14
                                                },
                                                bodyFont: {
                                                    size: 14
                                                },
                                                callbacks: {
                                                    title: function(tooltipItems) {
                                                        return tooltipItems[0].label;
                                                    },
                                                    label: function(context) {
                                                        return 'Students: ' + context.raw;
                                                    },
                                                    afterLabel: function(context) {
                                                        const percentage = Math.round((context.raw /
                                                            totalStudents) * 100);
                                                        return `${percentage}% of total enrollment`;
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                grid: {
                                                    drawBorder: false,
                                                    color: 'rgba(0, 0, 0, 0.05)'
                                                },
                                                ticks: {
                                                    font: {
                                                        size: 12
                                                    }
                                                }
                                            },
                                            x: {
                                                grid: {
                                                    display: false
                                                },
                                                ticks: {
                                                    font: {
                                                        size: 12
                                                    }
                                                }
                                            }
                                        }
                                    });
                            }
                        }

                        // Initialize enrollment chart
                        initEnrollmentChart();

                        // Initialize class distribution chart
                        function initClassDistributionChart() {
                            const chartContainer = document.getElementById('classDistributionChart');
                            chartContainer.innerHTML = '';

                            // Sample class distribution data
                            const classDistribution = {
                                'Science': 6,
                                'Mathematics': 7,
                                'English': 5,
                                'History': 4,
                                'Arts': 3,
                                'Physical Education': 4,
                                'Computer Science': 5
                            };

                            // Canvas for Chart.js
                            const canvas = document.createElement('canvas');
                            canvas.id = 'classDistributionPieChart';
                            chartContainer.appendChild(canvas);

                            // Chart colors
                            const colors = [
                                '#0d6efd', '#20c997', '#fd7e14', '#6610f2',
                                '#0dcaf0', '#ffc107', '#d63384'
                            ];

                            // Create chart
                            new Chart(canvas, {
                                    type: 'doughnut',
                                    data: {
                                        labels: Object.keys(classDistribution),
                                        datasets: [{
                                            data: Object.values(classDistribution),
                                            backgroundColor: colors,
                                            borderColor: '#ffffff',
                                            borderWidth: 2
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '60%',
                                        plugins: {
                                            legend: {
                                                position: 'bottom',
                                                labels: {
                                                    padding: 20,
                                                    usePointStyle: true,
                                                    pointStyle: 'circle'
                                                }
                                            },
                                            tooltip: {
                                                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                                                padding: 10,
                                                titleFont: {
                                                    size: 14
                                                },
                                                bodyFont: {
                                                    size: 14
                                                },
                                                callbacks: {
                                                    label: function(context) {
                                                        const label = context.label || '';
                                                        const value = context.raw || 0;
                                                        const total = context.dataset.data.reduce((a, b) => a + b,
                                                            0);
                                                        const percentage = Math.round((value / total) * 100);
                                                        return `${label}: ${value} classes (${percentage}%)`;
                                                    }
                                                }
                                            }
                                        });
                                }

                                // Initialize class distribution chart
                                initClassDistributionChart();

                                // Add event listener for the "View Detailed Analytics" button
                                document.getElementById('viewDetailedStats').addEventListener('click', function() {
                                    const selectedYear = document.getElementById('statsYearSelect').value;
                                    const yearLabel = selectedYear === 'current' ?
                                        academicYears.find(y => y.isCurrent).name + ' (Current)' :
                                        selectedYear;

                                    // Create a modal for detailed statistics
                                    const modalHTML = `
                                <div class="modal fade" id="detailedStatsModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Detailed Enrollment Analytics: ${yearLabel}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-4">
                                                    <div class="col-12">
                                                        <div class="card border-0 shadow-sm">
                                                            <div class="card-body">
                                                                <h6 class="fw-bold mb-3">Enrollment Trend</h6>
                                                                <div id="detailedEnrollmentTrend" style="height: 300px;"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border-0 shadow-sm">
                                                            <div class="card-body">
                                                                <h6 class="fw-bold mb-3">Student Demographics</h6>
                                                                <div id="demographicsChart" style="height: 250px;"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border-0 shadow-sm">
                                                            <div class="card-body">
                                                                <h6 class="fw-bold mb-3">Enrollment by Month</h6>
                                                                <div id="monthlyEnrollmentChart" style="height: 250px;"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-primary">Export Report</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;

                                    // Append modal to body
                                    const modalContainer = document.createElement('div');
                                    modalContainer.innerHTML = modalHTML;
                                    document.body.appendChild(modalContainer.firstElementChild);

                                    // Initialize the modal
                                    const modal = new bootstrap.Modal(document.getElementById(
                                        'detailedStatsModal'));
                                    modal.show();

                                    // When modal is shown, initialize charts
                                    document.getElementById('detailedStatsModal').addEventListener('shown.bs.modal',
                                        function() {
                                            // Sample trend data for 3 years
                                            const trendData = {
                                                months: ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
                                                    'Jan', 'Feb', 'Mar'
                                                ],
                                                enrollments: [320, 332, 335, 340, 345, 350, 352, 355, 355,
                                                    355
                                                ]
                                            };

                                            // Demographics data
                                            const demographics = {
                                                'Male': 185,
                                                'Female': 170
                                            };

                                            // Monthly enrollment
                                            const monthlyEnrollment = {
                                                'Jun': 15,
                                                'Jul': 12,
                                                'Aug': 3,
                                                'Sep': 5,
                                                'Oct': 5,
                                                'Nov': 5,
                                                'Dec': 2,
                                                'Jan': 3,
                                                'Feb': 0,
                                                'Mar': 0
                                            };

                                            // Create enrollment trend chart
                                            const trendCanvas = document.createElement('canvas');
                                            document.getElementById('detailedEnrollmentTrend').appendChild(
                                                trendCanvas);

                                            new Chart(trendCanvas, {
                                                type: 'line',
                                                data: {
                                                    labels: trendData.months,
                                                    datasets: [{
                                                        label: 'Total Enrollment',
                                                        data: trendData.enrollments,
                                                        borderColor: '#0d6efd',
                                                        borderWidth: 2,
                                                        pointBackgroundColor: '#0d6efd',
                                                        tension: 0.4,
                                                        fill: true,
                                                        backgroundColor: 'rgba(13, 110, 253, 0.1)'
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    scales: {
                                                        y: {
                                                            beginAtZero: false,
                                                            min: Math.min(...trendData.enrollments) - 20
                                                        }
                                                    }
                                                }
                                            });

                                            // Create demographics chart
                                            const demographicsCanvas = document.createElement('canvas');
                                            document.getElementById('demographicsChart').appendChild(
                                                demographicsCanvas);

                                            new Chart(demographicsCanvas, {
                                                type: 'pie',
                                                data: {
                                                    labels: Object.keys(demographics),
                                                    datasets: [{
                                                        data: Object.values(demographics),
                                                        backgroundColor: ['#0d6efd', '#fd7e14']
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: {
                                                            position: 'bottom'
                                                        }
                                                    }
                                                }
                                            });

                                            // Create monthly enrollment chart
                                            const monthlyCanvas = document.createElement('canvas');
                                            document.getElementById('monthlyEnrollmentChart').appendChild(
                                                monthlyCanvas);

                                            new Chart(monthlyCanvas, {
                                                type: 'bar',
                                                data: {
                                                    labels: Object.keys(monthlyEnrollment),
                                                    datasets: [{
                                                        label: 'New Enrollments',
                                                        data: Object.values(monthlyEnrollment),
                                                        backgroundColor: '#20c997'
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    scales: {
                                                        y: {
                                                            beginAtZero: true
                                                        }
                                                    }
                                                }
                                            });
                                        });

                                    // Clean up when modal is hidden
                                    document.getElementById('detailedStatsModal').addEventListener(
                                        'hidden.bs.modal',
                                        function() {
                                            this.remove();
                                        });
                                });
                            });
    </script>
@endpush
