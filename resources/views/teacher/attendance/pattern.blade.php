@extends('base')

@section('title', 'Attendance Pattern')

@section('content')
    <div class="container">
        <h1>Attendance Pattern</h1>

        <form method="GET" action="{{ route('teacher.attendance.pattern') }}">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="grade_level_id">Grade Level</label>
                        <select name="grade_level_id" id="grade_level_id" class="form-control">
                            <option value="">All</option>
                            @foreach ($gradeLevels as $gradeLevel)
                                <option value="{{ $gradeLevel->id }}" {{ request('grade_level_id') == $gradeLevel->id ? 'selected' : '' }}>
                                    {{ $gradeLevel->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="section_id">Section</label>
                        <select name="section_id" id="section_id" class="form-control">
                            <option value="">All</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                    {{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="subject_id">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-control">
                            <option value="">All</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Filter</button>
        </form>

        @if ($students->count() > 0)
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        @php
                            $months = ['June', 'July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March'];
                        @endphp
                        @foreach ($months as $month)
                            <th>{{ $month }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        <tr>
                            <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                            @foreach ($months as $month)
                                <td>
                                    @if (isset($student->attendance[$month]))
                                        {{ $student->attendance[$month]->where('status', 'present')->count() }} Present
                                        <br>
                                        {{ $student->attendance[$month]->where('status', 'absent')->count() }} Absent
                                        <br>
                                        {{ $student->attendance[$month]->where('status', 'late')->count() }} Late
                                    @else
                                        N/A
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="mt-3">No students found for the selected criteria.</p>
        @endif
    </div>
@endsection
