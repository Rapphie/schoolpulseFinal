@extends('base')
@section('content')
    @push('styles')
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f8f9fa;
            }

            .jumbotron {
                background-color: #e9ecef;
                padding: 3rem 2rem;
                margin-bottom: 2rem;
                border-radius: .3rem;
            }

            .jumbotron h1 {
                color: #007bff;
                /* Bootstrap's primary blue */
            }

            /* --- General Card Styling --- */
            .card {
                margin-bottom: 1.5rem;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075);
            }

            .card-header {
                color: white;
                font-weight: bold;
            }

            /* --- New Tracker-Specific CSS --- */

            /* Style for sections to give them some visual separation */
            section {
                padding: 2rem 0;
            }

            section:not(:last-of-type) {
                border-bottom: 1px solid #dee2e6;
                /* Light gray line between sections */
            }

            /* Item input specific styles */
            .item-input-group {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }

            .item-input-group label {
                flex: 0 0 80px;
                /* Fixed width for 'Item X' label */
                margin-right: 10px;
                font-weight: bold;
            }

            .item-input-group .form-control {
                flex: 1;
                /* Input takes remaining space */
                max-width: 100px;
                /* Max width for the number input */
                text-align: center;
            }

            /* List for insights */
            .list-group-item-danger {
                font-weight: bold;
                background-color: #f8d7da;
                /* Lighter red for background */
                color: #721c24;
                /* Darker red for text */
            }

            .list-group-item-success {
                font-weight: bold;
                background-color: #d4edda;
                color: #155724;
            }

            /* Small adjustments for form elements */
            .form-label {
                font-weight: 500;
            }

            /* Ensure canvas for chart scales correctly */
            canvas {
                max-width: 100%;
                height: auto;
            }

            /* Category list styling */
            #categoriesListItems .list-group-item {
                transition: all 0.2s ease-in-out;
                background-color: #f8f9fa;
            }

            #categoriesListItems .list-group-item:hover {
                background-color: #e9ecef;
            }

            .delete-category {
                margin-left: 10px;
            }
        </style>
    @endpush
    <section id="tracker-section">
        <h2 class="mb-4">Least Learned Competency</h2>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Define Exam Structure</div>
            <div class="card-body">
                <form id="examStructureForm">
                    <h5 class="mb-3">1. Basic Exam Details</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="quarter" class="form-label">Quarter:</label>
                            <select name="quarter" id="quarter" class="form-select">
                                <option value="1">First Quarter</option>
                                <option value="2">Second Quarter</option>
                                <option value="3">Third Quarter</option>
                                <option value="4">Fourth Quarter</option>
                            </select>
                        </div>
                        {{-- **NEW: Grade Level Dropdown** --}}
                        <div class="col-md-4">
                            <label for="grade_level_id" class="form-label">Grade Level:</label>
                            <select name="grade_level_id" id="grade_level_id" class="form-select" required>
                                <option value="">Select a Grade Level</option>
                                @foreach ($gradeLevels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="subject_id" class="form-label">Subject:</label>
                            <select name="subject_id" id="subject_id" class="form-select" required disabled>
                                <option value="">Select a grade level first</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="studentCount" class="form-label">Total Students Examined:</label>
                            <input type="number" class="form-control" id="studentCount" min="1" value="30"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="totalItems" class="form-label">Total Number of Items in Exam:</label>
                            <input type="number" class="form-control" id="totalItems" min="1" value="20"
                                required>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">2. Define Competency Categories & Item Mapping</h5>
                    <div class="alert alert-info alert-dismissible fade show" id="noCategoriesMessage">
                        No categories added yet. Use the form below to add categories.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="categoriesList" class="mb-3">
                        <ul class="list-group" id="categoriesListItems">
                            <!-- Categories will be listed here -->
                        </ul>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">Add Category</div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="categoryName" class="form-label">Category Name:</label>
                                    <input type="text" class="form-control" id="categoryName"
                                        placeholder="e.g., Vocabulary">
                                </div>
                                <div class="col-md-3">
                                    <label for="itemStart" class="form-label">Start Item #:</label>
                                    <input type="number" class="form-control" id="itemStart" min="1"
                                        placeholder="e.g., 1">
                                </div>
                                <div class="col-md-3">
                                    <label for="itemEnd" class="form-label">End Item #:</label>
                                    <input type="number" class="form-control" id="itemEnd" min="1"
                                        placeholder="e.g., 5">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" id="addCategoryBtn" class="btn btn-primary"><i
                                            data-feather="plus"></i></button>
                                </div>
                            </div>
                            <div id="categoryInputErrorContainer"></div>
                        </div>
                    </div>
                    <input type="hidden" id="categoriesMapping" value="">
                    <small class="form-text text-muted mt-2">
                        Ensure all items from 1 to the total number of items are covered, and item ranges do not
                        overlap.
                        The "Start Item Input" button will become active once all items are mapped.
                    </small>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="startItemInputBtn" disabled>Proceed to
                            Item Input <i class="bi bi-arrow-right-circle-fill"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4" id="categoryInputCard" style="display: none;">
            <div class="card-header bg-info text-white">
                Entering Data for: <span id="currentCategoryTitle"></span> (<span id="currentItemRange"></span>)
            </div>
            <div class="card-body">
                <p class="mb-3">Enter the **number of students who got each item wrong** (max: <span
                        id="maxStudentsAllowed"></span> students).</p>
                <div id="itemInputsContainer" class="row">
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <button class="btn btn-secondary" id="prevCategoryBtn" style="display: none;">Previous
                        Category</button>
                    <button class="btn btn-info" id="nextCategoryBtn">Next Category</button>
                    <button class="btn btn-primary" id="analyzeItemsButton" style="display: none;">Analyze
                        Performance</button>
                </div>
            </div>
        </div>

        <div class="card mb-4" id="analysisReportCard" style="display: none;">
            <div class="card-header bg-primary text-white">Analysis Report: Least Learned Categories</div>
            <div class="card-body">
                <h5 class="mb-3">Performance by Category (<span id="reportExamName"></span>)</h5>
                <canvas id="categoryChart"></canvas>
                <h5 class="mt-4 mb-3">Actionable Insights (Categories with > 50% of Students Wrong)</h5>
                <ul id="leastLearnedCategoriesList" class="list-group">
                </ul>
            </div>
        </div>
    </section>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const examStructureForm = document.getElementById('examStructureForm');
                const categoryInputCard = document.getElementById('categoryInputCard');
                const currentCategoryTitle = document.getElementById('currentCategoryTitle');
                const currentItemRange = document.getElementById('currentItemRange');
                const maxStudentsAllowed = document.getElementById('maxStudentsAllowed');
                const itemInputsContainer = document.getElementById('itemInputsContainer');
                const prevCategoryBtn = document.getElementById('prevCategoryBtn');
                const nextCategoryBtn = document.getElementById('nextCategoryBtn');
                const analyzeItemsButton = document.getElementById('analyzeItemsButton');
                const analysisReportCard = document.getElementById('analysisReportCard');
                const reportExamNameSpan = document.getElementById('reportExamName');
                const leastLearnedCategoriesList = document.getElementById('leastLearnedCategoriesList');
                const startItemInputBtn = document.getElementById('startItemInputBtn');

                let examDetails = {}; // Stores exam name, student count, total items
                let categoriesData = []; // Parsed categories with their item ranges
                let currentCategoryIndex = 0;
                let itemWrongCounts = {}; // Stores { itemId: numStudentsWrong, ... } for all items

                let categoryChartInstance; // To hold the Chart.js instance

                // Suggestion for insights (you can expand this significantly)
                const insightSuggestions = {
                    "Vocabulary": "Focus on root words, prefixes, suffixes, and contextual clues. Integrate word games and daily vocabulary drills.",
                    "Grammar": "Conduct targeted mini-lessons on specific grammatical errors. Use interactive exercises and peer correction.",
                    "Reading Comprehension": "Teach various reading strategies (skimming, scanning, predicting, inferring). Use graphic organizers and encourage active reading.",
                    "Literature": "Discuss literary elements (plot, character, theme, setting) thoroughly. Encourage critical analysis and comparative reading.",
                    "Problem Solving": "Break down problems into smaller steps. Practice different problem-solving heuristics and encourage drawing/diagramming.",
                    "Basic Operations": "Provide remedial drills and timed exercises. Use manipulatives for concrete understanding.",
                    "Scientific Method": "Conduct simple experiments to demonstrate each step. Review terms and their application.",
                    "Historical Context": "Use timelines, maps, and primary sources to build a clearer picture. Engage in discussions and debates.",
                    "Force and Motion": "Conduct hands-on experiments to demonstrate principles. Use visual aids and real-world examples.",
                    "Biological Processes": "Utilize diagrams, models, and real-life examples. Emphasize cause-and-effect relationships."
                }; // --- Category Management ---
                const categoriesListItems = document.getElementById('categoriesListItems');
                const noCategoriesMessage = document.getElementById('noCategoriesMessage');
                const addCategoryBtn = document.getElementById('addCategoryBtn');

                // Function to update the hidden field with the current categories data
                function updateCategoriesMapping() {
                    const mappingValue = categoriesData.map(cat => `${cat.name}: ${cat.start}-${cat.end}`).join('\n');
                    document.getElementById('categoriesMapping').value = mappingValue;
                } // Function to render the list of categories
                function renderCategoriesList() {
                    categoriesListItems.innerHTML = '';

                    if (categoriesData.length === 0) {
                        // Show the alert if it's not already dismissed by the user
                        if (!noCategoriesMessage.classList.contains('d-none')) {
                            noCategoriesMessage.classList.remove('d-none');
                            noCategoriesMessage.classList.add('show');
                        }
                        return;
                    }

                    // Hide the alert when categories exist
                    noCategoriesMessage.classList.add('d-none');

                    categoriesData.sort((a, b) => a.start - b.start);

                    categoriesData.forEach((cat, index) => {
                        const li = document.createElement('li');
                        li.classList.add('list-group-item', 'd-flex', 'justify-content-between',
                            'align-items-center');
                        li.innerHTML = `
                <span><strong>${cat.name}</strong>: Items ${cat.start}-${cat.end}</span>
                <button type="button" class="btn btn-sm btn-danger delete-category" data-index="${index}">
                    <i class="bi bi-trash"></i> Remove
                </button>
            `;
                        categoriesListItems.appendChild(li);
                    });

                    // Add event listeners to delete buttons
                    const deleteButtons = categoriesListItems.querySelectorAll('.delete-category');
                    deleteButtons.forEach(btn => {
                        btn.addEventListener('click', function() {
                            const index = parseInt(this.dataset.index);
                            categoriesData.splice(index, 1);
                            renderCategoriesList();
                            updateCategoriesMapping();
                        });
                    });

                    updateCategoriesMapping();
                    checkAllItemsMapped(); // Add this call
                } // Function to show a temporary success alert
                function showSuccessAlert(message) {
                    // Remove any existing success alerts
                    const existingAlerts = document.querySelectorAll('.alert-success.temp-alert');
                    existingAlerts.forEach(alert => alert.remove());
                    

                    // Create new alert
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success alert-dismissible fade show temp-alert mt-3';
                    successAlert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

                    // Insert before the categories list
                    document.getElementById('categoriesList').insertAdjacentElement('beforebegin', successAlert);

                    // Auto dismiss after 5 seconds
                    setTimeout(() => {
                        successAlert.classList.remove('show');
                        setTimeout(() => successAlert.remove(), 150);
                    }, 5000);
                } // Function to show category input error
                function showCategoryInputError(message) {
                    // Remove any existing error alerts in the category input section
                    const existingAlerts = document.querySelectorAll('.category-input-error');
                    existingAlerts.forEach(alert => alert.remove());

                    // Create new alert
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger alert-dismissible fade show category-input-error mb-3';
                    errorAlert.innerHTML = `
            <strong>Error:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

                    // Insert at the beginning of the card body
                    document.querySelector('.card-body .row').insertAdjacentElement('beforebegin', errorAlert);
                } // Add category button click handler
                addCategoryBtn.addEventListener('click', function() {
                    const categoryName = document.getElementById('categoryName').value.trim();
                    const itemStart = parseInt(document.getElementById('itemStart').value);
                    const itemEnd = parseInt(document.getElementById('itemEnd').value);

                    if (!categoryName) {
                        showCategoryInputError('Please enter a category name.');
                        return;
                    }

                    if (isNaN(itemStart) || itemStart < 1) {
                        showCategoryInputError('Start Item must be a positive number.');
                        return;
                    }

                    if (isNaN(itemEnd) || itemEnd < itemStart) {
                        showCategoryInputError('End Item must be greater than or equal to Start Item.');
                        return;
                    }

                    // Check for duplicate category names
                    const duplicateName = categoriesData.some(cat =>
                        cat.name.toLowerCase() === categoryName.toLowerCase()
                    );

                    if (duplicateName) {
                        showCategoryInputError(
                            'A category with this name already exists. Please use a unique category name.');
                        return;
                    }

                    // Check for overlapping ranges
                    const overlap = categoriesData.some(cat =>
                        (itemStart >= cat.start && itemStart <= cat.end) ||
                        (itemEnd >= cat.start && itemEnd <= cat.end) ||
                        (cat.start >= itemStart && cat.start <= itemEnd)
                    );

                    if (overlap) {
                        showCategoryInputError(
                            'This item range overlaps with an existing category. Please use a different range.'
                        );
                        return;
                    }

                    categoriesData.push({
                        name: categoryName,
                        start: itemStart,
                        end: itemEnd
                    });

                    // Clear input fields
                    document.getElementById('categoryName').value = '';
                    document.getElementById('itemStart').value = '';
                    document.getElementById('itemEnd').value = '';

                    // Show success message
                    showSuccessAlert(
                        `Category <strong>${categoryName}</strong> (Items ${itemStart}-${itemEnd}) added successfully!`
                    );

                    renderCategoriesList();
                    checkAllItemsMapped(); // Add this call
                });

                // --- Step 1: Define Exam Structure ---
                examStructureForm.addEventListener('submit', function(event) {
                    event.preventDefault();

                    // Clear previous validation errors
                    const existingAlerts = document.querySelectorAll('.validation-error-alert');
                    existingAlerts.forEach(alert => alert.remove());

                    examDetails.examName = document.getElementById('examName').value;
                    examDetails.studentCount = parseInt(document.getElementById('studentCount').value);
                    examDetails.totalItems = parseInt(document.getElementById('totalItems').value);

                    if (!examDetails.examName.trim()) {
                        showValidationError('Please enter an Exam Name.');
                        return;
                    }
                    if (isNaN(examDetails.studentCount) || examDetails.studentCount < 1) {
                        showValidationError('Total Students Examined must be a positive number.');
                        return;
                    }
                    if (isNaN(examDetails.totalItems) || examDetails.totalItems < 1) {
                        showValidationError('Total Number of Items in Exam must be a positive number.');
                        return;
                    }

                    if (categoriesData.length === 0) {
                        showValidationError('Please add at least one category before proceeding.');
                        return;
                    }

                    if (!validateExamDetails(examDetails, categoriesData)) {
                        return;
                    }

                    // Initialize itemWrongCounts for all items to 0
                    for (let i = 1; i <= examDetails.totalItems; i++) {
                        itemWrongCounts[i] = 0;
                    }

                    // Hide definition form, show category input
                    examStructureForm.closest('.card').style.display = 'none';
                    categoryInputCard.style.display = 'block';

                    currentCategoryIndex = 0; // Start with the first category
                    displayCurrentCategoryInputs();
                }); // Function to show validation error with dismissible alert
                function showValidationError(message) {
                    // Remove any existing error alerts
                    const existingAlerts = document.querySelectorAll('.validation-error-alert');
                    existingAlerts.forEach(alert => alert.remove());

                    // Create new alert
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger alert-dismissible fade show validation-error-alert mb-3';
                    errorAlert.innerHTML = `
            <strong>Validation Error:</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

                    // Insert at the top of the form
                    examStructureForm.insertAdjacentElement('afterbegin', errorAlert);

                    // Scroll to the error message
                    errorAlert.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                function validateExamDetails(details, categories) {
                    if (categories.length === 0) {
                        showValidationError('Please add at least one category with a valid item range.');
                        return false;
                    }

                    const allItemsCovered = new Set();
                    for (let i = 0; i < categories.length; i++) {
                        const cat = categories[i];
                        for (let j = cat.start; j <= cat.end; j++) {
                            if (allItemsCovered.has(j)) {
                                showValidationError(
                                    `Item ${j} is defined in multiple categories. Please ensure no overlapping ranges.`
                                );
                                return false;
                            }
                            allItemsCovered.add(j);

                            if (j > details.totalItems) {
                                showValidationError(
                                    `The category "${cat.name}" includes item ${j}, which exceeds the total number of items (${details.totalItems}).`
                                );
                                return false;
                            }
                        }
                    }

                    if (allItemsCovered.size !== details.totalItems) {
                        // Find missing items
                        const missingItems = [];
                        for (let i = 1; i <= details.totalItems; i++) {
                            if (!allItemsCovered.has(i)) {
                                missingItems.push(i);
                            }
                        }

                        if (missingItems.length > 0) {
                            showValidationError(
                                `The following items are not assigned to any category: ${missingItems.join(', ')}. All items from 1 to ${details.totalItems} must be assigned to a category.`
                            );
                        } else {
                            showValidationError(
                                `The total number of items defined in categories (${allItemsCovered.size}) does not match the 'Total Number of Items in Exam' (${details.totalItems}). Ensure all items from 1 to ${details.totalItems} are covered exactly once.`
                            );
                        }
                        return false;
                    }

                    if (!allItemsCovered.has(1)) {
                        showValidationError('Item numbering in categories should start from 1.');
                        return false;
                    }

                    return true;
                }

                // --- Step 2: Display Category Inputs ---
                function displayCurrentCategoryInputs() {
                    const currentCategory = categoriesData[currentCategoryIndex];
                    currentCategoryTitle.textContent = currentCategory.name;
                    currentItemRange.textContent = `Items ${currentCategory.start}-${currentCategory.end}`;
                    maxStudentsAllowed.textContent = examDetails.studentCount;
                    itemInputsContainer.innerHTML = '';

                    // Dynamically arrange items in rows and columns (rows = 5 for <=50 items, else 10)
                    const itemsCount = currentCategory.end - currentCategory.start + 1;
                    const rows = itemsCount <= 50 ? 5 : 10;
                    const cols = Math.ceil(itemsCount / rows);

                    for (let r = 0; r < rows; r++) {
                        const rowDiv = document.createElement('div');
                        rowDiv.classList.add('row');
                        for (let c = 0; c < cols; c++) {
                            const itemIndex = currentCategory.start + c * rows + r;
                            if (itemIndex > currentCategory.end) break;

                            const colDiv = document.createElement('div');
                            // Responsive column widths: 2 per row on xs, 3 on sm, 4 on md, 6 on lg
                            colDiv.classList.add('col-6', 'col-sm-4', 'col-md-3', 'col-lg-2', 'mb-3');

                            const inputGroup = document.createElement('div');
                            inputGroup.classList.add('item-input-group');

                            const label = document.createElement('label');
                            label.setAttribute('for', `item-${itemIndex}`);
                            label.textContent = `Item ${itemIndex}:`;
                            inputGroup.appendChild(label);

                            const input = document.createElement('input');
                            input.type = 'number';
                            input.classList.add('form-control', 'item-wrong-input');
                            input.id = `item-${itemIndex}`;
                            input.min = '0';
                            input.max = examDetails.studentCount;
                            input.value = itemWrongCounts[itemIndex] || 0;
                            input.dataset.itemId = itemIndex;
                            inputGroup.appendChild(input);

                            colDiv.appendChild(inputGroup);
                            rowDiv.appendChild(colDiv);
                        }
                        itemInputsContainer.appendChild(rowDiv);
                    }

                    updateNavigationButtons();
                    updateProgressBar();
                }

                function updateNavigationButtons() {
                    prevCategoryBtn.style.display = currentCategoryIndex > 0 ? 'inline-block' : 'none';
                    nextCategoryBtn.style.display = currentCategoryIndex < categoriesData.length - 1 ? 'inline-block' :
                        'none';
                    analyzeItemsButton.style.display = currentCategoryIndex === categoriesData.length - 1 ?
                        'inline-block' : 'none';
                }

                // --- Navigation Logic ---
                prevCategoryBtn.addEventListener('click', function() {
                    saveCurrentCategoryInputs();
                    currentCategoryIndex--;
                    displayCurrentCategoryInputs();
                });

                nextCategoryBtn.addEventListener('click', function() {
                    saveCurrentCategoryInputs();
                    currentCategoryIndex++;
                    displayCurrentCategoryInputs();
                });

                function saveCurrentCategoryInputs() {
                    const inputs = itemInputsContainer.querySelectorAll('.item-wrong-input');
                    inputs.forEach(input => {
                        const itemId = parseInt(input.dataset.itemId);
                        let value = parseInt(input.value);
                        // Basic validation to ensure value is within bounds
                        if (isNaN(value) || value < 0) value = 0;
                        if (value > examDetails.studentCount) value = examDetails.studentCount;
                        itemWrongCounts[itemId] = value;
                    });
                }

                // --- Step 3: Analyze Performance ---
                analyzeItemsButton.addEventListener('click', function() {
                    saveCurrentCategoryInputs(); // Save inputs from the last category

                    const studentCount = examDetails.studentCount;
                    const resultsByCategory = {};

                    // Calculate performance for each category
                    categoriesData.forEach(cat => {
                        let totalWrongInCat = 0;
                        let totalItemsInCat = (cat.end - cat.start + 1);
                        for (let i = cat.start; i <= cat.end; i++) {
                            totalWrongInCat += (itemWrongCounts[i] ||
                                0); // Sum up how many students got items wrong
                        }
                        // Calculate average percentage of students who got items wrong in this category
                        const avgWrongPercentage = (totalWrongInCat / (totalItemsInCat *
                            studentCount)) * 100;
                        resultsByCategory[cat.name] = {
                            avgWrongPercentage: avgWrongPercentage,
                            totalWrongMarks: totalWrongInCat, // Sum of wrong marks for all items in category
                            totalPossibleWrongMarks: totalItemsInCat *
                                studentCount, // Max possible wrong marks
                            items: {} // To store individual item wrong counts if needed for deeper dive
                        };
                        // Also store individual item wrong counts for this category
                        for (let i = cat.start; i <= cat.end; i++) {
                            resultsByCategory[cat.name].items[`Item ${i}`] = itemWrongCounts[i] || 0;
                        }
                    });

                    // --- Display Report ---
                    reportExamNameSpan.textContent = examDetails.examName;

                    // Chart for category performance
                    const ctx = document.getElementById('categoryChart').getContext('2d');
                    if (categoryChartInstance) {
                        categoryChartInstance.destroy(); // Destroy previous chart if it exists
                    }
                    categoryChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: Object.keys(resultsByCategory),
                            datasets: [{
                                label: 'Avg. Students Wrong (%)',
                                data: Object.values(resultsByCategory).map(res => res
                                    .avgWrongPercentage.toFixed(2)),
                                backgroundColor: Object.values(resultsByCategory).map(res => res
                                    .avgWrongPercentage > 50 ? 'rgba(255, 99, 132, 0.6)' :
                                    'rgba(75, 192, 192, 0.6)'
                                ), // Red if > 50% wrong, Green otherwise
                                borderColor: Object.values(resultsByCategory).map(res => res
                                    .avgWrongPercentage > 50 ? 'rgba(255, 99, 132, 1)' :
                                    'rgba(75, 192, 192, 1)'),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    title: {
                                        display: true,
                                        text: 'Average Students Who Got Items Wrong (%)'
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
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += Math.round(context.raw) + '%';
                                            const categoryName = context.label;
                                            const categoryData = resultsByCategory[categoryName];
                                            if (categoryData) {
                                                label +=
                                                    ` (Total Wrong Marks: ${categoryData.totalWrongMarks} out of ${categoryData.totalPossibleWrongMarks})`;
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // List least learned categories and insights
                    leastLearnedCategoriesList.innerHTML = '';
                    const sortedCategories = Object.entries(resultsByCategory)
                        .map(([name, data]) => ({
                            name,
                            ...data
                        }))
                        .sort((a, b) => b.avgWrongPercentage - a
                            .avgWrongPercentage); // Sort descending by wrong percentage

                    let foundLeastLearned = false;
                    sortedCategories.forEach(cat => {
                        if (cat.avgWrongPercentage > 50) { // Threshold for "least learned"
                            foundLeastLearned = true;
                            const li = document.createElement('li');
                            li.classList.add('list-group-item', 'list-group-item-danger');
                            let itemDetails = '';
                            for (const itemNum in cat.items) {
                                itemDetails += `${itemNum}: ${cat.items[itemNum]} wrong, `;
                            }
                            itemDetails = itemDetails.slice(0, -2); // Remove trailing comma and space

                            li.innerHTML = `
                    <strong>${cat.name} (${cat.avgWrongPercentage.toFixed(2)}% of students got items wrong)</strong>
                    <p class="mb-0 text-muted">Item Performance (Students Wrong): ${itemDetails}</p>
                    <p class="mb-0"><strong>Insight:</strong> ${insightSuggestions[cat.name] || "No specific insight available for this category. Review individual item performance for more details."}</p>
                `;
                            leastLearnedCategoriesList.appendChild(li);
                        }
                    });

                    if (!foundLeastLearned) {
                        const li = document.createElement('li');
                        li.classList.add('list-group-item', 'list-group-item-success');
                        li.textContent =
                            'Great job! All categories show strong performance (less than 50% average students getting items wrong).';
                        leastLearnedCategoriesList.appendChild(li);
                    }

                    analysisReportCard.style.display = 'block';
                    categoryInputCard.style.display = 'none'; // Hide input card once analysis is shown
                });

                // Function to check if all items are mapped and enable/disable the button
                function checkAllItemsMapped() {
                    const totalItems = parseInt(document.getElementById('totalItems').value) || 0;
                    if (totalItems === 0) {
                        startItemInputBtn.disabled = true;
                        return;
                    }

                    const allItemsCovered = new Set();
                    categoriesData.forEach(cat => {
                        for (let i = cat.start; i <= cat.end; i++) {
                            allItemsCovered.add(i);
                        }
                    });

                    let allMapped = true;
                    if (allItemsCovered.size !== totalItems) {
                        allMapped = false;
                    }
                    for (let i = 1; i <= totalItems; i++) {
                        if (!allItemsCovered.has(i)) {
                            allMapped = false;
                            break;
                        }
                    }
                    startItemInputBtn.disabled = !allMapped;
                }

                // Call checkAllItemsMapped whenever categoriesData or totalItems changes
                document.getElementById('totalItems').addEventListener('input', checkAllItemsMapped);
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // ... your existing JS code ...

                const gradeLevelSelect = document.getElementById('grade_level_id');
                const subjectSelect = document.getElementById('subject_id');

                gradeLevelSelect.addEventListener('change', function() {
                    const gradeLevelId = this.value;

                    // Reset and disable subject select
                    subjectSelect.innerHTML = '<option value="">Loading...</option>';
                    subjectSelect.disabled = true;

                    if (!gradeLevelId) {
                        subjectSelect.innerHTML = '<option value="">Select a grade level first</option>';
                        return;
                    }

                    // Fetch subjects for the selected grade level
                    fetch(`/admin/subjects/grade/${gradeLevelId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(subjects => {
                            subjectSelect.innerHTML =
                                '<option value="">Select a Subject</option>'; // Clear loading message
                            if (subjects.length > 0) {
                                subjects.forEach(subject => {
                                    const option = document.createElement('option');
                                    option.value = subject.id;
                                    option.textContent = subject.name;
                                    subjectSelect.appendChild(option);
                                });
                                subjectSelect.disabled = false; // Enable the dropdown
                            } else {
                                subjectSelect.innerHTML = '<option value="">No subjects found</option>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching subjects:', error);
                            subjectSelect.innerHTML = '<option value="">Failed to load subjects</option>';
                        });
                });
            });
        </script>
    @endpush
@endsection
