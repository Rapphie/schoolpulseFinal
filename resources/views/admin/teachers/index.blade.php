@extends('base')

@section('title', 'Manage Teachers')

@section('internal-css')
    <style>
        .advisory-orb {
            display: inline-flex;
            align-items: center;
            padding: 0.35em 0.65em;
            font-size: .85em;
            font-weight: 500;
            line-height: 1;
            color: #fff;
            background-color: #4e73df;
            border-radius: 10rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
            cursor: pointer;
            vertical-align: middle;
        }

        /* Table Styling */
        #teachersTable {
            border-collapse: separate;
            border-spacing: 0;
        }

        #teachersTable thead th {
            background-color: #f8f9fc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            color: #4e73df;
            border-bottom: 2px solid #e3e6f0;
            vertical-align: middle;
        }

        #teachersTable tbody td {
            border-top: 1px solid #e3e6f0;
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }

        #teachersTable tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
        }

        /* Status Badges */
        .badge.bg-success {
            background-color: #1cc88a !important;
        }

        .badge.bg-warning {
            background-color: #f6c23e !important;
            color: #000 !important;
        }

        .badge.bg-danger {
            background-color: #e74a3b !important;
        }

        /* Message Modal Styles */
        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100% - 3.5rem);
        }

        .message-modal .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .message-modal .modal-dialog {
            max-width: 800px;
            margin: 1.75rem auto;
        }

        .message-modal .modal-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-bottom: none;
            padding: 2rem 1.5rem;
        }

        .message-modal .modal-title {
            font-weight: 600;
            font-size: 1.6rem;
            color: white;
        }

        .message-modal .modal-title i {
            color: white;
        }

        .message-modal .modal-body {
            padding: 2rem;
        }

        .message-modal .modal-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }

        .message-modal .modal-title {
            font-weight: 600;
            font-size: 1.4rem;
        }

        .message-modal .modal-body {
            padding: 1.5rem;
        }

        .message-modal .form-control,
        .message-modal .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e3e6f0;
            transition: all 0.3s;
        }

        .message-modal .form-control:focus,
        .message-modal .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .message-modal textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .message-modal .modal-footer {
            border-top: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
            background-color: #f8f9fc;
        }

        .message-modal .btn-send {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .message-modal .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.3);
        }

        .message-modal .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .message-modal .btn-close:hover {
            opacity: 1;
        }

        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.5rem 1rem;
            background-color: #f8f9fc;
            border: 1px dashed #d1d3e2;
            border-radius: 8px;
            color: #6e707e;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-label:hover {
            background-color: #eaeaf1;
            border-color: #b7b9cc;
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6e707e;
        }

        .character-count {
            font-size: 0.75rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }

        /* Animation for modal */
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal.fade .modal-dialog {
            animation: modalSlideIn 0.3s ease-out;
        }

        /* Button Styles */
        .btn-outline-primary {
            color: #4e73df;
            border-color: #4e73df;
        }

        .btn-outline-primary:hover {
            background-color: #4e73df;
            color: #fff;
        }

        .btn-outline-success {
            color: #1cc88a;
            border-color: #1cc88a;
        }

        .btn-outline-success:hover {
            background-color: #1cc88a;
            color: #fff;
        }

        .btn-outline-danger {
            color: #e74a3b;
            border-color: #e74a3b;
        }

        .btn-outline-danger:hover {
            background-color: #e74a3b;
            color: #fff;
        }

        /* Add Teacher Card */
        .border-dashed {
            border: 2px dashed #d1d3e2 !important;
            background-color: #f8f9fc;
            transition: all 0.3s ease;
        }

        .border-dashed:hover {
            background-color: #eaecf4;
            transform: translateY(-5px);
            cursor: pointer;
        }

        /* Modal Styles */
        .modal-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .modal-title {
            color: #4e73df;
            font-weight: 600;
        }

        .form-label {
            font-weight: 500;
            color: #5a5c69;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d3e2;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #b7b9cc;
        }

        /* Responsive Adjustments */
        @media (max-width: 1399.98px) {
            .col-xxl-3 {
                flex: 0 0 33.333333%;
                max-width: 33.33333333%;
            }
        }

        @media (max-width: 1199.98px) {
            .col-xl-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (max-width: 767.98px) {
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .top-bar h1 {
                font-size: 1.25rem;
            }
        }

        /* Animation for rows */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #teachersTable tbody tr {
            animation: fadeIn 0.3s ease-out forwards;
        }

        #teachersTable tbody tr:nth-child(2) {
            animation-delay: 0.1s;
        }

        #teachersTable tbody tr:nth-child(3) {
            animation-delay: 0.2s;
        }

        #teachersTable tbody tr:nth-child(4) {
            animation-delay: 0.3s;
        }
    </style>
@endsection
@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.teachers.index') }}">Teachers</a></li>
                <li class="breadcrumb-item active" aria-current="page">List</li>
            </ol>
        </nav>
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary d-flex align-items-center">
            <i data-feather="plus" class="me-1"></i> Add Teacher
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i data-feather="search"></i></span>
                <input type="text" class="form-control border-start-0" id="searchTeacher"
                    placeholder="Search teachers...">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="gradeLevelFilter">
                <option value="">All Grade Levels</option>
                <option>Grade 1</option>
                <option>Grade 2</option>
                <option>Grade 3</option>
                <option>Grade 4</option>
                <option>Grade 5</option>
                <option>Grade 6</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="statusFilter">
                <option value="">All Status</option>
                <option>Active</option>
                <option>On Leave</option>
                <option>Inactive</option>
            </select>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="teachersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Advisories</th>
                            <th>Contact</th>
                            <th>Subjects</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teachers as $teacher)
                            @if ($teacher->user)
                                <tr>
                                    <td class="align-middle">
                                        <img src="{{ $teacher->user->profile_picture ? asset('storage/' . $teacher->user->profile_picture) : asset('images/user-placeholder.png') }}"
                                            class="rounded-circle" style="width: 40px; height: 40px;" alt="Teacher">
                                    </td>
                                    <td class="align-middle">
                                        <span class="fw-bold">{{ $teacher->user->full_name }}</span>
                                    </td>
                                    <td class="align-middle">
                                        @if ($teacher->classes->isNotEmpty())
                                            @php
                                                $advisories = $teacher->classes->map(function ($class) {
                                                    return optional($class->section->gradeLevel)->name .
                                                        '-' .
                                                        optional($class->section)->name;
                                                });
                                                $advisoryText = $advisories->implode(', ');
                                                $advisoryList =
                                                    '<ul>' .
                                                    $advisories
                                                        ->map(fn($val) => '<li>' . e($val) . '</li>')
                                                        ->implode('') .
                                                    '</ul>';
                                            @endphp
                                            <div class="advisory-orb" data-bs-toggle="popover" data-bs-trigger="hover"
                                                data-bs-html="true" data-bs-content="{{ $advisoryList }}"
                                                title="Advisory Classes">
                                                {{ $advisoryText }}
                                            </div>
                                        @else
                                            <span class="badge bg-secondary">No Advisory</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center"><i data-feather="mail"
                                                class="feather-sm me-1"></i> {{ $teacher->user->email }}
                                        </div>
                                        <div class="d-flex justify-content-center"><i data-feather="phone"
                                                class="feather-sm me-1"></i>
                                            {{ $teacher->phone ?? 'N/A' }}</div>
                                    </td>
                                    <td class="align-middle">
                                        @forelse ($teacher->subjects->unique() as $subject)
                                            <span class="badge bg-info text-dark">{{ $subject->name }}</span>
                                        @empty
                                            <span class="badge bg-secondary">No Subjects</span>
                                        @endforelse
                                    </td>
                                    <td class="align-middle">
                                        <span
                                            class="badge {{ $teacher->status == 'active' ? 'bg-success' : 'bg-danger' }}">{{ $teacher->status }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="d-flex gap-2">

                                            <a href="{{ route('admin.teachers.edit', $teacher->user) }}"
                                                class="btn btn-sm btn-outline-success" title="Edit">
                                                <i data-feather="edit-2"></i>
                                            </a>
                                            <form action="{{ route('admin.teachers.destroy', $teacher->id) }}"
                                                method="POST" class="mx-1"
                                                onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="tooltip" title="Delete">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No teachers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center" id="deleteConfirmationModalLabel">
                        <i data-feather="alert-triangle" class="feather-lg me-2"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this teacher? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteTeacherForm" action="" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger d-flex align-items-center" id="confirmDeleteBtn">
                            <i data-feather="trash-2" class="feather-sm me-1"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade message-modal" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">
                        <i data-feather="mail" class="feather-lg me-2"></i> Send Message
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="messageForm">
                        <div class="mb-3">
                            <label for="recipient" class="form-label">Recipient</label>
                            <input type="email" class="form-control" id="recipient" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="5" required></textarea>
                            <div class="character-count">0/2000</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Attachments</label>
                            <div class="file-upload">
                                <label for="attachment" class="file-upload-label w-100">
                                    <i data-feather="paperclip" class="feather-sm"></i>
                                    <span>Drag & drop files here or click to browse</span>
                                </label>
                                <input type="file" id="attachment" class="file-upload-input">
                            </div>
                            <div class="file-name mt-2"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-send">
                        <i data-feather="send" class="feather-sm me-1"></i> Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection


@push('scripts')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable with better configuration for list view
            $('#teachersTable').DataTable({
                responsive: true,
                order: [
                    [1, 'asc']
                ], // Sort by name column (index 1)
                columnDefs: [{
                        orderable: false,
                        targets: [0, 6]
                    }, // Disable sorting for profile image and actions columns
                    {
                        width: "10%",
                        targets: 0
                    }, // Profile column width
                    {
                        width: "15%",
                        targets: 1
                    }, // Name column width
                    {
                        width: "15%",
                        targets: 2
                    }, // Position column width
                    {
                        width: "20%",
                        targets: 3
                    }, // Contact column width
                    {
                        width: "15%",
                        targets: 4
                    }, // Subjects column width
                    {
                        width: "10%",
                        targets: 5
                    }, // Status column width
                    {
                        width: "15%",
                        targets: 6
                    } // Actions column width
                ],
                language: {
                    lengthMenu: "Show _MENU_ teachers per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ teachers",
                    infoEmpty: "Showing 0 to 0 of 0 teachers",
                    infoFiltered: "(filtered from _MAX_ total teachers)"
                },
                dom: 'lrtip' // This removes the search box from the DataTable
            });

            // Connect the custom search box to DataTable
            $('#searchTeacher').on('keyup', function() {
                $('#teachersTable').DataTable().search(this.value).draw();
            });

            // Connect the grade level filter
            $('#gradeLevelFilter').on('change', function() {
                let value = $(this).val();
                $('#teachersTable').DataTable().column(2).search(value).draw();
            });

            // Connect the status filter
            $('#statusFilter').on('change', function() {
                let value = $(this).val();
                $('#teachersTable').DataTable().column(5).search(value).draw();
            });

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Initialize popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl)
            })

            // Initialize message modal functionality
            $('.btn-message').on('click', function() {
                const email = $(this).data('email');
                // Prefill email in message modal if exists
                if (email && $('#messageForm #recipient').length) {
                    $('#messageForm #recipient').val(email);
                }
                $('#messageModal').modal('show');
            });

            // Handle delete button click
            $('.deleteTeacherBtn').on('click', function() {
                const teacherId = $(this).data('teacher-id');
                $('#deleteTeacherForm').attr('action', `/admin/teachers/destroy=${teacherId}`);
            });
        });

        function editTeacher(teacherId) {
            $('#editTeacherModal').modal('show');
        }

        function viewTeacher(teacherId) {
            alert('View teacher details for ID: ' + teacherId);
        }
    </script>
@endpush
