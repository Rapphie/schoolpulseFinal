@extends('base')

@section('title', 'Least Learned Competencies')

@push('styles')
    <style>
        .llc-card {
            box-shadow: 0 0.15rem 0.5rem rgba(58, 59, 69, 0.15);
            border: none;
        }

        .card-step-header {
            font-weight: 600;
            color: #0d6efd;
        }

        .item-input-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .item-input-group label {
            flex: 0 0 75px;
            margin-right: 8px;
            font-weight: 600;
        }

        .item-input-group .form-control {
            text-align: center;
        }

        #categoriesListItems .list-group-item {
            background-color: #f8f9fa;
        }

        #categoriesListItems .list-group-item:hover {
            background-color: #eef2ff;
        }

        .analysis-insight.bad {
            background-color: #fde2e1;
            color: #c53030;
        }

        .analysis-insight.good {
            background-color: #d1fae5;
            color: #065f46;
        }

        .llc-history-table th,
        .llc-history-table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800 mb-1">Least Learned Competencies</h1>
            <p class="text-muted mb-0">Track exam items by category, surface least mastered skills, and plan targeted
                remediation.</p>
        </div>
        <a href="{{ route('teacher.least-learned.index') }}" class="btn btn-outline-secondary btn-sm">
            <i data-feather="refresh-ccw" class="me-1"></i>Refresh
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="leastLearnedForm" method="POST" action="{{ route('teacher.least-learned.store') }}">
        @csrf
        <input type="hidden" name="categories_payload" id="categories_payload" value="{{ old('categories_payload') }}">
        @error('categories_payload')
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @enderror

        <div class="card llc-card mb-4" id="examDefinitionCard">
            <div class="card-header bg-white">
                <span class="card-step-header">Step 1 · Define Exam Context</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Quarter</label>
                        <select name="quarter" id="quarter" class="form-select" required>
                            @foreach ($quarters as $value => $label)
                                <option value="{{ $value }}" {{ old('quarter', 1) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('quarter')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Grade Level</label>
                        <select name="grade_level_id" id="grade_level_id" class="form-select" required>
                            <option value="">Select grade level</option>
                            @foreach ($gradeLevels as $level)
                                <option value="{{ $level->id }}"
                                    {{ (int) old('grade_level_id') === (int) $level->id ? 'selected' : '' }}>
                                    {{ $level->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('grade_level_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select name="section_id" id="section_id" class="form-select" required disabled>
                            <option value="">Select a grade level first</option>
                        </select>
                        @error('section_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Subject</label>
                        <select name="subject_id" id="subject_id" class="form-select" required disabled>
                            <option value="">Select a section first</option>
                        </select>
                        @error('subject_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label">Assessment Name (optional)</label>
                        <input type="text" class="form-control" id="exam_title" name="exam_title"
                            placeholder="e.g., Q1 Summative Test" value="{{ old('exam_title') }}">
                        @error('exam_title')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Students Examined</label>
                        <input type="number" class="form-control" id="studentCount" name="total_students" min="1"
                            max="200" value="{{ old('total_students', 30) }}" required>
                        @error('total_students')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Items in Exam</label>
                        <input type="number" class="form-control" id="totalItems" name="total_items" min="1"
                            max="200" value="{{ old('total_items', 20) }}" required>
                        @error('total_items')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="button" class="btn btn-primary" id="startItemInputBtn" disabled>
                        Proceed to Category Mapping
                        <i data-feather="arrow-right-circle" class="ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card llc-card mb-4" id="categoryBuilderCard">
            <div class="card-header bg-white">
                <span class="card-step-header">Step 2 · Map Items to Competency Categories</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info" id="noCategoriesMessage">
                    No categories defined yet. Add the competency strands covered by the exam and mapped item ranges.
                </div>
                <ul class="list-group mb-3" id="categoriesListItems"></ul>

                <div class="card border">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="categoryName"
                                    placeholder="e.g., Number Sense">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Start Item #</label>
                                <input type="number" class="form-control" id="itemStart" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Item #</label>
                                <input type="number" class="form-control" id="itemEnd" min="1">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="button" class="btn btn-outline-primary" id="addCategoryBtn">
                                    <i data-feather="plus"></i> Add
                                </button>
                            </div>
                        </div>
                        <div id="categoryInputErrorContainer" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card llc-card mb-4" id="categoryInputCard" style="display: none;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="card-step-header">Step 3 · Record Wrong Responses</span>
                <span class="text-muted" id="currentItemRange"></span>
            </div>
            <div class="card-body">
                <p class="mb-3">Enter the number of students who got each item wrong (max:
                    <strong id="maxStudentsAllowed">0</strong>). Use the navigation buttons to switch between categories.
                </p>
                <h5 id="currentCategoryTitle" class="mb-3"></h5>
                <div id="itemInputsContainer" class="row"></div>
                <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-outline-secondary" type="button" id="prevCategoryBtn"
                        style="display:none;">Previous Category</button>
                    <button class="btn btn-outline-primary" type="button" id="nextCategoryBtn"
                        style="display:none;">Next Category</button>
                    <button class="btn btn-primary" type="button" id="analyzeItemsButton" style="display:none;">
                        Analyze Performance
                    </button>
                </div>
            </div>
        </div>

        <div class="card llc-card mb-4" id="analysisReportCard" style="display: none;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="card-step-header">Step 4 · Review Insights & Save</span>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="resetWizardButton">
                    Start New Analysis
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <h5 class="mb-3">Performance by Category (<span id="reportExamName"></span>)</h5>
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="col-lg-6">
                        <h5 class="mb-3">Actionable Insights</h5>
                        <ul id="leastLearnedCategoriesList" class="list-group mb-3"></ul>
                        <button type="button" class="btn btn-success" id="saveAnalysisButton">
                            <i data-feather="save" class="me-1"></i>Save Result to Records
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card llc-card">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0">Saved Least Learned Analyses</h5>
                <small class="text-muted">Filter by section, subject, or quarter to find previous interventions.</small>
            </div>
            <form method="GET" action="{{ route('teacher.least-learned.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-0">Section</label>
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
                <div class="col-md-4">
                    <label class="form-label mb-0">Subject</label>
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
                <div class="col-md-3">
                    <label class="form-label mb-0">Quarter</label>
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
                <div class="col-md-1 d-grid">
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
                                <td colspan="7" class="text-center text-muted py-4">No least learned analyses recorded
                                    yet.
                                    Run your first analysis above.</td>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const examDefinitionCard = document.getElementById('examDefinitionCard');
            const categoryBuilderCard = document.getElementById('categoryBuilderCard');
            const categoryInputCard = document.getElementById('categoryInputCard');
            const analysisReportCard = document.getElementById('analysisReportCard');

            const gradeLevelSelect = document.getElementById('grade_level_id');
            const sectionSelect = document.getElementById('section_id');
            const subjectSelect = document.getElementById('subject_id');
            const categoriesPayloadInput = document.getElementById('categories_payload');

            const examTitleInput = document.getElementById('exam_title');
            const startItemInputBtn = document.getElementById('startItemInputBtn');
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            const categoriesListItems = document.getElementById('categoriesListItems');
            const noCategoriesMessage = document.getElementById('noCategoriesMessage');
            const categoryInputErrorContainer = document.getElementById('categoryInputErrorContainer');

            const currentCategoryTitle = document.getElementById('currentCategoryTitle');
            const currentItemRange = document.getElementById('currentItemRange');
            const maxStudentsAllowed = document.getElementById('maxStudentsAllowed');
            const itemInputsContainer = document.getElementById('itemInputsContainer');
            const prevCategoryBtn = document.getElementById('prevCategoryBtn');
            const nextCategoryBtn = document.getElementById('nextCategoryBtn');
            const analyzeItemsButton = document.getElementById('analyzeItemsButton');
            const saveAnalysisButton = document.getElementById('saveAnalysisButton');
            const resetWizardButton = document.getElementById('resetWizardButton');

            const categoryChartCanvas = document.getElementById('categoryChart');
            const leastLearnedCategoriesList = document.getElementById('leastLearnedCategoriesList');
            const reportExamNameSpan = document.getElementById('reportExamName');

            const insightSuggestions = {
                'Vocabulary': 'Strengthen contextual clues practice and daily word journals.',
                'Grammar': 'Run short, targeted drills on recurring mistakes.',
                'Reading Comprehension': 'Model think-aloud strategies and provide guiding questions.',
                'Problem Solving': 'Break multi-step problems into scaffolded parts.',
                'Number Sense': 'Use manipulatives and quick checks for misconceptions.',
                'Geometry': 'Integrate sketching and labeling routines before computation.',
                'Scientific Method': 'Let students design simple investigations to apply each step.',
            };

            const sectionsEndpoint = "{{ route('teacher.sections.by-grade-level') }}";
            const subjectsEndpointTemplate =
                "{{ route('teacher.subjects.by-section', ['section' => '__SECTION__']) }}";
            const initialGradeId = '{{ old('grade_level_id') }}';
            let pendingSectionId = '{{ old('section_id') }}';
            let pendingSubjectId = '{{ old('subject_id') }}';

            let categoriesData = [];
            let itemWrongCounts = {};
            let currentCategoryIndex = 0;
            let categoryChartInstance = null;
            let analysisCompleted = false;

            function resetWizard() {
                examDefinitionCard.style.display = 'block';
                categoryBuilderCard.style.display = 'block';
                categoryInputCard.style.display = 'none';
                analysisReportCard.style.display = 'none';
                categoriesData = [];
                itemWrongCounts = {};
                currentCategoryIndex = 0;
                analysisCompleted = false;
                categoriesListItems.innerHTML = '';
                categoriesPayloadInput.value = '';
                document.getElementById('categoryName').value = '';
                document.getElementById('itemStart').value = '';
                document.getElementById('itemEnd').value = '';
                noCategoriesMessage.classList.remove('d-none');
                startItemInputBtn.disabled = true;
                leastLearnedCategoriesList.innerHTML = '';
                if (categoryChartInstance) {
                    categoryChartInstance.destroy();
                    categoryChartInstance = null;
                }
            }

            resetWizardButton.addEventListener('click', function() {
                resetWizard();
                document.getElementById('leastLearnedForm').reset();
                subjectSelect.disabled = true;
                subjectSelect.innerHTML = '<option value="">Select a section first</option>';
                sectionSelect.disabled = true;
                sectionSelect.innerHTML = '<option value="">Select a grade level first</option>';
            });

            function showError(message) {
                categoryInputErrorContainer.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
            }

            function renderCategoriesList() {
                categoriesListItems.innerHTML = '';
                if (categoriesData.length === 0) {
                    noCategoriesMessage.classList.remove('d-none');
                    startItemInputBtn.disabled = true;
                    return;
                }

                noCategoriesMessage.classList.add('d-none');

                categoriesData
                    .sort((a, b) => a.start - b.start)
                    .forEach((cat, index) => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        li.innerHTML = `
                            <span><strong>${cat.name}</strong> · Items ${cat.start}-${cat.end}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-index="${index}">
                                <i data-feather="trash-2"></i>
                            </button>`;
                        li.querySelector('button').addEventListener('click', () => {
                            categoriesData.splice(index, 1);
                            renderCategoriesList();
                            checkAllItemsMapped();
                        });
                        categoriesListItems.appendChild(li);
                    });
                feather.replace();
                checkAllItemsMapped();
            }

            function checkAllItemsMapped() {
                const totalItems = parseInt(document.getElementById('totalItems').value, 10) || 0;
                if (!totalItems || categoriesData.length === 0) {
                    startItemInputBtn.disabled = true;
                    return;
                }

                const coverage = new Set();
                categoriesData.forEach(cat => {
                    for (let i = cat.start; i <= cat.end; i++) {
                        coverage.add(i);
                    }
                });

                startItemInputBtn.disabled = coverage.size !== totalItems;
            }

            addCategoryBtn.addEventListener('click', function() {
                const name = document.getElementById('categoryName').value.trim();
                const start = parseInt(document.getElementById('itemStart').value, 10);
                const end = parseInt(document.getElementById('itemEnd').value, 10);
                const totalItems = parseInt(document.getElementById('totalItems').value, 10) || 0;

                if (!name) {
                    showError('Please provide a category name.');
                    return;
                }
                if (!start || start < 1) {
                    showError('Start item must be 1 or higher.');
                    return;
                }
                if (!end || end < start) {
                    showError('End item must be greater than or equal to start item.');
                    return;
                }
                if (end > totalItems) {
                    showError('Item range exceeds total number of exam items.');
                    return;
                }
                const overlapping = categoriesData.some(cat =>
                    (start >= cat.start && start <= cat.end) ||
                    (end >= cat.start && end <= cat.end) ||
                    (cat.start >= start && cat.start <= end)
                );
                if (overlapping) {
                    showError('Item range overlaps with an existing category.');
                    return;
                }

                categoriesData.push({
                    name,
                    start,
                    end
                });
                document.getElementById('categoryName').value = '';
                document.getElementById('itemStart').value = '';
                document.getElementById('itemEnd').value = '';
                categoryInputErrorContainer.innerHTML = '';
                renderCategoriesList();
            });

            startItemInputBtn.addEventListener('click', function() {
                if (categoriesData.length === 0) {
                    showError('Please add at least one category.');
                    return;
                }
                itemWrongCounts = {};
                const totalItems = parseInt(document.getElementById('totalItems').value, 10) || 0;
                for (let i = 1; i <= totalItems; i++) {
                    itemWrongCounts[i] = 0;
                }
                currentCategoryIndex = 0;
                analysisCompleted = false;
                displayCurrentCategoryInputs();
                examDefinitionCard.style.display = 'none';
                categoryBuilderCard.scrollIntoView({
                    behavior: 'smooth'
                });
                categoryInputCard.style.display = 'block';
            });

            function displayCurrentCategoryInputs() {
                const currentCategory = categoriesData[currentCategoryIndex];
                if (!currentCategory) {
                    return;
                }
                currentCategoryTitle.textContent = currentCategory.name;
                currentItemRange.textContent = `Items ${currentCategory.start}-${currentCategory.end}`;
                maxStudentsAllowed.textContent = document.getElementById('studentCount').value;
                itemInputsContainer.innerHTML = '';

                const itemsCount = currentCategory.end - currentCategory.start + 1;
                const rows = itemsCount <= 30 ? 5 : 10;
                const cols = Math.ceil(itemsCount / rows);

                for (let r = 0; r < rows; r++) {
                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'row';
                    for (let c = 0; c < cols; c++) {
                        const itemIndex = currentCategory.start + c * rows + r;
                        if (itemIndex > currentCategory.end) break;

                        const colDiv = document.createElement('div');
                        colDiv.className = 'col-6 col-sm-4 col-md-3 col-lg-2 mb-3';
                        const group = document.createElement('div');
                        group.className = 'item-input-group';
                        const label = document.createElement('label');
                        label.textContent = `Item ${itemIndex}`;
                        const input = document.createElement('input');
                        input.type = 'number';
                        input.className = 'form-control item-wrong-input';
                        input.min = '0';
                        input.max = document.getElementById('studentCount').value;
                        input.value = itemWrongCounts[itemIndex] || 0;
                        input.dataset.itemId = itemIndex;
                        group.appendChild(label);
                        group.appendChild(input);
                        colDiv.appendChild(group);
                        rowDiv.appendChild(colDiv);
                    }
                    itemInputsContainer.appendChild(rowDiv);
                }
                updateNavigationButtons();
            }

            function updateNavigationButtons() {
                prevCategoryBtn.style.display = currentCategoryIndex > 0 ? 'inline-block' : 'none';
                nextCategoryBtn.style.display = currentCategoryIndex < categoriesData.length - 1 ? 'inline-block' :
                    'none';
                analyzeItemsButton.style.display = currentCategoryIndex === categoriesData.length - 1 ?
                    'inline-block' : 'none';
            }

            function persistCurrentInputs() {
                itemInputsContainer.querySelectorAll('.item-wrong-input').forEach(input => {
                    const itemId = parseInt(input.dataset.itemId, 10);
                    let value = parseInt(input.value, 10);
                    const limit = parseInt(document.getElementById('studentCount').value, 10) || 0;
                    if (isNaN(value) || value < 0) value = 0;
                    if (value > limit) value = limit;
                    itemWrongCounts[itemId] = value;
                });
            }

            prevCategoryBtn.addEventListener('click', function() {
                persistCurrentInputs();
                currentCategoryIndex--;
                displayCurrentCategoryInputs();
            });

            nextCategoryBtn.addEventListener('click', function() {
                persistCurrentInputs();
                currentCategoryIndex++;
                displayCurrentCategoryInputs();
            });

            analyzeItemsButton.addEventListener('click', function() {
                persistCurrentInputs();
                buildAnalysis();
            });

            saveAnalysisButton.addEventListener('click', function() {
                if (!analysisCompleted) {
                    alert('Please analyze the data before saving.');
                    return;
                }
                const payload = categoriesData.map(cat => {
                    const items = {};
                    for (let i = cat.start; i <= cat.end; i++) {
                        items[i] = itemWrongCounts[i] || 0;
                    }
                    return {
                        name: cat.name,
                        start: cat.start,
                        end: cat.end,
                        items
                    };
                });
                categoriesPayloadInput.value = JSON.stringify(payload);
                document.getElementById('leastLearnedForm').submit();
            });

            function buildAnalysis() {
                const studentCount = parseInt(document.getElementById('studentCount').value, 10) || 1;
                const examTitle = examTitleInput.value.trim() || 'Assessment';
                const results = categoriesData.map(cat => {
                    let totalWrong = 0;
                    const items = {};
                    for (let i = cat.start; i <= cat.end; i++) {
                        const wrong = itemWrongCounts[i] || 0;
                        totalWrong += wrong;
                        items[`Item ${i}`] = wrong;
                    }
                    const totalPossible = (cat.end - cat.start + 1) * studentCount;
                    const avgWrong = (totalWrong / totalPossible) * 100;
                    return {
                        name: cat.name,
                        totalWrong,
                        totalPossible,
                        avgWrongPercentage: avgWrong,
                        items,
                    };
                });

                const labels = results.map(res => res.name);
                const dataValues = results.map(res => res.avgWrongPercentage.toFixed(2));
                const colors = results.map(res => res.avgWrongPercentage > 50 ? 'rgba(231,74,59,0.8)' :
                    'rgba(28,200,138,0.8)');

                if (categoryChartInstance) {
                    categoryChartInstance.destroy();
                }
                categoryChartInstance = new Chart(categoryChartCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Avg. % of students who got items wrong',
                            data: dataValues,
                            backgroundColor: colors,
                            borderColor: colors.map(color => color.replace('0.8', '1')),
                            borderWidth: 1,
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
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
                                    label: ctx => `${ctx.raw}% of students struggled`
                                }
                            }
                        }
                    }
                });

                leastLearnedCategoriesList.innerHTML = '';
                let flaggedCount = 0;
                results.sort((a, b) => b.avgWrongPercentage - a.avgWrongPercentage)
                    .forEach(res => {
                        if (res.avgWrongPercentage > 50) {
                            flaggedCount++;
                            const li = document.createElement('li');
                            li.className = 'list-group-item analysis-insight bad mb-2';
                            const suggestion = insightSuggestions[res.name] ||
                                'Review lesson logs and reteach using varied modalities.';
                            li.innerHTML = `
                                <strong>${res.name}</strong> · ${res.avgWrongPercentage.toFixed(1)}% of responses incorrect
                                <div class="small mt-1 text-muted">${Object.entries(res.items).map(([item, wrong]) => `${item}: ${wrong} wrong`).join(' · ')}</div>
                                <div class="mt-2"><strong>Next Step:</strong> ${suggestion}</div>`;
                            leastLearnedCategoriesList.appendChild(li);
                        }
                    });
                if (flaggedCount === 0) {
                    const li = document.createElement('li');
                    li.className = 'list-group-item analysis-insight good';
                    li.textContent = 'Great work! All categories show less than 50% incorrect responses.';
                    leastLearnedCategoriesList.appendChild(li);
                }

                reportExamNameSpan.textContent = examTitle;
                analysisCompleted = true;
                analysisReportCard.style.display = 'block';
                categoryInputCard.style.display = 'none';
                analysisReportCard.scrollIntoView({
                    behavior: 'smooth'
                });
            }

            gradeLevelSelect.addEventListener('change', function() {
                const gradeLevelId = this.value;
                sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
                sectionSelect.disabled = true;
                subjectSelect.innerHTML = '<option value="">Select a section first</option>';
                subjectSelect.disabled = true;
                if (!gradeLevelId) {
                    sectionSelect.innerHTML = '<option value="">Select a grade level first</option>';
                    return;
                }
                fetch(`${sectionsEndpoint}?grade_level=${gradeLevelId}`)
                    .then(resp => resp.json())
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
                        sectionSelect.innerHTML = '<option value="">Select a section</option>';
                        uniqueSections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.id;
                            const gradeLabel = section.grade_level && section.grade_level.name ?
                                ` (${section.grade_level.name})` : '';
                            option.textContent = `${section.name}${gradeLabel}`;
                            sectionSelect.appendChild(option);
                        });
                        sectionSelect.disabled = false;
                        if (pendingSectionId) {
                            sectionSelect.value = pendingSectionId;
                            pendingSectionId = '';
                            sectionSelect.dispatchEvent(new Event('change'));
                        }
                    })
                    .catch(() => {
                        sectionSelect.innerHTML = '<option value="">Unable to load sections</option>';
                    });
            });

            sectionSelect.addEventListener('change', function() {
                const sectionId = this.value;
                subjectSelect.innerHTML = '<option value="">Loading subjects...</option>';
                subjectSelect.disabled = true;
                if (!sectionId) {
                    subjectSelect.innerHTML = '<option value="">Select a section first</option>';
                    return;
                }
                const url = subjectsEndpointTemplate.replace('__SECTION__', sectionId);
                fetch(url)
                    .then(resp => resp.json())
                    .then(subjects => {
                        if (!Array.isArray(subjects) || subjects.length === 0) {
                            subjectSelect.innerHTML = '<option value="">No subjects assigned</option>';
                            return;
                        }
                        subjectSelect.innerHTML = '<option value="">Select a subject</option>';
                        subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.id;
                            option.textContent = subject.name;
                            subjectSelect.appendChild(option);
                        });
                        subjectSelect.disabled = false;
                        if (pendingSubjectId) {
                            subjectSelect.value = pendingSubjectId;
                            pendingSubjectId = '';
                        }
                    })
                    .catch(() => {
                        subjectSelect.innerHTML = '<option value="">Unable to load subjects</option>';
                    });
            });

            // Hydrate previous selections
            (function hydrateForm() {
                if (initialGradeId) {
                    gradeLevelSelect.value = initialGradeId;
                    gradeLevelSelect.dispatchEvent(new Event('change'));
                }

                const payload = categoriesPayloadInput.value;
                if (payload) {
                    try {
                        const parsed = JSON.parse(payload);
                        categoriesData = parsed.map(cat => ({
                            name: cat.name,
                            start: cat.start,
                            end: cat.end
                        }));
                        itemWrongCounts = {};
                        parsed.forEach(cat => {
                            Object.entries(cat.items || {}).forEach(([item, wrong]) => {
                                itemWrongCounts[parseInt(item, 10)] = wrong;
                            });
                        });
                        renderCategoriesList();
                    } catch (error) {
                        console.warn('Unable to hydrate categories payload', error);
                    }
                }
            })();

            if (window.feather) {
                feather.replace();
            }
        });
    </script>
@endpush
