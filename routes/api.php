<?php

use App\Http\Controllers\Api\InspirationApiController;
use Illuminate\Support\Facades\Route;

Route::get('/inspirations', [InspirationApiController::class, 'index'])
    ->name('api.inspirations.index');

Route::get('/inspirations/{inspiration}', [InspirationApiController::class, 'show'])
    ->name('api.inspirations.show');
