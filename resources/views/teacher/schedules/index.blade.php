@extends('base')

@section('content')
    <div class="container-fluid">
        <div class="flex justify-between items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.schedules.index') }}">Schedules</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Overview</li>
                </ol>
            </nav>

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
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <style>
        .add-schedule-btn {
            background-color: #0d6efd !important;
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
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
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
                navLinks: false,
                editable: false,
                selectable: true,
                dayMaxEvents: true, // allow "more" link when too many events
                allDaySlot: false, // Don't show the all-day slot
                slotMinTime: '07:00:00', // Start the calendar at 7am
                slotMaxTime: '19:00:00', // End the calendar at 7pm
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
    </script>
@endpush
