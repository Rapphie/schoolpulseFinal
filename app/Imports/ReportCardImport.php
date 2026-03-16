<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ReportCardImport implements ToCollection
{
    private array $headerData = [];

    private array $maleStudents = [];

    private array $femaleStudents = [];

    private array $maleGrades = [];

    private array $femaleGrades = [];

    private ?array $quarterColumns = null;

    private array $headerMap = [
        'REGION' => 'region',
        'DIVISION' => 'division',
        'DISTRICT' => 'district',
        'SCHOOL NAME:' => 'school_name', // Corrected: Includes colon
        'SCHOOL ID' => 'school_id',
        'SCHOOL YEAR:' => 'school_year',
        'GRADE & SECTION:' => 'grade_section',
        'TEACHER:' => 'teacher',
        'SUBJECT:' => 'subject',
    ];

    private array $quarterMap = [
        '1ST QUARTER' => '1',
        '2ND QUARTER' => '2',
        '3RD QUARTER' => '3',
        '4TH QUARTER' => '4',
    ];

    /**
     * This is the main method that processes the Excel sheet.
     */
    public function collection(Collection $rows)
    {
        // First, find the locations of the headers and quarter columns
        $this->findHeaderData($rows);
        $this->findQuarterColumns($rows);

        $isFemaleSection = false;

        foreach ($rows as $row) {
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $trimmedRow = $row->map(fn ($item) => trim($item));

            // Check for section markers (MALE/FEMALE) based on the typical layout
            $markerCell = strtoupper($trimmedRow[1] ?? ''); // Student names/markers are in the 2nd column (index 1)
            if ($markerCell === 'MALE') {
                $isFemaleSection = false;

                continue;
            }
            if ($markerCell === 'FEMALE') {
                $isFemaleSection = true;

                continue;
            }

            // A valid student row must have a number in the first column and a name in the second
            if (isset($trimmedRow[0]) && is_numeric($trimmedRow[0]) && ! empty($trimmedRow[1])) {
                $studentName = $trimmedRow[1];
                $grades = [];

                // Use the found quarter columns to extract grades for this student
                if ($this->quarterColumns) {
                    foreach ($this->quarterColumns as $quarterKey => $columnIndex) {
                        $gradeValue = $trimmedRow[$columnIndex] ?? null;
                        if (is_numeric($gradeValue)) {
                            $grades[$quarterKey] = (float) $gradeValue;
                        }
                    }
                }

                // Add the student and their grades to the correct array
                if ($isFemaleSection) {
                    $this->femaleStudents[] = $studentName;
                    $this->femaleGrades[] = $grades;
                } else {
                    $this->maleStudents[] = $studentName;
                    $this->maleGrades[] = $grades;
                }
            }
        }
    }

    private function findHeaderData(Collection $rows)
    {
        foreach ($rows->take(10) as $row) {
            foreach ($row as $cellIndex => $cellValue) {
                if (empty($cellValue)) {
                    continue;
                }
                $cleanedCellValue = strtoupper(trim($cellValue));
                if (array_key_exists($cleanedCellValue, $this->headerMap)) {
                    $dataKey = $this->headerMap[$cleanedCellValue];
                    // Find the next non-empty cell for the value
                    for ($j = $cellIndex + 1; $j < $row->count(); $j++) {
                        if (! empty($row[$j])) {
                            $this->headerData[$dataKey] = trim($row[$j]);
                            break;
                        }
                    }
                }
            }
        }
    }

    private function findQuarterColumns(Collection $rows)
    {
        $this->quarterColumns = [];
        $quartersToFind = array_keys($this->quarterMap);

        foreach ($rows->take(15) as $row) {
            foreach ($row as $colIndex => $cellValue) {
                if (empty($cellValue)) {
                    continue;
                }

                // The headers in your file are "1st Quarter", not "1ST QUARTER"
                // We need to match that, but do it case-insensitively.
                $cleanedCellValue = strtoupper(trim($cellValue));

                // Also, your quarter map keys are "1ST Quarter", which is inconsistent.
                // Let's standardize on full uppercase.
                if (in_array(strtoupper($cellValue), $quartersToFind)) {
                    $quarterKey = $this->quarterMap[strtoupper($cellValue)];
                    $this->quarterColumns[$quarterKey] = $colIndex;
                }
            }
            if (count($this->quarterColumns) === count($this->quarterMap)) {
                break;
            }
        }
    }

    public function getExtractedData(): array
    {
        return [
            'headers' => $this->headerData,
            'male_students' => $this->maleStudents,
            'female_students' => $this->femaleStudents,
            'male_grades' => $this->maleGrades,
            'female_grades' => $this->femaleGrades,
        ];
    }
}
