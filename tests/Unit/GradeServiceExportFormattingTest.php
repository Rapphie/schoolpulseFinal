<?php

namespace Tests\Unit;

use App\Services\GradeService;
use PHPUnit\Framework\TestCase;

class GradeServiceExportFormattingTest extends TestCase
{
    public function test_format_final_grade_for_export_returns_empty_string_for_null_or_empty_values(): void
    {
        $this->assertSame('', GradeService::formatFinalGradeForExport(null));
        $this->assertSame('', GradeService::formatFinalGradeForExport(''));
    }

    public function test_format_final_grade_for_export_rounds_decimal_values_to_whole_numbers(): void
    {
        $this->assertSame('86', GradeService::formatFinalGradeForExport(85.5));
        $this->assertSame('85', GradeService::formatFinalGradeForExport(85.49));
        $this->assertSame('92', GradeService::formatFinalGradeForExport('91.6'));
    }
}
