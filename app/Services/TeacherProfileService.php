<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TeacherProfileService
{
    /**
     * Extract the first number from a string value.
     */
    public static function extractFirstNumber(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/\d+/', $value, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Normalize a grade level number or name by extracting the first number if present.
     */
    public static function normalizeGradeLevelNumberOrName(?string $gradeLevelName): ?string
    {
        if ($gradeLevelName === null || $gradeLevelName === '') {
            return null;
        }

        return self::extractFirstNumber($gradeLevelName) ?? trim($gradeLevelName);
    }

    /**
     * Ensure a grade level name has the "Grade" prefix.
     */
    public static function ensureGradePrefix(?string $gradeLevelName): ?string
    {
        $gradeLevelName = trim((string) $gradeLevelName);
        if ($gradeLevelName === '') {
            return null;
        }

        if (! preg_match('/^grade\b/i', $gradeLevelName)) {
            return 'Grade '.$gradeLevelName;
        }

        return $gradeLevelName;
    }

    /**
     * Get normalized advisory grade levels for a teacher from their classes.
     *
     * @param  Collection<int, \App\Models\Classes>  $classes
     * @return Collection<int, string>
     */
    public static function getAdvisoryGradeLevelsFromClasses(Collection $classes): Collection
    {
        return $classes
            ->map(fn ($class) => optional(optional($class->section)->gradeLevel)->name)
            ->map(fn ($name) => self::normalizeGradeLevelNumberOrName($name))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Get advisory section names from classes.
     *
     * @param  Collection<int, \App\Models\Classes>  $classes
     * @return Collection<int, string>
     */
    public static function getAdvisorySectionsFromClasses(Collection $classes): Collection
    {
        return $classes
            ->map(fn ($class) => optional($class->section)->name)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Get advisory sections with grade level names combined.
     *
     * @param  Collection<int, \App\Models\Classes>  $classes
     * @return Collection<int, string>
     */
    public static function getAdvisorySectionsWithGradeFromClasses(Collection $classes): Collection
    {
        return $classes
            ->map(function ($class) {
                $gradeLevelName = optional(optional($class->section)->gradeLevel)->name;
                $sectionName = optional($class->section)->name;

                $gradeLevelName = self::ensureGradePrefix($gradeLevelName);
                $sectionName = trim((string) $sectionName);

                if ($gradeLevelName === null && $sectionName === '') {
                    return null;
                }
                if ($gradeLevelName === null) {
                    return $sectionName;
                }
                if ($sectionName === '') {
                    return $gradeLevelName;
                }

                return $gradeLevelName.' - '.$sectionName;
            })
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Format a collection of items into a summary string with overflow count.
     *
     * @param  Collection<int, string>  $items
     */
    public static function formatCollectionSummary(Collection $items): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $preview = $items->take(3);
        $text = $preview->implode(', ');
        if ($items->count() > 3) {
            $text .= ' (+'.($items->count() - 3).' more)';
        }

        return $text;
    }

    /**
     * Format a collection of grade levels into a summary string.
     *
     * @param  Collection<int, string>  $gradeLevels
     */
    public static function formatGradeLevelSummary(Collection $gradeLevels): string
    {
        return self::formatCollectionSummary($gradeLevels);
    }

    /**
     * Format a collection of sections into a summary string.
     *
     * @param  Collection<int, string>  $sections
     */
    public static function formatSectionSummary(Collection $sections): string
    {
        return self::formatCollectionSummary($sections);
    }
}
