@extends('base')

@section('title', 'Attendance Pattern')

@section('content')
    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">View Attendance Pattern</h6>
            </div>
            <div class="card-body">
                <p>Select filters to view the monthly attendance patterns of students you teach.</p>
                <form method="GET" action="{{ route('teacher.attendance.pattern') }}" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="grade_level_id" class="form-label">Grade Level</label>
                            <select name="grade_level_id" id="grade_level_id" class="form-select">
                                <option value="">-- All Grades --</option>
                                @foreach ($gradeLevels as $gradeLevel)
                                    <option value="{{ $gradeLevel->id }}"
                                        {{ request('grade_level_id') == $gradeLevel->id ? 'selected' : '' }}>
                                        {{ $gradeLevel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="section_id" class="form-label">Section</label>
                            <select name="section_id" id="section_id" class="form-select">
                                <option value="">-- All Sections --</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}"
                                        {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                        {{ $section->gradeLevel->name }} - {{ $section->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select name="subject_id" id="subject_id" class="form-select">
                                <option value="">-- All Subjects --</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}"
                                        {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-1">
                            {{-- This link will be updated by the script below --}}
                            <a href="#" id="exportBtn" class="btn btn-success w-100" title="Export">
                                <i data-feather="download" class="feather-sm"></i>
                            </a>
                        </div>
                    </div>
                </form>

                @if (request()->hasAny(['grade_level_id', 'section_id', 'subject_id']))
                    @if ($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" style="font-size: 0.8rem;">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th class="align-middle" rowspan="2">Student Name</th>
                                        @php
                                            $months = [
                                                'June',
                                                'July',
                                                'August',
                                                'September',
                                                'October',
                                                'November',
                                                'December',
                                                'January',
                                                'February',
                                                'March',
                                                'April',
                                                'May',
                                            ];
                                        @endphp
                                        @foreach ($months as $month)
                                            <th class="align-middle" colspan="5">{{ $month }}</th>
                                        @endforeach
                                        <th class="align-middle" rowspan="2">Grand Total</th>
                                    </tr>
                                    <tr class="text-center">
                                        @foreach ($months as $month)
                                            <th>P</th>
                                            <th>A</th>
                                            <th>L</th>
                                            <th>E</th>
                                            <th>Total</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($students as $student)
                                        @php $grandTotal = 0; @endphp
                                        <tr>
                                            <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                            @foreach ($months as $month)
                                                @php
                                                    $presentCount = 0;
                                                    $absentCount = 0;
                                                    $lateCount = 0;
                                                    $excusedCount = 0;
                                                    $monthlyTotal = 0;

                                                    if (
                                                        isset($student->attendance_summary[$month]) &&
                                                        $student->attendance_summary[$month]->count() > 0
                                                    ) {
                                                        $presentCount = $student->attendance_summary[$month]
                                                            ->where('status', 'present')
                                                            ->count();
                                                        $absentCount = $student->attendance_summary[$month]
                                                            ->where('status', 'absent')
                                                            ->count();
                                                        $lateCount = $student->attendance_summary[$month]
                                                            ->where('status', 'late')
                                                            ->count();
                                                        $excusedCount = $student->attendance_summary[$month]
                                                            ->where('status', 'excused')
                                                            ->count();
                                                        $monthlyTotal =
                                                            $presentCount + $absentCount + $lateCount + $excusedCount;
                                                        $grandTotal += $monthlyTotal;
                                                    }
                                                @endphp
                                                <td class="text-center">{{ $presentCount > 0 ? $presentCount : '-' }}</td>
                                                <td class="text-center">{{ $absentCount > 0 ? $absentCount : '-' }}</td>
                                                <td class="text-center">{{ $lateCount > 0 ? $lateCount : '-' }}</td>
                                                <td class="text-center">{{ $excusedCount > 0 ? $excusedCount : '-' }}</td>
                                                <td class="text-center table-active">
                                                    <strong>{{ $monthlyTotal > 0 ? $monthlyTotal : '-' }}</strong>
                                                </td>
                                            @endforeach
                                            <td class="text-center table-primary">
                                                <strong>{{ $grandTotal > 0 ? $grandTotal : '-' }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mt-3">No students found for the selected criteria. Please try a
                            different filter.</div>
                    @endif
                @else
                    <div class="alert alert-secondary mt-3">Please select a filter to view attendance patterns.</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Feather icons replacement
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            const gradeLevelFilter = document.getElementById('grade_level_id');
            const sectionFilter = document.getElementById('section_id');
            const subjectFilter = document.getElementById('subject_id');
            const exportBtn = document.getElementById('exportBtn');

            function updateExportLink() {
                // This is the correct route name for the export function
                const baseUrl = "{{ route('teacher.attendance.pattern.export') }}";
                const params = new URLSearchParams();

                if (gradeLevelFilter.value) {
                    params.append('grade_level_id', gradeLevelFilter.value);
                }
                if (sectionFilter.value) {
                    params.append('section_id', sectionFilter.value);
                }
                if (subjectFilter.value) {
                    params.append('subject_id', subjectFilter.value);
                }

                // Update the href attribute of the export button
                exportBtn.href = `${baseUrl}?${params.toString()}`;
            }

            // Update the link immediately when the page loads
            updateExportLink();

            // Add event listeners to update the link whenever a filter is changed
            gradeLevelFilter.addEventListener('change', updateExportLink);
            sectionFilter.addEventListener('change', updateExportLink);
            subjectFilter.addEventListener('change', updateExportLink);
        });
    </script>
@endpush
