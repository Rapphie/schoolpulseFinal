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
        'teacher_id',
        'capacity'
    ];

    protected $appends = ['grade_level_name'];

    protected $casts = [
        'grade_level_id' => 'integer',
        'capacity' => 'integer',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
    public function adviser()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher_section', 'section_id', 'subject_id')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }


    // Accessor for backward compatibility with grade_level_id column
    public function getGradeLevelNameAttribute()
    {
        if ($this->gradeLevel) {
            return $this->gradeLevel->name;
        }
        // Fallback to the integer grade_level_id if grade_level_id relationship is not set
        return 'Grade ' . $this->grade_level_id;
    }
    public function getFullNameAttribute()
    {
        return "Grade {$this->grade_level_id} - {$this->name}";
    }

    /**
     * Get the grade level that this section belongs to
     */
    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }
}
