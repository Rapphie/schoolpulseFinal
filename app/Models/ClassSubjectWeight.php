<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubjectWeight extends Model
{
    protected $table = 'class_subject_weights';

    protected $fillable = [
        'class_id',
        'subject_id',
        'written_works_weight',
        'performance_tasks_weight',
        'quarterly_assessments_weight',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
