<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Repositories\Khanza\KamarRepository;
use Illuminate\View\View;

class PatientRoomController extends Controller
{
    public function __construct(
        private readonly KamarRepository $kamarRepository,
    ) {}

    public function index(): View
    {
        return view('patient.kamar', [
            'kelas' => $this->kamarRepository->availabilityByClass(),
        ]);
    }
}
