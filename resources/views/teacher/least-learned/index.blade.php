@extends('base')

@section('title', 'Least Learned Competencies - Item Analysis')

@push('styles')
    <style>
        /* Excel-like Spreadsheet Styling */
        .llc-spreadsheet-container {
            overflow-x: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
        }

        .llc-spreadsheet {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .llc-spreadsheet th,
        .llc-spreadsheet td {
            border: 1px solid #dee2e6;
            padding: 0;
            text-align: center;
            min-width: 50px;
            height: 36px;
        }

        .llc-spreadsheet thead th {
            background: linear-gradient(180deg, #4e73df 0%, #3a5bc7 100%);
            color: #fff;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
            padding: 8px 4px;
        }

        .llc-spreadsheet thead th.item-header {
            min-width: 80px;
            font-size: 12px;
        }

        .llc-spreadsheet thead th.category-header {
            background: #1cc88a;
            font-size: 12px;
            white-space: nowrap;
        }

        .llc-spreadsheet tbody td {
            background: #fff;
        }

        .llc-spreadsheet tbody td.item-number-cell {
            background: linear-gradient(180deg, #4e73df 0%, #3a5bc7 100%);
            color: #fff;
            font-weight: 600;
            text-align: center;
            padding: 8px 12px;
            white-space: nowrap;
            min-width: 80px;
        }

        .llc-spreadsheet tbody td.category-cell {
            background: #e8f4f8;
            font-weight: 500;
            font-size: 12px;
            text-align: left;
            padding: 8px 12px;
            min-width: 150px;
        }

        .llc-spreadsheet tbody td.input-cell {
            min-width: 120px;
        }

        .llc-spreadsheet tbody tr:hover td {
            background: #f0f4ff;
        }

        .llc-spreadsheet tbody tr:hover td.item-number-cell {
            background: linear-gradient(180deg, #5a7fe0 0%, #4a6bd0 100%);
        }

        .llc-spreadsheet tbody tr:hover td.mastery-high {
            background: #c3e6cb !important;
        }

        .llc-spreadsheet tbody tr:hover td.mastery-medium {
            background: #ffeeba !important;
        }

        .llc-spreadsheet tbody tr:hover td.mastery-low {
            background: #f5c6cb !important;
        }

        /* Input cells styling */
        .llc-spreadsheet .item-input {
            width: 100%;
            height: 100%;
            border: none;
            text-align: center;
            font-size: 13px;
            padding: 8px 4px;
            background: transparent;
        }

        .llc-spreadsheet .item-input:focus {
            outline: 2px solid #4e73df;
            background: #e8f0fe;
        }

        .llc-spreadsheet .item-input::-webkit-inner-spin-button,
        .llc-spreadsheet .item-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Mastery rate cells */
        .llc-spreadsheet td.mastery-cell {
            font-weight: 600;
            font-size: 12px;
        }

        .llc-spreadsheet td.mastery-high {
            background: #d4edda !important;
            color: #155724;
        }

        .llc-spreadsheet td.mastery-medium {
            background: #fff3cd !important;
            color: #856404;
        }

        .llc-spreadsheet td.mastery-low {
            background: #f8d7da !important;
            color: #721c24;
        }

        /* Summary row */
        .llc-spreadsheet tfoot td {
            background: #f8f9fc;
            font-weight: 600;
            padding: 10px 12px;
        }

        .llc-spreadsheet tfoot td.summary-label {
            text-align: left;
            padding-left: 12px;
            background: #e9ecef;
        }

        .llc-spreadsheet tfoot tr.category-summary-row td {
            background: #e8f4f8;
            border-top: 2px solid #1cc88a;
        }

        .llc-spreadsheet tfoot tr.category-summary-row td:first-child {
            background: #1cc88a;
            color: #fff;
        }

        /* Card styling */
        .llc-card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: none;
            border-radius: 12px;
        }

        .llc-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 16px 20px;
        }

        /* Category builder */
        .category-tag {
            display: inline-flex;
            align-items: center;
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 20px;
            padding: 6px 12px;
            margin: 4px;
            font-size: 13px;
        }

        .category-tag .tag-name {
            font-weight: 600;
            margin-right: 8px;
        }

        .category-tag .tag-range {
            color: #6c757d;
            margin-right: 8px;
        }

        .category-tag .tag-remove {
            background: none;
            border: none;
            color: #dc3545;
            padding: 0 4px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
        }

        /* Quick stats */
        .quick-stat {
            background: #f8f9fc;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }

        .quick-stat .stat-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .quick-stat .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quick-stat.stat-danger .stat-value {
            color: #e74a3b;
        }

        .quick-stat.stat-success .stat-value {
            color: #1cc88a;
        }

        .quick-stat.stat-warning .stat-value {
            color: #f6c23e;
        }

        /* Least learned list */
        .least-learned-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: #fef2f2;
            border-left: 4px solid #e74a3b;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .least-learned-item .item-info {
            font-weight: 600;
        }

        .least-learned-item .item-mastery {
            background: #e74a3b;
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* History table */
        .llc-history-table th,
        .llc-history-table td {
            vertical-align: middle;
        }

        /* Instructions */
        .instruction-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .instruction-step .step-number {
            background: #4e73df;
            color: #fff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .instruction-step .step-text {
            font-size: 13px;
            color: #5a5c69;
        }

        /* Responsive */
        @media (max-width: 768px) {

            .llc-spreadsheet th.item-header,
            .llc-spreadsheet td {
                min-width: 45px;
            }
        }
    </style>
@endpush

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i data-feather="check-circle" class="me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i data-feather="alert-circle" class="me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Setup Section --}}
    <div class="card llc-card mb-4" id="setupCard">
        <div class="card-header">
            <h5 class="mb-0"><i data-feather="settings" class="me-2"></i>Assessment Setup</h5>
        </div>
        <div class="card-body">
            <form id="setupForm">
                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Quarter</label>
                        <select id="quarter" class="form-select" required>
                            @foreach ($quarters as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Grade Level</label>
                        <select id="grade_level_id" class="form-select" required>
                            <option value="">Select...</option>
                            @foreach ($gradeLevels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Section</label>
                        <select id="section_id" class="form-select" required disabled>
                            <option value="">Select grade first</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Subject</label>
                        <select id="subject_id" class="form-select" required disabled>
                            <option value="">Select section first</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Total Students</label>
                        <input type="number" id="totalStudents" class="form-control" min="1" max="100"
                            value="30" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Total Items</label>
                        <input type="number" id="totalItems" class="form-control" min="1" max="100"
                            value="20" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Assessment Name (optional)</label>
                        <input type="text" id="examTitle" class="form-control"
                            placeholder="e.g., 1st Periodic Test, Quiz 1">
                    </div>
                </div>

                {{-- Category Builder --}}
                <div class="border rounded p-3 bg-light mb-3">
                    <h6 class="mb-3"><i data-feather="layers" class="me-1"></i>Map Competency Categories to Items</h6>
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label small">Competency/Category Name</label>
                            <input type="text" id="categoryName" class="form-control form-control-sm"
                                placeholder="e.g., Number Sense, Reading Comprehension">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">From Item #</label>
                            <input type="number" id="itemStart" class="form-control form-control-sm" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">To Item #</label>
                            <input type="number" id="itemEnd" class="form-control form-control-sm" min="1">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="addCategoryBtn" class="btn btn-primary btn-sm w-100">
                                <i data-feather="plus" class="me-1"></i>Add
                            </button>
                        </div>
                    </div>
                    <div id="categoriesContainer">
                        <p class="text-muted small mb-0" id="noCategoriesMsg">
                            <i data-feather="info" class="me-1"></i>No categories added yet. Add categories to map test
                            items to competencies.
                        </p>
                    </div>
                </div>

                <div class="text-end">
                    <button type="button" id="generateSpreadsheetBtn" class="btn btn-success" disabled>
                        <i data-feather="grid" class="me-1"></i>Generate Spreadsheet
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Spreadsheet Section --}}
    <div class="card llc-card mb-4" id="spreadsheetCard" style="display: none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1"><i data-feather="edit-3" class="me-2"></i>Item Analysis Spreadsheet</h5>
                <small class="text-muted">Enter the number of students who got each item <strong>WRONG</strong>. Press Tab
                    to move between cells.</small>
            </div>
            <div>
                <button type="button" id="backToSetupBtn" class="btn btn-outline-secondary btn-sm me-2">
                    <i data-feather="arrow-left" class="me-1"></i>Back to Setup
                </button>
                <button type="button" id="clearAllBtn" class="btn btn-outline-danger btn-sm">
                    <i data-feather="trash-2" class="me-1"></i>Clear All
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="llc-spreadsheet-container" style="max-height: 500px; overflow: auto;">
                <table class="llc-spreadsheet" id="analysisSpreadsheet">
                    <thead id="spreadsheetHead"></thead>
                    <tbody id="spreadsheetBody"></tbody>
                    <tfoot id="spreadsheetFoot"></tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Analysis Results --}}
    <div class="card llc-card mb-4" id="resultsCard" style="display: none;">
        <div class="card-header">
            <h5 class="mb-0"><i data-feather="pie-chart" class="me-2"></i>Analysis Results</h5>
        </div>
        <div class="card-body">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="quick-stat stat-primary">
                        <div class="stat-value" id="statTotalItems">0</div>
                        <div class="stat-label">Total Items</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="quick-stat stat-success">
                        <div class="stat-value" id="statMasteredItems">0</div>
                        <div class="stat-label">Mastered Items (≥75%)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="quick-stat stat-danger">
                        <div class="stat-value" id="statLeastLearned">0</div>
                        <div class="stat-label">Least Learned (&lt;75%)</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="quick-stat stat-warning">
                        <div class="stat-value" id="statOverallMastery">0%</div>
                        <div class="stat-label">Overall Mastery</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <h6 class="mb-3"><i data-feather="alert-triangle" class="me-1 text-danger"></i>Least Learned Items
                        (Need Remediation)</h6>
                    <div id="leastLearnedList">
                        <p class="text-muted">No items flagged yet. Enter data in the spreadsheet above.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="mb-3"><i data-feather="bar-chart" class="me-1"></i>Mastery by Category</h6>
                    <div style="height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 pt-4 border-top">
                <form id="saveAnalysisForm" method="POST" action="{{ route('teacher.least-learned.store') }}">
                    @csrf
                    <input type="hidden" name="quarter" id="hiddenQuarter">
                    <input type="hidden" name="grade_level_id" id="hiddenGradeLevel">
                    <input type="hidden" name="section_id" id="hiddenSection">
                    <input type="hidden" name="subject_id" id="hiddenSubject">
                    <input type="hidden" name="total_students" id="hiddenTotalStudents">
                    <input type="hidden" name="total_items" id="hiddenTotalItems">
                    <input type="hidden" name="exam_title" id="hiddenExamTitle">
                    <input type="hidden" name="categories_payload" id="hiddenCategoriesPayload">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i data-feather="save" class="me-2"></i>Save Item Analysis Report
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- History Section --}}
    <div class="card llc-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0"><i data-feather="clock" class="me-2"></i>Previous Analyses</h5>
                <small class="text-muted">View and review your saved item analysis reports.</small>
            </div>
            <form method="GET" action="{{ route('teacher.least-learned.index') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <select name="filter_section" class="form-select form-select-sm">
                        <option value="">All sections</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}"
                                {{ (int) ($filters['section'] ?? 0) === (int) $section->id ? 'selected' : '' }}>
                                {{ $section->name }}
                                @if ($section->gradeLevel)
                                    ({{ $section->gradeLevel->name }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="filter_subject" class="form-select form-select-sm">
                        <option value="">All subjects</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}"
                                {{ (int) ($filters['subject'] ?? 0) === (int) $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="filter_quarter" class="form-select form-select-sm">
                        <option value="">All quarters</option>
                        @foreach ($quarters as $value => $label)
                            <option value="{{ $value }}"
                                {{ (int) ($filters['quarter'] ?? 0) === (int) $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover llc-history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Assessment</th>
                            <th>Section</th>
                            <th>Subject</th>
                            <th>Quarter</th>
                            <th>Items</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($llcRecords as $record)
                            <tr>
                                <td>{{ $record->created_at?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    <strong>{{ $record->exam_title ?? 'Assessment' }}</strong>
                                    <div class="text-muted small">{{ $record->total_students }} students ·
                                        {{ $record->total_items }} items</div>
                                </td>
                                <td>
                                    {{ $record->section->name ?? 'N/A' }}
                                    <div class="text-muted small">
                                        {{ $record->section->gradeLevel->name ?? 'Grade' }}
                                    </div>
                                </td>
                                <td>{{ $record->subject->name ?? 'N/A' }}</td>
                                <td>{{ $quarters[$record->quarter] ?? 'Quarter ' . $record->quarter }}</td>
                                <td>{{ $record->llc_items_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('teacher.least-learned.show', $record) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i data-feather="eye" class="me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i data-feather="inbox" class="mb-2" style="width: 48px; height: 48px;"></i>
                                    <p class="mb-0">No item analysis reports saved yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $llcRecords->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const setupCard = document.getElementById('setupCard');
            const spreadsheetCard = document.getElementById('spreadsheetCard');
            const resultsCard = document.getElementById('resultsCard');

            const gradeLevelSelect = document.getElementById('grade_level_id');
            const sectionSelect = document.getElementById('section_id');
            const subjectSelect = document.getElementById('subject_id');
            const totalStudentsInput = document.getElementById('totalStudents');
            const totalItemsInput = document.getElementById('totalItems');
            const examTitleInput = document.getElementById('examTitle');
            const quarterSelect = document.getElementById('quarter');

            const categoryNameInput = document.getElementById('categoryName');
            const itemStartInput = document.getElementById('itemStart');
            const itemEndInput = document.getElementById('itemEnd');
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            const categoriesContainer = document.getElementById('categoriesContainer');
            const noCategoriesMsg = document.getElementById('noCategoriesMsg');
            const generateSpreadsheetBtn = document.getElementById('generateSpreadsheetBtn');

            const spreadsheetHead = document.getElementById('spreadsheetHead');
            const spreadsheetBody = document.getElementById('spreadsheetBody');
            const spreadsheetFoot = document.getElementById('spreadsheetFoot');
            const backToSetupBtn = document.getElementById('backToSetupBtn');
            const clearAllBtn = document.getElementById('clearAllBtn');

            const sectionsEndpoint = "{{ route('teacher.sections.by-grade-level') }}";
            const subjectsEndpointTemplate =
                "{{ route('teacher.subjects.by-section', ['section' => '__SECTION__']) }}";

            let categories = [];
            let itemData = {}; // { itemNumber: wrongCount }
            let chartInstance = null;

            // Grade level change - load sections
            gradeLevelSelect.addEventListener('change', function() {
                const gradeId = this.value;
                sectionSelect.innerHTML = '<option value="">Loading...</option>';
                sectionSelect.disabled = true;
                subjectSelect.innerHTML = '<option value="">Select section first</option>';
                subjectSelect.disabled = true;

                if (!gradeId) {
                    sectionSelect.innerHTML = '<option value="">Select grade first</option>';
                    return;
                }

                fetch(`${sectionsEndpoint}?grade_level=${gradeId}`)
                    .then(r => r.json())
                    .then(data => {
                        const classes = data.allClasses || [];
                        const uniqueSections = [];
                        const seen = new Set();

                        classes.forEach(cls => {
                            const section = cls.section;
                            if (section && !seen.has(section.id)) {
                                seen.add(section.id);
                                uniqueSections.push(section);
                            }
                        });

                        if (uniqueSections.length === 0) {
                            sectionSelect.innerHTML = '<option value="">No sections found</option>';
                            return;
                        }

                        sectionSelect.innerHTML = '<option value="">Select section</option>';
                        uniqueSections.forEach(section => {
                            const opt = document.createElement('option');
                            opt.value = section.id;
                            opt.textContent = section.name;
                            sectionSelect.appendChild(opt);
                        });
                        sectionSelect.disabled = false;
                    })
                    .catch(() => {
                        sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    });
            });

            // Section change - load subjects
            sectionSelect.addEventListener('change', function() {
                const sectionId = this.value;
                subjectSelect.innerHTML = '<option value="">Loading...</option>';
                subjectSelect.disabled = true;

                if (!sectionId) {
                    subjectSelect.innerHTML = '<option value="">Select section first</option>';
                    return;
                }

                const url = subjectsEndpointTemplate.replace('__SECTION__', sectionId);
                fetch(url)
                    .then(r => r.json())
                    .then(subjects => {
                        if (!Array.isArray(subjects) || subjects.length === 0) {
                            subjectSelect.innerHTML = '<option value="">No subjects found</option>';
                            return;
                        }

                        subjectSelect.innerHTML = '<option value="">Select subject</option>';
                        subjects.forEach(subject => {
                            const opt = document.createElement('option');
                            opt.value = subject.id;
                            opt.textContent = subject.name;
                            subjectSelect.appendChild(opt);
                        });
                        subjectSelect.disabled = false;
                    })
                    .catch(() => {
                        subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                    });
            });

            // Add category
            addCategoryBtn.addEventListener('click', function() {
                const name = categoryNameInput.value.trim();
                const start = parseInt(itemStartInput.value) || 0;
                const end = parseInt(itemEndInput.value) || 0;
                const totalItems = parseInt(totalItemsInput.value) || 0;

                if (!name) {
                    alert('Please enter a category name.');
                    return;
                }
                if (start < 1 || end < start) {
                    alert('Please enter a valid item range.');
                    return;
                }
                if (end > totalItems) {
                    alert(`Item range exceeds total items (${totalItems}).`);
                    return;
                }

                // Check overlap
                const overlaps = categories.some(cat =>
                    (start >= cat.start && start <= cat.end) ||
                    (end >= cat.start && end <= cat.end) ||
                    (cat.start >= start && cat.start <= end)
                );

                if (overlaps) {
                    alert('Item range overlaps with an existing category.');
                    return;
                }

                categories.push({
                    name,
                    start,
                    end
                });
                renderCategories();

                categoryNameInput.value = '';
                itemStartInput.value = '';
                itemEndInput.value = '';
                categoryNameInput.focus();
            });

            function renderCategories() {
                if (categories.length === 0) {
                    categoriesContainer.innerHTML =
                        '<p class="text-muted small mb-0" id="noCategoriesMsg"><i data-feather="info" class="me-1"></i>No categories added yet.</p>';
                    generateSpreadsheetBtn.disabled = true;
                    feather.replace();
                    return;
                }

                const totalItems = parseInt(totalItemsInput.value) || 0;
                let coveredItems = 0;
                categories.forEach(cat => {
                    coveredItems += (cat.end - cat.start + 1);
                });

                let html = '<div class="d-flex flex-wrap">';
                categories.forEach((cat, idx) => {
                    html += `
                        <div class="category-tag">
                            <span class="tag-name">${cat.name}</span>
                            <span class="tag-range">Items ${cat.start}-${cat.end}</span>
                            <button type="button" class="tag-remove" data-idx="${idx}">&times;</button>
                        </div>`;
                });
                html += '</div>';

                if (coveredItems < totalItems) {
                    html +=
                        `<p class="text-warning small mt-2 mb-0"><i data-feather="alert-circle" class="me-1"></i>${totalItems - coveredItems} items not yet mapped to categories.</p>`;
                } else if (coveredItems === totalItems) {
                    html +=
                        `<p class="text-success small mt-2 mb-0"><i data-feather="check-circle" class="me-1"></i>All ${totalItems} items are mapped to categories.</p>`;
                }

                categoriesContainer.innerHTML = html;
                feather.replace();

                // Enable generate button if all items are covered
                generateSpreadsheetBtn.disabled = coveredItems !== totalItems ||
                    !gradeLevelSelect.value ||
                    !sectionSelect.value ||
                    !subjectSelect.value;

                // Remove category
                categoriesContainer.querySelectorAll('.tag-remove').forEach(btn => {
                    btn.addEventListener('click', function() {
                        categories.splice(parseInt(this.dataset.idx), 1);
                        renderCategories();
                    });
                });
            }

            // Check form validity
            function checkFormValidity() {
                const totalItems = parseInt(totalItemsInput.value) || 0;
                let coveredItems = 0;
                categories.forEach(cat => {
                    coveredItems += (cat.end - cat.start + 1);
                });

                generateSpreadsheetBtn.disabled = coveredItems !== totalItems ||
                    !gradeLevelSelect.value ||
                    !sectionSelect.value ||
                    !subjectSelect.value;
            }

            gradeLevelSelect.addEventListener('change', checkFormValidity);
            sectionSelect.addEventListener('change', checkFormValidity);
            subjectSelect.addEventListener('change', checkFormValidity);
            totalItemsInput.addEventListener('change', function() {
                // Reset categories if total items change
                categories = [];
                renderCategories();
            });

            // Generate spreadsheet
            generateSpreadsheetBtn.addEventListener('click', function() {
                const totalItems = parseInt(totalItemsInput.value) || 0;
                const totalStudents = parseInt(totalStudentsInput.value) || 1;

                // Initialize item data
                itemData = {};
                for (let i = 1; i <= totalItems; i++) {
                    itemData[i] = 0;
                }

                // Sort categories by start
                categories.sort((a, b) => a.start - b.start);

                // Build header row (vertical layout: Item # | Category | Students Wrong | Mastery Rate)
                let headerHtml = `<tr>
                    <th style="min-width: 80px;">Item #</th>
                    <th style="min-width: 180px;">Competency/Category</th>
                    <th style="min-width: 140px;">Students Wrong</th>
                    <th style="min-width: 120px;">Mastery Rate</th>
                </tr>`;
                spreadsheetHead.innerHTML = headerHtml;

                // Build body rows - one row per item (vertical layout)
                let bodyHtml = '';
                for (let i = 1; i <= totalItems; i++) {
                    const category = getCategoryForItem(i);
                    bodyHtml += `
                        <tr data-item-row="${i}">
                            <td class="item-number-cell">Item ${i}</td>
                            <td class="category-cell">${category}</td>
                            <td class="input-cell">
                                <input type="number" class="item-input" data-item="${i}"
                                       min="0" max="${totalStudents}" value="0"
                                       tabindex="${i}" placeholder="0">
                            </td>
                            <td class="mastery-cell mastery-high" data-mastery-item="${i}">100%</td>
                        </tr>`;
                }
                spreadsheetBody.innerHTML = bodyHtml;

                // Footer - category summary rows
                let footHtml = '';
                categories.forEach(cat => {
                    footHtml += `
                        <tr class="category-summary-row">
                            <td class="summary-label" colspan="2">
                                <strong>${cat.name}</strong>
                                <span class="text-white-50 ms-2">(Items ${cat.start}-${cat.end})</span>
                            </td>
                            <td>—</td>
                            <td class="mastery-cell mastery-high" data-category-mastery="${cat.name}">100%</td>
                        </tr>`;
                });
                // Overall summary row
                footHtml += `
                    <tr style="background: #343a40;">
                        <td colspan="2" style="background: #343a40; color: #fff; font-weight: 700;">OVERALL MASTERY</td>
                        <td style="background: #343a40; color: #fff;">—</td>
                        <td class="mastery-cell" id="overallMasteryCell" style="background: #343a40; color: #fff; font-weight: 700;">100%</td>
                    </tr>`;
                spreadsheetFoot.innerHTML = footHtml;

                // Show spreadsheet, hide setup
                setupCard.style.display = 'none';
                spreadsheetCard.style.display = 'block';
                resultsCard.style.display = 'block';

                // Add input listeners
                spreadsheetBody.querySelectorAll('.item-input').forEach(input => {
                    input.addEventListener('input', handleInputChange);
                    input.addEventListener('change', handleInputChange);
                });

                updateAnalysis();
                feather.replace();
            });

            function handleInputChange(e) {
                const input = e.target;
                const itemNum = parseInt(input.dataset.item);
                let value = parseInt(input.value) || 0;
                const maxStudents = parseInt(totalStudentsInput.value) || 1;

                // Clamp value
                if (value < 0) value = 0;
                if (value > maxStudents) value = maxStudents;
                input.value = value;

                itemData[itemNum] = value;
                updateAnalysis();
            }

            function updateAnalysis() {
                const totalStudents = parseInt(totalStudentsInput.value) || 1;
                const totalItems = parseInt(totalItemsInput.value) || 1;

                let totalCorrect = 0;
                let masteredCount = 0;
                let leastLearnedItems = [];

                // Update individual item mastery
                Object.entries(itemData).forEach(([item, wrongCount]) => {
                    const itemNum = parseInt(item);
                    const correct = totalStudents - wrongCount;
                    const mastery = (correct / totalStudents) * 100;
                    totalCorrect += correct;

                    const cell = document.querySelector(`[data-mastery-item="${itemNum}"]`);
                    if (cell) {
                        cell.textContent = mastery.toFixed(0) + '%';
                        cell.className = 'mastery-cell ';
                        if (mastery >= 75) {
                            cell.classList.add('mastery-high');
                            masteredCount++;
                        } else if (mastery >= 50) {
                            cell.classList.add('mastery-medium');
                            leastLearnedItems.push({
                                item: itemNum,
                                mastery,
                                category: getCategoryForItem(itemNum)
                            });
                        } else {
                            cell.classList.add('mastery-low');
                            leastLearnedItems.push({
                                item: itemNum,
                                mastery,
                                category: getCategoryForItem(itemNum)
                            });
                        }
                    }
                });

                // Update category mastery
                const categoryMastery = {};
                categories.forEach(cat => {
                    let catCorrect = 0;
                    let catItems = 0;
                    for (let i = cat.start; i <= cat.end; i++) {
                        catCorrect += totalStudents - (itemData[i] || 0);
                        catItems++;
                    }
                    const mastery = (catCorrect / (catItems * totalStudents)) * 100;
                    categoryMastery[cat.name] = mastery;

                    const cell = document.querySelector(`[data-category-mastery="${cat.name}"]`);
                    if (cell) {
                        cell.textContent = mastery.toFixed(1) + '%';
                        cell.className = 'mastery-cell ';
                        if (mastery >= 75) {
                            cell.classList.add('mastery-high');
                        } else if (mastery >= 50) {
                            cell.classList.add('mastery-medium');
                        } else {
                            cell.classList.add('mastery-low');
                        }
                    }
                });

                // Update overall mastery cell in footer
                const overallMasteryCell = document.getElementById('overallMasteryCell');
                if (overallMasteryCell) {
                    overallMasteryCell.textContent = overallMastery.toFixed(1) + '%';
                }

                // Update stats
                const overallMastery = (totalCorrect / (totalStudents * totalItems)) * 100;
                document.getElementById('statTotalItems').textContent = totalItems;
                document.getElementById('statMasteredItems').textContent = masteredCount;
                document.getElementById('statLeastLearned').textContent = totalItems - masteredCount;
                document.getElementById('statOverallMastery').textContent = overallMastery.toFixed(1) + '%';

                // Update least learned list
                const leastLearnedList = document.getElementById('leastLearnedList');
                leastLearnedItems.sort((a, b) => a.mastery - b.mastery);

                if (leastLearnedItems.length === 0) {
                    leastLearnedList.innerHTML =
                        '<p class="text-success"><i data-feather="check-circle" class="me-1"></i>Great! All items have ≥75% mastery.</p>';
                } else {
                    let html = '';
                    leastLearnedItems.slice(0, 10).forEach(item => {
                        html += `
                            <div class="least-learned-item">
                                <div class="item-info">
                                    <strong>Item ${item.item}</strong>
                                    <span class="text-muted">(${item.category})</span>
                                </div>
                                <span class="item-mastery">${item.mastery.toFixed(0)}% mastery</span>
                            </div>`;
                    });
                    if (leastLearnedItems.length > 10) {
                        html +=
                            `<p class="text-muted small">+ ${leastLearnedItems.length - 10} more items below 75%</p>`;
                    }
                    leastLearnedList.innerHTML = html;
                }

                // Update chart
                updateChart(categoryMastery);
                feather.replace();
            }

            function getCategoryForItem(itemNum) {
                for (const cat of categories) {
                    if (itemNum >= cat.start && itemNum <= cat.end) {
                        return cat.name;
                    }
                }
                return 'Unknown';
            }

            function updateChart(categoryMastery) {
                const ctx = document.getElementById('categoryChart');
                const labels = Object.keys(categoryMastery);
                const data = Object.values(categoryMastery).map(v => v.toFixed(1));
                const colors = data.map(v => parseFloat(v) >= 75 ? 'rgba(28, 200, 138, 0.8)' :
                    parseFloat(v) >= 50 ? 'rgba(246, 194, 62, 0.8)' : 'rgba(231, 74, 59, 0.8)');

                if (chartInstance) {
                    chartInstance.destroy();
                }

                chartInstance = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Mastery Rate (%)',
                            data,
                            backgroundColor: colors,
                            borderColor: colors.map(c => c.replace('0.8', '1')),
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
                                ticks: {
                                    callback: v => v + '%'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => `${ctx.raw}% mastery`
                                }
                            }
                        }
                    }
                });
            }

            // Back to setup
            backToSetupBtn.addEventListener('click', function() {
                setupCard.style.display = 'block';
                spreadsheetCard.style.display = 'none';
                resultsCard.style.display = 'none';
            });

            // Clear all
            clearAllBtn.addEventListener('click', function() {
                if (confirm('Clear all entered data?')) {
                    const totalItems = parseInt(totalItemsInput.value) || 0;
                    for (let i = 1; i <= totalItems; i++) {
                        itemData[i] = 0;
                    }
                    spreadsheetBody.querySelectorAll('.item-input').forEach(input => {
                        input.value = 0;
                    });
                    updateAnalysis();
                }
            });

            // Save form
            document.getElementById('saveAnalysisForm').addEventListener('submit', function(e) {
                // Populate hidden fields
                document.getElementById('hiddenQuarter').value = quarterSelect.value;
                document.getElementById('hiddenGradeLevel').value = gradeLevelSelect.value;
                document.getElementById('hiddenSection').value = sectionSelect.value;
                document.getElementById('hiddenSubject').value = subjectSelect.value;
                document.getElementById('hiddenTotalStudents').value = totalStudentsInput.value;
                document.getElementById('hiddenTotalItems').value = totalItemsInput.value;
                document.getElementById('hiddenExamTitle').value = examTitleInput.value;

                // Build categories payload
                const payload = categories.map(cat => {
                    const items = {};
                    for (let i = cat.start; i <= cat.end; i++) {
                        items[i] = itemData[i] || 0;
                    }
                    return {
                        name: cat.name,
                        start: cat.start,
                        end: cat.end,
                        items
                    };
                });

                document.getElementById('hiddenCategoriesPayload').value = JSON.stringify(payload);
            });

            // Initialize feather icons
            if (window.feather) {
                feather.replace();
            }
        });
    </script>
@endpush
