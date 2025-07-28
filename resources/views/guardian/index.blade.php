@extends('base')

@section('title', 'My Student Grades')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My Student Grades</li>
                </ol>
            </nav>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white rounded-t-lg">
                <h6 class="m-0 font-weight-bold">Grades for Kim Cyril - Current School Year</h6>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <h5 class="text-gray-800 mb-3">Student Information</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                        <div>
                            <p><strong>Student Name:</strong> Kim Cyril</p>
                            <p><strong>Student ID:</strong> STD12345</p>
                            <p><strong>Grade Level:</strong> Grade 7</p>
                        </div>
                        <div>
                            <p><strong>Current School Year:</strong> 2025-2026</p>
                            <p><strong>Class/Section:</strong> Dagohoy</p>
                            <p><strong>LRN:</strong> 123456789012</p>
                        </div>
                    </div>
                </div>

                <hr class="my-4 border-gray-300">

                <h5 class="text-gray-800 mb-3">Academic Performance</h5>

                <ul class="nav nav-tabs mb-3" id="gradeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="first-quarter-tab" data-bs-toggle="tab"
                            data-bs-target="#first-quarter" type="button" role="tab" aria-controls="first-quarter"
                            aria-selected="true">1st Quarter</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="second-quarter-tab" data-bs-toggle="tab"
                            data-bs-target="#second-quarter" type="button" role="tab" aria-controls="second-quarter"
                            aria-selected="false">2nd Quarter</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="third-quarter-tab" data-bs-toggle="tab" data-bs-target="#third-quarter"
                            type="button" role="tab" aria-controls="third-quarter" aria-selected="false">3rd
                            Quarter</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="fourth-quarter-tab" data-bs-toggle="tab"
                            data-bs-target="#fourth-quarter" type="button" role="tab" aria-controls="fourth-quarter"
                            aria-selected="false">4th Quarter</button>
                    </li>
                </ul>

                <div class="tab-content" id="gradeTabsContent">
                    <!-- 1st Quarter Tab Content -->
                    <div class="tab-pane fade show active" id="first-quarter" role="tabpanel"
                        aria-labelledby="first-quarter-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover w-full text-gray-800">
                                <thead class="bg-gray-200">
                                    <tr>
                                        <th class="px-4 py-2">Subject</th>
                                        <th class="px-4 py-2">Teacher</th>
                                        <th class="px-4 py-2 text-center">Grade</th>
                                        <th class="px-4 py-2">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-2">Mathematics 7</td>
                                        <td class="px-4 py-2">Mr. John Doe</td>
                                        <td class="px-4 py-2 text-center">88</td>
                                        <td class="px-4 py-2">Good effort, needs to review algebra.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">Science 7</td>
                                        <td class="px-4 py-2">Ms. Jane Smith</td>
                                        <td class="px-4 py-2 text-center">92</td>
                                        <td class="px-4 py-2">Excellent work in experiments.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">English 7</td>
                                        <td class="px-4 py-2">Mrs. Emily White</td>
                                        <td class="px-4 py-2 text-center">85</td>
                                        <td class="px-4 py-2">Participates well, focus on grammar.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">Filipino 7</td>
                                        <td class="px-4 py-2">Gng. Maria Reyes</td>
                                        <td class="px-4 py-2 text-center">90</td>
                                        <td class="px-4 py-2">Mahusay! Continue reading Filipino literature.</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">Araling Panlipunan 7</td>
                                        <td class="px-4 py-2">G. Jose Cruz</td>
                                        <td class="px-4 py-2 text-center">87</td>
                                        <td class="px-4 py-2">Understands concepts, improve research skills.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2nd Quarter Tab Content -->
                    <div class="tab-pane fade" id="second-quarter" role="tabpanel" aria-labelledby="second-quarter-tab">
                        <div class="alert alert-info text-center py-3">
                            Grades for 2nd Quarter are not yet available.
                        </div>
                    </div>

                    <!-- 3rd Quarter Tab Content -->
                    <div class="tab-pane fade" id="third-quarter" role="tabpanel" aria-labelledby="third-quarter-tab">
                        <div class="alert alert-info text-center py-3">
                            Grades for 3rd Quarter are not yet available.
                        </div>
                    </div>

                    <!-- 4th Quarter Tab Content -->
                    <div class="tab-pane fade" id="fourth-quarter" role="tabpanel" aria-labelledby="fourth-quarter-tab">
                        <div class="alert alert-info text-center py-3">
                            Grades for 4th Quarter are not yet available.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Bootstrap JS for tabs (if not already included in base.blade.php) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Optional: Any specific JavaScript for this page can go here
        // For example, if you wanted to dynamically load grades, etc.
    </script>
@endpush
