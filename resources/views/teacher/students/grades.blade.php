@extends('base')

@section('title', 'Grades for ' . $student->first_name . ' ' . $student->last_name)

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('teacher.students.index') }}">Student Profiles</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('teacher.students.show', $student) }}">{{ $student->first_name }}
                                {{ $student->last_name }}</a>
                        </li>
                        <li class="breadcrumb-item active">Grades ({{ $schoolYear->name }})</li>
                    </ol>
                </nav>
                <h4 class="mb-0">Academic Grades</h4>
            </div>
            <a href="{{ route('teacher.students.show', $student) }}" class="btn btn-outline-secondary btn-sm">
                <i data-feather="arrow-left" class="icon-sm me-1"></i> Back to Profile
            </a>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $student->first_name }} {{ $student->last_name }}</h5>
                    <p class="text-muted mb-0">
                        Grades for School Year: <strong>{{ $schoolYear->name }}</strong>
                        @if ($enrollment)
                            - {{ $enrollment->class->section->gradeLevel->name }} | {{ $enrollment->class->section->name }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="card-body">
                @if ($gradesBySubject->isEmpty())
                    <div class="text-center py-5">
                        <i data-feather="alert-circle" class="text-muted" style="width: 48px; height: 48px;"></i>
                        <h5 class="mt-3">No Grades Found</h5>
                        <p class="text-muted">There are no grade records available for this student for the selected school
                            year.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Subject</th>
                                    <th scope="col" class="text-center">1st Quarter</th>
                                    <th scope="col" class="text-center">2nd Quarter</th>
                                    <th scope="col" class="text-center">3rd Quarter</th>
                                    <th scope="col" class="text-center">4th Quarter</th>
                                    <th scope="col" class="text-center">Final Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gradesBySubject as $subject => $grades)
                                    <tr>
                                        <td><strong>{{ $subject }}</strong></td>
                                        @php
                                            $q1 = $grades->firstWhere('quarter', 1);
                                            $q2 = $grades->firstWhere('quarter', 2);
                                            $q3 = $grades->firstWhere('quarter', 3);
                                            $q4 = $grades->firstWhere('quarter', 4);

                                            // Ensure consistent display by applying the same
                                            // transmutation guard used in the report card view.
                                            // Grades stored via the current assessment system are
                                            // already transmuted (60-100), but legacy/imported
                                            // grades < 60 need re-transmutation for consistency.
                                            $normalise = function ($gradeValue) {
                                                if ($gradeValue !== null && $gradeValue < 60) {
                                                    return \App\Services\GradeService::transmute($gradeValue);
                                                }
                                                return $gradeValue;
                                            };

                                            $g1 = $normalise($q1->grade ?? null);
                                            $g2 = $normalise($q2->grade ?? null);
                                            $g3 = $normalise($q3->grade ?? null);
                                            $g4 = $normalise($q4->grade ?? null);

                                            $existing = array_filter([$g1, $g2, $g3, $g4], fn($v) => $v !== null);
                                            $finalGrade =
                                                count($existing) > 0 ? array_sum($existing) / count($existing) : null;
                                        @endphp
                                        <td class="text-center">{{ $g1 !== null ? round($g1) : '-' }}</td>
                                        <td class="text-center">{{ $g2 !== null ? round($g2) : '-' }}</td>
                                        <td class="text-center">{{ $g3 !== null ? round($g3) : '-' }}</td>
                                        <td class="text-center">{{ $g4 !== null ? round($g4) : '-' }}</td>
                                        <td class="text-center">
                                            {{ $finalGrade !== null ? number_format($finalGrade, 2) : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold">Final Average:</td>
                                    <td class="text-center fw-bold">
                                        @if ($profile && !is_null($profile->final_average))
                                            {{ number_format($profile->final_average, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
