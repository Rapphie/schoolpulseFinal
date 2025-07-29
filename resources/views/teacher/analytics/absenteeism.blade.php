@extends('base')

@section('title', 'Absenteeism Analytics')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Absenteeism Analytics</h1>
        </div>

        <div class="row">
            <!-- Monthly Attendance Trend -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Rate (%)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <div id="monthlyTrendChart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Absences by Subject -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Absences by Subject</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2">
                            <div id="subjectAbsenceChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Absentees -->
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Students with Most Absences</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student Name</th>
                                        <th>lrn</th>
                                        <th class="text-center">Total Absences</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topAbsentees as $student)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                            <td>{{ $student->lrn }}</td>
                                            <td class="text-center">{{ $student->absent_count }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No absence data available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Make sure you have ApexCharts available in your base layout or include it here --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Monthly Attendance Trend Chart
            const monthlyTrendData = @json($monthlyTrend);
            const monthlyTrendOptions = {
                series: [{
                    name: 'Attendance Rate',
                    data: Object.values(monthlyTrendData)
                }],
                chart: {
                    height: 350,
                    type: 'area',
                    toolbar: {
                        show: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth'
                },
                xaxis: {
                    type: 'category',
                    categories: Object.keys(monthlyTrendData)
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0) + "%";
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val.toFixed(2) + "%"
                        }
                    }
                }
            };
            const monthlyTrendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"),
                monthlyTrendOptions);
            monthlyTrendChart.render();


            // 2. Absences by Subject Chart
            const subjectAbsenceData = @json($absencesBySubject);
            const subjectAbsenceOptions = {
                series: Object.values(subjectAbsenceData),
                chart: {
                    height: 350,
                    type: 'donut',
                },
                labels: Object.keys(subjectAbsenceData),
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };
            const subjectAbsenceChart = new ApexCharts(document.querySelector("#subjectAbsenceChart"),
                subjectAbsenceOptions);
            subjectAbsenceChart.render();
        });
    </script>
@endpush
