<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function gradeLevelSubjects(): HasMany
    {
        return $this->hasMany(GradeLevelSubject::class);
    }
}
