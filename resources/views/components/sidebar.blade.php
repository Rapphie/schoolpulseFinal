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
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.schedules.*') || request()->routeIs('admin.sections.*') ? 'active' : 'link-dark' }} dropdown-toggle"
                        href="#" id="sectionsDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false" style="width: 100%;">
                        <i data-feather="grid" class="me-2"></i>
                        <span>Sections</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="sectionsDropdown">
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
                        <span>Reports</span>
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
                        {{-- <li>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ route('admin.reports.least-learned') }}">
                                <i class="me-2" data-feather="alert-circle"></i>
                                <span>Least Learned</span>
                            </a>
                        </li> --}}
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
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('admin.settings.index') ? 'active' : 'link-dark' }}"
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

                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.students*') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.students.index') }}" style="width: 100%;">
                        <i data-feather="users" class="me-2"></i>
                        <span>Student Profiles</span>
                    </a>
                </li>

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
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.assessments.*') || request()->routeIs('teacher.grades*') || request()->routeIs('teacher.oral-participation.*') ? 'active' : 'link-dark' }} dropdown-toggle"
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
                            <button type="button"
                                class="dropdown-item d-flex align-items-center js-oral-participation-trigger"
                                data-oral-participation-trigger="true">
                                <i data-feather="message-circle" class="me-1"></i>
                                <span>Oral Participation</span>
                            </button>
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
                {{-- <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.least-learned.*') ? 'active' : 'link-dark' }}"
                        href="{{ route('teacher.least-learned.index') }}" style="width: 100%;">
                        <i data-feather="bar-chart-2" class="me-2"></i>
                        <span>Least Learned</span>
                    </a>
                </li> --}}
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('teacher.analytics.*') || request()->routeIs('analytics.*') ? 'active' : 'link-dark' }}"
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
            </ul>

            <div class="modal fade" id="oralParticipationSelectorModal" tabindex="-1"
                aria-labelledby="oralParticipationSelectorModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="oralParticipationSelectorModalLabel">
                                <i data-feather="message-circle" class="me-2"></i>Oral Participation
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="oralParticipationSelectorLoading" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mb-0 mt-2 text-muted">Loading available classes and subjects...</p>
                            </div>

                            <div id="oralParticipationSelectorError" class="alert alert-danger d-none mb-3"></div>

                            <div id="oralParticipationElementaryPanel" class="d-none">
                                <p class="text-muted">Select a subject to open Oral Participation.</p>
                                <div id="oralParticipationElementarySubjects" class="d-grid gap-2"></div>
                            </div>

                            <div id="oralParticipationDepartmentalPanel" class="d-none">
                                <p class="text-muted mb-3">Choose grade level and section first.</p>
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label for="oralParticipationGradeLevel" class="form-label fw-semibold">Grade
                                            Level</label>
                                        <select id="oralParticipationGradeLevel" class="form-select">
                                            <option value="">Select grade level</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="oralParticipationSection"
                                            class="form-label fw-semibold">Section</label>
                                        <select id="oralParticipationSection" class="form-select" disabled>
                                            <option value="">Select section</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="oralParticipationDepartmentalSubjects" class="d-grid gap-2"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const modalElement = document.getElementById('oralParticipationSelectorModal');
                    if (!modalElement) {
                        return;
                    }

                    const oralParticipationTriggers = document.querySelectorAll(
                        '[data-oral-participation-trigger="true"]'
                    );
                    const gradebookDropdownTrigger = document.getElementById('gradebookDropdown');
                    const selectorEndpoint = @json(route('teacher.oral-participation.selector'));
                    const sectionsEndpoint = @json(route('teacher.oral-participation.sections'));
                    const subjectsEndpointTemplate = @json(route('teacher.classes.subjects', ['class' => '__CLASS__']));
                    const oralParticipationIndexTemplate = @json(route('teacher.oral-participation.index', ['class' => '__CLASS__']));

                    const loadingElement = document.getElementById('oralParticipationSelectorLoading');
                    const errorElement = document.getElementById('oralParticipationSelectorError');
                    const elementaryPanel = document.getElementById('oralParticipationElementaryPanel');
                    const elementarySubjects = document.getElementById('oralParticipationElementarySubjects');
                    const departmentalPanel = document.getElementById('oralParticipationDepartmentalPanel');
                    const gradeLevelSelect = document.getElementById('oralParticipationGradeLevel');
                    const sectionSelect = document.getElementById('oralParticipationSection');
                    const departmentalSubjects = document.getElementById('oralParticipationDepartmentalSubjects');

                    function escapeHtml(value) {
                        return String(value)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    }

                    function buildSubjectsEndpoint(classId) {
                        return subjectsEndpointTemplate.replace('__CLASS__', encodeURIComponent(String(classId)));
                    }

                    function buildOralParticipationUrl(classId, subjectId) {
                        const base = oralParticipationIndexTemplate.replace('__CLASS__', encodeURIComponent(String(
                            classId)));
                        const delimiter = base.includes('?') ? '&' : '?';

                        return `${base}${delimiter}subject_id=${encodeURIComponent(String(subjectId))}`;
                    }

                    function closeMobileSidebarIfOpen() {
                        const sidebar = document.getElementById('sidebar');
                        if (!sidebar || window.innerWidth >= 768) {
                            return;
                        }

                        if (sidebar.classList.contains('show')) {
                            sidebar.classList.remove('show');
                            sidebar.classList.add('collapsed');
                            document.body.style.overflow = '';
                        }
                    }

                    function openSelectorModal() {
                        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                            return;
                        }

                        const selectorModal = bootstrap.Modal.getOrCreateInstance(modalElement);
                        selectorModal.show();
                    }

                    if (modalElement.parentElement !== document.body) {
                        document.body.appendChild(modalElement);
                    }

                    oralParticipationTriggers.forEach((trigger) => {
                        trigger.addEventListener('click', function(event) {
                            event.preventDefault();

                            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown &&
                                gradebookDropdownTrigger) {
                                const gradebookDropdown = bootstrap.Dropdown.getOrCreateInstance(
                                    gradebookDropdownTrigger
                                );
                                gradebookDropdown.hide();
                            }

                            closeMobileSidebarIfOpen();
                            window.setTimeout(openSelectorModal, 120);
                        });
                    });

                    async function fetchJson(url) {
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const payload = await response.json().catch(() => null);
                        if (!response.ok) {
                            throw new Error(payload?.message || 'Unable to load selector data.');
                        }

                        return payload || {};
                    }

                    function setLoading(isLoading) {
                        if (isLoading) {
                            loadingElement.classList.remove('d-none');
                        } else {
                            loadingElement.classList.add('d-none');
                        }
                    }

                    function showError(message) {
                        errorElement.textContent = message;
                        errorElement.classList.remove('d-none');
                    }

                    function clearError() {
                        errorElement.textContent = '';
                        errorElement.classList.add('d-none');
                    }

                    function resetPanels() {
                        clearError();
                        elementaryPanel.classList.add('d-none');
                        departmentalPanel.classList.add('d-none');
                        elementarySubjects.innerHTML = '';
                        departmentalSubjects.innerHTML = '';
                        gradeLevelSelect.innerHTML = '<option value="">Select grade level</option>';
                        sectionSelect.innerHTML = '<option value="">Select section</option>';
                        sectionSelect.disabled = true;
                    }

                    function createSubjectLink(classId, subjectId, subjectName, detailText) {
                        const link = document.createElement('a');
                        link.href = buildOralParticipationUrl(classId, subjectId);
                        link.className = 'btn btn-outline-success text-start';

                        const details = detailText ?
                            `<small class="text-muted d-block">${escapeHtml(detailText)}</small>` : '';

                        link.innerHTML =
                            `<i data-feather="book-open" class="me-2"></i>${escapeHtml(subjectName)}${details}`;

                        return link;
                    }

                    function renderEmpty(target, message) {
                        target.innerHTML = `<div class="alert alert-info mb-0">${escapeHtml(message)}</div>`;
                    }

                    function renderElementarySubjects(subjects) {
                        elementaryPanel.classList.remove('d-none');

                        if (!Array.isArray(subjects) || subjects.length === 0) {
                            renderEmpty(elementarySubjects, 'No advisory subjects available for oral participation.');

                            return;
                        }

                        subjects.forEach((subject) => {
                            const subjectButton = createSubjectLink(
                                subject.class_id,
                                subject.subject_id,
                                subject.subject_name,
                                subject.class_label
                            );
                            elementarySubjects.appendChild(subjectButton);
                        });
                    }

                    function renderDepartmentalGradeLevels(gradeLevels) {
                        departmentalPanel.classList.remove('d-none');

                        if (!Array.isArray(gradeLevels) || gradeLevels.length === 0) {
                            renderEmpty(departmentalSubjects,
                                'No scheduled grade levels available for oral participation.');

                            return;
                        }

                        gradeLevels.forEach((gradeLevel) => {
                            const option = document.createElement('option');
                            option.value = gradeLevel.id;
                            option.textContent = gradeLevel.name;
                            gradeLevelSelect.appendChild(option);
                        });
                    }

                    function renderDepartmentalSubjects(classId, subjects) {
                        departmentalSubjects.innerHTML = '';

                        if (!Array.isArray(subjects) || subjects.length === 0) {
                            renderEmpty(departmentalSubjects, 'No subjects found for the selected section.');

                            return;
                        }

                        subjects.forEach((subject) => {
                            const subjectButton = createSubjectLink(
                                classId,
                                subject.id,
                                subject.name,
                                ''
                            );
                            departmentalSubjects.appendChild(subjectButton);
                        });
                    }

                    gradeLevelSelect.addEventListener('change', async function() {
                        const gradeLevelId = this.value;
                        sectionSelect.innerHTML = '<option value="">Select section</option>';
                        sectionSelect.disabled = true;
                        departmentalSubjects.innerHTML = '';

                        if (!gradeLevelId) {
                            return;
                        }

                        setLoading(true);
                        clearError();
                        try {
                            const payload = await fetchJson(
                                `${sectionsEndpoint}?grade_level_id=${encodeURIComponent(gradeLevelId)}`
                            );
                            const sections = payload.sections || [];
                            if (sections.length === 0) {
                                renderEmpty(departmentalSubjects,
                                    'No handled sections found for this grade level.');
                                setLoading(false);

                                return;
                            }

                            sections.forEach((section) => {
                                const option = document.createElement('option');
                                option.value = section.id;
                                option.textContent = section.name;
                                sectionSelect.appendChild(option);
                            });
                            sectionSelect.disabled = false;
                        } catch (error) {
                            showError(error.message);
                        } finally {
                            setLoading(false);
                        }
                    });

                    sectionSelect.addEventListener('change', async function() {
                        const classId = this.value;
                        departmentalSubjects.innerHTML = '';
                        if (!classId) {
                            return;
                        }

                        setLoading(true);
                        clearError();
                        try {
                            const payload = await fetchJson(buildSubjectsEndpoint(classId));
                            renderDepartmentalSubjects(classId, payload.subjects || []);
                        } catch (error) {
                            showError(error.message);
                        } finally {
                            setLoading(false);
                        }
                    });

                    modalElement.addEventListener('show.bs.modal', async function() {
                        resetPanels();
                        setLoading(true);

                        try {
                            const payload = await fetchJson(selectorEndpoint);

                            if (payload.mode === 'elementary_adviser') {
                                renderElementarySubjects(payload.subjects || []);
                            } else {
                                renderDepartmentalGradeLevels(payload.grade_levels || []);
                            }
                        } catch (error) {
                            showError(error.message);
                        } finally {
                            setLoading(false);
                            if (typeof feather !== 'undefined') {
                                feather.replace();
                            }
                        }
                    });
                });
            </script>
        @elseif (Auth::check() && Auth::user()->hasRole('guardian'))
            <ul class="nav nav-pills flex-column mb-auto w-100">
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('guardian.dashboard') ? 'active' : 'link-dark' }}"
                        href="{{ route('guardian.dashboard') }}" style="width: 100%;">
                        <i data-feather="home" class="me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('guardian.grades') ? 'active' : 'link-dark' }}"
                        href="{{ route('guardian.grades') }}" style="width: 100%;">
                        <i data-feather="book" class="me-2"></i>
                        <span>Grades</span>
                    </a>
                </li>
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('guardian.attendance') ? 'active' : 'link-dark' }}"
                        href="{{ route('guardian.attendance') }}" style="width: 100%;">
                        <i data-feather="clipboard" class="me-2"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item mb-3 w-100">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('profile') ? 'active' : 'link-dark' }}"
                        href="{{ route('profile') }}" style="width: 100%;">
                        <i data-feather="user" class="me-2"></i>
                        <span>My Profile</span>
                    </a>
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
