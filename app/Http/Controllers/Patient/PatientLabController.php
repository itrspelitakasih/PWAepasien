<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Repositories\Khanza\PermintaanLabRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PatientLabController extends Controller
{
    public function __construct(
        private readonly PermintaanLabRepository $permintaanLabRepository,
    ) {}

    public function index(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        return view('patient.lab', [
            'account' => $account,
            'labs' => $this->permintaanLabRepository->historyForPatient($account->no_rkm_medis),
        ]);
    }

    public function show(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $lab = $this->permintaanLabRepository->findForPatient((string) $request->query('noorder'), $account->no_rkm_medis);

        if ($lab === null) {
            throw new NotFoundHttpException;
        }

        return view('patient.lab-detail', [
            'lab' => $lab,
            'hasil' => $this->permintaanLabRepository->hasil($lab),
        ]);
    }
}
