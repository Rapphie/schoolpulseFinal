@extends('base')

@section('title', 'Manage Grade Levels')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.grade-levels.index') }}">Grade Levels</a></li>
                    <li class="breadcrumb-item active" aria-current="page">List</li>
                </ol>
            </nav>
            <button class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                data-bs-target="#addGradeLevelModal">
                <i data-feather="plus" class="feather-sm me-1"></i> Add Grade Level
            </button>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <table id="grade-levels-table" class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th class="dt-head-left">Level</th>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gradeLevels as $grade)
                            <tr>
                                <td class="dt-body-left">{{ $grade->level }}</td>
                                <td>{{ $grade->name }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary edit-btn" data-bs-toggle="modal"
                                        data-bs-target="#editGradeLevelModal" data-id="{{ $grade->id }}"
                                        data-name="{{ $grade->name }}" data-level="{{ $grade->level }}">
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.grade-levels.destroy', $grade) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addGradeLevelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Grade Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.grade-levels.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="level" class="form-label">Level (e.g., 1, 2, 3)</label>
                            <input type="number" class="form-control" name="level" required>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name (e.g., Grade 1)</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>

                        {{-- Dynamically added section inputs --}}
                        <div class="mb-3">
                            <label class="form-label">Sections</label>
                            <div id="sections-container">
                                {{-- Initial Section Input --}}
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="sections[]"
                                        placeholder="Section Name (e.g., A, B, St. John)">
                                </div>
                            </div>
                            <div class="w-100  d-flex justify-end">
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2 d-flex align-items-center"
                                    id="add-section-btn">
                                    <i data-feather="plus" class="feather-sm"></i> Add Section
                                </button>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Grade Level</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editGradeLevelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Grade Level</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editGradeLevelForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_level" class="form-label">Level</label>
                            <input type="number" class="form-control" id="edit_level" name="level" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Grade Level</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Existing script for Edit Modal ---
            const editModal = document.getElementById('editGradeLevelModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const level = button.getAttribute('data-level');

                const form = editModal.querySelector('#editGradeLevelForm');
                const nameInput = editModal.querySelector('#edit_name');
                const levelInput = editModal.querySelector('#edit_level');

                form.action = `/admin/grade-levels/${id}`;
                nameInput.value = name;
                levelInput.value = level;
            });

            // --- New script for adding/removing sections in Add Modal ---
            const addSectionBtn = document.getElementById('add-section-btn');
            const sectionsContainer = document.getElementById('sections-container');

            // Add a new section input field
            addSectionBtn.addEventListener('click', function() {
                const newSectionDiv = document.createElement('div');
                newSectionDiv.classList.add('input-group', 'mb-2');
                newSectionDiv.innerHTML = `
                <input type="text" class="form-control" name="sections[]" placeholder="Section Name">
                <button class="btn btn-outline-danger remove-section-btn" type="button">Remove</button>
            `;
                sectionsContainer.appendChild(newSectionDiv);
            });

            // Remove a section input field using event delegation
            sectionsContainer.addEventListener('click', function(event) {
                if (event.target && event.target.classList.contains('remove-section-btn')) {
                    event.target.closest('.input-group').remove();
                }
            });

            // Initialize DataTables for the grade levels table
            if (window.jQuery && $.fn.DataTable) {
                $('#grade-levels-table').DataTable();
            }
        });
    </script>
@endpush
