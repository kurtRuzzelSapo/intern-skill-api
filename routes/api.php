<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Authcontroller;
use App\Http\Controllers\InternController;
use App\Http\Controllers\RecruiterController;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [Authcontroller::class, 'login']);
Route::post('/register/intern', [Authcontroller::class, 'registerIntern']);
Route::post('/register/recruiter', [Authcontroller::class, 'registerRecruiter']);


// ADMIN ROUTE
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('admin', AdminController::class);
    });

// RECRUITER ROUTE
Route::middleware('role:recruiter')->group(function () {
    Route::apiResource('recruiter', RecruiterController::class);
});

// INTERN ROUTE
    Route::middleware('role:intern')->group(function () {
        Route::apiResource('intern', InternController::class);
        Route::get('/mydata', [InternController::class, 'getMyData']);
    });
});



