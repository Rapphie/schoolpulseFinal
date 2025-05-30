<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;



class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, Authorizable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's full name
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the sections where the user is an adviser
     */
    public function sectionsAdvised(): HasMany
    {
        return $this->hasMany(Section::class, 'adviser_id');
    }

    /**
     * Get the subjects taught by the user
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'section_subject', 'teacher_id', 'subject_id')
            ->withPivot('section_id')
            ->withTimestamps();
    }

    /**
     * Get the user's schedules
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    /**
     * Check if user is a teacher
     */
    public function isTeacher(): bool
    {
        return $this->role_id === 2;
    }

    /**
     * Get the student record for this user
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn(string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role->name == $role;
    }


    //
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the sections where the user is an adviser
     */
    /**
     * Get all sections associated with the user through section_subject pivot
     */
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_subject', 'teacher_id', 'section_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
    public function llc(): HasMany
    {
        return $this->hasMany(LLC::class);
    }
}
