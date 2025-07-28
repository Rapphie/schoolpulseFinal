<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * The "booted" method of the model.
     * This is used to register model event listeners.
     */
    protected static function booted(): void
    {
        // This event ensures that only one school year can be active at a time.
        // When a school year is being updated...
        static::updating(function (SchoolYear $schoolYear) {
            // ...and its 'is_active' flag is being set to true...
            if ($schoolYear->isDirty('is_active') && $schoolYear->is_active) {
                // ...then update all other school years to set their 'is_active' flag to false.
                static::where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }
        });
        // This event runs every time a SchoolYear record is being created or updated.
        static::saving(function (SchoolYear $schoolYear) {
            // If the 'is_active' flag is being set to true...
            if ($schoolYear->is_active) {
                // ...then update all other school years to set their 'is_active' flag to false.
                static::where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }
        });

        
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Define the relationships for the SchoolYear model.
    |
    */

    /**
     * Get all of the classes for the SchoolYear.
     */
    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    /**
     * Get all of the enrollments for the SchoolYear.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get all of the grades for the SchoolYear.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get all of the attendances for the SchoolYear.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Define reusable query scopes for the SchoolYear model.
    |
    */

    /**
     * Scope a query to only include the active school year.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
