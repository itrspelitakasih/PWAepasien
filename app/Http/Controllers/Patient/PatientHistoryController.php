<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Repositories\Khanza\DokterRepository;
use App\Repositories\Khanza\PoliklinikRepository;
use App\Repositories\Khanza\RegPeriksaRepository;
use App\Repositories\Khanza\RiwayatPerawatanRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PatientHistoryController extends Controller
{
    public function __construct(
        private readonly RegPeriksaRepository $regPeriksaRepository,
        private readonly PoliklinikRepository $poliklinikRepository,
        private readonly DokterRepository $dokterRepository,
        private readonly RiwayatPerawatanRepository $riwayatPerawatanRepository,
    ) {}

    public function index(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $visits = $this->regPeriksaRepository->historyForPatient($account->no_rkm_medis);

        return view('patient.riwayat', [
            'account' => $account,
            'visits' => $visits,
            'polis' => $this->poliklinikRepository->findMany($visits->pluck('kd_poli')->all()),
            'dokters' => $this->dokterRepository->findMany($visits->pluck('kd_dokter')->all()),
        ]);
    }

    public function show(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $noRawat = (string) $request->query('no_rawat');

        $visit = $this->regPeriksaRepository->findForPatient($noRawat, $account->no_rkm_medis);

        if ($visit === null) {
            throw new NotFoundHttpException;
        }

        return view('patient.riwayat-detail', [
            'visit' => $visit,
            'poli' => $this->poliklinikRepository->find($visit->kd_poli),
            'dokter' => $this->dokterRepository->find($visit->kd_dokter),
            'pemeriksaan' => $this->riwayatPerawatanRepository->pemeriksaan($noRawat, $visit->status_lanjut),
            'diagnosa' => $this->riwayatPerawatanRepository->diagnosa($noRawat),
            'resep' => $this->riwayatPerawatanRepository->resep($noRawat),
        ]);
    }
}
