<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeLevelSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level_id',
        'subject_id',
        'is_active',
        'written_works_weight',
        'performance_tasks_weight',
        'quarterly_assessments_weight',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'written_works_weight' => 'integer',
        'performance_tasks_weight' => 'integer',
        'quarterly_assessments_weight' => 'integer',
    ];

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function getTotalWeight(): int
    {
        return $this->written_works_weight + $this->performance_tasks_weight + $this->quarterly_assessments_weight;
    }
}
