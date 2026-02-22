@extends('base')

@section('title', 'Enroll Past Students')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Students from Previous School Year Not Yet Enrolled</h5>
                </div>
                <div class="card-body">
                    @php
                        $isPromotionOpen = $currentSchoolYear?->is_promotion_open ?? false;
                    @endphp

                    @if (!$isPromotionOpen && $students->isNotEmpty())
                        <div class="alert alert-warning">
                            <strong>Promotion Not Enabled</strong><br>
                            Returning students from previous school years cannot be re-enrolled at this time. 
                            Promotion must be enabled in the school year settings to allow returning students to enroll.
                        </div>
                    @endif

                    @if ($students->isEmpty())
                        <p>No students found from the previous school year who are not yet enrolled in the current
                            school year.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover{{ !$isPromotionOpen ? ' opacity-50' : '' }}">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Last Enrolled School Year</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($students as $student)
                                        <tr>
                                            <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                            <td>{{ $student->enrollments->last()->schoolYear->name ?? 'N/A' }}</td>
                                            <td>
                                                @if ($isPromotionOpen)
                                                    <form action="{{ route('teacher.enrollment.storePastStudent') }}"
                                                        method="POST">
                                                        @csrf
                                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                        <div class="d-flex gap-2 align-items-center">
                                                            <select name="class_id" id="class_id_{{ $student->id }}"
                                                                class="form-select form-select-sm" style="width: auto;">
                                                                <option value="">Select Class</option>
                                                                @foreach (\App\Models\Classes::where('school_year_id', \App\Models\SchoolYear::active()->first()->id ?? null)->with('section.gradeLevel')->get()->sortBy('section.gradeLevel.level') as $class)
                                                                    <option value="{{ $class->id }}">
                                                                        {{ $class->section->gradeLevel->name }} -
                                                                        {{ $class->section->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                Enroll
                                                            </button>
                                                        </div>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
