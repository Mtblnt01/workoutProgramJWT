<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'role',
        'age'
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Reláció: a felhasználó által beiratkozottak az edzések közül.
     */
    public function enrollments()
    {
        return $this->hasMany(\App\Models\UserWorkout::class, 'user_id');
    }

    /**
     * Many-to-Many reláció: a felhasználó edzéseihez.
     */
    public function workouts()
    {
        return $this->belongsToMany(\App\Models\Workout::class, 'user_workouts', 'user_id', 'workout_id')
                    ->withPivot('progress', 'last_done', 'completed_at')
                    ->withTimestamps();
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
