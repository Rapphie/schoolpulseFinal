<div class="d-flex flex-column gap-4">
    @if ($students->isEmpty())
        <div class="alert alert-info mb-0">
            <strong>No linked students yet.</strong> Please contact the school administrator to link your guardian account
            to a student profile.
        </div>
    @else
        @include('livewire.guardian.partials.student-selector')

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Grade Records</h5>
                <small class="text-muted">
                    {{ $selectedStudentSummary['full_name'] }}
                </small>
            </div>
            <div class="card-body">
                <ul class="nav nav-pills flex-wrap gap-2 mb-3">
                    @foreach ($quarterLabels as $quarterValue => $quarterLabel)
                        <li class="nav-item">
                            <button type="button"
                                class="nav-link {{ $selectedQuarter === $quarterValue ? 'active' : '' }}"
                                wire:click="setQuarter({{ $quarterValue }})"
                                wire:key="grades-quarter-tab-{{ $quarterValue }}">
                                {{ $quarterLabel }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th class="text-center">Grade</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($selectedQuarterGrades as $grade)
                                <tr wire:key="grades-row-{{ $selectedQuarter }}-{{ $grade['subject'] }}-{{ $loop->index }}">
                                    <td>{{ $grade['subject'] }}</td>
                                    <td>{{ $grade['teacher'] }}</td>
                                    <td class="text-center fw-semibold {{ ($grade['grade'] ?? 0) >= 75 ? 'text-success' : 'text-danger' }}">
                                        {{ $grade['grade'] ?? '—' }}
                                    </td>
                                    <td>{{ $grade['remarks'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No grades recorded for {{ $selectedQuarterLabel }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Quarter Average</th>
                                <th>{{ $selectedQuarterAverage !== null ? number_format($selectedQuarterAverage, 2) : '—' }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
