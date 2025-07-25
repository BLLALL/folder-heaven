<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


Route::middleware('api_guest')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});


Route::middleware('auth:sanctum')->controller(AuthController::class)->group(function () {
    Route::get('user', 'currentUser');
    Route::post('logout', 'logout');
});