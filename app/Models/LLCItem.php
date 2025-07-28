<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LLCItem extends Model
{
    /** @use HasFactory<\Database\Factories\LLCItemFactory> */
    use HasFactory;

    protected $table = "llc_items";

    protected $fillable = [
        "llc_id",
        "item_number",
        "students_wrong",
        "category_name",
        "item_start",
        "item_end"
    ];

    public function llc(): BelongsTo
    {
        return $this->belongsTo(LLC::class);
    }
}
