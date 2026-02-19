<div class="d-flex flex-column gap-4">
    @if ($students->isEmpty())
        <div class="alert alert-info mb-0">
            <strong>No linked students yet.</strong> Please contact the school administrator to link your guardian account
            to a student profile.
        </div>
    @else
        @include('livewire.guardian.partials.student-selector')

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Attendance Summary</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($attendanceSummaryCards as $summaryCard)
                        @php
                            $statusColorMap = [
                                'present' => 'success',
                                'absent' => 'danger',
                                'late' => 'warning',
                                'excused' => 'info',
                            ];
                            $statusColor = $statusColorMap[$summaryCard['status']] ?? 'secondary';
                        @endphp
                        <div class="col-sm-6 col-lg-3" wire:key="attendance-summary-{{ $summaryCard['status'] }}">
                            <div class="card border-{{ $statusColor }} h-100">
                                <div class="card-body">
                                    <p class="text-uppercase small text-muted mb-2">{{ ucfirst($summaryCard['status']) }}</p>
                                    <h4 class="mb-1">{{ $summaryCard['count'] }}</h4>
                                    <p class="small mb-0 text-muted">{{ number_format($summaryCard['percentage'], 2) }}%</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Attendance Records</h5>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <div class="row g-3">
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="quarterFilter">Quarter</label>
                        <select id="quarterFilter" class="form-select" wire:model.live="quarterFilter">
                            <option value="all">All Quarters</option>
                            @foreach ($quarterLabels as $quarterValue => $quarterLabel)
                                <option value="{{ $quarterValue }}">{{ $quarterLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="statusFilter">Status</label>
                        <select id="statusFilter" class="form-select" wire:model.live="statusFilter">
                            <option value="all">All Statuses</option>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="excused">Excused</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label mb-1" for="dateFrom">Date From</label>
                        <input id="dateFrom" type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label mb-1" for="dateTo">Date To</label>
                        <input id="dateTo" type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label mb-1" for="perPage">Rows</label>
                        <select id="perPage" class="form-select" wire:model.live="perPage">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div wire:loading wire:target="quarterFilter,statusFilter,dateFrom,dateTo,perPage,selectedStudentId"
                    class="small text-primary">
                    Loading attendance records...
                </div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Quarter</th>
                                <th>Time In</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendanceRecords as $attendance)
                                @php
                                    $badgeClass = $statusVariants[$attendance['status']] ?? 'bg-secondary';
                                @endphp
                                <tr wire:key="attendance-row-{{ $attendance['formatted_date'] }}-{{ $loop->index }}">
                                    <td>{{ $attendance['formatted_date'] }}</td>
                                    <td>{{ $attendance['subject'] }}</td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($attendance['status']) }}</span>
                                    </td>
                                    <td>{{ $attendance['quarter'] }}</td>
                                    <td>{{ $attendance['time_in'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No attendance records match the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($attendanceRecords, 'links'))
                    <div class="d-flex justify-content-end">
                        {{ $attendanceRecords->links() }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
