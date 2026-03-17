@extends('base')

@section('title', 'Add New Schedule')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.schedules.index') }}">Schedules</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add New Schedule</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Create New Schedule Entry</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.schedules.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="grade_level_id" class="form-label">1. Select Grade Level <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="grade_level_id" name="grade_level_id" required>
                                <option value="">-- Select a grade level --</option>
                                @foreach ($gradeLevels as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="class_id" class="form-label">2. Select Class <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">-- Select a grade level first --</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subject_id" class="form-label">3. Select Subject <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">-- Select a grade level first --</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="teacher_id" class="form-label">4. Assign Teacher <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" name="teacher_id" required>
                                <option value="">-- Select a teacher --</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->user->first_name }}
                                        {{ $teacher->user->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">5. Day(s) of the Week <span class="text-danger">*</span></label>
                        <div>
                            @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="day_of_week[]"
                                        value="{{ $day }}" id="day_{{ $day }}">
                                    <label class="form-check-label"
                                        for="day_{{ $day }}">{{ ucfirst($day) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <select class="form-select" name="start_time" id="start_time" required></select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <select class="form-select" name="end_time" id="end_time" required></select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="room" class="form-label">Room (Optional)</label>
                            <input type="text" class="form-control" name="room">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('admin.schedules.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ── Time dropdown population (15-min intervals, 7 AM – 5 PM) ──
            function populateTimeDropdowns(startId, endId) {
                const startSel = document.getElementById(startId);
                const endSel = document.getElementById(endId);
                if (!startSel || !endSel) return;
                startSel.innerHTML = '<option value="">-- Select --</option>';
                endSel.innerHTML = '<option value="">-- Select --</option>';
                let cur = new Date();
                cur.setHours(7, 0, 0, 0);
                const stop = new Date();
                stop.setHours(17, 0, 0, 0);
                while (cur <= stop) {
                    const h = cur.getHours(),
                        m = cur.getMinutes();
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const dh = h % 12 === 0 ? 12 : h % 12;
                    const dm = m < 10 ? '0' + m : m;
                    const label = `${dh}:${dm} ${ampm}`;
                    const val = `${h < 10 ? '0'+h : h}:${dm}`;
                    startSel.add(new Option(label, val));
                    endSel.add(new Option(label, val));
                    cur.setMinutes(m + 15);
                }
            }
            populateTimeDropdowns('start_time', 'end_time');

            const gradeLevelSelect = document.getElementById('grade_level_id');
            const classSelect = document.getElementById('class_id');
            const subjectSelect = document.getElementById('subject_id');
            const oldGradeLevel = @json(old('grade_level_id'));
            const oldClass = @json(old('class_id'));
            const oldSubject = @json(old('subject_id'));

            // Pass the full collections from PHP to JavaScript
            const classes = @json($classes);
            const subjects = @json($subjects);

            gradeLevelSelect.addEventListener('change', function() {
                const gradeId = parseInt(this.value, 10);

                // Reset class and subject options when grade level changes.
                classSelect.innerHTML = '<option value="">-- Select a grade level first --</option>';
                subjectSelect.innerHTML = '<option value="">-- Select a grade level first --</option>';

                if (gradeId) {
                    // Populate Classes
                    classSelect.innerHTML = '<option value="">-- Select a class --</option>';
                    const filteredClasses = classes.filter(c => c.section.grade_level_id === gradeId);
                    if (filteredClasses.length > 0) {
                        filteredClasses.forEach(function(classItem) {
                            const option = new Option(
                                `${classItem.section.grade_level.name} - ${classItem.section.name}`,
                                classItem.id);
                            classSelect.add(option);
                        });
                    } else {
                        classSelect.innerHTML = '<option value="">No classes found for this grade</option>';
                    }

                    // Populate Subjects
                    subjectSelect.innerHTML = '<option value="">-- Select a subject --</option>';
                    const filteredSubjects = subjects.filter(s => s.grade_level_id === gradeId);
                    if (filteredSubjects.length > 0) {
                        filteredSubjects.forEach(function(subject) {
                            const option = new Option(subject.name, subject.id);
                            subjectSelect.add(option);
                        });
                    } else {
                        subjectSelect.innerHTML =
                            '<option value="">No subjects found for this grade</option>';
                    }
                }
            });

            if (oldGradeLevel) {
                gradeLevelSelect.value = String(oldGradeLevel);
                gradeLevelSelect.dispatchEvent(new Event('change'));

                if (oldClass) {
                    classSelect.value = String(oldClass);
                }

                if (oldSubject) {
                    subjectSelect.value = String(oldSubject);
                }
            }
        });
    </script>
@endpush
