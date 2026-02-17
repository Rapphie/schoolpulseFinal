<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $table = 'guardians';

    protected $fillable = [
        'user_id',
        'phone',
        'relationship',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
