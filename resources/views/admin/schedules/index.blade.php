@extends('base')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.schedules.index') }}">Schedules</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ $selectedTeacher ? $selectedTeacher->user->first_name . ' ' . $selectedTeacher->user->last_name : 'Overview' }}
                    </li>
                </ol>
            </nav>
            <a href="{{ route('admin.schedules.create') }}"
                class="btn btn-primary d-inline-flex align-items-center px-3 py-2 fw-bold add-schedule-btn text-decoration-none">
                <i data-feather="plus" class="feather-sm me-1"></i>
                <span class="ms-2">Add Schedule</span>
            </a>
        </div>

        {{-- Filters --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('admin.schedules.index') }}" id="scheduleFilterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filterTeacherSearch" class="form-label fw-semibold mb-1">Teacher</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="filterTeacherSearch"
                                    placeholder="Search for a teacher..." autocomplete="off"
                                    value="{{ $filters['teacher_id'] ?? '' ? $teachers->firstWhere('id', $filters['teacher_id'])?->user?->first_name . ' ' . $teachers->firstWhere('id', $filters['teacher_id'])?->user?->last_name : '' }}">
                                <input type="hidden" name="teacher_id" id="filterTeacherId"
                                    value="{{ $filters['teacher_id'] ?? '' }}">
                                <div class="dropdown-menu w-100" id="teacherFilterDropdown"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="filterGradeLevel" class="form-label fw-semibold mb-1">Grade Level</label>
                            <select class="form-select" id="filterGradeLevel" name="grade_level_id"
                                {{ $selectedTeacher ? '' : 'disabled' }}
                                onchange="filterSectionsByGradeLevel(); document.getElementById('scheduleFilterForm').submit()">
                                <option value="">All Grade Levels</option>
                                @foreach ($gradeLevels as $gradeLevel)
                                    <option value="{{ $gradeLevel->id }}"
                                        {{ ($filters['grade_level_id'] ?? '') == $gradeLevel->id ? 'selected' : '' }}>
                                        {{ $gradeLevel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filterSection" class="form-label fw-semibold mb-1">Section</label>
                            <select class="form-select" id="filterSection" name="section_id"
                                {{ ($selectedTeacher && ($filters['grade_level_id'] ?? '')) ? '' : 'disabled' }}
                                onchange="document.getElementById('scheduleFilterForm').submit()">
                                <option value="">All Sections</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}"
                                        data-grade-level-id="{{ $section->grade_level_id }}"
                                        {{ ($filters['section_id'] ?? '') == $section->id ? 'selected' : '' }}>
                                        {{ $section->gradeLevel->name ?? '' }} - {{ $section->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            @if ($selectedTeacher)
                                <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary w-100">
                                    <i data-feather="arrow-left" class="feather-sm me-1"></i> Back to All Teachers
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedTeacher)
            {{-- Teacher Calendar View --}}
            <div class="d-flex align-items-center mb-3">
                <h5 class="mb-0 me-2 text-muted">Viewing:</h5>
                <span class="badge bg-primary fs-6">{{ $selectedTeacher->user->first_name }} {{ $selectedTeacher->user->last_name }}'s Schedule</span>
                @if ($filters['grade_level_id'] ?? false)
                    <span class="badge bg-secondary fs-6 ms-2">Grade: {{ $gradeLevels->firstWhere('id', $filters['grade_level_id'])->name ?? '' }}</span>
                @endif
                @if ($filters['section_id'] ?? false)
                    <span class="badge bg-secondary fs-6 ms-2">Section: {{ $sections->firstWhere('id', $filters['section_id'])->name ?? '' }}</span>
                @endif
            </div>

            <div id='calendar-container' class="bg-white p-4 rounded shadow-sm">
                <div id='calendar-loader' class="text-center py-5">
                    <p>Loading schedules...</p>
                </div>
                <div id='calendar' style="visibility: hidden; min-height: 800px;"></div>
            </div>
        @else
            {{-- Teacher Cards Grid --}}
            @if ($teacherCards->isNotEmpty())
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4" id="teacherCardsGrid">
                    @foreach ($teacherCards as $card)
                        <div class="col teacher-card-col">
                            <a href="{{ route('admin.schedules.index', ['teacher_id' => $card['id']]) }}"
                                class="card shadow-sm h-100 text-decoration-none text-dark teacher-card"
                                data-teacher-name="{{ strtolower($card['name']) }}">
                                <div class="card-body text-center py-4">
                                    <div class="teacher-avatar mx-auto mb-3">{{ $card['initials'] }}</div>
                                    <h6 class="card-title fw-bold mb-1">{{ $card['name'] }}</h6>
                                    <span class="badge bg-light text-dark mb-2">
                                        {{ $card['schedule_count'] }} {{ \Illuminate\Support\Str::plural('schedule', $card['schedule_count']) }}
                                    </span>
                                    @if ($card['subjects']->isNotEmpty())
                                        <p class="card-text small text-muted mb-0">
                                            {{ $card['subjects']->implode(', ') }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Empty state for client-side filtering --}}
                <div id="noTeacherResults" class="text-center py-5 d-none">
                    <i data-feather="search" style="width: 48px; height: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No teachers match your search</h5>
                    <p class="text-muted">Try a different name.</p>
                </div>
            @else
                <div class="text-center py-5">
                    <i data-feather="calendar" style="width: 48px; height: 48px; color: #ccc;"></i>
                    <h5 class="mt-3 text-muted">No teachers with schedules found</h5>
                    <p class="text-muted">Add schedules to get started.</p>
                </div>
            @endif
        @endif
    </div>
@endsection

@push('styles')
    <style>
        .add-schedule-btn {
            background-color: #0d6efd !important;
        }

        #teacherFilterDropdown {
            z-index: 9999;
            position: absolute;
            top: 100%;
            left: 0;
            max-height: 250px;
            overflow-y: auto;
        }

        #teacherFilterDropdown.show {
            display: block;
        }

        .card.shadow-sm.mb-4 {
            overflow: visible !important;
            pointer-events: auto;
        }

        .card.shadow-sm.mb-4:hover {
            transform: none !important;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
            cursor: default;
        }

        .card.shadow-sm.mb-4 .card-body {
            overflow: visible !important;
        }

        .fc .fc-button-primary {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        .fc .fc-button-primary:hover {
            background-color: #0b5ed7 !important;
            border-color: #0a58ca !important;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background-color: #0a58ca !important;
            border-color: #0a53be !important;
        }

        .fc .fc-timegrid-slot {
            height: 3em !important;
        }

        .fc-event {
            border-radius: 4px;
            border: none;
            padding: 2px;
            margin: 1px 0;
        }

        .fc-event-main {
            padding: 2px 4px;
        }

        .fc-daygrid-event {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .teacher-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            letter-spacing: 0.5px;
        }

        .teacher-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            border: 1px solid rgba(0, 0, 0, 0.075);
        }

        .teacher-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12) !important;
            border-color: #667eea;
        }

        .teacher-card .badge.bg-light {
            border: 1px solid #dee2e6;
        }
    </style>
@endpush

@push('scripts')
    @if ($selectedTeacher)
        <script src="{{ asset('js/calendar/main.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                var loader = document.getElementById('calendar-loader');

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },
                    events: {!! $events !!},
                    businessHours: {
                        daysOfWeek: [1, 2, 3, 4, 5],
                        startTime: '07:00',
                        endTime: '18:00',
                    },
                    weekends: false,
                    height: 'auto',
                    contentHeight: 800,
                    eventOverlap: false,
                    slotEventOverlap: false,
                    navLinks: true,
                    editable: false,
                    selectable: true,
                    dayMaxEvents: true,
                    allDaySlot: false,
                    slotMinTime: '07:00:00',
                    slotMaxTime: '19:00:00',
                    eventClick: function(info) {
                        info.jsEvent.preventDefault();
                        if (info.event.url) {
                            window.open(info.event.url, "_self");
                        }
                    },
                    eventContent: function(arg) {
                        let eventEl = document.createElement('div');
                        let html = `<div class="p-1 overflow-hidden">
                                        <div class="fw-bold small">${arg.timeText}</div>`;

                        if (arg.event.extendedProps.subject) {
                            html +=
                                `<div class="fw-semibold text-truncate d-block small">${arg.event.extendedProps.subject}</div>`;
                        }
                        if (arg.event.extendedProps.section) {
                            html +=
                                `<div class="small text-truncate d-block">${arg.event.extendedProps.section}</div>`;
                        }
                        if (arg.event.extendedProps.room) {
                            html +=
                                `<div class="small text-truncate bg-light rounded px-1 mt-1 text-dark d-block">${arg.event.extendedProps.room}</div>`;
                        }
                        html += `</div>`;
                        eventEl.innerHTML = html;
                        eventEl.style.overflow = 'hidden';
                        eventEl.style.whiteSpace = 'normal';

                        return {
                            domNodes: [eventEl]
                        };
                    },
                    loading: function(isLoading) {
                        if (isLoading) {
                            loader.style.display = 'block';
                            calendarEl.style.visibility = 'hidden';
                        } else {
                            loader.style.display = 'none';
                            calendarEl.style.visibility = 'visible';
                        }
                    }
                });

                calendar.render();
            });
        </script>
    @endif

    <script>
        function filterSectionsByGradeLevel() {
            var gradeLevelId = document.getElementById('filterGradeLevel').value;
            var sectionSelect = document.getElementById('filterSection');
            var options = sectionSelect.querySelectorAll('option[data-grade-level-id]');

            sectionSelect.value = '';
            sectionSelect.disabled = !gradeLevelId;

            options.forEach(function(option) {
                if (!gradeLevelId || option.getAttribute('data-grade-level-id') === gradeLevelId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var gradeLevelId = document.getElementById('filterGradeLevel').value;
            if (gradeLevelId) {
                var sectionSelect = document.getElementById('filterSection');
                var options = sectionSelect.querySelectorAll('option[data-grade-level-id]');
                options.forEach(function(option) {
                    if (option.getAttribute('data-grade-level-id') !== gradeLevelId) {
                        option.style.display = 'none';
                    }
                });
            }

            const teachersList = @json($teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->user->first_name . ' ' . $t->user->last_name]));
            const teacherInput = document.getElementById('filterTeacherSearch');
            const teacherHidden = document.getElementById('filterTeacherId');
            const teacherMenu = document.getElementById('teacherFilterDropdown');

            if (!teacherInput || !teacherHidden || !teacherMenu) return;

            const allTeachersOption = {
                id: '',
                name: 'All Teachers'
            };

            const renderTeacherDropdown = (teachers) => {
                teacherMenu.innerHTML = '';
                if (teachers.length === 0) {
                    teacherMenu.innerHTML = '<div class="dropdown-item text-muted">No teachers found</div>';
                    return;
                }
                teachers.forEach(teacher => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'dropdown-item';
                    item.textContent = teacher.name;
                    item.dataset.id = teacher.id;
                    item.addEventListener('click', () => {
                        teacherInput.value = teacher.id ? teacher.name : '';
                        teacherHidden.value = teacher.id;
                        teacherMenu.classList.remove('show');
                        document.getElementById('scheduleFilterForm').submit();
                    });
                    teacherMenu.appendChild(item);
                });
            };

            teacherInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let filtered = teachersList.filter(t => t.name.toLowerCase().includes(query));
                filtered = [allTeachersOption, ...filtered];
                renderTeacherDropdown(filtered);
                teacherMenu.classList.add('show');

                // Client-side filtering of teacher cards
                const cardsGrid = document.getElementById('teacherCardsGrid');
                const noResults = document.getElementById('noTeacherResults');
                if (cardsGrid && noResults) {
                    const cards = cardsGrid.querySelectorAll('.teacher-card-col');
                    let visibleCount = 0;
                    cards.forEach(card => {
                        const teacherName = card.querySelector('.teacher-card')?.dataset
                            .teacherName || '';
                        if (!query || teacherName.includes(query)) {
                            card.style.display = '';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    noResults.classList.toggle('d-none', visibleCount > 0);
                }
            });

            teacherInput.addEventListener('focus', function() {
                const query = this.value.toLowerCase().trim();
                let filtered = query ?
                    teachersList.filter(t => t.name.toLowerCase().includes(query)) :
                    teachersList;
                filtered = [allTeachersOption, ...filtered];
                renderTeacherDropdown(filtered);
                teacherMenu.classList.add('show');
            });

            document.addEventListener('click', function(e) {
                if (!teacherInput.contains(e.target) && !teacherMenu.contains(e.target)) {
                    teacherMenu.classList.remove('show');
                }
            });
        });
    </script>
@endpush
