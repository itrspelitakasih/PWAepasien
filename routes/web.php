<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('patient.dashboard');
});

require __DIR__.'/patient.php';
