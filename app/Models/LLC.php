<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LLC extends Model
{
    /** @use HasFactory<\Database\Factories\LLCFactory> */
    use HasFactory;

    protected $table = "llc";

    protected $fillable = [
        "description"
    ];

    public function subject(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function user(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
