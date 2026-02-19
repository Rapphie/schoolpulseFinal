<div class="d-flex flex-column gap-4">
    @if ($students->isEmpty())
        <div class="alert alert-info mb-0">
            <strong>No linked students yet.</strong> Please contact the school administrator to link your guardian account
            to a student profile.
        </div>
    @else
        @include('livewire.guardian.partials.student-selector')

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold"
                            style="width: 56px; height: 56px;">
                            {{ $studentInitials }}
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $selectedStudentSummary['full_name'] }}</h4>
                            <p class="text-muted mb-0">
                                {{ $selectedStudentSummary['grade_level'] }}
                                <span class="mx-1">·</span>
                                {{ $selectedStudentSummary['class_section'] }}
                            </p>
                        </div>
                    </div>
                    <div class="text-muted small text-md-end">
                        <div><strong>Student ID:</strong> {{ $selectedStudentSummary['student_identifier'] }}</div>
                        <div><strong>LRN:</strong> {{ $selectedStudentSummary['lrn'] }}</div>
                        <div><strong>School Year:</strong> {{ $selectedStudentSummary['school_year'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-start border-4 border-primary shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">GWA</p>
                        <h4 class="mb-0">{{ $gwa !== null ? number_format($gwa, 2) : '—' }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-start border-4 border-success shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Present Days</p>
                        <h4 class="mb-0">{{ $attendanceSummary['present'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-start border-4 border-danger shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Absent Days</p>
                        <h4 class="mb-0">{{ $attendanceSummary['absent'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-start border-4 border-info shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted text-uppercase small mb-2">Attendance Rate</p>
                        <h4 class="mb-0">{{ number_format($attendanceRate, 2) }}%</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Grades Snapshot</h5>
                            <small class="text-muted">
                                {{ $latestQuarterLabel ? $latestQuarterLabel : 'No quarter with recorded grades yet' }}
                            </small>
                        </div>
                        <a href="{{ route('guardian.grades') }}" class="btn btn-sm btn-outline-primary">View All Grades</a>
                    </div>
                    <div class="card-body">
                        @if ($latestQuarterGrades->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Subject</th>
                                            <th class="text-center">Grade</th>
                                            <th>Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($latestQuarterGrades as $grade)
                                            <tr wire:key="dashboard-grade-{{ $grade['subject'] }}-{{ $loop->index }}">
                                                <td>{{ $grade['subject'] }}</td>
                                                <td class="text-center fw-semibold">{{ $grade['grade'] ?? '—' }}</td>
                                                <td>{{ $grade['remarks'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                No grades have been recorded for the selected student yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Quarter Averages</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            @foreach ($quarterAverages as $quarterLabel => $quarterAverage)
                                <div class="d-flex justify-content-between align-items-center border rounded px-3 py-2"
                                    wire:key="dashboard-quarter-average-{{ $quarterLabel }}">
                                    <span>{{ $quarterLabel }}</span>
                                    <span class="fw-semibold">{{ $quarterAverage !== null ? number_format($quarterAverage, 2) : '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Attendance Snapshot</h5>
                        <a href="{{ route('guardian.attendance') }}" class="btn btn-sm btn-outline-primary">View Full
                            Attendance</a>
                    </div>
                    <div class="card-body">
                        @if ($recentAttendanceRecords->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Quarter</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recentAttendanceRecords as $attendance)
                                            @php
                                                $badgeClass = $statusVariants[$attendance['status']] ?? 'bg-secondary';
                                            @endphp
                                            <tr wire:key="dashboard-attendance-{{ $attendance['formatted_date'] }}-{{ $loop->index }}">
                                                <td>{{ $attendance['formatted_date'] }}</td>
                                                <td>{{ $attendance['subject'] }}</td>
                                                <td>
                                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($attendance['status']) }}</span>
                                                </td>
                                                <td>{{ $attendance['quarter'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                No attendance logs are available for the selected student.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        @if ($recentActivities->isNotEmpty())
                            <div class="list-group list-group-flush">
                                @foreach ($recentActivities as $activity)
                                    <div class="list-group-item px-0" wire:key="dashboard-activity-{{ $activity['type'] }}-{{ $loop->index }}">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <p class="mb-1 fw-semibold">{{ $activity['title'] }}</p>
                                                <p class="mb-0 text-muted small">{{ $activity['description'] }}</p>
                                            </div>
                                            <span class="text-muted small">{{ $activity['occurred_at_label'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                No recent activity available.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
