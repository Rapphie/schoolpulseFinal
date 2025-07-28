@extends('base')

@section('title', 'Manage Grades')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.4/css/rowGroup.dataTables.min.css">
@endpush

@section('content')
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Manage Grades</h6>
            <div>
                <select id="section-filter" class="form-select form-select-sm">
                    <option value="" selected>Select a Section</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->gradeLevel->name }} - {{ $section->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-primary btn-sm ms-2" data-bs-toggle="modal"
                    data-bs-target="#importReportCardModal">
                    Import Report Card
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="gradesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Grade rows will be dynamically inserted here --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="importReportCardModal" tabindex="-1" role="dialog"
        aria-labelledby="importReportCardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importReportCardModalLabel">Import Report Card</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="importClassRecordForm" method="POST"
                    action="{{ route('teacher.report-card.upload', ['section_id' => '']) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="section_id" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="report_card_file" class="form-label">Select Report Card File</label>
                            <input type="file" class="form-control" id="report_card_file" name="report_card_file"
                                accept=".xlsx, .xls, .csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Import</button>
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
    <script src="https://cdn.datatables.net/rowgroup/1.1.4/js/dataTables.rowGroup.min.js"></script>

    <script>
        $(document).ready(function() {
            const table = $('#gradesTable').DataTable({
                // ... your datatable options
            });

            // --- Section Filter Logic ---
            $('#section-filter').on('change', function() {
                const sectionId = $(this).val();
                table.clear().draw();

                if (sectionId) {
                    // 1. THIS IS THE FIX: Create a URL template from the PHP route function
                    let urlTemplate =
                        "{{ route('teacher.sections.grades', ['section' => ':section_id']) }}";

                    // 2. Replace the placeholder with the real ID from the dropdown
                    let finalUrl = urlTemplate.replace(':section_id', sectionId);

                    // 3. Use the corrected, final URL in your fetch request
                    fetch(finalUrl)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(grades => {
                            // 1. Create a URL template for the report card route
                            let reportCardUrlTemplate =
                                "{{ route('teacher.report-card.show', ['student' => ':student_id']) }}";
                            grades.forEach(studentData => {
                                let finalReportCardUrl = reportCardUrlTemplate.replace(
                                    ':student_id', studentData.student_id);

                                let actionButton =
                                    `<a href="${finalReportCardUrl}" class="btn btn-info btn-sm text-white" target="_blank">View Report Card</a>`;
                                table.row.add([
                                    studentData.student_id,
                                    studentData.student_name,
                                    studentData.gender,
                                    actionButton
                                ]).draw(false);
                            });
                        })
                        .catch(error => {
                            console.error('There has been a problem with your fetch operation:', error);
                            alert(
                                'Failed to load grades. Check the developer console for more details.'
                            );
                        });
                }
            });

            // This part updates the hidden input for your import form
            $('#section-filter').on('change', function() {
                const sectionId = $(this).val();
                const form = $('#importClassRecordForm');

                if (sectionId) {
                    form.find('input[name="section_id"]').val(sectionId);
                    let urlTemplate =
                        "{{ route('teacher.report-card.upload', ['section_id' => ':section_id']) }}";
                    let newUrl = urlTemplate.replace(':section_id', sectionId);
                    form.attr('action', newUrl);
                }
            });
        });
    </script>
@endpush
