<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Repositories\Khanza\PeriksaRadiologiRepository;
use App\Support\RadiologiImage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PatientRadiologiController extends Controller
{
    public function __construct(
        private readonly PeriksaRadiologiRepository $periksaRadiologiRepository,
    ) {}

    public function index(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        return view('patient.radiologi', [
            'exams' => $this->periksaRadiologiRepository->historyForPatient($account->no_rkm_medis),
        ]);
    }

    public function show(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $exam = $this->periksaRadiologiRepository->findForPatient(
            (string) $request->query('no_rawat'),
            (string) $request->query('tgl'),
            (string) $request->query('jam'),
            $account->no_rkm_medis,
        );

        if ($exam === null) {
            throw new NotFoundHttpException;
        }

        return view('patient.radiologi-detail', [
            'exam' => $exam,
            'bacaan' => $this->periksaRadiologiRepository->bacaan($exam),
            'gambar' => RadiologiImage::isConfigured()
                ? $this->periksaRadiologiRepository->gambar($exam)->map(fn (string $path): ?string => RadiologiImage::url($path))->filter()->values()
                : collect(),
        ]);
    }
}
