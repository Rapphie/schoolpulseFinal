<?php

namespace App\Services;

/**
 * Grade Service for handling DepEd transmutation and grade calculations.
 *
 * Based on DepEd Order No. 8, s. 2015 - Policy Guidelines on Classroom Assessment
 * for the K to 12 Basic Education Program
 */
class GradeService
{
    private const MAPEH_LABEL = 'MAPEH';

    private const MAPEH_COMPONENT_NAMES = [
        'music',
        'arts',
        'physical education',
        'health',
    ];

    /**
     * DepEd Transmutation Table
     * Maps raw/initial grade ranges to transmuted grades
     *
     * This follows the official DepEd transmutation table where:
     * - Raw grades from 0-100% are converted to transmuted grades 60-100
     * - The lowest transmuted grade is 60
     */
    private const TRANSMUTATION_TABLE = [
        100.00 => 100,
        98.40 => 99,
        96.80 => 98,
        95.20 => 97,
        93.60 => 96,
        92.00 => 95,
        90.40 => 94,
        88.80 => 93,
        87.20 => 92,
        85.60 => 91,
        84.00 => 90,
        82.40 => 89,
        80.80 => 88,
        79.20 => 87,
        77.60 => 86,
        76.00 => 85,
        74.40 => 84,
        72.80 => 83,
        71.20 => 82,
        69.60 => 81,
        68.00 => 80,
        66.40 => 79,
        64.80 => 78,
        63.20 => 77,
        61.60 => 76,
        60.00 => 75,
        56.00 => 74,
        52.00 => 73,
        48.00 => 72,
        44.00 => 71,
        40.00 => 70,
        36.00 => 69,
        32.00 => 68,
        28.00 => 67,
        24.00 => 66,
        20.00 => 65,
        16.00 => 64,
        12.00 => 63,
        8.00 => 62,
        4.00 => 61,
        0.00 => 60,
    ];

    /**
     * Transmute a raw/initial grade to DepEd transmuted grade.
     *
     * @param  float|null  $rawGrade  The raw initial grade (0-100 scale)
     * @return int|null The transmuted grade (60-100 scale) or null if input is null
     */
    public static function transmute(?float $rawGrade): ?int
    {
        if ($rawGrade === null) {
            return null;
        }

        // Clamp the raw grade between 0 and 100
        $rawGrade = max(0, min(100, $rawGrade));

        // Find the appropriate transmuted grade
        foreach (self::TRANSMUTATION_TABLE as $threshold => $transmutedGrade) {
            if ($rawGrade >= $threshold) {
                return $transmutedGrade;
            }
        }

        // If we somehow get here, return the minimum transmuted grade
        return 60;
    }

    /**
     * Calculate the final grade from quarterly grades.
     * The final grade is the sum of all 4 quarters divided by 4.
     * Each quarter grade should already be transmuted.
     *
     * @param  array  $quarterlyGrades  Array with keys 1, 2, 3, 4 containing quarterly grades
     * @param  bool  $requireAllQuarters  If true, returns null unless all 4 quarters have grades
     * @return float|null The final grade rounded to nearest whole number, or null if insufficient data
     */
    public static function calculateFinalGrade(array $quarterlyGrades, bool $requireAllQuarters = false): ?float
    {
        $existingGrades = array_filter($quarterlyGrades, fn ($val) => $val !== null && $val !== '');
        $count = count($existingGrades);

        if ($count === 0) {
            return null;
        }

        // If we require all 4 quarters and don't have them, return null
        if ($requireAllQuarters && $count < 4) {
            return null;
        }

        // The final grade is the average of all 4 quarters. Missing quarters are treated as 0.
        $sum = array_sum($quarterlyGrades); // array_sum treats null values in the array as 0.

        // Per DepEd guidelines: Final Grade = (Q1 + Q2 + Q3 + Q4) / 4
        // Always divide by 4 since there are exactly 4 quarters in a school year.
        return round($sum / 4, 2);
    }

    /**
     * Format a final grade for report card export.
     */
    public static function formatFinalGradeForExport(float|int|string|null $finalGrade): string
    {
        if ($finalGrade === null || $finalGrade === '') {
            return '';
        }

        return (string) round((float) $finalGrade, 0);
    }

    /**
     * Get the remarks based on the final grade.
     * Per DepEd guidelines: 75 and above is PASSED, below 75 is FAILED
     *
     * @param  float|null  $finalGrade  The final grade
     * @return string The remarks (Passed, Failed, or empty)
     */
    public static function getRemarks(?float $finalGrade): string
    {
        if ($finalGrade === null) {
            return '';
        }

        return $finalGrade >= 75 ? 'Passed' : 'Failed';
    }

    /**
     * Get the descriptor based on the grade.
     * Per DepEd grading scale:
     * - 90-100: Outstanding
     * - 85-89: Very Satisfactory
     * - 80-84: Satisfactory
     * - 75-79: Fairly Satisfactory
     * - Below 75: Did Not Meet Expectations
     *
     * @param  float|null  $grade  The grade
     * @return string The descriptor
     */
    public static function getDescriptor(?float $grade): string
    {
        if ($grade === null) {
            return '';
        }

        if ($grade >= 90) {
            return 'Outstanding';
        }
        if ($grade >= 85) {
            return 'Very Satisfactory';
        }
        if ($grade >= 80) {
            return 'Satisfactory';
        }
        if ($grade >= 75) {
            return 'Fairly Satisfactory';
        }

        return 'Did Not Meet Expectations';
    }

    private static function normalizeSubjectName(string $subjectName): string
    {
        $normalized = strtolower($subjectName);
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }

    /**
     * @param  array<int, string>  $subjectNamesById
     * @return array<int>|null
     */
    private static function maybeSubjectIdsForMapehComponents(array $subjectNamesById): ?array
    {
        $componentIdsByName = [];
        $componentNamesLookup = array_flip(self::MAPEH_COMPONENT_NAMES);

        foreach ($subjectNamesById as $subjectId => $subjectName) {
            $normalizedSubjectName = self::normalizeSubjectName($subjectName);

            if ($normalizedSubjectName === self::normalizeSubjectName(self::MAPEH_LABEL)) {
                return null;
            }

            if (isset($componentNamesLookup[$normalizedSubjectName])) {
                $componentIdsByName[$normalizedSubjectName] = (int) $subjectId;
            }
        }

        foreach (self::MAPEH_COMPONENT_NAMES as $componentName) {
            if (! isset($componentIdsByName[$componentName])) {
                return null;
            }
        }

        return array_values(array_map(
            fn (string $componentName): int => $componentIdsByName[$componentName],
            self::MAPEH_COMPONENT_NAMES
        ));
    }

    /**
     * @param  array<int>  $componentSubjectIds
     * @param  array<int, array<int, float|int|null>>  $quarterlyGradesBySubjectId
     * @return array<string, float|int|string|null>|null
     */
    private static function buildSyntheticMapehRow(array $componentSubjectIds, array $quarterlyGradesBySubjectId): ?array
    {
        if (count($componentSubjectIds) !== count(self::MAPEH_COMPONENT_NAMES)) {
            return null;
        }

        $syntheticQuarters = [1 => null, 2 => null, 3 => null, 4 => null];

        foreach ([1, 2, 3, 4] as $quarter) {
            $quarterValues = [];

            foreach ($componentSubjectIds as $subjectId) {
                $subjectQuarterlyGrades = $quarterlyGradesBySubjectId[$subjectId] ?? null;
                $quarterValue = $subjectQuarterlyGrades[$quarter] ?? null;

                if ($quarterValue === null) {
                    $quarterValues = [];
                    break;
                }

                $quarterValues[] = (float) $quarterValue;
            }

            if (count($quarterValues) === count($componentSubjectIds)) {
                $syntheticQuarters[$quarter] = round(array_sum($quarterValues) / count($quarterValues), 2);
            }
        }

        $existingSyntheticQuarterGrades = array_filter($syntheticQuarters, fn ($value) => $value !== null);
        $syntheticFinal = null;

        if (count($existingSyntheticQuarterGrades) === 4) {
            $syntheticFinal = self::calculateFinalGrade($syntheticQuarters, true);
        }

        return [
            'subject_name' => self::MAPEH_LABEL,
            'q1' => $syntheticQuarters[1] !== null ? round($syntheticQuarters[1], 0) : null,
            'q2' => $syntheticQuarters[2] !== null ? round($syntheticQuarters[2], 0) : null,
            'q3' => $syntheticQuarters[3] !== null ? round($syntheticQuarters[3], 0) : null,
            'q4' => $syntheticQuarters[4] !== null ? round($syntheticQuarters[4], 0) : null,
            'final_grade' => $syntheticFinal,
            'remarks' => self::getRemarks($syntheticFinal),
        ];
    }

    /**
     * Process student grades for report card display.
     * Returns an array with quarterly grades, final grade, and remarks.
     *
     * @param  \Illuminate\Support\Collection  $rawGrades  Collection of Grade models grouped by subject_id
     * @param  array<int>|null  $requiredSubjectIds  Required subject IDs for computing general average
     * @return array Array of processed grades data per subject
     */
    public static function processGradesForReportCard($rawGrades, ?array $requiredSubjectIds = null): array
    {
        $gradesData = [];
        $finalGrades = [];
        $finalGradesBySubjectId = [];
        $subjectNamesById = [];
        $quarterlyGradesBySubjectId = [];

        foreach ($rawGrades as $subjectId => $collection) {
            $subjectName = optional($collection->first()->subject)->name ?? 'Subject';
            $quarters = [1 => null, 2 => null, 3 => null, 4 => null];

            foreach ($collection as $g) {
                // Parse quarter number from various formats
                $qi = $g->quarter_int ?? (int) preg_replace('/[^0-9]/', '', $g->quarter) ?: null;
                if ($qi && $qi >= 1 && $qi <= 4) {
                    $gradeValue = $g->grade;

                    // Check if grade appears to be a raw/initial grade (less than 60)
                    // Transmuted grades are always 60-100 per DepEd guidelines
                    // If grade is below 60, it's likely a raw grade that needs transmutation
                    if ($gradeValue !== null && $gradeValue < 60) {
                        $gradeValue = self::transmute($gradeValue);
                    }

                    $quarters[$qi] = $gradeValue;
                }
            }

            // Calculate final grade only when all quarters are available.
            $existingGrades = array_filter($quarters, fn ($val) => $val !== null);
            $final = null;

            if (count($existingGrades) === 4) {
                $final = self::calculateFinalGrade($quarters, true);
            }

            if ($final !== null) {
                $finalGrades[] = $final;
            }

            $finalGradesBySubjectId[(int) $subjectId] = $final;
            $subjectNamesById[(int) $subjectId] = $subjectName;
            $quarterlyGradesBySubjectId[(int) $subjectId] = $quarters;

            $gradesData[] = [
                'subject_id' => (int) $subjectId,
                'subject_name' => $subjectName,
                'q1' => $quarters[1] !== null ? round($quarters[1], 0) : null,
                'q2' => $quarters[2] !== null ? round($quarters[2], 0) : null,
                'q3' => $quarters[3] !== null ? round($quarters[3], 0) : null,
                'q4' => $quarters[4] !== null ? round($quarters[4], 0) : null,
                'final_grade' => $final,
                'remarks' => self::getRemarks($final),
            ];
        }

        $mapehComponentSubjectIds = self::maybeSubjectIdsForMapehComponents($subjectNamesById);
        $syntheticMapehRow = null;
        $syntheticMapehFinal = null;

        if ($mapehComponentSubjectIds !== null) {
            $syntheticMapehRow = self::buildSyntheticMapehRow($mapehComponentSubjectIds, $quarterlyGradesBySubjectId);
            $syntheticMapehFinal = $syntheticMapehRow['final_grade'] ?? null;

            if ($syntheticMapehRow !== null) {
                $componentSubjectIdsLookup = array_flip($mapehComponentSubjectIds);
                $collapsedGradesData = [];
                $hasInsertedSyntheticMapeh = false;

                foreach ($gradesData as $row) {
                    $rowSubjectId = $row['subject_id'];

                    if (isset($componentSubjectIdsLookup[$rowSubjectId])) {
                        if (! $hasInsertedSyntheticMapeh) {
                            $collapsedGradesData[] = ['subject_id' => null] + $syntheticMapehRow;
                            $hasInsertedSyntheticMapeh = true;
                        }

                        continue;
                    }

                    $collapsedGradesData[] = $row;
                }

                $gradesData = $collapsedGradesData;
            }
        }

        $finalGrades = [];
        foreach ($gradesData as $row) {
            if (($row['final_grade'] ?? null) !== null) {
                $finalGrades[] = $row['final_grade'];
            }
        }

        $generalAverage = null;

        if ($requiredSubjectIds !== null) {
            $requiredSubjectIds = array_values(array_unique(array_map('intval', $requiredSubjectIds)));

            if ($requiredSubjectIds !== []) {
                $requiredFinalGrades = [];
                $allRequiredSubjectsComplete = true;
                $mapehComponentSubjectIdsLookup = $mapehComponentSubjectIds !== null
                    ? array_flip($mapehComponentSubjectIds)
                    : [];
                $countedSyntheticMapeh = false;

                foreach ($requiredSubjectIds as $requiredSubjectId) {
                    if (isset($mapehComponentSubjectIdsLookup[$requiredSubjectId])) {
                        if (! $countedSyntheticMapeh) {
                            if ($syntheticMapehFinal === null) {
                                $allRequiredSubjectsComplete = false;
                                break;
                            }

                            $requiredFinalGrades[] = $syntheticMapehFinal;
                            $countedSyntheticMapeh = true;
                        }

                        continue;
                    }

                    $finalGrade = $finalGradesBySubjectId[$requiredSubjectId] ?? null;

                    if ($finalGrade === null) {
                        $allRequiredSubjectsComplete = false;
                        break;
                    }

                    $requiredFinalGrades[] = $finalGrade;
                }

                if ($allRequiredSubjectsComplete) {
                    $generalAverage = round(array_sum($requiredFinalGrades) / count($requiredFinalGrades), 0);
                }
            }
        } elseif (count($finalGrades) > 0) {
            $generalAverage = round(array_sum($finalGrades) / count($finalGrades), 0);
        }

        foreach ($gradesData as &$row) {
            unset($row['subject_id']);
        }
        unset($row);

        return [
            'gradesData' => $gradesData,
            'generalAverage' => $generalAverage,
            'finalGrades' => $finalGrades,
        ];
    }
}
