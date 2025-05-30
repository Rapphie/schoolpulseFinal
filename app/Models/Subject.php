<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'units',
        'hours_per_week',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'units' => 'integer',
        'hours_per_week' => 'integer'
    ];

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_subject')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'section_subject', 'subject_id', 'teacher_id')
            ->withPivot('section_id')
            ->withTimestamps()
            ->distinct();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
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
