@extends('base')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-4">Settings</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="{{ route('admin.settings.index', ['panel' => 'teacher_enrollment']) }}"
                        class="list-group-item list-group-item-action {{ $panel === 'teacher_enrollment' ? 'active' : '' }}">
                        Teacher Enrollment
                    </a>
                    <a href="{{ route('admin.settings.index', ['panel' => 'assessment_weights']) }}"
                        class="list-group-item list-group-item-action {{ $panel === 'assessment_weights' ? 'active' : '' }}">
                        Assessment Weights
                    </a>
                    <a href="{{ route('admin.settings.index', ['panel' => 'school_year_month_days']) }}"
                        class="list-group-item list-group-item-action {{ $panel === 'school_year_month_days' ? 'active' : '' }}">
                        School Year Month Days
                    </a>
                </div>
            </div>
            <div class="col-md-9">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="panel" value="{{ $panel }}">

                    @if ($panel === 'teacher_enrollment')
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Teacher Enrollment Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="teacher_enrollment" class="form-label">Enable Teacher Enrollment</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="teacher_enrollment"
                                            id="teacher_enrollment" value="1"
                                            {{ $teacherEnrollment && filter_var($teacherEnrollment->value, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="teacher_enrollment">Allow teachers to manage
                                            enrollments</label>
                                    </div>
                                    <small class="text-muted">When enabled, teachers will be able to create and manage
                                        student enrollments in their assigned sections.</small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </div>
                    @endif

                    @if ($panel === 'assessment_weights')
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Assessment Weights</h5>
                            </div>
                            <div class="card-body">
                                @if (!$schoolYear)
                                    <div class="alert alert-warning">
                                        No active school year found. Please create a school year first.
                                    </div>
                                @elseif ($gradeLevelSubjects->isEmpty())
                                    <div class="alert alert-info">
                                        No subject assignments found. Please assign subjects to grade levels first.
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Grade Level</th>
                                                    <th>Subject</th>
                                                    <th>Written Works (%)</th>
                                                    <th>Performance Tasks (%)</th>
                                                    <th>Quarterly Assessments (%)</th>
                                                    <th>Total (%)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($gradeLevels as $gradeLevel)
                                                    @php
                                                        $subjects = $gradeLevelSubjects->get($gradeLevel->id, collect());
                                                    @endphp
                                                    @foreach ($subjects as $gls)
                                                        <tr class="{{ $gls->is_active ? '' : 'table-secondary' }}">
                                                            <td>{{ $gradeLevel->name }}</td>
                                                            <td>
                                                                {{ $gls->subject?->name ?? 'Unknown Subject' }}
                                                                @if (!$gls->is_active)
                                                                    <span class="badge bg-secondary">Inactive</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <input type="number"
                                                                    name="weights[{{ $gls->id }}][written_works_weight]"
                                                                    class="form-control form-control-sm weight-input"
                                                                    value="{{ $gls->written_works_weight }}" min="0"
                                                                    max="100"
                                                                    {{ !$gls->is_active ? 'readonly' : '' }}>
                                                            </td>
                                                            <td>
                                                                <input type="number"
                                                                    name="weights[{{ $gls->id }}][performance_tasks_weight]"
                                                                    class="form-control form-control-sm weight-input"
                                                                    value="{{ $gls->performance_tasks_weight }}" min="0"
                                                                    max="100"
                                                                    {{ !$gls->is_active ? 'readonly' : '' }}>
                                                            </td>
                                                            <td>
                                                                <input type="number"
                                                                    name="weights[{{ $gls->id }}][quarterly_assessments_weight]"
                                                                    class="form-control form-control-sm weight-input"
                                                                    value="{{ $gls->quarterly_assessments_weight }}"
                                                                    min="0" max="100"
                                                                    {{ !$gls->is_active ? 'readonly' : '' }}>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="weight-total {{ $gls->getTotalWeight() !== 100 ? 'text-danger' : 'text-success' }}">
                                                                    {{ $gls->getTotalWeight() }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted">Inactive subjects are shown muted and cannot be
                                        edited.</small>
                                @endif
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Weights</button>
                            </div>
                        </div>
                    @endif

                    @if ($panel === 'school_year_month_days')
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">School Year Month Days</h5>
                            </div>
                            <div class="card-body">
                                @if (!$schoolYear)
                                    <div class="alert alert-warning">
                                        No active school year found. Please create a school year first.
                                    </div>
                                @else
                                    <div class="mb-3">
                                        <strong>School Year:</strong> {{ $schoolYear->name }}
                                        <br>
                                        <strong>Period:</strong>
                                        {{ $schoolYear->start_date?->format('F j, Y') ?? 'N/A' }} -
                                        {{ $schoolYear->end_date?->format('F j, Y') ?? 'N/A' }}
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>School Days</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($monthsInRange as $month)
                                                    <tr>
                                                        <td>{{ App\Models\SchoolYearMonthDay::getMonthName($month) }}</td>
                                                        <td>
                                                            <input type="number"
                                                                name="school_days[{{ $month }}]"
                                                                class="form-control form-control-sm"
                                                                value="{{ $monthDays->get($month)?->school_days ?? 0 }}"
                                                                min="0" max="31">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save Month Days</button>
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weightInputs = document.querySelectorAll('.weight-input');

            weightInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const inputs = row.querySelectorAll('.weight-input');
                    let total = 0;
                    inputs.forEach(i => total += parseInt(i.value) || 0);

                    const totalSpan = row.querySelector('.weight-total');
                    totalSpan.textContent = total;
                    totalSpan.className = 'weight-total ' + (total === 100 ? 'text-success' : 'text-danger');
                });
            });
        });
    </script>
@endpush
