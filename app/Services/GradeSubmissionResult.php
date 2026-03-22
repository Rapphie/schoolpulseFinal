<?php

namespace App\Services;

class GradeSubmissionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly int $savedCount,
        public readonly int $clearedCount,
        public readonly array $rejectedCells,
        public readonly int $statusCode = 200
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'saved_count' => $this->savedCount,
            'cleared_count' => $this->clearedCount,
            'rejected_count' => count($this->rejectedCells),
            'rejected_cells' => $this->rejectedCells,
        ];
    }
}
