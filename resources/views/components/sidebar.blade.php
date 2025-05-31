@php use Illuminate\Support\Facades\Auth; @endphp
<div class="sidebar" id="sidebar">

    <div>
        <div class="brand d-flex align-items-center mb-3">
            <div class="mini-logo-container">
                <img src="{{ asset('images/school-logo.png') }}" alt="SchoolPulse Logo" class="brand-logo me-2">
            </div>
            <span class="fs-4 fw-bold text-primary">SchoolPulse</span>
        </div>
        <hr>
        {{-- @if (Auth::check() && Auth::user()->hasRole('admin')) --}}
        <ul class="nav nav-pills flex-column mb-auto w-100">
            <li class="nav-item mb-3 w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('dashboard') ? 'active' : 'link-dark' }}"
                    href="{{ route('dashboard') }}" style="width: 100%;">
                    <i data-feather="home" class="me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item mb-3 dropdown position-static w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.subjects.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                    href="#" id="subjectsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                    style="width: 100%;">
                    <i data-feather="book-open" class="me-2"></i>
                    <span>Subjects</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="subjectsDropdown"
                    style="min-width: 200px; left: 100%; top: 0; position: fixed; z-index: 999; overflow: auto; max-height: 80vh;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center"
                            href="{{ route('admin.subjects.index', ['openModal' => 'true']) }}">
                            <i data-feather="plus" class="me-2"></i>
                            <span>Add Subjects</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.subjects.index') }}">
                            <i data-feather="list" class="me-2"></i>
                            <span>All Subjects</span>
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

            <li class="nav-item mb-3 w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.sections.*') ? 'active' : 'link-dark' }}"
                    href="{{ route('admin.sections.index') }}" style="width: 100%;">
                    <i data-feather="layers" class="me-2"></i>
                    <span>Sections</span>
                </a>
            </li>

            <li class="nav-item mb-3 dropdown position-static w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('reports.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                    href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"
                    style="width: 100%;">
                    <i data-feather="bar-chart-2" class="me-2"></i>
                    <span>Reports</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="reportsDropdown"
                    style="min-width: 200px; left: 100%; top: 0; position: fixed; z-index: 999; overflow: auto; max-height: 80vh;">
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.enrollees') }}">
                            <i data-feather="user-plus"></i>
                            <span>Enrollees</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.records') }}">
                            <i data-feather="check-circle"></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.grades') }}">
                            <i data-feather="award"></i>
                            <span>Grades</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.least-learned') }}">
                            <i data-feather="alert-circle"></i>
                            <span>Least Learned</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.cumulative') }}">
                            <i data-feather="trending-up"></i>
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
        </ul>
        {{-- @elseif (Auth::check() && Auth::user()->hasRole('teacher')) --}}
        <ul class="nav nav-pills flex-column mb-auto w-100">
            <li class="nav-item mb-3 w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.dashboard') ? 'active' : 'link-dark' }}"
                    href="{{ route('teacher.dashboard') }}" style="width: 100%;">
                    <i data-feather="home" class="me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item mb-3 w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.classes*') ? 'active' : 'link-dark' }}"
                    href="{{ route('teacher.classes') }}" style="width: 100%;">
                    <i data-feather="layers" class="me-2"></i>
                    <span>Classes</span>
                </a>
            </li>

            <li class="nav-item mb-3 w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.students*') ? 'active' : 'link-dark' }}"
                    href="{{ route('teacher.students') }}" style="width: 100%;">
                    <i data-feather="users" class="me-2"></i>
                    <span>Students</span>
                </a>
            </li>

            {{-- <li class="nav-item mb-3">
                    <a class="nav-link {{ request()->routeIs('teacher.grades*') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.grades') }}">
                        <i data-feather="award"></i>
                        <span>Grades</span>
                    </a>
                </li> --}}

            <li class="nav-item mb-3 dropdown position-static w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.gradebook.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                    href="#" id="gradebookDropdown" role="button" data-bs-toggle="dropdown"
                    aria-expanded="false" style="width: 100%;">
                    <i data-feather="book" class="me-2"></i>
                    <span>Gradebook</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="gradebookDropdown"
                    style="min-width: 200px; left: 100%; top: 0; position: fixed; z-index: 999; overflow: auto; max-height: 80vh;">
                    <li>
                        <a class="dropdown-item" href="{{ route('teacher.gradebook.quiz') }}">
                            <i data-feather="edit-3"></i>
                            <span>Quiz</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('teacher.gradebook.exam') }}">
                            <i data-feather="file-text"></i>
                            <span>Exam</span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item mb-3 w-100">
                <a class="nav-link d-flex align-items-center {{ request()->routeIs('llc') ? 'active' : 'link-dark' }}"
                    href="{{ route('llc') }}" style="width: 100%;">
                    <i data-feather="layers" class="me-2"></i>
                    <span>Least Learned Competency</span>
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
                        <a class="dropdown-item" href="{{ route('teacher.attendance.take') }}">
                            <i data-feather="edit"></i>
                            <span>Take Attendance</span>
                        </a>
                    </li>
                    {{-- <li>
                            <a class="dropdown-item" href="{{ route('teacher.attendance.records') }}">
                                <i data-feather="list"></i>
                                <span>Attendance Records</span>
                            </a>
                        </li> --}}
                </ul>
            </li>
        </ul>
        {{-- @endif --}}
    </div>
    <div class="mt-auto w-100">
        <hr>
        <a class="nav-link link-dark d-flex align-items-center" href="{{ route('logout') }}"
            style="width: 100%; padding-left: 1rem;">
            <span data-feather="log-out" class="me-2"></span> <span>Log out</span>
        </a>
    </div>
</div>
