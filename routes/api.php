<?php

use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::prefix('v1')->name('v1.')->group(__DIR__ . '/api-routes/v1.php');
});
