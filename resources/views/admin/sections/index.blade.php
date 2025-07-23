@extends('base')

@section('title', 'Sections')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.sections.index') }}">Sections</a></li>
                    <li class="breadcrumb-item active" aria-current="page">List</li>
                </ol>
            </nav>
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal"
                data-bs-target="#addSectionModal">
                <i data-feather="plus" class="me-2"></i> Add Section
            </button>
        </div>

        @if ($sections->isEmpty())
            <div class="alert alert-info text-center" role="alert">
                <h4 class="alert-heading">No Sections Found!</h4>
                <p>There are no sections created yet. You can start by adding a new section for any grade level.</p>
                <hr>
                <p class="mb-0">Click the "Add Section" button to get started.</p>
            </div>
        @else
            <ul class="nav nav-tabs" id="gradeLevelTabs" role="tablist">
                @php
                    $gradeLevels = range(1, 6);
                @endphp
                @foreach ($gradeLevels as $gradeLevel)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="grade-{{ $gradeLevel }}-tab"
                            data-bs-toggle="tab" data-bs-target="#grade-{{ $gradeLevel }}-pane" type="button"
                            role="tab" aria-controls="grade-{{ $gradeLevel }}-pane"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">Grade {{ $gradeLevel }}</button>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content" id="gradeLevelTabsContent">
                @foreach ($gradeLevels as $gradeLevel)
                    @php
                        $sectionsInGrade = $sections->filter(function ($section) use ($gradeLevel) {
                            if (isset($section->gradeLevel) && $section->gradeLevel->level == $gradeLevel) {
                                return true;
                            }
                            if (isset($section->grade_level_id) && $section->grade_level_id == $gradeLevel) {
                                return true;
                            }
                            if (isset($section->grade_level) && $section->grade_level == $gradeLevel) {
                                return true;
                            }
                            return false;
                        });
                    @endphp
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="grade-{{ $gradeLevel }}-pane"
                        role="tabpanel" aria-labelledby="grade-{{ $gradeLevel }}-tab" tabindex="0">
                        <div class="card card-body">
                            @if ($sectionsInGrade->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-hover sections-table" width="100%" cellspacing="0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Adviser</th>
                                                <th class="text-center">Students</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($sectionsInGrade as $section)
                                                <tr>
                                                    <td>{{ $section->id }}</td>
                                                    <td>{{ $section->name }}</td>
                                                    <td>{{ $section->teacher->user->full_name ?? 'N/A' }}</td>
                                                    <td class="text-center">
                                                        {{ $section->students_count ?? 0 }} /
                                                        {{ $section->capacity ?? 'N/A' }}
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                                type="button" id="dropdownMenuButton-{{ $section->id }}"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                                Actions
                                                            </button>
                                                            <ul class="dropdown-menu"
                                                                aria-labelledby="dropdownMenuButton-{{ $section->id }}">
                                                                <li><a class="dropdown-item"
                                                                        href="{{ route('admin.sections.manage', $section->id) }}"><i
                                                                            data-feather="settings"
                                                                            class="feather-sm me-2"></i>Manage</a>
                                                                </li>
                                                                <li><a class="dropdown-item" href="#"
                                                                        onclick="event.preventDefault(); viewStudents({{ $section->id }})"><i
                                                                            data-feather="users"
                                                                            class="feather-sm me-2"></i>View Students</a>
                                                                </li>
                                                                <li><a class="dropdown-item" href="#"
                                                                        onclick="event.preventDefault(); editSection({{ $section->id }})"><i
                                                                            data-feather="edit-2"
                                                                            class="feather-sm me-2"></i>Edit</a></li>
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li>
                                                                    <form
                                                                        action="{{ route('admin.sections.destroy', $section->id) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Are you sure you want to delete this section? This action cannot be undone.');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                            class="dropdown-item text-danger"><i
                                                                                data-feather="trash-2"
                                                                                class="feather-sm me-2"></i>Delete</button>
                                                                    </form>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-center text-muted mb-0">No sections found for Grade Level
                                    {{ $gradeLevel }}.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addSectionModalLabel">Add Section</h5>
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
                                <option value="{{ $i }}"
                                    {{ old('grade_level_id') == $i ? 'selected' : '' }}>Grade {{ $i }}
                                </option>
                            @endfor
                        </select>
                        @error('grade_level_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="teacher_id" class="form-label">Adviser <span class="text-danger">*</span></label>
                        <select class="form-select @error('teacher_id') is-invalid @enderror" id="teacher_id"
                            name="teacher_id" required>
                            <option value="">Select Adviser</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}"
                                    {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->user->full_name ?? ($teacher->user->name ?? ($teacher->name ?? 'Teacher #' . $teacher->id)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('capacity') is-invalid @enderror"
                            id="capacity" name="capacity" min="1" max="50"
                            value="{{ old('capacity', 40) }}" required>
                        @error('capacity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="subjects" class="form-label">Subjects</label>
                        <select class="form-select" id="subjects" name="subjects[]" multiple>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
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
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Class Record Modal -->
<div class="modal fade" id="uploadClassRecordModal" tabindex="-1" aria-labelledby="uploadClassRecordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadClassRecordModalLabel">Upload Class Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('teacher.class-record.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="section_id" class="form-label">Section <span class="text-danger">*</span></label>
                        <select class="form-select" id="section_id" name="section_id" required>
                            <option value="">Select Section</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="class_record_file" class="form-label">Class Record File <span
                                class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="class_record_file" name="class_record_file"
                            required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadButton">Upload</button>
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

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #d0ebff;
            color: #005b9f;
            border: none;
            border-radius: 15px;
            padding: 4px 8px;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: #005b9f;
            margin-left: 8px;
        }

        .sections-table .dropdown-menu {
            position: absolute !important;
        }

        .table-responsive {
            overflow: visible !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.table-responsive').forEach(function(table) {
                table.addEventListener('hide.bs.dropdown', function(e) {
                    table.style.overflow = 'auto';
                });
            });

            if (window.jQuery && $('#teacher_id').length) {
                $('#teacher_id').select2({
                    dropdownParent: $('#addSectionModal'),
                    width: '100%',
                    placeholder: 'Select Adviser',
                    allowClear: true
                });
            }

            if (window.jQuery && $('#subjects').length) {
                $('#subjects').select2({
                    dropdownParent: $('#addSectionModal'),
                    width: '100%',
                    placeholder: 'Select Subjects',
                    allowClear: true,
                    theme: 'bootstrap-5'
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

                    const gradeLevelSelect = document.getElementById('edit_grade_level_id');
                    if (data.grade_level_id) {
                        gradeLevelSelect.value = data.grade_level_id;
                    } else if (data.gradeLevel && data.gradeLevel.level) {
                        gradeLevelSelect.value = data.gradeLevel.level;
                    } else if (data.grade_level) {
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
