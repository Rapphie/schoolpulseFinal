<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(Schedule::class, Classes::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }
}
