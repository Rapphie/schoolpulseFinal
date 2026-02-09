@extends('base')

@section('content')
    <div class="container-fluid">
        <div class="flex justify-between items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.schedules.index') }}">Schedules</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Overview</li>
                </ol>
            </nav>
            <a href="{{ route('admin.schedules.create') }}"
                class="inline-flex items-center bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded add-schedule-btn text-decoration-none">
                <i data-feather="plus" class="feather-sm me-1"></i>
                <span class="ml-2">Add Schedule</span>
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
                                {{ $filters['grade_level_id'] ?? '' ? '' : 'disabled' }}
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
                        <div class="col-md-3">
                            @if (array_filter($filters ?? []))
                                <a href="{{ route('admin.schedules.index') }}" class="btn btn-outline-secondary w-100">
                                    <i data-feather="x" class="feather-sm me-1"></i> Clear Filters
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <div id='calendar-container' class="bg-white p-6 rounded-lg shadow-md">
            <div id='calendar-loader' class="text-center py-10">
                <p>Loading schedules...</p>
            </div>
            <div id='calendar' style="visibility: hidden; min-height: 800px;"></div>
        </div>
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

        /* Increase the height of time slots for better visibility */
        .fc .fc-timegrid-slot {
            height: 3em !important;
        }

        /* Improve event styling */
        .fc-event {
            border-radius: 4px;
            border: none;
            padding: 2px;
            margin: 1px 0;
        }

        /* Better readability for event content */
        .fc-event-main {
            padding: 2px 4px;
        }

        /* Add shadow to events for better separation */
        .fc-daygrid-event {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <!-- FullCalendar JS -->
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
                    daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
                    startTime: '07:00',
                    endTime: '18:00',
                },
                // Exclude weekends (Saturday and Sunday)
                weekends: false,
                // Increase the height of the calendar
                height: 'auto',
                contentHeight: 800,
                // Improve handling of overlapping events
                eventOverlap: false,
                slotEventOverlap: false,
                navLinks: true, // can click day/week names to navigate views
                editable: false,
                selectable: true,
                dayMaxEvents: true, // allow "more" link when too many events
                allDaySlot: false, // Don't show the all-day slot
                slotMinTime: '07:00:00', // Start the calendar at 7am
                slotMaxTime: '19:00:00', // End the calendar at 7pm
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.open(info.event.url, "_self");
                    }
                },
                eventContent: function(arg) {
                    let eventEl = document.createElement('div');
                    let html = `<div class="p-1 overflow-hidden">
                                    <div class="font-bold">${arg.timeText}</div>`;

                    if (arg.event.extendedProps.subject) {
                        html +=
                            `<div class="font-semibold truncate">${arg.event.extendedProps.subject}</div>`;
                    }
                    if (arg.event.extendedProps.section) {
                        html +=
                            `<div class="text-sm truncate">${arg.event.extendedProps.section}</div>`;
                    }
                    if (arg.event.extendedProps.teacher) {
                        html +=
                            `<div class="text-sm font-italic truncate">${arg.event.extendedProps.teacher}</div>`;
                    }
                    if (arg.event.extendedProps.room) {
                        html +=
                            `<div class="text-xs truncate bg-gray-100 rounded px-1 mt-1 text-black">${arg.event.extendedProps.room}</div>`;
                    }
                    html += `</div>`;
                    eventEl.innerHTML = html;

                    // Add custom styling for better visibility
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

        function filterSectionsByGradeLevel() {
            var gradeLevelId = document.getElementById('filterGradeLevel').value;
            var sectionSelect = document.getElementById('filterSection');
            var options = sectionSelect.querySelectorAll('option[data-grade-level-id]');

            // Reset section selection when grade level changes
            sectionSelect.value = '';

            // Enable/disable section dropdown based on grade level selection
            sectionSelect.disabled = !gradeLevelId;

            options.forEach(function(option) {
                if (!gradeLevelId || option.getAttribute('data-grade-level-id') === gradeLevelId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        // Apply filter on page load if grade level is pre-selected
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

            // Teacher searchable dropdown
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
