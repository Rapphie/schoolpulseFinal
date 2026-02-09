@extends('base')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Edit Schedule</h1>
            <a href="{{ route('admin.schedules.index') }}"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Back to Schedules</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Row 1 -->
                    <div>
                        <label for="section_id" class="block text-sm font-medium text-gray-700">Student Group *</label>
                        <select id="section_id" name="section_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required>
                            <option value="">Select a group</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" data-grade-level-id="{{ $section->grade_level_id }}"
                                    {{ $schedule->section_id == $section->id ? 'selected' : '' }}>{{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="school_year" class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <input type="text" id="school_year" name="school_year" value="{{ $schedule->school_year }}"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-100 rounded-md shadow-sm"
                            readonly>
                    </div>

                    <!-- Row 2 -->
                    <div>
                        <label for="subject_id" class="block text-sm font-medium text-gray-700">Course *</label>
                        <select id="subject_id" name="subject_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required>
                            <option value="">Select a course</option>
                            {{-- Options will be populated by JavaScript --}}
                        </select>
                    </div>
                    <div>
                        <label for="quarter" class="block text-sm font-medium text-gray-700">Academic Term</label>
                        <select id="quarter" name="quarter"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required>
                            <option value="1" {{ $schedule->quarter == 1 ? 'selected' : '' }}>Term 1</option>
                            <option value="2" {{ $schedule->quarter == 2 ? 'selected' : '' }}>Term 2</option>
                            <option value="3" {{ $schedule->quarter == 3 ? 'selected' : '' }}>Term 3</option>
                            <option value="4" {{ $schedule->quarter == 4 ? 'selected' : '' }}>Term 4</option>
                        </select>
                    </div>

                    <!-- Row 3 -->
                    <div>
                        <label for="grade_level" class="block text-sm font-medium text-gray-700">Program</label>
                        <input type="text" id="grade_level" name="grade_level"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-100 rounded-md shadow-sm"
                            readonly>
                    </div>
                    <div></div> <!-- Empty cell for alignment -->

                    <!-- Divider -->
                    <div class="col-span-2 my-4 border-t border-gray-200"></div>

                    <!-- Row 4 -->
                    <div>
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700">Instructor *</label>
                        <select id="teacher_id" name="teacher_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required>
                            <option value="">Select an instructor</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}"
                                    {{ $schedule->teacher_id == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->first_name }} {{ $teacher->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="room" class="block text-sm font-medium text-gray-700">Room *</label>
                        <input type="text" id="room" name="room" value="{{ $schedule->room }}"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required>
                    </div>

                    <!-- Row 5 -->
                    <div>
                        <label for="instructor_name" class="block text-sm font-medium text-gray-700">Instructor Name</label>
                        <input type="text" id="instructor_name" name="instructor_name"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-100 rounded-md shadow-sm"
                            readonly>
                    </div>
                    <div>
                        <label for="class_schedule_color" class="block text-sm font-medium text-gray-700">Class Schedule
                            Color</label>
                        <select id="class_schedule_color" name="class_schedule_color"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="orange">Orange</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="red">Red</option>
                        </select>
                    </div>

                    <!-- Divider -->
                    <div class="col-span-2 my-4 border-t border-gray-200"></div>

                    <!-- Row 6: Days of the week -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Day of the Week</label>
                        <select name="day_of_week"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="Monday" {{ $schedule->day_of_week == 'Monday' ? 'selected' : '' }}>Monday
                            </option>
                            <option value="Tuesday" {{ $schedule->day_of_week == 'Tuesday' ? 'selected' : '' }}>Tuesday
                            </option>
                            <option value="Wednesday" {{ $schedule->day_of_week == 'Wednesday' ? 'selected' : '' }}>
                                Wednesday</option>
                            <option value="Thursday" {{ $schedule->day_of_week == 'Thursday' ? 'selected' : '' }}>Thursday
                            </option>
                            <option value="Friday" {{ $schedule->day_of_week == 'Friday' ? 'selected' : '' }}>Friday
                            </option>
                            <option value="Saturday" {{ $schedule->day_of_week == 'Saturday' ? 'selected' : '' }}>Saturday
                            </option>
                            <option value="Sunday" {{ $schedule->day_of_week == 'Sunday' ? 'selected' : '' }}>Sunday
                            </option>
                        </select>
                    </div>

                    <!-- Row 7: Time -->
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700">From Time *</label>
                        <select id="start_time" name="start_time"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required></select>
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">To Time *</label>
                        <select id="end_time" name="end_time"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required></select>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

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

            const sectionSelect = document.getElementById('section_id');
            const subjectSelect = document.getElementById('subject_id');
            const gradeLevelInput = document.getElementById('grade_level');
            const teacherSelect = document.getElementById('teacher_id');
            const instructorNameInput = document.getElementById('instructor_name');

            const subjects = @json($subjects->groupBy('grade_level_id'));
            const gradeLevels = @json($gradeLevels->keyBy('id'));
            const teachers = @json($teachers->keyBy('id'));
            const schedule = @json($schedule);

            // Pre-select existing time values
            const startTimeSel = document.getElementById('start_time');
            const endTimeSel = document.getElementById('end_time');
            if (startTimeSel && schedule.start_time) {
                startTimeSel.value = schedule.start_time.substring(0, 5);
            }
            if (endTimeSel && schedule.end_time) {
                endTimeSel.value = schedule.end_time.substring(0, 5);
            }

            function populateSubjects() {
                const selectedOption = sectionSelect.options[sectionSelect.selectedIndex];
                const gradeLevelId = selectedOption.getAttribute('data-grade-level-id');

                // Update Program/Grade Level field
                if (gradeLevelId && gradeLevels[gradeLevelId]) {
                    gradeLevelInput.value = gradeLevels[gradeLevelId].name;
                } else {
                    gradeLevelInput.value = '';
                }

                // Update Subjects dropdown
                subjectSelect.innerHTML = '<option value="">Select a course</option>';
                if (gradeLevelId && subjects[gradeLevelId]) {
                    subjects[gradeLevelId].forEach(function(subject) {
                        const option = new Option(subject.name, subject.id);
                        if (schedule.subject_id == subject.id) {
                            option.selected = true;
                        }
                        subjectSelect.add(option);
                    });
                    subjectSelect.disabled = false;
                } else {
                    subjectSelect.disabled = true;
                }
            }

            sectionSelect.addEventListener('change', populateSubjects);

            teacherSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                instructorNameInput.value = selectedOption.text;
            });

            // Initial population
            populateSubjects();
            const selectedTeacherOption = teacherSelect.options[teacherSelect.selectedIndex];
            if (selectedTeacherOption) {
                instructorNameInput.value = selectedTeacherOption.text;
            }
        });
    </script>
@endsection
