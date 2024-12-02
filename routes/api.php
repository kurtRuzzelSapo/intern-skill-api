<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Authcontroller;
use App\Http\Controllers\InternController;
use App\Http\Controllers\RecruiterController;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [Authcontroller::class, 'login']);
Route::post('/register/intern', [Authcontroller::class, 'registerIntern']);
Route::post('/register/recruiter', [Authcontroller::class, 'registerRecruiter']);

// FORUM ROUTE THIS IS FOR ALL USERS (SKILL LINK)
Route::apiResource('forum', ForumController::class);
Route::delete('forum/{forum}/forcedestroy', [ForumController::class, 'forceDestroy'])->middleware('auth:sanctum');
Route::post('forum/{forum}/restore', [ForumController::class, 'restore'])->middleware('auth:sanctum');

// COMMENT ROUTE
Route::apiResource('comment', CommentController::class)->middleware('auth:sanctum');

// LIKE ROUTE
Route::apiResource('like', LikeController::class)->middleware('auth:sanctum');

// ADMIN ROUTE
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('admin', AdminController::class);
    });

// RECRUITER ROUTE
Route::middleware('role:recruiter')->group(function () {
    Route::apiResource('recruiter', RecruiterController::class);
    Route::get('/recruiter/internships', [RecruiterController::class, 'getRecruiterInternships']);
});

// INTERN ROUTE
    Route::middleware('role:intern')->group(function () {
        Route::apiResource('intern', InternController::class);
        Route::get('/intern/mydata', [InternController::class, 'getMyData']);
        Route::post('/intern/apply', [InternController::class, 'applyForInternship']);
    });
});



