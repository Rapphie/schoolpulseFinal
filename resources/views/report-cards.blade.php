@extends('base')

@section('content')
    <div class="container">
        <h1>Extracted Report Card Data</h1>

        @if (isset($reportCard['headers']) && !empty($reportCard['headers']))
            <h2>Report Card Details</h2>
            <table class="table table-bordered">
                @foreach ($reportCard['headers'] as $key => $value)
                    <tr>
                        <th>{{ ucfirst(str_replace('_', ' ', $key)) }}</th>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if (isset($reportCard['male_students']) && !empty($reportCard['male_students']))
            <h2>Male Students</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>LRN</th>
                        <th>1st Quarter</th>
                        <th>2nd Quarter</th>
                        <th>3rd Quarter</th>
                        <th>4th Quarter</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportCard['male_students'] as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student }}</td>
                            <td>{{ $reportCard['male_lrns'][$index] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['male_grades'][$index][1] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['male_grades'][$index][2] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['male_grades'][$index][3] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['male_grades'][$index][4] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if (isset($reportCard['female_students']) && !empty($reportCard['female_students']))
            <h2>Female Students</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>LRN</th>
                        <th>1st Quarter</th>
                        <th>2nd Quarter</th>
                        <th>3rd Quarter</th>
                        <th>4th Quarter</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reportCard['female_students'] as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student }}</td>
                            <td>{{ $reportCard['female_lrns'][$index] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['female_grades'][$index][1] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['female_grades'][$index][2] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['female_grades'][$index][3] ?? 'N/A' }}</td>
                            <td>{{ $reportCard['female_grades'][$index][4] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if (empty($reportCard['headers']) && empty($reportCard['male_students']) && empty($reportCard['female_students']))
            <div class="alert alert-warning" role="alert">
                No data could be extracted from the uploaded file. Please ensure the file is formatted correctly.
            </div>
        @endif

        <a href="{{ route('teacher.grades') }}" class="btn btn-primary">Upload Another File</a>
    </div>
@endsection
