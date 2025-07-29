<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $fillable = [
        'section_id',
        'school_year_id',
        'teacher_id',
        'capacity',

    ];
    protected $table = 'classes';

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    public function sections()
    {
        return $this->belongsTo(Section::class);
    }
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'class_id');
    }
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id');
    }
}
