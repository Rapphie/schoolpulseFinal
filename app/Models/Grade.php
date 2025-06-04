<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    /** @use HasFactory<\Database\Factories\GradeFactory> */
    use HasFactory;

    protected $table = "grades";

    protected $fillable = [
        'student_id',
        'subject_id',
        'grade',
        'quarter',
        'school_year',
        'teacher_id'
    ];

    protected $casts = [
        'grade' => 'float',
        'quarter' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getScorePercentageAttribute()
    {
        return ($this->grade / $this->max_score) * 100;
    }
}
