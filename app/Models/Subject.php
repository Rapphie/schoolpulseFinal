<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function attendances(): HasMany
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
