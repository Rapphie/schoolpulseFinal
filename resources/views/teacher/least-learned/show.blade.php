@extends('base')

@section('title', 'Least Learned Report')

@push('styles')
    <style>
        .llc-summary-card li+li {
            margin-top: 0.35rem;
        }

        .category-badge.bad {
            background-color: #fde2e1;
            color: #b91c1c;
        }

        .category-badge.good {
            background-color: #dcfce7;
            color: #065f46;
        }

        .item-mastery-pill {
            font-size: 0.85rem;
            border-radius: 999px;
            padding: 0.25rem 0.75rem;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 mb-1">Least Learned Insights</h1>
            <p class="text-muted mb-0">{{ $llc->subject->name ?? 'Subject' }} ·
                {{ $llc->section->gradeLevel->name ?? 'Grade Level' }} — {{ $llc->section->name ?? 'Section' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('teacher.least-learned.index') }}" class="btn btn-outline-secondary">
                <i data-feather="arrow-left" class="me-1"></i>Back to Module
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm llc-summary-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Assessment Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li><strong>Assessment:</strong> {{ $llc->exam_title ?? 'Assessment' }}</li>
                        <li><strong>Quarter:</strong> {{ $quarters[$llc->quarter] ?? 'Quarter ' . $llc->quarter }}</li>
                        <li><strong>Subject:</strong> {{ $llc->subject->name ?? 'N/A' }}</li>
                        <li><strong>Section:</strong> {{ $llc->section->name ?? 'N/A' }}</li>
                        <li><strong>Grade Level:</strong> {{ $llc->section->gradeLevel->name ?? 'N/A' }}</li>
                        <li><strong>Date Logged:</strong> {{ $llc->created_at?->format('M d, Y') ?? '—' }}</li>
                        <li><strong>Total Students:</strong> {{ $llc->total_students }}</li>
                        <li><strong>Total Items:</strong> {{ $llc->total_items }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Category Mastery Overview</h5>
                    <span class="text-muted small">% of students who answered incorrectly per category</span>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="categoryMasteryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $insightPlaybook = [
            'Vocabulary' => 'Use picture cues, realia, and daily vocabulary journals to widen word exposure.',
            'Grammar' => 'Chunk lessons by rule, model corrections, and provide immediate feedback drills.',
            'Reading Comprehension' =>
                'Integrate prediction, questioning, and summarizing strategies in guided reading.',
            'Problem Solving' => 'Have learners explain solution steps aloud and compare multiple solution paths.',
            'Number Sense' => 'Use manipulatives and estimation warm-ups to rebuild foundational understanding.',
            'Geometry' => 'Connect concepts to real objects and encourage sketching before computation.',
            'Scientific Method' => 'Guide students through mini-investigations that highlight each inquiry step.',
        ];
    @endphp

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Category-Level Insights</h5>
        </div>
        <div class="card-body">
            @forelse ($categorySummary as $idx => $category)
                <div class="border rounded-3 p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-1">{{ $category['category'] }}</h6>
                            <small class="text-muted">Items
                                {{ $category['item_start'] }}–{{ $category['item_end'] }}</small>
                        </div>
                        @php
                            $avgWrong = $category['avg_wrong_percentage'];
                            $badgeClass = $avgWrong > 50 ? 'bad' : 'good';
                            $mastery = max(0, 100 - $avgWrong);
                        @endphp
                        <span class="badge category-badge {{ $badgeClass }}">{{ number_format($avgWrong, 1) }}%
                            incorrect ·
                            {{ number_format($mastery, 1) }}% mastery</span>
                    </div>
                    <p class="mb-2 small text-muted">Total wrong responses: {{ $category['total_wrong'] }} out of
                        {{ $llc->total_students * ($category['item_end'] - $category['item_start'] + 1) }}</p>
                    <div class="mb-3">
                        @foreach ($category['items'] as $item)
                            <span class="badge bg-light text-dark item-mastery-pill me-2 mb-2">Item
                                {{ $item['item_number'] }} · {{ $item['students_wrong'] }} wrong
                                ({{ number_format($item['mastery_rate'], 1) }}% mastery)
                            </span>
                        @endforeach
                    </div>
                    <div class="alert {{ $avgWrong > 50 ? 'alert-danger' : 'alert-success' }} mb-0">
                        <strong>Action Step:</strong>
                        {{ $insightPlaybook[$category['category']] ?? 'Revisit this strand using varied modalities, then reassess quickly to capture improvement.' }}
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No recorded items for this analysis.</p>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('categoryMasteryChart');
            if (!ctx) {
                return;
            }
            const labels = @json($categorySummary->pluck('category'));
            const dataset = @json($categorySummary->pluck('avg_wrong_percentage')->map(fn($value) => round($value, 2)));
            const backgroundColors = dataset.map(value => value > 50 ? 'rgba(231, 74, 59, 0.8)' :
                'rgba(28, 200, 138, 0.8)');
            const borderColors = backgroundColors.map(color => color.replace('0.8', '1'));

            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: '% of students who struggled',
                        data: dataset,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: value => `${value}%`
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.raw}% of students answered incorrectly`
                            }
                        }
                    }
                }
            });

            if (window.feather) {
                feather.replace();
            }
        });
    </script>
@endpush
