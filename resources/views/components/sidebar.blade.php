@php use Illuminate\Support\Facades\Auth; @endphp
<div class="sidebar" id="sidebar">
    <button class="sidebar-close" id="sidebarCloseBtn" aria-label="Close sidebar">
        <i data-feather="x"></i>
    </button>

    <div>
        <div class="brand d-flex align-items-center mb-3">
            <div class="mini-logo-container">
                <img src="{{ asset('images/school-logo.png') }}" alt="SchoolPulse Logo" class="brand-logo me-2">
            </div>
            <span class="fs-4 fw-bold text-primary">SchoolPulse</span>
        </div>
        <hr>
        @if (Auth::check() && Auth::user()->hasRole('admin'))
            <ul class="nav nav-pills flex-column mb-auto w-100">
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('dashboard') ? 'active' : 'link-dark' }}"
                        href="{{ route('dashboard') }}" style="width: 100%;">
                        <i data-feather="home" class="me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.grade-levels.*') ? 'active' : 'link-dark' }}"
                        href="{{ route('admin.grade-levels.index') }}" style="width: 100%;">
                        <i data-feather="layers" class="me-2"></i>
                        <span>Grade Levels</span>
                    </a>
                </li>

                <li class="nav-item mb-3 dropdown position-static w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.subjects.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                        href="#" id="subjectsDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false" style="width: 100%;">
                        <i data-feather="book-open" class="me-2"></i>
                        <span>Subjects</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="subjectsDropdown"
                        style="min-width: 200px; left: 100%; top: 0; position: fixed; z-index: 999; overflow: auto; max-height: 80vh;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.subjects.index', ['openModal' => 'true']) }}">
                                <i data-feather="plus" class="me-2"></i>
                                <span>Add Subject</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.subjects.index') }}">
                                <i data-feather="list" class="me-2"></i>
                                <span>All Subjects</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item mb-3 dropdown position-static w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.schedule.*') || request()->routeIs('admin.sections.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                        href="#" id="subjectsDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false" style="width: 100%;">
                        <i data-feather="grid" class="me-2"></i>
                        <span>Sections</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="SectionsDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.schedules.index') }}" style="width: 100%;">
                                <i data-feather="calendar" class="me-2"></i>
                                <span>Class Schedules</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.sections.index') }}">
                                <i data-feather="layers" class="me-2"></i>
                                <span>All Sections</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.teachers.*') ? 'active' : 'link-dark' }}"
                        href="{{ route('admin.teachers.index') }}" style="width: 100%;">
                        <i data-feather="users" class="me-2"></i>
                        <span>Teachers</span>
                    </a>
                </li>


                <li class="nav-item mb-3 dropdown position-static w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.reports.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                        href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false" style="width: 100%;">
                        <i data-feather="pie-chart" class="me-2"></i>
                        <span>Reports&Analytics</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="reportsDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.reports.enrollees') }}">
                                <i class="me-2" data-feather="user-plus"></i>
                                <span>Enrollees</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.reports.attendance') }}">
                                <i class="me-2" data-feather="check-circle"></i>
                                <span>Attendance</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.reports.grades') }}">
                                <i class="me-2" data-feather="award"></i>
                                <span>Grades</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.reports.least-learned') }}">
                                <i class="me-2" data-feather="alert-circle"></i>
                                <span>Least Learned</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.reports.cumulative') }}">
                                <i class="me-2" data-feather="trending-up"></i>
                                <span>Cumulative</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- <li class="nav-item mb-3">
                    <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : 'link-dark' }}"
                        href="{{ route('settings.index') }}">
                        <i data-feather="settings"></i>
                        <span>Settings</span>
                    </a>
                </li> --}}
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.settings') ? 'active' : 'link-dark' }}"
                        href="{{ route('admin.settings.index') }}" style="width: 100%;">
                        <i class="me-2" data-feather="settings"></i>
                        <span>System Settings</span>
                    </a>
                </li>
            </ul>
        @elseif (Auth::check() && Auth::user()->hasRole('teacher'))
            <ul class="nav nav-pills flex-column mb-auto w-100">
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.dashboard') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.dashboard') }}" style="width: 100%;">
                        <i data-feather="home" class="me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.schedules.*') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.schedules.index') }}" style="width: 100%;">
                        <i data-feather="calendar" class="me-2"></i>
                        <span>Schedules</span>
                    </a>
                </li>
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.classes*') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.classes') }}" style="width: 100%;">
                        <i data-feather="layers" class="me-2"></i>
                        <span>Sections</span>
                    </a>
                </li>
                {{--
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.students*') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.students') }}" style="width: 100%;">
                        <i data-feather="users" class="me-2"></i>
                        <span>Students</span>
                    </a>
                </li> --}}

                @if ($teacher_enrollment_enabled)
                    <li class="nav-item mb-3 w-100">
                        <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.enrollment*') ? 'active' : 'link-dark' }}"
                            href="{{ route('teacher.enrollment.index') }}" style="width: 100%;">
                            <i data-feather="user-plus" class="me-2"></i>
                            <span>Enrollment</span>
                        </a>
                    </li>
                @endif
                <li class="nav-item mb-3 dropdown position-static w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.assessments.*') || request()->routeIs('teacher.grades*') ? 'active' : 'link-dark' }} dropdown-toggle"
                        href="#" id="gradebookDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false" style="width: 100%;">
                        <i data-feather="book" class="me-2"></i>
                        <span>Gradebook</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="gradebookDropdown"
                        style="min-width: 200px; left: 100%; top: 0; position: fixed; z-index: 999; overflow: auto; max-height: 80vh;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('teacher.assessments.list') }}">
                                <i data-feather="edit-3" class="me-1"></i>
                                <span>Grade Management</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('teacher.grades') }}">
                                <i data-feather="award" class="me-1"></i>
                                <span>Report Card</span>
                            </a>
                        </li>

                    </ul>
                </li>
                {{-- <li class="nav-item mb-3">
                    <a class="nav-link {{ request()->routeIs('teacher.grades*') ? 'active' : 'link-dark' }} d-flex align-items-center"
                        href="{{ route('teacher.grades') }}">
                        <i class="me-1" data-feather="award"></i>
                        <span>Grades</span>
                    </a>
                </li> --}}
                {{--
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('llc') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.least-learned.index') }}" style="width: 100%;">
                        <i data-feather="bar-chart-2" class="me-2"></i>
                        <span>Least Learned</span>
                    </a>
                </li> --}}
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.analytics.*') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.analytics.absenteeism') }}" style="width: 100%;">
                        <i data-feather="pie-chart" class="me-2"></i>
                        <span>Absenteeism</span>
                    </a>
                </li>
                <li class="nav-item mb-3 dropdown position-static w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.attendance.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                        href="#" id="attendanceDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false" style="width: 100%;">
                        <i data-feather="check-circle" class="me-2"></i>
                        <span>Attendance</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="attendanceDropdown"
                        style="min-width: 200px; left: 100%; top: 0; position: fixed; z-index: 999; overflow: auto; max-height: 80vh;">
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('teacher.attendance.take') }}">
                                <i data-feather="edit" class="me-1"></i>
                                <span>Take Attendance</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('teacher.attendance.records') }}">
                                <i data-feather="list" class="me-1"></i>
                                <span>Attendance Records</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('teacher.attendance.pattern') }}">
                                <i data-feather="bar-chart" class="me-1"></i>
                                <span>Attendance Pattern</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
            </ul>
            </li>

            </ul>
        @endif
    </div>
    <div class="mt-auto w-100">
        <hr>
        <a class="nav-link link-dark d-flex align-items-center" href="#" data-bs-toggle="modal"
            data-bs-target="#logoutModal" style="width: 100%; padding-left: 1rem;">
            <span data-feather="log-out" class="me-2"></span> <span>Log out</span>
        </a>
    </div>
</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title d-flex align-items-center" id="logoutModalLabel">
                    <i data-feather="alert-triangle" class="me-2 text-danger"></i>
                    Confirm Logout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-0 text-center">Are you sure you want to log out of your account?</p>
                @if (Auth::check())
                    <p class="text-center text-muted mt-2">
                        Logged in as: <strong>{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</strong>
                    </p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary d-flex align-items-center"
                    data-bs-dismiss="modal">
                    <i data-feather="x" class="feather-sm me-1"></i> Cancel
                </button>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger d-flex align-items-center">
                        <i data-feather="log-out" class="feather-sm me-1"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
