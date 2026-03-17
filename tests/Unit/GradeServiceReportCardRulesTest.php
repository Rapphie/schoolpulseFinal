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

    public function test_mapeh_components_are_collapsed_into_one_mapeh_row(): void
    {
        $rawGrades = collect([
            2001 => new Collection([
                (object) ['quarter' => '1', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
                (object) ['quarter' => '2', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
                (object) ['quarter' => '3', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
                (object) ['quarter' => '4', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
            ]),
            2002 => new Collection([
                (object) ['quarter' => '1', 'grade' => 90.0, 'subject' => (object) ['name' => 'Arts']],
                (object) ['quarter' => '2', 'grade' => 90.0, 'subject' => (object) ['name' => 'Arts']],
                (object) ['quarter' => '3', 'grade' => 90.0, 'subject' => (object) ['name' => 'Arts']],
                (object) ['quarter' => '4', 'grade' => 90.0, 'subject' => (object) ['name' => 'Arts']],
            ]),
            2003 => new Collection([
                (object) ['quarter' => '1', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
                (object) ['quarter' => '2', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
                (object) ['quarter' => '3', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
                (object) ['quarter' => '4', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
            ]),
            2004 => new Collection([
                (object) ['quarter' => '1', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
                (object) ['quarter' => '2', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
                (object) ['quarter' => '3', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
                (object) ['quarter' => '4', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
            ]),
            3001 => new Collection([
                (object) ['quarter' => '1', 'grade' => 95.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '2', 'grade' => 95.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '3', 'grade' => 95.0, 'subject' => (object) ['name' => 'Mathematics']],
                (object) ['quarter' => '4', 'grade' => 95.0, 'subject' => (object) ['name' => 'Mathematics']],
            ]),
        ]);

        $processed = GradeService::processGradesForReportCard($rawGrades, [2001, 2002, 2003, 2004, 3001]);

        $mapehRow = collect($processed['gradesData'])->firstWhere('subject_name', 'MAPEH');
        $musicRow = collect($processed['gradesData'])->firstWhere('subject_name', 'Music');
        $artsRow = collect($processed['gradesData'])->firstWhere('subject_name', 'Arts');
        $peRow = collect($processed['gradesData'])->firstWhere('subject_name', 'Physical Education');
        $healthRow = collect($processed['gradesData'])->firstWhere('subject_name', 'Health');

        $this->assertNotNull($mapehRow);
        $this->assertNull($musicRow);
        $this->assertNull($artsRow);
        $this->assertNull($peRow);
        $this->assertNull($healthRow);
        $this->assertSame(85.0, $mapehRow['final_grade']);
        $this->assertSame(90.0, $processed['generalAverage']);
    }

    public function test_general_average_is_blank_when_required_mapeh_components_are_incomplete(): void
    {
        $rawGrades = collect([
            2001 => new Collection([
                (object) ['quarter' => '1', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
                (object) ['quarter' => '2', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
                (object) ['quarter' => '3', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
                (object) ['quarter' => '4', 'grade' => 80.0, 'subject' => (object) ['name' => 'Music']],
            ]),
            2002 => new Collection([
                (object) ['quarter' => '1', 'grade' => 90.0, 'subject' => (object) ['name' => 'Arts']],
                (object) ['quarter' => '2', 'grade' => 90.0, 'subject' => (object) ['name' => 'Arts']],
                (object) ['quarter' => '3', 'grade' => 90.0, 'subject' => (object) ['name' => 'Arts']],
            ]),
            2003 => new Collection([
                (object) ['quarter' => '1', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
                (object) ['quarter' => '2', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
                (object) ['quarter' => '3', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
                (object) ['quarter' => '4', 'grade' => 100.0, 'subject' => (object) ['name' => 'Physical Education']],
            ]),
            2004 => new Collection([
                (object) ['quarter' => '1', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
                (object) ['quarter' => '2', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
                (object) ['quarter' => '3', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
                (object) ['quarter' => '4', 'grade' => 70.0, 'subject' => (object) ['name' => 'Health']],
            ]),
        ]);

        $processed = GradeService::processGradesForReportCard($rawGrades, [2001, 2002, 2003, 2004]);

        $mapehRow = collect($processed['gradesData'])->firstWhere('subject_name', 'MAPEH');
        $this->assertNotNull($mapehRow);
        $this->assertNull($mapehRow['final_grade']);
        $this->assertNull($processed['generalAverage']);
    }
}
