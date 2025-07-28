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

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
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

    public function advisoryClasses()
    {
        return $this->hasMany(Classes::class);
    }

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subjects()
    {
        return $this->hasManyThrough(Subject::class, Schedule::class, 'teacher_id', 'id', 'id', 'subject_id');
    }
}
