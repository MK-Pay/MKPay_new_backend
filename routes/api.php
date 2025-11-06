<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::prefix('v1')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('api.register');
    Route::post('/login', [LoginController::class, 'store'])->name('api.login');
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:sanctum')->name('api.logout');
});

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');
Route::middleware(['auth:sanctum'])->get('/me', fn (Request $request) => $request->user());
