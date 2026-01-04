<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'difficulty'
    ];

    public function enrollments()
    {
        return $this->hasMany(UserWorkout::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_workouts', 'workout_id', 'user_id')
                    ->withPivot('progress', 'last_done', 'completed_at')
                    ->withTimestamps();
    }
}
