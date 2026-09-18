<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PatientQueueController extends Controller
{
    public function index(): View
    {
        return view('patient.antrian');
    }
}
