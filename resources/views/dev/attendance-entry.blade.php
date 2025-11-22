@php
    $saved = session('saved');
    $summary = session('summary') ?? ($summary ?? null);
    $prediction = session('prediction') ?? ($prediction ?? null);
    $features_named = session('features_named') ?? ($features_named ?? null);
@endphp
@extends('base')
@section('content')
    <div class="container py-4">
        <h1 class="h4 mb-3">Temporary Attendance Entry</h1>
        <p class="text-muted">Enter a student's attendance for a given date. Data is normalized and monthly summary updates
            below.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($saved)
            <div class="alert alert-success">
                Saved attendance #{{ $saved['id'] }} for student ID {{ $saved['student_id'] }} (Status:
                {{ ucfirst($saved['status']) }})
            </div>
        @endif

        @if ($prediction)
            <div class="alert alert-info">
                Prediction confidence: <strong>{{ number_format($prediction['prediction_confidence'] ?? 0, 2) }}%</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('dev.attendance.store') }}" class="card mb-4">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">-- Select Student --</option>
                            @foreach ($students as $s)
                                <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ $s->last_name }},
                                    {{ $s->first_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Select Subject --</option>
                            @foreach ($subjects as $sub)
                                <option value="{{ $sub->id }}" @selected(old('subject_id') == $sub->id)>{{ $sub->name }}
                                    ({{ $sub->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teacher</label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Teacher ID --</option>
                            @foreach ($teachers as $t)
                                <option value="{{ $t->id }}" @selected(old('teacher_id') == $t->id)>Teacher
                                    #{{ $t->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Class ID --</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}" @selected(old('class_id') == $c->id)>Class
                                    #{{ $c->id }} (Section {{ $c->section_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                            class="form-control" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <input type="text" name="status" value="{{ old('status') }}"
                            placeholder="present/absent/late/excused" class="form-control" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quarter (optional)</label>
                        <input type="text" name="quarter" value="{{ old('quarter') }}" class="form-control"
                            placeholder="Q1,Q2,..." />
                    </div>
                    <div class="col-md-12">
                        <button class="btn btn-primary">Save Attendance</button>
                        <a href="{{ route('dev.attendance.form') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Monthly Summary</span>
                <form method="GET" action="{{ route('dev.attendance.form') }}" class="d-flex gap-2 align-items-center">
                    <select name="student_id" class="form-select form-select-sm" style="width:auto" required>
                        <option value="">Student...</option>
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected(request('student_id') == $s->id)>{{ $s->last_name }},
                                {{ $s->first_name }}</option>
                        @endforeach
                    </select>
                    <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}"
                        class="form-control form-control-sm" style="width:auto" />
                    <input type="hidden" name="date" value="{{ request('month', now()->format('Y-m')) }}-01" />
                    <button class="btn btn-outline-primary btn-sm">Refresh</button>
                </form>
            </div>
            <div class="card-body">
                @if ($summary)
                    <table class="table table-sm mb-3">
                        <tr>
                            <th>Month</th>
                            <td>{{ $summary['month'] }}</td>
                        </tr>
                        <tr>
                            <th>Total Records</th>
                            <td>{{ $summary['total'] }}</td>
                        </tr>
                        <tr>
                            <th>Present</th>
                            <td>{{ $summary['present'] }} (Rate: {{ $summary['present_rate'] }})</td>
                        </tr>
                        <tr>
                            <th>Unexcused Absences</th>
                            <td>{{ $summary['absent'] }} (Rate: {{ $summary['unexcused_absent_rate'] }})</td>
                        </tr>
                        <tr>
                            <th>Excused Absences</th>
                            <td>{{ $summary['excused'] }} (Rate: {{ $summary['excused_absent_rate'] }})</td>
                        </tr>
                        <tr>
                            <th>Late</th>
                            <td>{{ $summary['late'] }} (Rate: {{ $summary['late_rate'] }})</td>
                        </tr>
                    </table>
                    <p class="text-muted mb-0">All rates are counts divided by total records in the month.</p>
                @else
                    <p class="text-muted mb-0">Select a student and month to view summary.</p>
                @endif
            </div>
        </div>

        @if ($features_named)
            <div class="card mt-3">
                <div class="card-header">Features used for prediction</div>
                <div class="card-body">
                    @php
                        $order = [



                        
                        ];
                        $fmt = function ($k, $v) {
                            $isRate = str_contains($k, 'rate') || str_contains($k, 'avg');
                            return $isRate ? number_format((float) $v, 3) : number_format((float) $v, 0);
                        };
                    @endphp
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Feature</th>
                                <th class="text-end">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order as $key)
                                @if (array_key_exists($key, $features_named))
                                    <tr>
                                        <td><code>{{ $key }}</code></td>
                                        <td class="text-end">{{ $fmt($key, $features_named[$key]) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
@endsection
