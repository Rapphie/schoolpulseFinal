@extends('base')

@section('title', 'Edit Schedule')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.schedules.index') }}">Schedules</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Schedule</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Update Schedule Entry</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="grade_level_id" class="form-label">1. Select Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade_level_id" required>
                                <option value="">-- Select a grade level --</option>
                                @foreach ($gradeLevels as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="class_id" class="form-label">2. Select Class <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_id" name="class_id" required disabled>
                                <option value="">-- Select a grade level first --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">3. Select Subject <span class="text-danger">*</span></label>
                            <select class="form-select" id="subject_id" name="subject_id" required disabled>
                                <option value="">-- Select a grade level first --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacher_id" class="form-label">4. Assign Teacher <span class="text-danger">*</span></label>
                            <select class="form-select" id="teacher_id" name="teacher_id">
                                <option value="">-- Select a teacher --</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ $schedule->teacher_id == $teacher->id ? 'selected' : '' }}>
                                        {{ optional($teacher->user)->first_name ? optional($teacher->user)->first_name . ' ' . optional($teacher->user)->last_name : 'Teacher #' . $teacher->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @php
                        $selectedDays = collect($schedule->day_of_week ?? [])->map(fn($day) => strtolower((string) $day))->all();
                    @endphp

                    <div class="mb-3">
                        <label class="form-label">5. Day(s) of the Week <span class="text-danger">*</span></label>
                        <div>
                            @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="day_of_week[]" value="{{ $day }}"
                                        id="day_{{ $day }}" {{ in_array($day, $selectedDays, true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="day_{{ $day }}">{{ ucfirst($day) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <select class="form-select" id="start_time" name="start_time" required></select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <select class="form-select" id="end_time" name="end_time" required></select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="room" class="form-label">Room (Optional)</label>
                            <input type="text" class="form-control" id="room" name="room" value="{{ $schedule->room }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $schedulePayload = [
            'class_id' => $schedule->class_id,
            'subject_id' => $schedule->subject_id,
            'start_time' => optional($schedule->start_time)->format('H:i:s'),
            'end_time' => optional($schedule->end_time)->format('H:i:s'),
        ];
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gradeLevelSelect = document.getElementById('grade_level_id');
            const classSelect = document.getElementById('class_id');
            const subjectSelect = document.getElementById('subject_id');
            const startTimeSelect = document.getElementById('start_time');
            const endTimeSelect = document.getElementById('end_time');

            const classes = @json($classes->values());
            const subjects = @json($subjects->values());
            const schedule = @json($schedulePayload);

            function populateTimeDropdowns() {
                startTimeSelect.innerHTML = '<option value="">-- Select --</option>';
                endTimeSelect.innerHTML = '<option value="">-- Select --</option>';

                const cursor = new Date();
                cursor.setHours(7, 0, 0, 0);

                const stop = new Date();
                stop.setHours(17, 0, 0, 0);

                while (cursor <= stop) {
                    const hours = cursor.getHours();
                    const minutes = cursor.getMinutes();
                    const amPm = hours >= 12 ? 'PM' : 'AM';
                    const displayHours = hours % 12 === 0 ? 12 : hours % 12;
                    const displayMinutes = minutes < 10 ? `0${minutes}` : `${minutes}`;
                    const label = `${displayHours}:${displayMinutes} ${amPm}`;
                    const value = `${hours < 10 ? '0' + hours : hours}:${displayMinutes}`;

                    startTimeSelect.add(new Option(label, value));
                    endTimeSelect.add(new Option(label, value));

                    cursor.setMinutes(minutes + 15);
                }

                const startTime = (schedule.start_time || '').substring(0, 5);
                const endTime = (schedule.end_time || '').substring(0, 5);
                if (startTime) {
                    startTimeSelect.value = startTime;
                }
                if (endTime) {
                    endTimeSelect.value = endTime;
                }
            }

            function populateByGrade(selectedGradeLevelId) {
                classSelect.innerHTML = '<option value="">-- Select a class --</option>';
                subjectSelect.innerHTML = '<option value="">-- Select a subject --</option>';

                if (!selectedGradeLevelId) {
                    classSelect.disabled = true;
                    subjectSelect.disabled = true;
                    return;
                }

                const gradeLevelId = parseInt(selectedGradeLevelId, 10);

                const filteredClasses = classes.filter(item =>
                    item.section && item.section.grade_level_id === gradeLevelId
                );
                filteredClasses.forEach(item => {
                    const gradeName = item.section && item.section.grade_level ? item.section.grade_level.name : 'Grade';
                    const sectionName = item.section ? item.section.name : `Class ${item.id}`;
                    classSelect.add(new Option(`${gradeName} - ${sectionName}`, item.id));
                });

                const filteredSubjects = subjects.filter(item => item.grade_level_id === gradeLevelId);
                filteredSubjects.forEach(item => {
                    subjectSelect.add(new Option(item.name, item.id));
                });

                classSelect.disabled = filteredClasses.length === 0;
                subjectSelect.disabled = filteredSubjects.length === 0;

                if (schedule.class_id) {
                    classSelect.value = String(schedule.class_id);
                }
                if (schedule.subject_id) {
                    subjectSelect.value = String(schedule.subject_id);
                }
            }

            gradeLevelSelect.addEventListener('change', function() {
                populateByGrade(this.value);
            });

            const currentClass = classes.find(item => item.id === Number(schedule.class_id));
            const currentGradeLevelId = currentClass && currentClass.section ? currentClass.section.grade_level_id : null;
            if (currentGradeLevelId) {
                gradeLevelSelect.value = String(currentGradeLevelId);
                populateByGrade(currentGradeLevelId);
            }

            populateTimeDropdowns();
        });
    </script>
@endpush
