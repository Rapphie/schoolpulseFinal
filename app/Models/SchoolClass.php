<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = [
        'section_id',
        'school_year_id',
        'teacher_id',
        'capacity',

    ];

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'classes';
    public function section()
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
}
