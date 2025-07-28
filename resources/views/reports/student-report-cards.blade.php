<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Cards for {{ $section->gradeLevel->name }} - {{ $section->name }}</title>
    {{-- Basic styling for a clean, printable report card --}}
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .report-card {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 20px;
            page-break-after: always; /* This ensures each report card starts on a new page when printing */
        }
        .report-card:last-child {
            page-break-after: auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h4, .header h5 {
            margin: 5px 0;
        }
        .student-info {
            margin-bottom: 20px;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .grades-table th {
            background-color: #f2f2f2;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        /* Hide the print button when printing the page */
        @media print {
            .print-button {
                display: none;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <button class="print-button" onclick="window.print()">Print All Reports</button>

    @if($students->isEmpty())
        <p>No students found in this section.</p>
    @else
        {{-- This loop creates a separate report card for each student --}}
        @foreach($students as $student)
            <div class="report-card">
                <div class="header">
                    <h4>STA. CRUZ ELEM. SCHOOL</h4>
                    <h5>Panabo City, Davao Region</h5>
                    <h5>Report on Learning Progress and Achievement</h5>
                    <h6>SY: 2025-2026</h6>
                </div>

                <div class="student-info">
                    <strong>Student Name:</strong> {{ $student->last_name }}, {{ $student->first_name }} <br>
                    <strong>Grade & Section:</strong> {{ $section->gradeLevel->name }} - {{ $section->name }}
                </div>

                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Learning Areas</th>
                            <th>Quarter 1</th>
                            <th>Quarter 2</th>
                            <th>Quarter 3</th>
                            <th>Quarter 4</th>
                            <th>Final Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- This inner loop lists all subjects and grades for the student --}}
                        @foreach($student->gradesBySubject as $subjectName => $grades)
                            @php
                                // Place the grades into a simple map for easy access
                                $quarterlyGrades = $grades->keyBy('quarter');
                                $q1 = $quarterlyGrades->get('1') ? $quarterlyGrades->get('1')->grade : null;
                                $q2 = $quarterlyGrades->get('2') ? $quarterlyGrades->get('2')->grade : null;
                                $q3 = $quarterlyGrades->get('3') ? $quarterlyGrades->get('3')->grade : null;
                                $q4 = $quarterlyGrades->get('4') ? $quarterlyGrades->get('4')->grade : null;

                                // Calculate final grade (simple average)
                                $gradesForAverage = array_filter([$q1, $q2, $q3, $q4]);
                                $finalGrade = !empty($gradesForAverage) ? round(array_sum($gradesForAverage) / count($gradesForAverage)) : null;
                                $remarks = $finalGrade !== null ? ($finalGrade >= 75 ? 'PASSED' : 'FAILED') : 'N/A';
                            @endphp
                            <tr>
                                <td style="text-align: left;">{{ $subjectName }}</td>
                                <td>{{ $q1 ?? 'N/A' }}</td>
                                <td>{{ $q2 ?? 'N/A' }}</td>
                                <td>{{ $q3 ?? 'N/A' }}</td>
                                <td>{{ $q4 ?? 'N/A' }}</td>
                                <td><strong>{{ $finalGrade ?? 'N/A' }}</strong></td>
                                <td><strong>{{ $remarks }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

</body>
</html>
