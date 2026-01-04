<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkoutController;

// -------------------------
// PUBLIC ROUTES
// -------------------------
Route::get('/ping', function () {
    return response()->json(['message' => 'API works!']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// -------------------------
// AUTHENTICATED ROUTES (JWT)
// -------------------------
Route::middleware('auth:api')->group(function () {

    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);

    // User
    Route::get('/users/me', [UserController::class, 'me']);
    Route::put('/users/me', [UserController::class, 'updateMe']);

    // User listing
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    
    // Admin Only: User delete
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('admin');

    // Workouts
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::get('/workouts/{workout}', [WorkoutController::class, 'show']);
    
    // Admin Only: Workout CRUD
    Route::post('/workouts', [WorkoutController::class, 'store'])->middleware('admin');
    Route::put('/workouts/{workout}', [WorkoutController::class, 'update'])->middleware('admin');
    Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy'])->middleware('admin');
    
    // Enrollment
    Route::post('/workouts/{workout}/enroll', [WorkoutController::class, 'enroll']);
    Route::post('/workouts/{workout}/complete', [WorkoutController::class, 'complete']);
});
