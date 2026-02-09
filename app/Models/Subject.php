<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level_id',
        'name',
        'code',
        'description',
        'duration_minutes',
        'is_active',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullNameAttribute()
    {
        return "{$this->code} - {$this->name}";
    }
}
