<?php

namespace Tests\Unit;

use App\Services\GradeService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GradeServiceReportCardRulesTest extends TestCase
{
    public function test_subject_final_grade_and_remarks_are_blank_when_any_quarter_is_missing(): void
    {
        $rawGrades = collect([
            1001 => new Collection([
                (object) ['quarter' => '1', 'grade' => 85.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '2', 'grade' => 86.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '3', 'grade' => 87.0, 'subject' => (object) ['name' => 'Mathematics']],
            ]),
        ]);

        $processed = GradeService::processGradesForReportCard($rawGrades, [1001]);
        $mathRow = collect($processed['gradesData'])->firstWhere('subject_name', 'Mathematics');

        $this->assertNotNull($mathRow);
        $this->assertNull($mathRow['final_grade']);
        $this->assertSame('', $mathRow['remarks']);
    }

    public function test_general_average_is_blank_when_a_required_subject_is_missing(): void
    {
        $rawGrades = collect([
            1001 => new Collection([
                (object) ['quarter' => '1', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '2', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '3', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '4', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
            ]),
        ]);

        $processed = GradeService::processGradesForReportCard($rawGrades, [1001, 1002]);

        $this->assertNull($processed['generalAverage']);
    }

    public function test_general_average_is_computed_when_all_required_subjects_are_complete(): void
    {
        $rawGrades = collect([
            1001 => new Collection([
                (object) ['quarter' => '1', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '2', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '3', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '4', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
            ]),
            1002 => new Collection([
                (object) ['quarter' => '1', 'grade' => 90.0, 'subject' => (object) ['name' => 'Science']],
                (object) ['quarter' => '2', 'grade' => 90.0, 'subject' => (object) ['name' => 'Science']],
                (object) ['quarter' => '3', 'grade' => 90.0, 'subject' => (object) ['name' => 'Science']],
                (object) ['quarter' => '4', 'grade' => 90.0, 'subject' => (object) ['name' => 'Science']],
            ]),
        ]);

        $processed = GradeService::processGradesForReportCard($rawGrades, [1001, 1002]);

        $this->assertSame(85.0, $processed['generalAverage']);
    }

    public function test_general_average_is_blank_when_required_subject_exists_but_is_incomplete(): void
    {
        $rawGrades = collect([
            1001 => new Collection([
                (object) ['quarter' => '1', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '2', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '3', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '4', 'grade' => 80.0, 'subject' => (object) ['name' => 'Mathematics']],
            ]),
            1002 => new Collection([
                (object) ['quarter' => '1', 'grade' => 90.0, 'subject' => (object) ['name' => 'Science']],
                (object) ['quarter' => '2', 'grade' => 90.0, 'subject' => (object) ['name' => 'Science']],
                (object) ['quarter' => '3', 'grade' => 90.0, 'subject' => (object) ['name' => 'Science']],
            ]),
        ]);

        $processed = GradeService::processGradesForReportCard($rawGrades, [1001, 1002]);

        $this->assertNull($processed['generalAverage']);
    }
}
