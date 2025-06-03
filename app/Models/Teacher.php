<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teachers';

    protected $fillable = [
        'user_id',
        'phone',
        'gender',
        'date_of_birth',
        'address',
        'qualification',
        'status',
    ];


    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function llcs()
    {
        return $this->hasMany(LLC::class);
    }

    public function advisories()
    {
        return $this->belongsToMany(Section::class, 'section_subject', 'teacher_id', 'section_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }
}
