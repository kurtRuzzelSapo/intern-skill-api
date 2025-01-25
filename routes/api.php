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
use App\Http\Controllers\InternshipController;
use Illuminate\Container\Attributes\Auth;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AUTH ROUTE
Route::post('/login', [Authcontroller::class, 'login']);

Route::post('/register/intern', [Authcontroller::class, 'registerIntern']);

Route::post('/register/recruiter', [Authcontroller::class, 'registerRecruiter']);

Route::get('/mydata/{id}', [AuthController::class, 'getMyData']);

Route::get('specializations/post', [AuthController::class, 'getSpecialization']);

Route::get('specializations/myFilter', [AuthController::class, 'getFilterForums']);


// FORUM ROUTE THIS IS FOR ALL USERS (SKILL LINK)
Route::post('/like/forum', [LikeController::class, 'likePost']);

Route::apiResource('forum', ForumController::class);

Route::apiResource('comment', CommentController::class);

Route::post('/reply', [CommentController::class, 'reply']);

Route::get('/comments/{forumId}', [CommentController::class, 'showComments']);

Route::get('/user/{id}/forums', [ForumController::class, 'getMyForum']);

Route::delete('forum/{forum}/forcedestroy', [ForumController::class, 'forceDestroy'])->middleware('auth:sanctum');

Route::post('forum/{forum}/restore', [ForumController::class, 'restore'])->middleware('auth:sanctum');



// =======INTERN CONNECT=============

// RECRUITER

Route::post('resume', [Authcontroller::class, 'updateResume']);

Route::post('updateProfile', [Authcontroller::class, 'updateProfile']);

Route::post('apply', [InternController::class, 'applyForInternship']);

Route::post('/applications/{applicationId}/status', [RecruiterController::class, 'updateApplicationStatus']);

Route::post('internship', [InternshipController::class, 'store']);

Route::get('internship', [InternshipController::class, 'index']);

Route::get('/interviews/{application_id}', [RecruiterController::class, 'GetMyApplications']);

Route::get('/myInternships/{id}', [InternshipController::class, 'getMyInternships']);
// INTERN




























// LIKE ROUTE
// Route::apiResource('like', LikeController::class)->middleware('auth:sanctum');

// ADMIN ROUTE
// Route::middleware('auth:sanctum')->group(function () {
//     Route::middleware('role:admin')->group(function () {
//         Route::apiResource('admin', AdminController::class);
//     });

// // RECRUITER ROUTE

//     Route::apiResource('recruiter', RecruiterController::class);
//     Route::apiResource('internships', InternshipController::class);
//     // Route::get('/intern/mydata', [RecruiterController::class, 'getMyData']);
//     Route::get('/recruiter/internships', [RecruiterController::class, 'getRecruiterInternships']);
//     Route::get('recruiter/{recruiterId}/internships', [RecruiterController::class, 'getRecruiterInternshipsById']);
//     Route::patch('applications/{applicationId}/status', [RecruiterController::class, 'updateApplicationStatus']);


// // INTERN ROUTE

//         Route::apiResource('intern', InternController::class);
//         //GETTING MY OWN DATA
//         // Route::get('/intern/mydata', [InternController::class, 'getMyData']);
//         Route::get('/intern/my-applications/{id}', [InternController::class, 'showMyApplications']);
//         Route::post('/intern/apply', [InternController::class, 'applyForInternship']);

// //SKILL LINK ROUTES


//         Route::apiResource('forum', ForumController::class);

//         Route::post('/forums/{forum}/share-to-profile', [ForumController::class, 'shareToProfile']);
//         // Route::post('forum/{forum}/like', [LikeController::class, 'store']);
// });





