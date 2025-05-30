@extends('admin.layout')

@section('title', 'Manage Sections')


@section('content')
    <main class="p-4">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Manage Sections</h1>
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal"
                data-bs-target="#addSectionModal">
                <i data-feather="plus" class="me-2"></i> Add New Section
            </button>
        </div>
        @php
            $gradeLevels = range(1, 6);
            $sections = $sections ?? collect(); // Ensure $sections is a collection
        @endphp
        @foreach ($gradeLevels as $gradeLevel)
            @php
                $sectionsInGrade = $sections->filter(function ($section) use ($gradeLevel) {
                    // First check gradeLevel relationship (new structure)
                    if (isset($section->gradeLevel) && $section->gradeLevel->level == $gradeLevel) {
                        return true;
                    }

                    // Then check grade_level_id (transitional structure)
                    if (isset($section->grade_level_id) && $section->grade_level_id == $gradeLevel) {
                        return true;
                    }

                    // Finally check direct grade_level property (old structure)
                    if (isset($section->grade_level) && $section->grade_level == $gradeLevel) {
                        return true;
                    }

                    return false;
                });
            @endphp
            <div class="card shadow mb-3">
                <div class="card-header py-3" id="headingGrade{{ $gradeLevel }}" style="cursor: pointer;"
                    data-bs-toggle="collapse" data-bs-target="#collapseGrade{{ $gradeLevel }}" aria-expanded="false"
                    aria-controls="collapseGrade{{ $gradeLevel }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Grade Level {{ $gradeLevel }}</h6>
                        <i id="icon-grade-{{ $gradeLevel }}" data-feather="chevron-down"></i>
                    </div>
                </div>
                <div id="collapseGrade{{ $gradeLevel }}" class="collapse"
                    aria-labelledby="headingGrade{{ $gradeLevel }}">
                    <div class="card-body">
                        @if ($sectionsInGrade->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-bordered sections-table" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Student Count</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sectionsInGrade as $section)
                                            <tr>
                                                <td>{{ $section->id }}</td>
                                                <td>{{ $section->name }}</td>
                                                <td>{{ $section->description ?? 'N/A' }}</td>
                                                <td>{{ $section->students_count ?? 0 }}</td>
                                                <td>
                                                    <div class="d-flex justify-content-center align-items-start">
                                                        <button class="btn btn-sm btn-info me-2" data-bs-toggle="tooltip"
                                                            title="View Students"
                                                            onclick="event.stopPropagation(); viewStudents({{ $section->id }})">
                                                            <i data-feather="users" class="feather-sm"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-warning text-white me-2"
                                                            data-bs-toggle="tooltip" title="Edit Section"
                                                            onclick="event.stopPropagation(); editSection({{ $section->id }})">
                                                            <i data-feather="edit-2" class="feather-sm"></i>
                                                        </button>
                                                        <form action="{{ route('admin.sections.destroy', $section->id) }}"
                                                            method="POST" class="d-inline"
                                                            onsubmit="return confirm('Are you sure you want to delete this section? This action cannot be undone.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="event.stopPropagation();" data-bs-toggle="tooltip"
                                                                title="Delete Section">
                                                                <i data-feather="trash-2" class="feather-sm"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center">No sections found for Grade Level {{ $gradeLevel }}.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </main>
@endsection
<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.sections.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Section Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="grade_level_id" class="form-label">Grade Level <span
                                class="text-danger">*</span></label>
                        <select class="form-select @error('grade_level_id') is-invalid @enderror" id="grade_level_id"
                            name="grade_level_id" required>
                            <option value="">Select Grade Level</option>
                            @for ($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}" {{ old('grade_level') == $i ? 'selected' : '' }}>
                                    Grade {{ $i }}
                                </option>
                            @endfor
                        </select>
                        @error('grade_level_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                            rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Section Modal -->
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSectionModalLabel">Edit Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSectionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Section Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_grade_level_id" class="form-label">Grade Level <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="edit_grade_level_id" name="grade_level_id" required>
                            <option value="">Select Grade Level</option>
                            @for ($i = 1; $i <= 6; $i++)
                                <option value="{{ $i }}">Grade {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // We don't need the manual toggle function anymore as we're using Bootstrap's data attributes
        // But we'll keep a utility function if needed programmatically
        function toggleGradeLevel(gradeLevel) {
            const collapseEl = document.getElementById('collapseGrade' + gradeLevel);
            if (!collapseEl) return;

            const bsCollapse = new bootstrap.Collapse(collapseEl, {
                toggle: true
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Check if jQuery is available
            if (typeof $ === 'undefined') {
                console.error('jQuery is not loaded. Loading jQuery from CDN...');
                // Create a script element to load jQuery
                var script = document.createElement('script');
                script.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
                script.onload = initializeComponents;
                document.head.appendChild(script);
            } else {
                initializeComponents();
            }

            function initializeComponents() {
                // Initialize DataTable for all tables with the class 'sections-table'
                if ($.fn.DataTable) {
                    $('.sections-table').each(function() {
                        $(this).DataTable({
                            responsive: true,
                            order: [
                                [1, 'asc']
                            ], // Order by section name (second column, index 1)
                        });
                    });
                } else {
                    console.warn('DataTables not loaded. Tables will not have sorting/filtering functionality.');
                }

                // Make sure any action buttons inside tables don't trigger the collapse
                document.querySelectorAll('.table button, .table form').forEach(function(el) {
                    el.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                });

                // Utility function to manually open a grade level
                window.openGradeLevel = function(gradeLevel) {
                    const collapseEl = document.getElementById('collapseGrade' + gradeLevel);
                    if (collapseEl && !collapseEl.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(collapseEl);
                        bsCollapse.show();
                    }
                };

                // Make sure action buttons don't trigger collapse
                document.querySelectorAll('.btn-sm, form.d-inline').forEach(function(el) {
                    el.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                });

                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Initial replacement of feather icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }

                // Handle collapse icon change and DataTable redraw
                var collapseElements = document.querySelectorAll('div.collapse[id^="collapseGrade"]');
                collapseElements.forEach(function(collapseEl) {
                    var gradeId = collapseEl.id;
                    var gradeLevel = gradeId.replace('collapseGrade', '');
                    var headerButton = document.querySelector('[data-bs-target="#' + gradeId + '"]');
                    var icon = document.getElementById('icon-grade-' + gradeLevel);

                    // Update icon and initialize DataTable on show event
                    collapseEl.addEventListener('show.bs.collapse', function() {
                        if (icon) {
                            icon.setAttribute('data-feather', 'chevron-up');
                            feather.replace();
                        }

                        // Adjust DataTable columns on show if DataTables is available
                        if ($.fn.DataTable) {
                            var table = $(this).find('.sections-table');
                            if ($.fn.DataTable.isDataTable(table)) {
                                setTimeout(function() {
                                    table.DataTable().columns.adjust().responsive.recalc();
                                }, 100);
                            }
                        }
                    });

                    // Update icon on hide event
                    collapseEl.addEventListener('hide.bs.collapse', function() {
                        if (icon) {
                            icon.setAttribute('data-feather', 'chevron-down');
                            feather.replace();
                        }
                    });
                });
            }
        });

        // Functions for editing and viewing students
        window.editSection = function(sectionId) {
            fetch(`/admin/sections/${sectionId}/data`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    const form = document.getElementById('editSectionForm');
                    form.action = `/admin/sections/${sectionId}`;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_description').value = data.description || '';

                    // Set the grade level value (try different properties based on database schema)
                    const gradeLevelSelect = document.getElementById('edit_grade_level_id');
                    if (data.grade_level_id) {
                        // New schema with direct grade_level_id
                        gradeLevelSelect.value = data.grade_level_id;
                    } else if (data.gradeLevel && data.gradeLevel.level) {
                        // New schema with gradeLevel relationship
                        gradeLevelSelect.value = data.gradeLevel.level;
                    } else if (data.grade_level) {
                        // Old schema with direct grade_level
                        gradeLevelSelect.value = data.grade_level;
                    }

                    const editModal = new bootstrap.Modal(document.getElementById('editSectionModal'));
                    editModal.show();
                })
                .catch(error => {
                    console.error('Error fetching section data:', error);
                    alert('Error loading section data. Please try again or contact support.');
                });
        }

        window.viewStudents = function(sectionId) {
            window.location.href = `/admin/sections/students/${sectionId}/`;
        }
    </script>
@endpush
