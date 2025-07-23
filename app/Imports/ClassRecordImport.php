<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ClassRecordImport implements ToCollection
{
    /**
     * @var array Holds the final extracted data.
     */
    private array $headerData = [];

    /**
     * @var array Holds the extracted male students.
     */
    private array $maleStudents = [];

    /**
     * @var array Holds the extracted female students.
     */
    private array $femaleStudents = [];

    /**
     * @var array Holds the LRNs for male students.
     */
    private array $maleLrns = [];

    /**
     * @var array Holds the LRNs for female students.
     */
    private array $femaleLrns = [];

    /**
     * @var int|null The column index for the LRN.
     */
    private ?int $lrnColumn = null;


    /**
     * A map of the header text we're searching for in the file (key)
     * and the clean key we want to use in our final output (value).
     * We search for these in uppercase to make the search case-insensitive.
     * @var string[]
     */
    private array $headerMap = [
        'REGION'          => 'region',
        'DIVISION'        => 'division',
        'DISTRICT'        => 'district',
        'SCHOOL NAME'     => 'school_name',
        'SCHOOL ID'       => 'school_id',
        'SCHOOL YEAR'     => 'school_year',
        'GRADE & SECTION:' => 'grade_section',
        'TEACHER:'        => 'teacher',
        'SUBJECT:'        => 'subject',
    ];

    /**
     * A map for quarter names to their numeric values.
     * @var array
     */
    private array $quarterMap = [
        'FIRST QUARTER'  => 1,
        'SECOND QUARTER' => 2,
        'THIRD QUARTER'  => 3,
        'FOURTH QUARTER' => 4,
    ];

    /**
     * This method processes the entire sheet.
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Pre-scan to find the LRN column index first.
        $this->findLrnColumn($rows);

        // Get the list of headers we need to find.
        $headersToFind = array_keys($this->headerMap);
        $quartersToFind = array_keys($this->quarterMap);

        // Loop through each row in the Excel sheet.
        foreach ($rows as $rowIndex => $row) {
            // Loop through each cell in the current row.
            for ($i = 0; $i < count($row); $i++) {
                $cellValue = $row[$i];

                // Continue if the cell is empty.
                if (empty($cellValue)) {
                    continue;
                }

                // Clean up the cell text and check if it's a header we're looking for.
                $cleanedCellValue = strtoupper(trim($cellValue));

                // Check for MALE/FEMALE headers to start extracting student names.
                if ($cleanedCellValue === 'MALE' || $cleanedCellValue === 'FEMALE') {
                    // The names are in the rows below this header, in the same column.
                    for ($j = $rowIndex + 1; $j < count($rows); $j++) {
                        // Use the same column index ($i) to look for names.
                        $studentName = $rows[$j][$i] ?? null;

                        // If the cell is empty, we've reached the end of the list.
                        if (empty(trim($studentName))) {
                            break;
                        }

                        // Extract LRN for the student.
                        $lrn = 'N/A';
                        if ($this->lrnColumn !== null) {
                            $lrnValue = $rows[$j][$this->lrnColumn] ?? null;
                            if (!empty(trim($lrnValue))) {
                                $lrn = trim($lrnValue);
                            }
                        }
                        $parsedName = $this->parseStudentName(trim($studentName));

                        // ✨ NEW: Combine all student data into a single structured array.
                        $studentData = [
                            'first_name' => $parsedName['first_name'],
                            'last_name'  => $parsedName['last_name'],
                            'lrn'        => $lrn,
                        ];

                        // ✨ NEW: Add the structured data to the correct list.
                        if ($cleanedCellValue === 'MALE') {
                            $this->maleStudents[] = $studentData;
                        } else {
                            $this->femaleStudents[] = $studentData;
                        }
                    }
                    // Continue to the next cell, as we've processed this column.
                    continue;
                }

                // Check if the cell contains a quarter name.
                if (in_array($cleanedCellValue, $quartersToFind)) {
                    $this->headerData['quarter'] = $this->quarterMap[$cleanedCellValue];
                    // Continue to the next cell, as we've found what we need.
                    continue;
                }

                if (in_array($cleanedCellValue, $headersToFind)) {
                    // We found a header! Now find its value in the same row.
                    // The value is the *next* non-empty cell to the right.
                    $value = null;
                    for ($j = $i + 1; $j < count($row); $j++) {
                        if (!empty($row[$j])) {
                            $value = trim($row[$j]);
                            break; // Exit after finding the first value.
                        }
                    }

                    // Get the clean key (e.g., 'school_name') from our map.
                    $dataKey = $this->headerMap[$cleanedCellValue];
                    // Save the found value.
                    $this->headerData[$dataKey] = $value;
                }
            }
        }
    }

    private function parseStudentName(string $fullName): array
    {
        $parts = explode(',', $fullName, 2);

        // If there's no comma, assume the whole string is the first name.
        if (count($parts) < 2) {
            return ['first_name' => $fullName, 'last_name' => ''];
        }

        $lastName = trim($parts[0]);
        $firstNamePart = trim($parts[1]);

        // Find the last space to remove the middle initial.
        $lastSpacePos = strrpos($firstNamePart, ' ');

        // Check if a space was found and if the part after it looks like an initial.
        if ($lastSpacePos !== false) {
            $potentialInitial = substr($firstNamePart, $lastSpacePos + 1);
            // An initial is typically 1 char, or 2 if it includes a period (e.g., "P.").
            if (strlen($potentialInitial) <= 2 && ctype_upper(substr($potentialInitial, 0, 1))) {
                // It's likely an initial, so we trim it off.
                $firstName = trim(substr($firstNamePart, 0, $lastSpacePos));
            } else {
                // It's likely part of a compound first name (e.g., "Mary Anne"), so keep it.
                $firstName = $firstNamePart;
            }
        } else {
            // No spaces, so the whole part is the first name.
            $firstName = $firstNamePart;
        }

        return [
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ];
    }

    /**
     * Find and set the LRN column index.
     * @param Collection $rows
     */
    private function findLrnColumn(Collection $rows)
    {
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                if (strtoupper(trim($value)) === 'LRN') {
                    $this->lrnColumn = $key;
                    return; // Exit once found
                }
            }
        }
    }

    /**
     * A public method to get the extracted data after parsing.
     * @return array
     */
    public function getExtractedHeaderData(): array
    {
        return $this->headerData;
    }

    /**
     * Gets the list of male students.
     * @return array
     */
    public function getMaleStudents(): array
    {
        return $this->maleStudents;
    }

    /**
     * Gets the list of female students.
     * @return array
     */
    public function getFemaleStudents(): array
    {
        return $this->femaleStudents;
    }
}
