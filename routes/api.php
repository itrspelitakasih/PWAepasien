<?php

use App\Http\Controllers\Api\AntrianCallController;
use App\Http\Middleware\VerifyAntrianApiToken;
use Illuminate\Support\Facades\Route;

Route::post('antrian/panggil', [AntrianCallController::class, 'store'])
    ->middleware(VerifyAntrianApiToken::class)
    ->name('api.antrian.panggil');
