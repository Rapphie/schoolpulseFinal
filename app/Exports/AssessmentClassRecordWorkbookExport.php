<?php

namespace App\Exports;

use App\Models\Classes;
use App\Models\Subject;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssessmentClassRecordWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Classes $classroom,
        private readonly Subject $subject,
        private readonly int $teacherId
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $sheets[] = new AssessmentClassRecordExport(
                $this->classroom,
                $this->subject,
                $this->teacherId,
                $quarter
            );
        }

        return $sheets;
    }
}
