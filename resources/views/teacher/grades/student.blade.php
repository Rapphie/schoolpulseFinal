@extends('base')

@section('title', 'Student Grades Preview')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('teacher.assessments.list') }}">Grades</a></li>
            <li class="breadcrumb-item"><a href="{{ route('teacher.grades.show', $class->id) }}">Class Grades</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $student->full_name }}</li>
        </ol>
    </nav>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-end gap-2 mb-3">
        <a href="{{ route('teacher.grades.show', $class->id) }}" class="btn btn-secondary">
            <i data-feather="arrow-left" class="feather-sm"></i> Back to Class
        </a>
        <a href="{{ route('teacher.assessments.index', ['class' => $class->id, 'highlight_student' => $student->id]) }}"
            class="btn btn-warning text-white">
            <i data-feather="edit-2" class="feather-sm text-white"></i> Edit Grades
        </a>
        <a href="{{ route('teacher.report-card.show', ['studentId' => $student->id]) }}" class="btn btn-primary"
            target="_blank">
            <i data-feather="download" class="feather-sm"></i> Download Report Card(.docx)
        </a>
    </div>

    {{-- Report Card Preview --}}
    <div class="card shadow">
        <div class="card-body p-0">
            <style>
                .report-card-preview {
                    font-family: 'Times New Roman', Times, serif;
                    background-color: white;
                    padding: 1.5rem;
                }

                .report-card-preview table {
                    border-collapse: collapse;
                    width: 100%;
                    font-size: 10pt;
                }

                .report-card-preview th,
                .report-card-preview td {
                    border: 1px solid #000;
                    padding: 4px 8px;
                    vertical-align: middle;
                }

                .report-card-preview .no-border {
                    border: none;
                }

                .report-card-preview .border-bottom-only {
                    border: none;
                    border-bottom: 1px solid #000;
                }

                .report-card-preview .text-center {
                    text-align: center;
                }

                .report-card-preview .font-bold {
                    font-weight: bold;
                }

                .report-card-preview .text-xs {
                    font-size: 9pt;
                }

                .report-card-preview .text-sm {
                    font-size: 10pt;
                }

                .report-card-preview .grade-passed {
                    color: #198754;
                }

                .report-card-preview .grade-failed {
                    color: #dc3545;
                }
            </style>

            <div class="report-card-preview">
                <div class="row">
                    {{-- LEFT COLUMN --}}
                    <div class="col-md-6">
                        {{-- Header --}}
                        <div class="text-center mb-3">
                            <p class="text-sm mb-0">Republic of the Philippines</p>
                            <p class="font-bold text-sm mb-0">Department of Education</p>
                            <p class="text-sm mb-0">REGION XI</p>
                            <p class="text-sm mb-0">PANABO CITY</p>
                            <p class="font-bold text-sm mb-0">STA. CRUZ ELEM. SCHOOL</p>
                        </div>

                        {{-- Student Info --}}
                        <table class="text-xs mb-3">
                            <tr>
                                <td class="no-border" style="width: 15%;">Name:</td>
                                <td class="no-border font-bold" colspan="3">{{ $student->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="no-border">Age:</td>
                                <td class="no-border font-bold">{{ $student->birthdate ? $student->birthdate->age : '' }}
                                </td>
                                <td class="no-border ps-4">Sex:</td>
                                <td class="no-border font-bold">{{ ucfirst($student->gender) }}</td>
                            </tr>
                            <tr>
                                <td class="no-border">Grade:</td>
                                <td class="no-border font-bold">{{ $class->section->gradeLevel->name ?? '' }}</td>
                                <td class="no-border ps-4">Section:</td>
                                <td class="no-border font-bold">{{ $class->section->name ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="no-border">LRN:</td>
                                <td class="no-border font-bold">{{ $student->lrn }}</td>
                                <td class="no-border ps-4">School Year:</td>
                                <td class="no-border font-bold">{{ $activeSchoolYear->name ?? '' }}</td>
                            </tr>
                        </table>

                        <div class="text-xs mb-3">
                            <p class="font-bold">Dear Parents:</p>
                            <p>This report card shows the ability and progress your child has made in the different learning
                                areas as well as his/her core values. The school welcomes you should you desire to know more
                                about your child's progress.</p>
                        </div>

                        {{-- Grades Table --}}
                        <div class="text-xs">
                            <p class="font-bold text-center mb-2">REPORT ON LEARNING PROGRESS AND ACHIEVEMENT</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th rowspan="2" style="width: 40%;">Learning Areas</th>
                                        <th colspan="4" class="text-center">Quarter</th>
                                        <th rowspan="2" class="text-center">Final Grade</th>
                                        <th rowspan="2" class="text-center">Remarks</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center">1</th>
                                        <th class="text-center">2</th>
                                        <th class="text-center">3</th>
                                        <th class="text-center">4</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($gradesData as $grade)
                                        <tr>
                                            <td>{{ $grade['subject_name'] }}</td>
                                            <td
                                                class="text-center {{ $grade['q1'] !== null && $grade['q1'] < 75 ? 'grade-failed' : '' }}">
                                                {{ $grade['q1'] ?? '' }}
                                            </td>
                                            <td
                                                class="text-center {{ $grade['q2'] !== null && $grade['q2'] < 75 ? 'grade-failed' : '' }}">
                                                {{ $grade['q2'] ?? '' }}
                                            </td>
                                            <td
                                                class="text-center {{ $grade['q3'] !== null && $grade['q3'] < 75 ? 'grade-failed' : '' }}">
                                                {{ $grade['q3'] ?? '' }}
                                            </td>
                                            <td
                                                class="text-center {{ $grade['q4'] !== null && $grade['q4'] < 75 ? 'grade-failed' : '' }}">
                                                {{ $grade['q4'] ?? '' }}
                                            </td>
                                            <td
                                                class="text-center font-bold {{ $grade['final_grade'] !== null && $grade['final_grade'] < 75 ? 'grade-failed' : 'grade-passed' }}">
                                                {{ $grade['final_grade'] ?? '' }}
                                            </td>
                                            <td
                                                class="text-center {{ $grade['remarks'] === 'Failed' ? 'grade-failed' : 'grade-passed' }}">
                                                {{ $grade['remarks'] }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No grades recorded yet.</td>
                                        </tr>
                                    @endforelse
                                    <tr>
                                        <td class="font-bold">General Average</td>
                                        <td colspan="4"></td>
                                        <td
                                            class="text-center font-bold {{ $generalAverage !== null && $generalAverage < 75 ? 'grade-failed' : 'grade-passed' }}">
                                            {{ $generalAverage ?? '' }}
                                        </td>
                                        <td
                                            class="text-center font-bold {{ $generalAverage !== null && $generalAverage < 75 ? 'grade-failed' : 'grade-passed' }}">
                                            @if ($generalAverage !== null)
                                                {{ $generalAverage >= 75 ? 'Passed' : 'Failed' }}
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Grading Scale --}}
                        <table class="mt-3 text-xs">
                            <thead>
                                <tr>
                                    <th>Descriptors</th>
                                    <th>Grading Scale</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Outstanding</td>
                                    <td>90-100</td>
                                    <td>Passed</td>
                                </tr>
                                <tr>
                                    <td>Very Satisfactory</td>
                                    <td>85-89</td>
                                    <td>Passed</td>
                                </tr>
                                <tr>
                                    <td>Satisfactory</td>
                                    <td>80-84</td>
                                    <td>Passed</td>
                                </tr>
                                <tr>
                                    <td>Fair Satisfactory</td>
                                    <td>75-79</td>
                                    <td>Passed</td>
                                </tr>
                                <tr>
                                    <td>Did not meet Expectation</td>
                                    <td>Below 75</td>
                                    <td>Failed</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- RIGHT COLUMN --}}
                    <div class="col-md-6">
                        {{-- Attendance Report --}}
                        <div class="text-xs">
                            <p class="font-bold text-center mb-2">REPORT ON ATTENDANCE</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th class="font-normal">Month</th>
                                        <th>Jun</th>
                                        <th>Jul</th>
                                        <th>Aug</th>
                                        <th>Sep</th>
                                        <th>Oct</th>
                                        <th>Nov</th>
                                        <th>Dec</th>
                                        <th>Jan</th>
                                        <th>Feb</th>
                                        <th>Mar</th>
                                        <th>Apr</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-xs">No. of School Days</td>
                                        <td class="text-center">{{ $attendanceData['jun']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['jul']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['aug']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['sep']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['oct']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['nov']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['dec']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['jan']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['feb']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['mar']['school_days'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['apr']['school_days'] ?? 0 }}</td>
                                        <td class="text-center font-bold">{{ $totalSchoolDays ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-xs">No. of Days Present</td>
                                        <td class="text-center">{{ $attendanceData['jun']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['jul']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['aug']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['sep']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['oct']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['nov']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['dec']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['jan']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['feb']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['mar']['present'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['apr']['present'] ?? 0 }}</td>
                                        <td class="text-center font-bold">{{ $totalDaysPresent ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-xs">No. of Days Absent</td>
                                        <td class="text-center">{{ $attendanceData['jun']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['jul']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['aug']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['sep']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['oct']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['nov']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['dec']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['jan']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['feb']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['mar']['absent'] ?? 0 }}</td>
                                        <td class="text-center">{{ $attendanceData['apr']['absent'] ?? 0 }}</td>
                                        <td class="text-center font-bold">{{ $totalDaysAbsent ?? 0 }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Core Values --}}
                        <div class="mt-4 text-xs">
                            <p class="font-bold text-center mb-2">REPORTS ON LEARNERS OBSERVED VALUES</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th rowspan="2">Core Values</th>
                                        <th rowspan="2" style="width: 50%;">Behavior Statement</th>
                                        <th colspan="4" class="text-center">Quarter</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center">1</th>
                                        <th class="text-center">2</th>
                                        <th class="text-center">3</th>
                                        <th class="text-center">4</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td rowspan="2">1. Maka-Diyos</td>
                                        <td class="text-xs">Expresses one's spiritual beliefs while respecting the
                                            spiritual beliefs of others.</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="text-xs">Shows adherence to ethical principles by upholding truth.</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td rowspan="2">2. Makatao</td>
                                        <td class="text-xs">Is sensitive to individual, social, and cultural differences
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="text-xs">Demonstrates contributions towards solidarity.</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>3. Maka-Kalikasan</td>
                                        <td class="text-xs">Cares for the environment and utilizes resources wisely,
                                            judiciously and economically.</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>4. Maka-Bansa</td>
                                        <td class="text-xs">Demonstrates pride in being a Filipino; exercises the rights
                                            and responsibilities of a Filipino citizen.</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Marking Legend --}}
                        <div class="d-flex justify-content-between mt-3 text-xs">
                            <div style="width: 48%;">
                                <p class="font-bold">Marking</p>
                                <p class="mb-0">AO</p>
                                <p class="mb-0">SO</p>
                                <p class="mb-0">RO</p>
                                <p class="mb-0">NO</p>
                            </div>
                            <div style="width: 48%;">
                                <p class="font-bold">Non-numerical Rating</p>
                                <p class="mb-0">Always Observed</p>
                                <p class="mb-0">Sometimes Observed</p>
                                <p class="mb-0">Rarely Observed</p>
                                <p class="mb-0">Not Observed</p>
                            </div>
                        </div>

                        {{-- Parent Signature --}}
                        <div class="mt-4 text-xs">
                            <p class="font-bold text-center mb-2">PARENT/GUARDIAN'S SIGNATURE</p>
                            <p class="mt-2">1st Quarter <span class="border-bottom-only d-inline-block"
                                    style="width: 60%; float: right;"></span></p>
                            <p class="mt-2">2nd Quarter <span class="border-bottom-only d-inline-block"
                                    style="width: 60%; float: right;"></span></p>
                            <p class="mt-2">3rd Quarter <span class="border-bottom-only d-inline-block"
                                    style="width: 60%; float: right;"></span></p>
                            <p class="mt-2">4th Quarter <span class="border-bottom-only d-inline-block"
                                    style="width: 60%; float: right;"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Grade Level History Section --}}
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i data-feather="bookmark" class="feather-sm me-2"></i>Grade Level History
            </h6>
            <span class="badge bg-secondary">{{ $gradeHistory->count() }} School Year(s)</span>
        </div>
        <div class="card-body">
            @if ($gradeHistory->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%">
                        <thead class="table-light">
                            <tr>
                                <th>School Year</th>
                                <th>Grade Level</th>
                                <th>Section</th>
                                <th>Final Average</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gradeHistory as $profile)
                                <tr @if ($profile->school_year_id === ($activeSchoolYear->id ?? null)) class="table-primary" @endif>
                                    <td>
                                        {{ $profile->schoolYear->name ?? 'N/A' }}
                                        @if ($profile->school_year_id === ($activeSchoolYear->id ?? null))
                                            <span class="badge bg-success ms-1">Current</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $profile->gradeLevel->name ?? 'N/A' }}</strong></td>
                                    <td>{{ $student->enrollments->firstWhere('school_year_id', $profile->school_year_id)?->class?->section?->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        @if ($profile->final_average)
                                            <span
                                                class="fw-bold {{ $profile->final_average >= 75 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($profile->final_average, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted">--</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'active' => 'primary',
                                                'promoted' => 'success',
                                                'retained' => 'warning',
                                                'transferred' => 'info',
                                                'dropped' => 'danger',
                                                'graduated' => 'success',
                                            ];
                                            $color = $statusColors[$profile->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ ucfirst($profile->status) }}</span>
                                    </td>
                                    <td>{{ $profile->remarks ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No grade level history found. Profile will be created upon enrollment.</p>
            @endif
        </div>
    </div>
@endsection
