<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LLC extends Model
{
    /** @use HasFactory<\Database\Factories\LLCFactory> */
    use HasFactory;

    protected $table = "llc";

    protected $fillable = [
        "subject_id",
        "section_id",
        "teacher_id",
        "school_year_id",
        "quarter",
        "total_students",
        "total_items"
    ];
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }



    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function llcItems(): HasMany
    {
        return $this->hasMany(LLCItem::class);
    }
}
