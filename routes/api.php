<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');
Route::middleware(['auth:sanctum'])->get('/me', fn (Request $request) => $request->user());
