<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grade_level_id',
        'description',
    ];

    protected $casts = [
        'grade_level_id' => 'integer',
    ];

    /**
     * Get the grade level that this section belongs to
     */
    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function schedules()
    {
        return $this->hasManyThrough(Schedule::class, Classes::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }
}
