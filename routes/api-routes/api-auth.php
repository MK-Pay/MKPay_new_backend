<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\V1\CurrentUserController;

Route::post('/register', [RegisteredUserController::class, 'store'])->name('register');
Route::post('/login', [LoginController::class, 'store'])->name('login');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth:sanctum')->name('logout');

Route::match(['get', 'post'], '/user', CurrentUserController::class)->middleware('auth:sanctum')->name('auth_user_info');
Route::middleware(['auth:sanctum'])->any('/me', CurrentUserController::class)->name('me');
Route::middleware(['auth:sanctum'])->any('/token-is-valid', fn (Request $request) => [
    'success' => boolval($request->user()?->name),
])->name('token-is-valid');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');
