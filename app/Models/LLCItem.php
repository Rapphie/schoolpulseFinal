<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LLCItem extends Model
{
    /** @use HasFactory<\Database\Factories\LLCItemFactory> */
    use HasFactory;

    protected $table = "llc_item";

    protected $fillable = [
        "description"
    ];

    public function student(): HasMany
    {
        return $this->hasMany(Student::class);
    }
    public function subject(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
    public function user(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
