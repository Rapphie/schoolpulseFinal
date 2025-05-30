<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GradeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'level',
        'description',
        'is_active'
    ];

    protected $casts = [
        'level' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the sections for this grade level
     */
    public function sections()
    {
        return $this->hasMany(Section::class);
    }
}
