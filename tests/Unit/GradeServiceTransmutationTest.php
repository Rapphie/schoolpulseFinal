<?php

namespace Tests\Unit;

use App\Services\GradeService;
use PHPUnit\Framework\TestCase;

class GradeServiceTransmutationTest extends TestCase
{
    public function test_transmute_respects_decimal_threshold_boundaries(): void
    {
        $this->assertSame(77, GradeService::transmute(64.79));
        $this->assertSame(78, GradeService::transmute(64.80));

        $this->assertSame(82, GradeService::transmute(72.79));
        $this->assertSame(83, GradeService::transmute(72.80));

        $this->assertSame(91, GradeService::transmute(87.19));
        $this->assertSame(92, GradeService::transmute(87.20));
    }

    public function test_transmute_clamps_values_to_valid_range(): void
    {
        $this->assertSame(60, GradeService::transmute(-999.0));
        $this->assertSame(100, GradeService::transmute(999.0));
    }
}
