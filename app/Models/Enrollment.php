<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'student_profile_id',
        'enrollment_date',
        'status',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    /**
     * The student profile this enrollment is linked to.
     */
    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
