<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Repositories\Khanza\SuratRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientSuratController extends Controller
{
    public function __construct(
        private readonly SuratRepository $suratRepository,
    ) {}

    public function index(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        return view('patient.surat', [
            'account' => $account,
            'surats' => $this->suratRepository->historyForPatient($account->no_rkm_medis),
        ]);
    }
}
