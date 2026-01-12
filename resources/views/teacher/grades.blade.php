@extends('base')

@section('title', 'Manage Grades')

@push('styles')
    {{-- You can include DataTables specific styles here if needed --}}
@endpush

@section('content')
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">View Student Grades</h6>
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
                    {{-- Student data will be populated dynamically by JavaScript --}}
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
            </div>
        </div>
    </div>
@endsection

@push('scripts')
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

            // Re-initialize feather icons if they are used
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>
@endpush
