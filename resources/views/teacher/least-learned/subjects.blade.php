@extends('base')

@section('title', 'Least Learned Competencies')

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Least Learned Competencies by Subject</h6>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addLLCModal">
                <i data-feather="plus"></i> Add LLC
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="section-filter" class="form-label">Filter by Section</label>
                        <select id="section-filter" class="form-select">
                            <option value="">All Sections</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->name }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">~
                        <label for="subject-filter" class="form-label">Filter by Subject</label>
                        <select id="subject-filter" class="form-select">
                            <option value="">All Subjects</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->name }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="llcTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Section</th>
                            <th>Quarter</th>
                            <th>Competencies</th>
                            <th>Date Identified</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($llcs as $llc)
                            <tr>
                                <td>{{ $llc->subject_name }}</td>
                                <td>{{ $llc->section_name }}</td>
                                <td>{{ $llc->quarter }}</td>
                                <td>{{ $llc->competency_count }}</td>
                                <td>{{ $llc->date_identified }}</td>
                                <td>
                                    <span class="badge bg-{{ $llc->status === 'resolved' ? 'success' : 'warning' }}">
                                        {{ ucfirst($llc->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-start">
                                        <a href="{{ route('teacher.least-learned.view', $llc->id) }}"
                                            class="btn btn-info btn-sm mx-1" title="View LLC">
                                            <i data-feather="eye" class="feather-sm text-white"></i>
                                        </a>
                                        <a href="{{ route('teacher.least-learned.edit', $llc->id) }}"
                                            class="btn btn-primary btn-sm mx-1" title="Edit LLC">
                                            <i data-feather="edit-2" class="feather-sm"></i>
                                        </a>
                                        <a href="{{ route('teacher.least-learned.plan', $llc->id) }}"
                                            class="btn btn-success btn-sm mx-1" title="Intervention Plan">
                                            <i data-feather="clipboard" class="feather-sm"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm mx-1 delete-llc-btn"
                                            data-bs-toggle="modal" data-bs-target="#deleteLLCModal"
                                            data-id="{{ $llc->id }}" data-subject="{{ $llc->subject_name }}"
                                            data-section="{{ $llc->section_name }}" title="Delete LLC">
                                            <i data-feather="trash-2" class="feather-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No least learned competencies found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add LLC Modal -->
    <div class="modal fade" id="addLLCModal" tabindex="-1" aria-labelledby="addLLCModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <a href="/least-learned">Add Least Learned Competency</a>
                    <h5 class="modal-title" id="addLLCModalLabel">Add Least Learned Competency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addLLCForm" action="#" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="section_id" class="form-label">Section <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="section_id" name="section_id" required>
                                    <option value="">Select Section</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="subject_id" class="form-label">Subject <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="quarter" class="form-label">Quarter <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="quarter" name="quarter" required>
                                    <option value="">Select Quarter</option>
                                    <option value="1">1st Quarter</option>
                                    <option value="2">2nd Quarter</option>
                                    <option value="3">3rd Quarter</option>
                                    <option value="4">4th Quarter</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Competencies <span class="text-danger">*</span></label>
                            <div id="competencies-container">
                                <div class="competency-item mb-2 row">
                                    <div class="col-md-11">
                                        <input type="text" class="form-control" name="competencies[]"
                                            placeholder="Enter competency" required>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger remove-competency-btn" disabled>
                                            <i data-feather="x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-success mt-2" id="add-competency-btn">
                                <i data-feather="plus"></i> Add Another Competency
                            </button>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save LLC</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete LLC Modal -->
    <div class="modal fade" id="deleteLLCModal" tabindex="-1" aria-labelledby="deleteLLCModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLLCModalLabel">Delete Least Learned Competency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteLLCForm" action="#" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" id="delete_llc_id" name="llc_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the LLC for <span id="delete-llc-subject-section"></span>?</p>
                        <p class="text-danger">This action cannot be undone and will remove all associated intervention
                            plans.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Feather Icons
            feather.replace();

            // Initialize DataTable
            const table = $('#llcTable').DataTable({
                responsive: true,
                order: [
                    [4, 'desc']
                ]
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Section filter
            $('#section-filter').on('change', function() {
                const value = $(this).val();
                table.column(1).search(value).draw();
            });

            // Subject filter
            $('#subject-filter').on('change', function() {
                const value = $(this).val();
                table.column(0).search(value).draw();
            });

            // Add Competency Button Click Event
            $('#add-competency-btn').on('click', function() {
                const competencyItem = $('.competency-item').first().clone();
                competencyItem.find('input').val('');
                competencyItem.find('.remove-competency-btn').prop('disabled', false);
                $('#competencies-container').append(competencyItem);

                // Re-initialize feather icons
                feather.replace();

                // Attach remove event to the new button
                attachRemoveEvent();
            });

            // Function to handle remove competency item
            function attachRemoveEvent() {
                $('.remove-competency-btn').off('click').on('click', function() {
                    $(this).closest('.competency-item').remove();
                });
            }

            // Initialize remove event
            attachRemoveEvent();

            // Delete LLC Button Click Event
            $('.delete-llc-btn').on('click', function() {
                const id = $(this).data('id');
                const subject = $(this).data('subject');
                const section = $(this).data('section');

                $('#delete_llc_id').val(id);
                $('#delete-llc-subject-section').text(`${subject} - ${section}`);

                const actionUrl = `/teacher/least-learned/${id}/delete`;
                $('#deleteLLCForm').attr('action', actionUrl);
            });
        });
    </script>
@endpush
