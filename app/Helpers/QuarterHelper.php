<?php

namespace App\Helpers;

class QuarterHelper
{
    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return [
            1 => '1st Quarter',
            2 => '2nd Quarter',
            3 => '3rd Quarter',
            4 => '4th Quarter',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function searchValues(int $quarterNumber): array
    {
        $labels = self::labels();
        $wordLabels = [
            1 => 'FIRST QUARTER',
            2 => 'SECOND QUARTER',
            3 => 'THIRD QUARTER',
            4 => 'FOURTH QUARTER',
        ];

        return array_values(array_unique([
            (string) $quarterNumber,
            'Q'.$quarterNumber,
            $labels[$quarterNumber] ?? '',
            $wordLabels[$quarterNumber] ?? '',
        ]));
    }

    public static function numberFromValue(?string $value): int
    {
        if ($value === null) {
            return 1;
        }

        $labels = self::labels();
        $numeric = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        if ($numeric >= 1 && $numeric <= count($labels)) {
            return $numeric;
        }

        $normalized = strtoupper(trim($value));
        $map = [
            'FIRST QUARTER' => 1,
            'SECOND QUARTER' => 2,
            'THIRD QUARTER' => 3,
            'FOURTH QUARTER' => 4,
            'Q1' => 1,
            'Q2' => 2,
            'Q3' => 3,
            'Q4' => 4,
            '1ST QUARTER' => 1,
            '2ND QUARTER' => 2,
            '3RD QUARTER' => 3,
            '4TH QUARTER' => 4,
        ];

        return $map[$normalized] ?? 1;
    }

    public static function normalizeLabel(?string $value): string
    {
        $labels = self::labels();

        if ($value === null || trim($value) === '') {
            return $labels[1];
        }

        $quarterNumber = self::numberFromValue($value);

        return $labels[$quarterNumber] ?? trim($value);
    }
}
