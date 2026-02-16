@extends('base')

@section('title', 'Least Learned Competencies Report')

@section('internal-css')
    <style>
        .report-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
        }

        .competency-card {
            transition: all 0.3s ease;
        }

        .competency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0.15rem rgba(58, 59, 69, 0.2);
        }

        .progress {
            height: 10px;
        }

        .progress-bar-warning {
            background-color: #f6c23e;
        }

        .progress-bar-danger {
            background-color: #e74a3b;
        }

        .chart-container {
            height: 300px;
        }
    </style>
@endsection

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Least Learned Competencies Report</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.least-learned') }}" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section" required>
                            <option value="">Select Section</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}"
                                    {{ $selectedSection == $section->id ? 'selected' : '' }}>
                                    {{ $section->name }}
                                    ({{ $section->gradeLevel ? 'Grade ' . $section->gradeLevel->level : 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="">Select Subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}"
                                    {{ $selectedSubject == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="quarter" class="form-label">Quarter</label>
                        <select class="form-select" id="quarter" name="quarter">
                            @foreach ($quarters as $quarter)
                                <option value="{{ $quarter }}" {{ $selectedQuarter == $quarter ? 'selected' : '' }}>
                                    {{ $quarter }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i data-feather="filter"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>

            @if ($llcData)
                <div class="alert alert-info mb-4">
                    <h5>LLC Data Summary</h5>
                    <p><strong>Subject:</strong> {{ $llcData->subject->name }}</p>
                    <p><strong>Teacher:</strong> {{ $llcData->teacher->user->first_name }}
                        {{ $llcData->teacher->user->last_name }}</p>
                    <p><strong>Quarter:</strong> {{ $selectedQuarter }}</p>
                    <p><strong>Total Students:</strong> {{ $llcData->total_students }}</p>
                    <p><strong>Total Items:</strong> {{ $llcData->total_items }}</p>
                </div>

                @if ($llcItems->count() > 0)
                    <div class="card report-card mb-4">
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="llcChart"></canvas>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <div class="card report-card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Least Learned Competencies Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item Number</th>
                                    <th>Category Name</th>
                                    <th>Item Range</th>
                                    <th>Students Wrong</th>
                                    <th>Mastery Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($llcItems as $item)
                                    @php
                                        $masteryRate = $llcData
                                            ? (($llcData->total_students - $item->students_wrong) /
                                                    $llcData->total_students) *
                                                100
                                            : 0;
                                        $progressColor =
                                            $masteryRate >= 75
                                                ? 'bg-success'
                                                : ($masteryRate >= 50
                                                    ? 'bg-warning'
                                                    : 'bg-danger');
                                    @endphp
                                    <tr>
                                        <td>{{ $item->item_number }}</td>
                                        <td>{{ $item->category_name }}</td>
                                        <td>Items {{ $item->item_start }} - {{ $item->item_end }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-danger">{{ $item->students_wrong }}</span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar {{ $progressColor }}" role="progressbar"
                                                    style="width: {{ $masteryRate }}%"
                                                    aria-valuenow="{{ $masteryRate }}" aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    {{ number_format($masteryRate, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            @if ($selectedSection && $selectedSubject)
                                                No LLC data found for the selected filters.
                                            @else
                                                Please select a section and subject to view LLC data.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();

            @if ($llcItems->count() > 0 && $llcData)
                // Initialize Chart with real data
                const ctx = document.getElementById('llcChart').getContext('2d');

                const labels = @json($llcItems->pluck('category_name'));
                const studentsWrong = @json($llcItems->pluck('students_wrong'));
                const totalStudents = {{ $llcData->total_students }};

                // Calculate mastery rates
                const masteryRates = studentsWrong.map(wrong => {
                    return ((totalStudents - wrong) / totalStudents * 100).toFixed(1);
                });

                // Determine colors based on mastery rate
                const backgroundColors = masteryRates.map(rate => {
                    if (rate >= 75) return 'rgba(28, 200, 138, 0.8)';
                    if (rate >= 50) return 'rgba(246, 194, 62, 0.8)';
                    return 'rgba(231, 74, 59, 0.8)';
                });

                const borderColors = masteryRates.map(rate => {
                    if (rate >= 75) return 'rgb(28, 200, 138)';
                    if (rate >= 50) return 'rgb(246, 194, 62)';
                    return 'rgb(231, 74, 59)';
                });

                const llcChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Mastery Rate (%)',
                            data: masteryRates,
                            backgroundColor: backgroundColors,
                            borderColor: borderColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Mastery Rate (%)'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Category'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.raw + '% mastery rate';
                                    }
                                }
                            }
                        }
                    }
                });
            @endif
        });
    </script>
@endpush
