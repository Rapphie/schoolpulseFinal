@extends('base')

@section('title', 'Manage Grades')

@push('styles')
    {{-- You can include DataTables specific styles here if needed --}}
@endpush

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Manage Student Grades</h6>
            <div class="d-flex align-items-center">
                <label for="section-filter" class="form-label me-2 mb-0">Section:</label>
                <select id="section-filter" class="form-select form-select-sm" style="width: 250px;">
                    <option value="" selected>-- Select a Section --</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->gradeLevel->name }} - {{ $section->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal"
                    data-bs-target="#importReportCardModal" id="importBtn" disabled>
                    <i data-feather="upload" class="feather-sm"></i> Import
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="gradesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>lrn</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Christian</td>
                            <td>Plasabas</td>
                            <td>Male</td>
                            <td><a href="{{ route('teacher.report-card.show') }}" class="btn btn-info btn-sm text-white"
                                    target="_blank">View
                                    Report Card</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Import Report Card Modal -->
    <div class="modal fade" id="importReportCardModal" tabindex="-1" role="dialog"
        aria-labelledby="importReportCardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importReportCardModalLabel">Import Report Card</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importClassRecordForm" method="POST" action="" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="report_card_file" class="form-label">Select Report Card File (.csv, .xlsx)</label>
                            <input type="file" class="form-control" id="report_card_file" name="report_card_file"
                                accept=".xlsx, .xls, .csv" required>
                        </div>
                        <div class="alert alert-info">
                            <p class="mb-0">Please ensure your file has the correct format before uploading.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload and Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- jQuery and DataTables CDN links --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            const table = $('#gradesTable').DataTable({
                processing: true,
                columns: [{
                        data: 'student_id'
                    },
                    {
                        data: 'student_name'
                    },
                    {
                        data: 'gender'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                // Add a message for when the table is empty
                language: {
                    emptyTable: "Please select a section to view students."
                }
            });

            // --- Section Filter Logic ---
            $('#section-filter').on('change', function() {
                const sectionId = $(this).val();
                table.clear().draw();

                if (sectionId) {
                    // 1. Create a URL template from the PHP route function
                    let urlTemplate =
                        "{{ route('teacher.sections.grades', ['section' => ':section_id']) }}";

                    // 2. Replace the placeholder with the real ID from the dropdown
                    let finalUrl = urlTemplate.replace(':section_id', sectionId);

                    // 3. Use the corrected, final URL in your fetch request
                    fetch(finalUrl)
                        .then(response => response.json())
                        .then(students => {
                            // ... logic to add students to the table
                        });
                }
            });

            // Re-initialize feather icons if they are used
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>
@endpush
