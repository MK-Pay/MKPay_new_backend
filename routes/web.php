<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\System\HealthCheckController;

Route::any('/', [HealthCheckController::class, 'basicCheckBool']);

require __DIR__ . '/auth.php';
