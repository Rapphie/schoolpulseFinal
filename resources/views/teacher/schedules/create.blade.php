@extends('base')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Class scheduling</h1>
            <a href="{{ route('admin.schedules.index') }}"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-decoration-none">Back to
                Schedules</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <form action="{{ route('admin.schedules.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Grade Level -->
                    <div>
                        <label for="grade_level_id" class="block text-sm font-medium text-gray-700">Grade Level *</label>
                        <select id="grade_level_id" name="grade_level_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required>
                            <option value="">Select a grade level</option>
                            @foreach ($gradeLevels as $gradeLevel)
                                <option value="{{ $gradeLevel->id }}">{{ $gradeLevel->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div>
                        <label for="section_id" class="block text-sm font-medium text-gray-700">Section *</label>
                        <select id="section_id" name="section_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required disabled>
                            <option value="">Select a grade level first</option>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label for="subject_id" class="block text-sm font-medium text-gray-700">Subject *</label>
                        <select id="subject_id" name="subject_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required disabled>
                            <option value="">Select a grade level first</option>
                        </select>
                    </div>

                    <!-- Instructor -->
                    <div>
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700">Instructor *</label>
                        <select id="teacher_id" name="teacher_id"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            required>
                            <option value="">Select an instructor</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->user->first_name }}
                                    {{ $teacher->user->last_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Room -->
                    <div>
                        <label for="room" class="block text-sm font-medium text-gray-700">Room</label>
                        <input type="text" id="room" name="room"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <!-- School Year (Read-only) -->
                    <div>
                        <label for="school_year" class="block text-sm font-medium text-gray-700">School Year</label>
                        <input type="text" id="school_year" name="school_year"
                            class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-100 rounded-md shadow-sm"
                            readonly>
                    </div>

                    <!-- Divider -->
                    <div class="col-span-2 my-4 border-t border-gray-200"></div>

                    <!-- Days of the week -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Days of the Week *</label>
                        <div class="mt-2 grid grid-cols-3 md:grid-cols-7 gap-4">
                            <div><input type="checkbox" name="day_of_week[]" value="monday" class="form-checkbox"> Monday
                            </div>
                            <div><input type="checkbox" name="day_of_week[]" value="tuesday" class="form-checkbox"> Tuesday
                            </div>
                            <div><input type="checkbox" name="day_of_week[]" value="wednesday" class="form-checkbox">
                                Wednesday</div>
                            <div><input type="checkbox" name="day_of_week[]" value="thursday" class="form-checkbox">
                                Thursday</div>
                            <div><input type="checkbox" name="day_of_week[]" value="friday" class="form-checkbox"> Friday
                            </div>
                        </div>
                    </div>

                    <!-- Time -->
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
                        Schedule class
                    </button>
                </div>
            </form>
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

            // ── Subject duration auto-calc ──
            const subjectsByGradeFlat = @json($subjects);
            const subjectSelectForDuration = document.getElementById('subject_id');
            const startTimeForDuration = document.getElementById('start_time');
            const endTimeForDuration = document.getElementById('end_time');

            function getSelectedDuration() {
                if (!subjectSelectForDuration) return null;
                const id = parseInt(subjectSelectForDuration.value, 10);
                const subj = subjectsByGradeFlat.find(s => s.id === id);
                return subj?.duration_minutes || null;
            }

            function autoCalcEndTime() {
                const dur = getSelectedDuration();
                if (!dur || !startTimeForDuration || !startTimeForDuration.value) return;
                const [sh, sm] = startTimeForDuration.value.split(':').map(Number);
                const end = new Date();
                end.setHours(sh, sm + dur, 0, 0);
                const eh = end.getHours(),
                    em = end.getMinutes();
                const endVal = `${eh < 10 ? '0'+eh : eh}:${em < 10 ? '0'+em : em}`;
                if (endTimeForDuration) {
                    const opt = Array.from(endTimeForDuration.options).find(o => o.value === endVal);
                    if (opt) endTimeForDuration.value = endVal;
                }
            }

            if (subjectSelectForDuration) subjectSelectForDuration.addEventListener('change', autoCalcEndTime);
            if (startTimeForDuration) startTimeForDuration.addEventListener('change', autoCalcEndTime);

            const gradeLevelSelect = document.getElementById('grade_level_id');
            const sectionSelect = document.getElementById('section_id');
            const subjectSelect = document.getElementById('subject_id');
            const schoolYearInput = document.getElementById('school_year');

            const sectionsByGrade = @json($sections->groupBy('grade_level_id'));
            const subjectsByGrade = @json($subjects->groupBy('grade_level_id'));

            // Set Academic Year
            const currentYear = new Date().getFullYear();
            schoolYearInput.value = `${currentYear}-${currentYear + 1}`;

            gradeLevelSelect.addEventListener('change', function() {
                const gradeLevelId = this.value;

                // Clear and update sections
                sectionSelect.innerHTML = '<option value="">Select a section</option>';
                if (gradeLevelId && sectionsByGrade[gradeLevelId]) {
                    sectionsByGrade[gradeLevelId].forEach(function(section) {
                        const option = new Option(section.name, section.id);
                        sectionSelect.add(option);
                    });
                    sectionSelect.disabled = false;
                } else {
                    sectionSelect.disabled = true;
                    sectionSelect.innerHTML = '<option value="">Select a grade level first</option>';
                }

                // Clear and update subjects
                subjectSelect.innerHTML = '<option value="">Select a subject</option>';
                if (gradeLevelId && subjectsByGrade[gradeLevelId]) {
                    subjectsByGrade[gradeLevelId].forEach(function(subject) {
                        const option = new Option(subject.name, subject.id);
                        subjectSelect.add(option);
                    });
                    subjectSelect.disabled = false;
                } else {
                    subjectSelect.disabled = true;
                    subjectSelect.innerHTML = '<option value="">Select a grade level first</option>';
                }
            });
        });
    </script>
@endpush
